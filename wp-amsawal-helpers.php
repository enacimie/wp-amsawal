<?php
/**
 * Helpers transversales: i18n, rate-limiting, logging.
 * Concentrar aquí lo que se invoca desde varios archivos del plugin evita
 * duplicación y facilita el mantenimiento.
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_AMSAWAL_TEXTDOMAIN' ) ) {
	define( 'WP_AMSAWAL_TEXTDOMAIN', 'amsawal' );
}

/*───────────────────────────────────────────────────────────────────────
 * 1. i18n — Carga del text domain
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Registra el text domain 'amsawal' y carga los .mo desde /languages.
 * Idempotente: safe de llamar varias veces.
 */
function wp_amsawal_load_textdomain() {
	// load_plugin_textdomain quedó deprecado para plugins en WordPress 4.6+,
	// pero seguimos usándolo por compatibilidad con instalaciones legacy.
	load_plugin_textdomain(
		WP_AMSAWAL_TEXTDOMAIN,
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}
add_action( 'plugins_loaded', 'wp_amsawal_load_textdomain' );


/*───────────────────────────────────────────────────────────────────────
 * 2. Logging — Helper centralizado
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Escribe una entrada en el log centralizado del plugin.
 *
 * Almacena hasta 200 entradas en la opción `wp_amsawal_log` (FIFO).
 * Si el entorno lo permite, también emite a error_log.
 *
 * @param string $level   'info' | 'warning' | 'error' | 'debug'
 * @param string $message Mensaje a loguear (se sanitiza al persistir).
 * @param array  $context Datos extra serializables (se truncan a 2 KB).
 */
function wp_amsawal_log( $level, $message, $context = array() ) {
	static $allowed = array( 'info', 'warning', 'error', 'debug' );
	$level = in_array( $level, $allowed, true ) ? $level : 'info';

	$entry = array(
		'time'    => current_time( 'mysql' ),
		'level'   => $level,
		'message' => wp_amsawal_truncate( (string) $message, 1000 ),
		'context' => wp_amsawal_truncate( wp_json_encode( $context ), 2048 ),
		'user'    => get_current_user_id(),
	);

	// Always mirror to error_log for production diagnostics (primary log channel).
	error_log( sprintf( '[amsawal][%s][u%d] %s', $level, $entry['user'], $entry['message'] ) );

	// Persistencia en option (cap FIFO 200) — only update if not doing AJAX
	// to avoid N+1 serialization overhead on high-frequency AJAX calls.
	if ( ! wp_doing_ajax() ) {
		$log   = get_option( 'wp_amsawal_log', array() );
		$log[] = $entry;
		if ( count( $log ) > 200 ) {
			$log = array_slice( $log, -200 );
		}
		update_option( 'wp_amsawal_log', $log, false );
	}
}

/**
 * Helper para truncar strings preservando UTF-8.
 */
function wp_amsawal_truncate( $value, $max_len ) {
	if ( ! is_string( $value ) ) {
		$value = (string) $value;
	}
	if ( mb_strlen( $value ) <= $max_len ) {
		return $value;
	}
	return mb_substr( $value, 0, $max_len - 1 ) . '…';
}


/*───────────────────────────────────────────────────────────────────────
 * 3. Rate-Limiting — Protección de endpoints LLM
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Comprueba si el (user_id, action) ha superado el límite de invocaciones.
 * Implementación basada en transients de WP: 1 minuto de ventana.
 *
 * @param string $action    Identificador del endpoint (ej: 'evaluate_essay').
 * @param int    $max_calls Máximo de llamadas permitidas en la ventana.
 * @param int    $window    Duración de la ventana en segundos.
 * @return true si está dentro del límite; false si debe bloquearse.
 */
function wp_amsawal_rate_limit_check( $action, $max_calls = 10, $window = 60 ) {
	$user_id = get_current_user_id();
	$key     = sprintf( 'amsawal_rl_%s_%d_%d', $action, $user_id, (int) ( time() / $window ) );
	$count   = (int) get_transient( $key );

	if ( $count >= $max_calls ) {
		return false;
	}

	// Use wp_cache_incr for atomic increment if available (multisite/Redis)
	// Fallback: set_transient (non-atomic but adequate for single-site)
	$lock_key = $key . '_lock';
	if ( wp_cache_add( $lock_key, 1, '', 5 ) ) {
		set_transient( $key, $count + 1, $window );
		wp_cache_delete( $lock_key );
	} else {
		// Another request is incrementing; wait briefly and re-check
		usleep( 50000 ); // 50ms
		$count = (int) get_transient( $key );
		if ( $count >= $max_calls ) {
			return false;
		}
		set_transient( $key, $count + 1, $window );
	}
	return true;
}

/**
 * Versión "must-exit" para usar al inicio de un handler AJAX:
 * wp_amsawal_rate_limit_or_die( 'evaluate_essay', 5, 60 );
 */
function wp_amsawal_rate_limit_or_die( $action, $max_calls = 10, $window = 60 ) {
	if ( ! wp_amsawal_rate_limit_check( $action, $max_calls, $window ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Has hecho demasiadas solicitudes. Espera un momento e inténtalo de nuevo.', WP_AMSAWAL_TEXTDOMAIN ),
				'retry_in'=> $window,
			),
			429
		);
	}
}


/*───────────────────────────────────────────────────────────────────────
 * 4. Emoji Icon System — Unicode emojis only (zero dependencies)
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Devuelve el emoji para un icono de navegación.
 * Solo emojis Unicode, sin SVG ni Dashicons.
 *
 * @param string $name Nombre del icono: 'home', 'trophy', 'list', 'user',
 *                     'book', 'bars', 'lock', 'eye', 'edit', 'sliders',
 *                     'share', 'close', 'play', 'star', 'check', 'fire'.
 * @param string $size Tamaño: 'sm' (20px), 'md' (28px, default), 'lg' (40px).
 * @return string HTML del span con emoji.
 */
function wp_amsawal_nav_icon( $name = 'home', $size = 'md' ) {
	$size_class = $size === 'sm' ? ' duo-nav-icon--sm' : ( $size === 'lg' ? ' duo-nav-icon--lg' : '' );
	$emoji = '';

	switch ( $name ) {
		case 'home':
			$emoji = '🏠';
			break;
		case 'trophy':
			$emoji = '🏆';
			break;
		case 'list':
			$emoji = '📋';
			break;
		case 'user':
			$emoji = '👤';
			break;
		case 'book':
			$emoji = '📖';
			break;
		case 'bars':
			$emoji = '☰';
			break;
		case 'lock':
			$emoji = '🔒';
			break;
		case 'eye':
			$emoji = '👁️';
			break;
		case 'edit':
			$emoji = '✏️';
			break;
		case 'sliders':
			$emoji = '⚙️';
			break;
		case 'share':
			$emoji = '🔗';
			break;
		case 'close':
			$emoji = '❌';
			break;
		case 'play':
			$emoji = '▶️';
			break;
		case 'star':
			$emoji = '⭐';
			break;
		case 'check':
			$emoji = '✅';
			break;
		case 'fire':
		case 'streak':
			$emoji = '🔥';
			break;
		case 'money':
		case 'coin':
		case 'coins':
			$emoji = '💰';
			break;
		case 'heart':
			$emoji = '❤️';
			break;
		case 'medal':
			$emoji = '🏅';
			break;
		case 'shop':
			$emoji = '🛍️';
			break;
		case 'refresh':
			$emoji = '🔄';
			break;
		default:
			return '';
	}

	return '<span class="duo-nav-icon' . $size_class . '" aria-hidden="true">' . $emoji . '</span>';
}

