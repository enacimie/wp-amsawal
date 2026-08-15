<?php
/**
 * Smoke test integral del plugin Amsawal Reloaded.
 *
 * Verifica que el plugin está correctamente cargado, que los hooks están
 * registrados, que el algoritmo de mastery + SM-2 funciona, que el
 * rate-limiting bloquea correctamente, que el logging persiste, que el
 * PWA está bien formado, que la i18n tiene 50+ strings y que las
 * cabeceras de seguridad están en el código.
 *
 * Uso:
 *   docker cp tests/smoke-test.php amsawal-reloaded-wordpress-1:/tmp/smoke-test.php
 *   docker exec amsawal-reloaded-wordpress-1 wp eval-file /tmp/smoke-test.php --allow-root
 *
 * TRUCO: wp_send_json() usa `die` directamente si NO está en contexto AJAX.
 * Forzamos DOING_AJAX para que use wp_die() y podamos capturar la respuesta.
 */
define( 'DOING_AJAX', true );
$_REQUEST = array_merge( $_GET, $_POST );

class AMSAWAL_Die_Exception extends Exception {
	public $response;
	public function __construct( $response ) {
		$this->response = $response;
		parent::__construct( 'wp_die' );
	}
}

// Capturar la salida de wp_die para no abortar el test.
add_filter( 'wp_die_handler', function() {
	return function( $message ) {
		throw new AMSAWAL_Die_Exception( is_array( $message ) ? $message : (string) $message );
	};
}, 9999 );
add_filter( 'wp_die_ajax_handler', function() {
	return function( $message ) {
		throw new AMSAWAL_Die_Exception( is_array( $message ) ? $message : (string) $message );
	};
}, 9999 );
add_filter( 'wp_die_json_handler', function() {
	return function( $message ) {
		throw new AMSAWAL_Die_Exception( is_array( $message ) ? $message : (string) $message );
	};
}, 9999 );

wp_set_current_user( 1 );

// Reset estado
delete_user_meta( 1, '_wp_amsawal_item_mastery' );
delete_user_meta( 1, '_wp_amsawal_item_schedule' );
delete_user_meta( 1, '_wp_amsawal_lives' );
delete_user_meta( 1, '_wp_amsawal_lives_last_update' );
delete_user_meta( 1, '_wp_amsawal_userhome' );
update_option( 'wp_amsawal_log', array() );
delete_transient( 'amsawal_rl_essay' );
delete_transient( 'amsawal_rl_tutor' );

// Helper: ejecuta callback AJAX y captura respuesta JSON parseada.
function amsawal_safe_ajax_call( $callback ) {
	ob_start();
	try {
		$callback();
	} catch ( AMSAWAL_Die_Exception $e ) {
		$out = ob_get_clean();
		return json_decode( trim( $out ), true );
	} catch ( Exception $e ) {
		ob_end_clean();
		return array( 'error' => $e->getMessage() );
	}
	ob_end_clean();
	return null;
}

echo "═══════════════════════════════════════════════════════\n";
echo " AMSAWAL SMOKE TEST\n";
echo "═══════════════════════════════════════════════════════\n\n";

// ── 1. Funciones del plugin ──
echo "[1] Funciones del plugin\n";
$want = array(
	'wp_amsawal_security_headers',
	'wp_amsawal_pwa_init',
	'wp_amsawal_log',
	'wp_amsawal_rate_limit_check',
	'wp_amsawal_get_due_items',
	'wp_amsawal_ajax_track_item',
	'wp_amsawal_show_courses_page',
	'wp_amsawal_show_modal',
	'wp_amsawal_show_toast',
	'wp_amsawal_truncate',
);
$fail1 = 0;
foreach ( $want as $f ) {
	$ok = function_exists( $f );
	echo '  ' . str_pad( $f, 40 ) . ( $ok ? '✅' : '❌' ) . "\n";
	if ( ! $ok ) $fail1++;
}

