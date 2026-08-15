<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'pre_get_posts', 'wp_amsawal_show_user_homepage' );
function wp_amsawal_show_user_homepage( $query ) {
	if ( is_admin()) return;
	$user_id = wp_get_current_user()->ID;
	$newhomepage = get_option('wp_amsawal_current_homepage_'.$user_id, false);
	if( $query->is_main_query()) {
		global $wpdb;
		if (!empty($query->queried_object_id)) return;
		if (is_user_logged_in() && (!empty($newhomepage))) {
			$query->set('page_id', $newhomepage);
			return $query;
		}
	}
	return;
}

add_filter('the_content', 'wp_amsawal_set_user_homepage');
function wp_amsawal_set_user_homepage ($content) {
	if ( is_admin() || ! is_user_logged_in() || (!is_page('courses') && !is_page('cursos-disponibles'))) return $content;
	if( !isset( $_POST['wp_amsawal_newuserhome_nonce'] ) || !wp_verify_nonce( $_POST['wp_amsawal_newuserhome_nonce'], 'wp_amsawal_newuserhome' ) ) return $content;
	$user_id = wp_get_current_user()->ID;
	$new_home = intval( $_POST['wp_amsawal_userhome'] );
	if ( $new_home > 0 && get_post( $new_home ) ) {
		update_option( 'wp_amsawal_current_homepage_' . $user_id, $new_home );
	}
	wp_redirect(site_url());
	exit;
}

function wp_amsawal_get_user_homepage () {
	$user_id = wp_get_current_user()->ID;
	return get_option('wp_amsawal_current_homepage_'.$user_id, false);
}


/**
 * Garantiza que exista la página "Cursos disponibles" (slug: courses).
 *
 * Antes: corría en cada `the_content` con dos queries (get_page_by_title +
 * get_page_by_path). Ahora: un solo transient cachea la existencia; sólo si
 * el transient está "desconocido" hacemos las queries. La creación se hace
 * una vez por instalación gracias al transient que persiste 1 día.
 */
add_action('admin_init', 'wp_amsawal_create_courses_page');
function wp_amsawal_create_courses_page() {
	if ( ! is_admin() ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$transient_key = 'wp_amsawal_courses_page_id';
	$page_id = get_transient( $transient_key );
	if ( false !== $page_id ) {
		// -1 = sabemos que no existe, no reintentar en este request
		if ( -1 === (int) $page_id ) return;
		if ( get_post( (int) $page_id ) ) return;
		// El transient apuntaba a algo que ya no existe: limpiar
		delete_transient( $transient_key );
	}

	$existing_pages = get_posts(array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'name'        => 'courses',
		'numberposts' => 1,
	));
	$existing = !empty($existing_pages) ? $existing_pages[0] : null;
	if ( $existing ) {
		set_transient( $transient_key, (int) $existing->ID, DAY_IN_SECONDS );
		return;
	}

	$post_id = wp_insert_post( array(
		'post_title'   => 'Cursos disponibles',
		'post_name'    => 'courses',
		'post_content' => '',
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_type'    => 'page',
	), true );

	if ( is_wp_error( $post_id ) ) {
		set_transient( $transient_key, -1, HOUR_IN_SECONDS );
		return;
	}

	set_transient( $transient_key, (int) $post_id, DAY_IN_SECONDS );
}
