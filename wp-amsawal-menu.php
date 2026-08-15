<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* Duolingo-style top bar — replaced old slide-out TouchMenuLA
 * Single semantic <header> used as the site banner for all users.
 */
add_action( 'wp_body_open', 'wp_amsawal_top_bar' );
add_action( 'wp_head', 'wp_amsawal_top_bar' );
function wp_amsawal_top_bar () {
	static $rendered = false;
	if ($rendered) return;
	$rendered = true;

	if (is_admin()) return;

	$userid   = get_current_user_id();
	$pagename = get_query_var('pagename');
	$allowed_pages = array(
		'registro',
		'activar',
		'politica-de-privacidad'
	);
	$active = 'activar/';
	global $wp;
	$current_slug = add_query_arg( array(), $wp->request );

	$is_public_landing = ! is_user_logged_in()
		&& ( in_array( $pagename, $allowed_pages, true ) || substr( $current_slug, 0, strlen( $active ) ) === $active );

	// Logged-in: logo + streak + coins + admin + logout
	$current_points = function_exists('gamipress_get_user_points') ? gamipress_get_user_points($userid, 'monedas') : 0;
	
	// Obtener Racha
	$streak = (int) get_user_meta($userid, '_wp_amsawal_streak_days', true);
	$streak_display = $streak > 0 ? $streak : 0;

	// Texto del mascot: contextual según estado (streak + vidas).
	// Pedagógicamente, NPC con personalidad reactiva (Norman, 2004; affective computing).
	$display_name = esc_html( wp_get_current_user()->display_name );

	// Detección de "estás en una lección de Tamazight" → saludo bilingüe.
	// Usamos is_singular() + post meta para evitar falsos positivos en páginas
	// administrativas o de BuddyPress que no son contenido pedagógico.
	$on_tamazight_lesson = is_singular()
		&& get_post_meta( get_queried_object_id(), 'wp_amsawal_mb_lesson', true );

	if ( $streak_display >= 7 ) {
		$mascot_line = sprintf(
			/* translators: 1: user display name, 2: streak days */
			__( '¡%1$s, %2$d días seguidos! Eres una leyenda. ⭐', WP_AMSAWAL_TEXTDOMAIN ),
			$display_name, $streak_display
		);
	} elseif ( $streak_display >= 3 ) {
		$mascot_line = sprintf(
			/* translators: %s: user display name */
			__( '¡%s, llevas una racha increíble! 👍', WP_AMSAWAL_TEXTDOMAIN ),
			$display_name
		);
	} elseif ( $on_tamazight_lesson ) {
		// Saludo bilingüe: español + Tifinagh.
		$mascot_line = sprintf(
			'%s ⵜⴰⵎⴰⵣⵉⵖⵜ!',
			sprintf(
				/* translators: %s: user display name */
				__( '¡Hola, %s!', WP_AMSAWAL_TEXTDOMAIN ),
				$display_name
			)
		);
	} else {
		$mascot_line = sprintf(
			/* translators: %s: user display name */
			__( '¡Azul, %s! 😊', WP_AMSAWAL_TEXTDOMAIN ),
			$display_name
		);
	}

	// Atributos accesibles para stats (WCAG 1.3.1 + 4.1.2).
	$streak_title = $streak_display === 1
		? __( 'Racha de 1 día', WP_AMSAWAL_TEXTDOMAIN )
		: sprintf( __( 'Racha de %d días', WP_AMSAWAL_TEXTDOMAIN ), $streak_display );

	// Preferred language for course content
	$preferred_lang = get_user_meta( $userid, '_wp_amsawal_preferred_lang', true );
	if ( ! $preferred_lang ) $preferred_lang = 'tzm';

	// SM-2: contar ítems con next_review <= now (los que tocan hoy).
	$due_count = 0;
	if ( function_exists( 'wp_amsawal_get_due_items' ) ) {
		$due_count = count( wp_amsawal_get_due_items( $userid ) );
	}
	$due_title = $due_count === 0
		? __( 'Sin repasos pendientes', WP_AMSAWAL_TEXTDOMAIN )
		: sprintf(
			/* translators: %d: number of due items */
			_n( '%d ítem para repasar hoy', '%d ítems para repasar hoy', $due_count, WP_AMSAWAL_TEXTDOMAIN ),
			$due_count
		);

	// Public landing: only logo inside the semantic header.
	$logo_url   = plugins_url( 'images/amsawal5.png', __FILE__ );
	$site_title = esc_attr( get_bloginfo( 'title' ) );
	$home_url   = esc_url( site_url() );

	echo '
	<nav aria-label="' . esc_attr__( 'Navegación de acceso', WP_AMSAWAL_TEXTDOMAIN ) . '"><a class="duo-skip-link" href="#duo-main-content">' . esc_html__( 'Saltar al contenido principal', WP_AMSAWAL_TEXTDOMAIN ) . '</a></nav>
	<header class="duo-topbar" role="banner">
		<div class="duo-topbar-brand">
			<h1 class="screen-reader-text">' . esc_html( get_bloginfo( 'name' ) ) . '</h1>
			<a href="' . $home_url . '" aria-label="' . esc_attr__( 'Ir al inicio', WP_AMSAWAL_TEXTDOMAIN ) . '">
				<img src="' . $logo_url . '" alt="' . $site_title . '" class="duo-topbar-logo" />
			</a>
		</div>';

	if ( ! $is_public_landing ) {
		echo '
		<div class="duo-topbar-end">
			<div class="duo-topbar-profile" role="button" tabindex="0" aria-label="' . esc_attr__( 'Perfil de usuario', WP_AMSAWAL_TEXTDOMAIN ) . '"></div>
			<div class="duo-topbar-menu">
				<button type="button" class="duo-topbar-toggle" aria-label="' . esc_attr__( 'Menú de usuario', WP_AMSAWAL_TEXTDOMAIN ) . '" aria-expanded="false" aria-controls="duo-topbar-dropdown">☰</button>
				<div class="duo-topbar-dropdown" id="duo-topbar-dropdown" role="group" aria-label="' . esc_attr__( 'Estadísticas del usuario', WP_AMSAWAL_TEXTDOMAIN ) . '" hidden>
					<div class="duo-dropdown-stats">
						<div class="duo-dropdown-stat duo-dropdown-stat--streak" title="' . esc_attr( $streak_title ) . '" aria-label="' . esc_attr( $streak_title ) . '">
							<span class="duo-dropdown-stat-label">Racha</span>
							<span class="duo-dropdown-stat-value">⭐ ' . esc_html( $streak_display ) . '</span>
						</div>
						<div class="duo-dropdown-stat duo-dropdown-stat--due" title="' . esc_attr( $due_title ) . '" aria-label="' . esc_attr( $due_title ) . '">
							<span class="duo-dropdown-stat-label">Repasos</span>
							<span class="duo-dropdown-stat-value">🔄 ' . esc_html( $due_count ) . '</span>
						</div>
						<div class="duo-dropdown-stat duo-dropdown-stat--coins">
							<span class="duo-dropdown-stat-label">Monedas</span>
							<span class="duo-dropdown-stat-value">💰 ' . esc_html( $current_points ) . '</span>
						</div>
					</div>
				</div>
			</div>
		</div>';
	}

	echo '
	</header>';

	// Hidden sidebar / future drawer markup kept as <aside> for semantic clarity.
	if ( ! $is_public_landing ) {
		echo '
	<aside class="duo-sidebar" id="duo-sidebar" aria-label="' . esc_attr__( 'Menú lateral', WP_AMSAWAL_TEXTDOMAIN ) . '" hidden>
		<div class="duo-sidebar-top">
			<img src="' . $logo_url . '" alt="' . $site_title . '" class="duo-sidebar-logo" onclick="window.location.href=\'' . $home_url . '\'" />
		</div>

		<div class="duo-mascot-sidebar">
			<svg viewBox="0 0 100 100" class="duo-yaz-mascot" role="img" aria-label="' . esc_attr__( 'Mascota Yaz', WP_AMSAWAL_TEXTDOMAIN ) . '">
				<circle cx="50" cy="50" r="42" fill="var(--amsawal-tifinagh)" />
				<path d="M30 65 L40 45 L50 55 L60 35 L70 65" stroke="var(--amsawal-primary-dark)" stroke-width="6" fill="none" stroke-linecap="round" stroke-linejoin="round" />
				<circle cx="50" cy="45" r="8" fill="var(--amsawal-primary)" />
				<circle cx="40" cy="40" r="4" fill="var(--amsawal-primary-dark)" />
				<circle cx="60" cy="40" r="4" fill="var(--amsawal-primary-dark)" />
				<path d="M40 55 Q50 65 60 55" stroke="var(--amsawal-primary-dark)" stroke-width="3" fill="none" stroke-linecap="round" />
			</svg>
			<div class="duo-mascot-bubble" aria-live="polite">' . esc_html( $mascot_line ) . '</div>
		</div>

		<div class="duo-league-summary">
			<div class="duo-league-summary-header">
				<h2>🏆 ' . esc_html__( 'Top 3', WP_AMSAWAL_TEXTDOMAIN ) . '</h2>
				<a href="' . esc_url( site_url( '/liderazgos/' ) ) . '">' . esc_html__( 'Ver ligas', WP_AMSAWAL_TEXTDOMAIN ) . '</a>
			</div>
			' . do_shortcode( '[amsawal_leaderboard type="monedas" limit="3"]' ) . '
		</div>

		<nav class="duo-sidebar-nav" aria-label="' . esc_attr__( 'Navegación principal', WP_AMSAWAL_TEXTDOMAIN ) . '">
			<a class="nav-item active" href="' . esc_url( site_url() ) . '">
				' . wp_amsawal_nav_icon( 'home', 'md' ) . '
				<span>' . esc_html__( 'Aprender', WP_AMSAWAL_TEXTDOMAIN ) . '</span>
			</a>
			<a class="nav-item" href="' . esc_url( site_url( '/logros/' ) ) . '">
				' . wp_amsawal_nav_icon( 'medal', 'md' ) . '
				<span>' . esc_html__( 'Logros', WP_AMSAWAL_TEXTDOMAIN ) . '</span>
			</a>
			<a class="nav-item" href="' . esc_url( site_url( '/liderazgos/' ) ) . '">
				' . wp_amsawal_nav_icon( 'trophy', 'md' ) . '
				<span>' . esc_html__( 'Ligas', WP_AMSAWAL_TEXTDOMAIN ) . '</span>
			</a>
			<a class="nav-item" href="' . esc_url( site_url( '/tienda/' ) ) . '">
				' . wp_amsawal_nav_icon( 'shop', 'md' ) . '
				<span>' . esc_html__( 'Tienda', WP_AMSAWAL_TEXTDOMAIN ) . '</span>
			</a>
			<a class="nav-item" href="' . esc_url( site_url( '/mis-resultados/' ) ) . '">
				' . wp_amsawal_nav_icon( 'list', 'md' ) . '
				<span>' . esc_html__( 'Notas', WP_AMSAWAL_TEXTDOMAIN ) . '</span>
			</a>
			<a class="nav-item" href="' . esc_url( site_url( '/repaso/' ) ) . '">
				' . wp_amsawal_nav_icon( 'refresh', 'md' ) . '
				<span>' . esc_html__( 'Repaso', WP_AMSAWAL_TEXTDOMAIN ) . '</span>
			</a>
			<a class="nav-item" href="' . esc_url( home_url( '/i/' . rawurlencode( wp_get_current_user()->user_login ) ) ) . '">
				' . wp_amsawal_nav_icon( 'user', 'md' ) . '
				<span>' . esc_html__( 'Perfil', WP_AMSAWAL_TEXTDOMAIN ) . '</span>
			</a>
		</nav>
	</aside>
		';
	}
}
add_action( 'wp_footer', 'wp_amsawal_footer_menu' );
function wp_amsawal_footer_menu ( $content ) {
	// Mobile-first: bottom nav visible en todas las resoluciones
	if (!is_admin()) {
		// Detect current page to set the active item
		global $wp;
		$current_slug = add_query_arg( array(), $wp->request );

		$nav_items = array(
			array( 'slug' => '',              'label' => __( 'Aprender',    WP_AMSAWAL_TEXTDOMAIN ), 'icon' => 'home',   'url' => site_url() ),
			array( 'slug' => 'logros',        'label' => __( 'Logros',      WP_AMSAWAL_TEXTDOMAIN ), 'icon' => 'medal',  'url' => site_url( '/logros/' ) ),
			array( 'slug' => 'liderazgos',   'label' => __( 'Ligas',       WP_AMSAWAL_TEXTDOMAIN ), 'icon' => 'trophy', 'url' => site_url( '/liderazgos/' ) ),
			array( 'slug' => 'tienda',        'label' => __( 'Tienda',      WP_AMSAWAL_TEXTDOMAIN ), 'icon' => 'shop',   'url' => site_url( '/tienda/' ) ),
			array( 'slug' => 'mis-resultados', 'label' => __( 'Resultados',  WP_AMSAWAL_TEXTDOMAIN ), 'icon' => 'list',   'url' => site_url( '/mis-resultados/' ) ),
			array( 'slug' => 'repaso',        'label' => __( 'Repaso',      WP_AMSAWAL_TEXTDOMAIN ), 'icon' => 'refresh','url' => site_url( '/repaso/' ) ),
			array( 'slug' => 'i',             'label' => __( 'Perfil',      WP_AMSAWAL_TEXTDOMAIN ), 'icon' => 'user',   'url' => home_url( '/i/' . rawurlencode( wp_get_current_user()->user_login ) ) ),
		);

		echo '<nav class="duo-mobile-nav" aria-label="' . esc_attr__( 'Navegación principal', WP_AMSAWAL_TEXTDOMAIN ) . '">';
		foreach ( $nav_items as $item ) {
			$is_active = ( $item['slug'] === '' && ( $current_slug === '' || $current_slug === '/' ) )
				|| ( $item['slug'] !== '' && strpos( $current_slug, $item['slug'] ) === 0 );
			$class = $is_active ? 'active' : '';
			printf(
				'<a class="%s" href="%s" aria-label="%s"%s>%s<span>%s</span></a>',
				esc_attr( $class ),
				esc_url( $item['url'] ),
				esc_attr( $item['label'] ),
				$is_active ? ' aria-current="page"' : '',
				wp_amsawal_nav_icon( $item['icon'], 'md' ),
				esc_html( $item['label'] )
			);
		}
		echo '</nav>';
	}
	return $content;
}

/**
 * Prepara la sidebar para actuar como drawer en mobile:
 *  - Le añade id="duo-sidebar" para que el botón toggle pueda controlarlo
 *    (aria-controls) y para CSS (selector por id).
 *  - Lo enganchamos en wp_footer (tarde) para asegurar que ya se renderizó.
 */
add_action( 'wp_footer', 'wp_amsawal_prepare_sidebar_drawer', 5 );
function wp_amsawal_prepare_sidebar_drawer() {
	if ( is_admin() || ! is_user_logged_in() ) return;
	?>
	<script>
	(function() {
		var sb = document.querySelector('.duo-sidebar');
		if (sb && !sb.id) sb.id = 'duo-sidebar';
	})();
	</script>
	<?php
}