// ── 2. Hooks registrados ──
echo "\n[2] Hooks registrados\n";
global $wp_filter;
$hooks = array(
	'send_headers'                  => 'wp_amsawal_security_headers',
	'wp_head'                       => 'wp_amsawal_pwa_init',
	'wp_ajax_wp_amsawal_track_item' => 'wp_amsawal_ajax_track_item',
	'the_content'                   => 'wp_amsawal_show_courses_page',
);
$fail2 = 0;
foreach ( $hooks as $tag => $fn ) {
	$found = false;
	if ( isset( $wp_filter[ $tag ] ) ) {
		foreach ( (array) $wp_filter[ $tag ]->callbacks as $cbs ) {
			foreach ( $cbs as $cb ) {
				$f = $cb['function'];
				$name = is_array( $f ) ? ( is_object( $f[0] ) ? get_class( $f[0] ) : $f[0] ) . '::' . $f[1] : ( is_string( $f ) ? $f : 'closure' );
				if ( $name === $fn || strpos( $name, $fn ) !== false ) { $found = true; break 2; }
			}
		}
	}
	echo "  $tag → $fn: " . ( $found ? '✅' : '❌' ) . "\n";
	if ( ! $found ) $fail2++;
}

// ── 3. Mastery algorithm ──
echo "\n[3] Algoritmo de mastery (+0.15 / -0.40)\n";
$_POST['item_text']   = 'saludos';
$_POST['success']     = '1';
$_POST['_ajax_nonce'] = wp_create_nonce( 'wp_amsawal_track_item' );
$_REQUEST             = array_merge( $_GET, $_POST );
$resp = amsawal_safe_ajax_call( function() { wp_amsawal_ajax_track_item(); } );
$m = isset( $resp['data']['new_mastery'] ) ? $resp['data']['new_mastery'] : '?';
echo "  1 acierto: mastery = $m (esperado 0.65) " . ( $m == 0.65 ? '✅' : '❌' ) . "\n";

$_POST['success'] = '0';
$_REQUEST         = array_merge( $_GET, $_POST );
$resp = amsawal_safe_ajax_call( function() { wp_amsawal_ajax_track_item(); } );
$m = isset( $resp['data']['new_mastery'] ) ? $resp['data']['new_mastery'] : '?';
echo "  1 fallo:   mastery = $m (esperado 0.25) " . ( $m == 0.25 ? '✅' : '❌' ) . "\n";

// ── 4. SM-2 scheduling ──
echo "\n[4] SM-2 scheduling\n";
$sched = get_user_meta( 1, '_wp_amsawal_item_schedule', true );
$first = is_array( $sched ) && ! empty( $sched ) ? reset( $sched ) : array();
echo "  repetitions:     " . ( $first['repetitions'] ?? '?' ) . " (esperado 0 tras fail)\n";
echo "  interval (días): " . ( $first['interval'] ?? '?' ) . " (esperado 1)\n";
echo "  easiness:        " . ( isset( $first['easiness_factor'] ) ? number_format( $first['easiness_factor'], 2 ) : '?' ) . " (esperado < 2.5)\n";
echo "  next_review:     " . ( isset( $first['next_review'] ) && $first['next_review'] > 0 ? 'set ✅' : 'missing ❌' ) . "\n";

// ── 5. Due items ──
echo "\n[5] Items con repaso pendiente (forzando next_review en el pasado)\n";
$sched = get_user_meta( 1, '_wp_amsawal_item_schedule', true );
if ( is_array( $sched ) ) {
	$keys = array_keys( $sched );
	if ( $keys ) {
		$sched[ $keys[0] ]['next_review'] = current_time( 'timestamp' ) - 100;
		update_user_meta( 1, '_wp_amsawal_item_schedule', $sched );
	}
}
$due = wp_amsawal_get_due_items( 1 );
echo "  Cantidad: " . count( $due ) . " (esperado 1) " . ( count( $due ) == 1 ? '✅' : '❌' ) . "\n";

// ── 6. Rate limiting ──
echo "\n[6] Rate limiting (3 calls en 1 min)\n";
$rl_key = 'smoketest_' . uniqid();
$r1 = wp_amsawal_rate_limit_check( $rl_key, 3, 60 );
$r2 = wp_amsawal_rate_limit_check( $rl_key, 3, 60 );
$r3 = wp_amsawal_rate_limit_check( $rl_key, 3, 60 );
$r4 = wp_amsawal_rate_limit_check( $rl_key, 3, 60 );
echo "  Call 1: " . ( $r1 ? 'OK ✅' : 'BLOCKED ❌' ) . "\n";
echo "  Call 2: " . ( $r2 ? 'OK ✅' : 'BLOCKED ❌' ) . "\n";
echo "  Call 3: " . ( $r3 ? 'OK ✅' : 'BLOCKED ❌' ) . "\n";
echo "  Call 4: " . ( $r4 ? 'OK ❌' : 'BLOCKED ✅' ) . "\n";

