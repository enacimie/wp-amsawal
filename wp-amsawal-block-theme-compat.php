<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Compatibilidad con Block Themes (Twenty Twenty-Five, etc.).
 *
 * Los block themes no disparan `the_content` para el contenido principal
 * de la página; usan el bloque `core/post-content` cuyo HTML se filtra
 * con `render_block`. Este shim captura el bloque `core/post-content` y
 * invoca las mismas funciones que `the_content` para mantener
 * compatibilidad con el plugin.
 *
 * Sin este shim, los shortcodes / grids del plugin (cursos, ligas,
 * actividades, tutor) no se renderizan en block themes.
 *
 * Detección: si `wp_is_block_theme()` es true, se activa el shim.
 *
 * Cubre las 9 funciones enganchadas a `the_content`:
 *   - wp_amsawal_show_courses_page       (wp-amsawal-courses.php)
 *   - wp_amsawal_show_front_page         (wp-amsawal-view.php)
 *   - wp_amsawal_ai_render_activities    (wp-amsawal-ai.php)
 *   - wp_amsawal_buddypress              (wp-amsawal-buddypress.php)
 *   - wp_amsawal_check_gamipress         (wp-amsawal-gamipress.php)
 *   - wp_amsawal_leaders_tables_gamipress (wp-amsawal-gamipress.php)
 *   - wp_amsawal_h5p_results             (wp-amsawal-h5p.php)
 *   - wp_amsawal_set_user_homepage       (wp-amsawal-users.php)
 *   - wp_amsawal_breadcrumbs (filter)    (wp-amsawal-view.php)
 *
 * Estrategia: cuando `render_block` ve `core/post-content` y
 * `is_singular()` es true, redirige a `the_content` con la post actual.
 * Esto replica exactamente lo que hace WordPress internamente en
 * themes clásicos.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'render_block', 'wp_amsawal_block_theme_render_post_content', 10, 2 );

/**
 * Si el bloque es `core/post-content`, lo reemplaza por el contenido
 * procesado por `the_content` (que dispara todos los hooks del plugin).
 *
 * En block themes, el bloque `core/post-content` es el que renderiza
 * el contenido del post/página. Por defecto solo muestra
 * `$post->post_content` sin pasar por `the_content`.
 */
function wp_amsawal_block_theme_render_post_content( $block_content, $block ) {
	if ( ! is_singular() ) {
		return $block_content;
	}
	if ( ! isset( $block['blockName'] ) ) {
		return $block_content;
	}
	if ( $block['blockName'] !== 'core/post-content' ) {
		return $block_content;
	}

	// Aplica the_content para que todos los hooks del plugin se disparen.
	$post = get_post();
	if ( ! $post ) {
		return $block_content;
	}
	$processed = apply_filters( 'the_content', $post->post_content );
	return $processed;
}