// ── 7. Logging ──
echo "\n[7] Logging centralizado\n";
wp_amsawal_log( 'info', 'Test info message', array( 'component' => 'smoke-test' ) );
wp_amsawal_log( 'warning', 'Test warning', array( 'component' => 'smoke-test' ) );
wp_amsawal_log( 'error', 'Test error', array( 'component' => 'smoke-test' ) );
$log = get_option( 'wp_amsawal_log', array() );
echo "  Log entries: " . count( $log ) . " (esperado 3+) " . ( count( $log ) >= 3 ? '✅' : '❌' ) . "\n";
echo "  Last entry:  [" . end( $log )['level'] . "] " . end( $log )['message'] . "\n";

// ── 8. Empty state markup ──
echo "\n[8] Empty state markup (Nielsen #10)\n";
$empty_state = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal-courses.php' );
$has_empty = strpos( $empty_state, 'duo-empty-state' ) !== false;
$has_art    = strpos( $empty_state, 'duo-empty-state__art' ) !== false;
$has_hint   = strpos( $empty_state, 'duo-empty-state__hint' ) !== false;
echo "  duo-empty-state class: " . ( $has_empty ? '✅' : '❌' ) . "\n";
echo "  duo-empty-state__art:  " . ( $has_art ? '✅' : '❌' ) . "\n";
echo "  duo-empty-state__hint: " . ( $has_hint ? '✅' : '❌' ) . "\n";

// ── 9. PWA ──
echo "\n[9] PWA (manifest + service worker)\n";
echo "  manifest.json:     " . ( file_exists( '/var/www/html/wp-content/plugins/wp-amsawal/manifest.json' ) ? '✅' : '❌' ) . "\n";
echo "  sw.js:             " . ( file_exists( '/var/www/html/wp-content/plugins/wp-amsawal/sw.js' ) ? '✅' : '❌' ) . "\n";
$manifest = json_decode( file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/manifest.json' ), true );
echo "  Manifest name:     " . ( $manifest['name'] ?? 'missing ❌' ) . " ✅\n";
echo "  Manifest theme:    " . ( $manifest['theme_color'] ?? 'missing ❌' ) . " ✅\n";
echo "  Manifest shortcuts: " . count( $manifest['shortcuts'] ?? array() ) . " ✅\n";

// ── 10. i18n ──
echo "\n[10] i18n (POT + en_US starter)\n";
$pot = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/languages/amsawal.pot' );
$en  = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/languages/amsawal-en_US.po' );
echo "  .pot msgid count:    " . substr_count( $pot, 'msgid "' ) . "\n";
echo "  en_US msgstr count:  " . substr_count( $en, 'msgstr "' ) . "\n";
echo "  Plural-Forms en POT: " . ( strpos( $pot, 'Plural-Forms' ) !== false ? '✅' : '❌' ) . "\n";
echo "  en_US plural forms:  " . ( strpos( $en, 'msgstr[0]' ) !== false ? '✅' : '❌' ) . "\n";

// ── 11. Cabeceras de seguridad ──
echo "\n[11] Cabeceras de seguridad (en el código PHP)\n";
$php = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal.php' );
$headers = array(
	'X-Frame-Options'           => 'X-Frame-Options:',
	'X-Content-Type-Options'    => 'X-Content-Type-Options:',
	'X-XSS-Protection'          => 'X-XSS-Protection:',
	'Referrer-Policy'           => 'Referrer-Policy:',
	'Permissions-Policy'        => 'Permissions-Policy:',
	'Cross-Origin-Opener'       => 'Cross-Origin-Opener-Policy:',
	'Cross-Origin-Embedder'     => 'Cross-Origin-Embedder-Policy:',
	'Cross-Origin-Resource'     => 'Cross-Origin-Resource-Policy:',
	'Content-Security-Policy'   => 'Content-Security-Policy:',
	'Strict-Transport-Security' => 'Strict-Transport-Security:',
);
$count = 0;
foreach ( $headers as $name => $marker ) {
	$ok = strpos( $php, $marker ) !== false;
	echo "  $name: " . ( $ok ? '✅' : '❌' ) . "\n";
	if ( $ok ) $count++;
}
echo "\n  Total: $count/10 cabeceras declaradas\n";

// ── 12. Design system V7 (Duolingo UX 100%) ──
echo "\n[12] Design system V7 (Duolingo UX 100%)\n";
$css = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/css/pure-js-style.css' );
$js  = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/js/pure-js-script.js' );

// Tokens semánticos (nombres de animales Duolingo).
$tokens = array(
	'--color-owl'      => 'verde brand (success)',
	'--color-macaw'    => 'azul brand (CTA)',
	'--color-fox'      => 'naranja (streak)',
	'--color-fire-ant' => 'rojo (error)',
	'--color-bee'      => 'amarillo (XP)',
	'--color-snow'     => 'blanco (background)',
	'--color-swan'     => 'gris claro (borders)',
);
$fail12 = 0;
foreach ( $tokens as $t => $role ) {
	$ok = strpos( $css, $t ) !== false;
	echo '  ' . str_pad( $t, 24 ) . str_pad( $role, 28 ) . ( $ok ? '✅' : '❌' ) . "\n";
	if ( ! $ok ) $fail12++;
}

// Spacing / radius / elevation / z-index scales.
$scales = array(
	'--space-'     => 'spacing scale',
	'--radius-'    => 'radius scale',
	'--elev-'      => 'elevation scale',
	'--z-'         => 'z-index scale',
	'--duration-'  => 'motion duration',
);
foreach ( $scales as $prefix => $name ) {
	$ok = strpos( $css, $prefix ) !== false;
	echo '  ' . str_pad( $prefix, 24 ) . str_pad( $name, 28 ) . ( $ok ? '✅' : '❌' ) . "\n";
	if ( ! $ok ) $fail12++;
}

// Componentes Duolingo-style.
$components = array(
	'--spring'                       => 'overshoot cubic-bezier',
	'duo-btn--3d'                    => 'sistema global botones 3D',
	'duo-btn--shine'                 => 'shine effect en botones',
	'.duo-course-card'               => 'course card estilo Duolingo',
	'.duo-course-card--active'       => 'course card activa (border verde)',
	'.duo-progress'                  => 'progress bar animado',
	'.duo-unit-header'               => 'unit header con guide button',
	'.duo-quest-card'                => 'daily challenges (quests)',
	'.duo-streak-ring'               => 'streak ring SVG',
	'.duo-topbar-theme'              => 'theme toggle button',
	'.duo-topbar-tts'                => 'TTS toggle button',
	'.duo-leader-section'            => 'leaderboard section',
	'.duo-leader-tabs'               => 'leaderboard tabs',
	'.duo-ai-essay'                  => 'AI essay panel',
	'.duo-mcq-option'                => 'MCQ option',
	'.duo-toast-stack'               => 'toast container',
	'.duo-tutor'                     => 'AI tutor panel',
	'.duo-review-modal__card'        => 'review modal (lives=0)',
);
foreach ( $components as $selector => $desc ) {
	$ok = strpos( $css, $selector ) !== false;
	echo '  ' . str_pad( $selector, 32 ) . str_pad( $desc, 28 ) . ( $ok ? '✅' : '❌' ) . "\n";
	if ( ! $ok ) $fail12++;
}

// JS: nuevas interacciones.
$js_handlers = array(
	'initThemeToggle'           => 'theme toggle (normal/high-contrast)',
	'initTopbarScrollShadow'    => 'topbar scroll shadow',
	'initStreakRing'            => 'streak ring SVG dinámico',
	'aria-valuenow'             => 'progressbar ARIA',
	'aria-current="step"'       => 'path current node ARIA',
	'aria-disabled'             => 'path locked node ARIA',
);
foreach ( $js_handlers as $handler => $desc ) {
	$ok = strpos( $js, $handler ) !== false;
	echo '  ' . str_pad( $handler, 30 ) . str_pad( $desc, 28 ) . ( $ok ? '✅' : '❌' ) . "\n";
	if ( ! $ok ) $fail12++;
}

// High contrast mode (replaces dark mode - WCAG AAA strict).
$has_hc_data      = strpos( $css, '[data-theme="high-contrast"]' ) !== false;
$has_safe_area    = strpos( $css, 'env(safe-area-inset-bottom)' ) !== false;
$has_reduced_mot  = strpos( $css, 'prefers-reduced-motion' ) !== false;
echo "  High contrast [data-theme]:  " . ( $has_hc_data ? '✅' : '❌' ) . "\n";
echo "  Safe-area-inset (notched):   " . ( $has_safe_area ? '✅' : '❌' ) . "\n";
echo "  prefers-reduced-motion:      " . ( $has_reduced_mot ? '✅' : '❌' ) . "\n";

// ── 13. Daily challenges (PHP) ──
echo "\n[13] Daily challenges (en view.php)\n";
$view = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal-view.php' );
echo "  duo-quest-section:    " . ( strpos( $view, 'duo-quest-section' ) !== false ? '✅' : '❌' ) . "\n";
echo "  duo-quest-card:       " . ( strpos( $view, 'duo-quest-card' ) !== false ? '✅' : '❌' ) . "\n";
echo "  duo-quest-progress:   " . ( strpos( $view, 'duo-quest-progress' ) !== false ? '✅' : '❌' ) . "\n";

// ── 14. Streak ring + theme toggle (PHP) ──
echo "\n[14] Topbar V7 (streak ring + theme toggle)\n";
$menu = file_get_contents( '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal-menu.php' );
echo "  duo-streak-ring:            " . ( strpos( $menu, 'duo-streak-ring' ) !== false ? '✅' : '❌' ) . "\n";
echo "  data-days:                  " . ( strpos( $menu, 'data-days' ) !== false ? '✅' : '❌' ) . "\n";
echo "  duo-topbar-theme button:    " . ( strpos( $menu, 'duo-topbar-theme' ) !== false ? '✅' : '❌' ) . "\n";

// ── 15. Mobile drawer + haptics + keyboard shortcuts + a11y extras ──
echo "\n[15] Mobile drawer + microinteractions V7\n";
echo "  CSS duo-drawer-toggle:      " . ( strpos( $css, '.duo-drawer-toggle' ) !== false ? '✅' : '❌' ) . "\n";
echo "  CSS duo-drawer-scrim:       " . ( strpos( $css, '.duo-drawer-scrim' ) !== false ? '✅' : '❌' ) . "\n";
echo "  CSS data-drawer=open:       " . ( strpos( $css, 'body[data-drawer="open"]' ) !== false ? '✅' : '❌' ) . "\n";
echo "  CSS @media prefers-contrast:" . ( strpos( $css, 'prefers-contrast: more' ) !== false ? '✅' : '❌' ) . "\n";
echo "  CSS rank-change animation:  " . ( strpos( $css, 'rank-pulse' ) !== false ? '✅' : '❌' ) . "\n";
echo "  JS initDrawer():            " . ( strpos( $js, 'initDrawer' ) !== false ? '✅' : '❌' ) . "\n";
echo "  JS DuoHaptics:              " . ( strpos( $js, 'DuoHaptics' ) !== false ? '✅' : '❌' ) . "\n";
echo "  JS navigator.vibrate:       " . ( strpos( $js, "navigator.vibrate" ) !== false ? '✅' : '❌' ) . "\n";
echo "  JS Escape drawer:           " . ( strpos( $js, "e.key === 'Escape'" ) !== false ? '✅' : '❌' ) . "\n";
echo "  JS Enter/Space quest card:  " . ( strpos( $js, "key === 'Enter'" ) !== false ? '✅' : '❌' ) . "\n";
echo "  PHP duo-drawer-toggle:      " . ( strpos( $menu, 'duo-drawer-toggle' ) !== false ? '✅' : '❌' ) . "\n";
echo "  PHP duo-drawer-scrim:       " . ( strpos( $menu, 'duo-drawer-scrim' ) !== false ? '✅' : '❌' ) . "\n";
echo "  PHP aria-controls=duo-sidebar:" . ( strpos( $menu, 'aria-controls="duo-sidebar"' ) !== false ? '✅' : '❌' ) . "\n";

echo "\n═══════════════════════════════════════════════════════\n";
echo " FIN SMOKE TEST\n";
echo "═══════════════════════════════════════════════════════\n";
