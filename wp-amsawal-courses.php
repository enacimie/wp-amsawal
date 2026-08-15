<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter('the_content', 'wp_amsawal_show_courses_page');
function wp_amsawal_show_courses_page ($content) {
	if ( is_admin() ) {
		return $content;
	}

	global $post;
	$cursos_pages = get_posts(array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'title'       => 'Cursos disponibles',
		'numberposts' => 1,
	));
	$cursos_page = !empty($cursos_pages) ? $cursos_pages[0] : null;
	$current_id = get_queried_object_id();
	$front_id = wp_amsawal_get_user_homepage ();
	if (!isset($_GET['f']) && $front_id) {
		return $content;
	}
	if (!$front_id) {
		$front_id = get_option('page_on_front');
	}
	$url = get_site_url() . '/courses';
	
	if ( !(is_page('courses') || is_page('cursos-disponibles') || (($cursos_page->ID == $front_id) && ($cursos_page == $current_id))) && (empty($_GET['f']))) {
		return $content;
	}
	unset($_GET['f']);
	$args = array(
		'sort_order' => 'asc',
		'sort_column' => 'post_title',
		'parent' => 0,
		'post_type' => 'page',
		'post_status' => 'publish'
	); 

	$user_front_id = wp_amsawal_get_user_homepage();

	echo '<div class="duo-container" id="duo-main-content" tabindex="-1">';
	echo '<div class="duo-courses-header">
		<h2>📚 Elige tu curso</h2>
		<p>Selecciona el curso al que quieres acceder</p>
	</div>';
	
	echo '<form action="'.esc_url($url).'" id="wp_amsawal_homeform" method="post">';
	echo '<div class="duo-course-grid">';
	$pages = get_pages($args);
	$rendered = 0;
	foreach ( $pages as $page ) {
		$custom_fields = get_post_custom($page->ID);
		$imageID = get_post_thumbnail_id( $page->ID );
		$image = wp_get_attachment_image_src( $imageID, 'medium' );
		if (!isset($custom_fields["wp_amsawal_mb_course"])) continue;
		if (empty($custom_fields["wp_amsawal_mb_course"][0])) continue;
		$rendered++;

		$is_current = ($user_front_id == $page->ID);
		$card_class = $is_current ? 'duo-course-card duo-course-card--active' : 'duo-course-card';

		echo '<div class="'.esc_attr($card_class).'">';
		if ($is_current) {
			echo '<span class="duo-course-badge">✅ '.esc_html__( 'Actual', WP_AMSAWAL_TEXTDOMAIN ).'</span>';
		}
		echo '<div class="duo-course-img"';
		if (empty($image[0])) echo ' data-fallback="true"';
		echo '>';
		if (empty($image[0])) {
			echo '<span class="duo-course-img-fallback" aria-hidden="true">📚</span>';
		} else {
			echo '<img src="'.esc_url($image[0]).'" alt="'.esc_attr($page->post_title).'" loading="lazy" />';
		}
		echo '</div>';
		echo '<div class="duo-course-body">';
		echo '<h3 class="duo-course-title">'.esc_html($page->post_title).'</h3>';
		echo '<p class="duo-course-meta">📖 '.sprintf( esc_html__( 'Curso de %s', WP_AMSAWAL_TEXTDOMAIN ), esc_html( $custom_fields["wp_amsawal_mb_course"][0] ) ).'</p>';
		$btn_label = $is_current
			? '📖 ' . esc_html__( 'Continuar aprendiendo', WP_AMSAWAL_TEXTDOMAIN )
			: '🚀 ' . esc_html__( 'Empezar curso', WP_AMSAWAL_TEXTDOMAIN );
		$btn_class = 'duo-course-btn';
		if ( ! $is_current && $user_front_id ) {
			$btn_class .= ' duo-course-btn--switch';
		}
		echo '<button type="submit" class="'.esc_attr($btn_class).'" value="'.esc_attr($page->ID).'" name="wp_amsawal_userhome"';
		if ( ! $is_current && $user_front_id ) {
			echo ' data-confirm="true" data-course-name="'.esc_attr($page->post_title).'"';
		}
		echo '>';
		echo esc_html($btn_label);
		echo '</button>';

		if (isset($custom_fields["wp_amsawal_mb_video"]) && !empty($custom_fields["wp_amsawal_mb_video"][0]) && $custom_fields["wp_amsawal_mb_video"][0] != '') {
			$video_url = esc_url($custom_fields["wp_amsawal_mb_video"][0]);
			$course_name = sanitize_text_field($custom_fields["wp_amsawal_mb_course"][0]);
			$modal_id = 'Video' . sanitize_title($course_name);
			echo '<button type="button" class="duo-course-video-btn" data-toggle="modal" data-target="#' . esc_attr($modal_id) . '">🎬 Ver presentación</button>';
			$video_html = '<video controls="controls" id="video1" style="width: 100%; height: auto; margin:0 auto; frameborder:0;">
				<source src="' . $video_url . '" type="video/mp4">
				Your browser does not support the HTML5 Video element.
				</video>';
			wp_amsawal_show_modal('Curso de ' . esc_html($course_name), $video_html, "", true, true, $modal_id);
		}

		echo '</div>';
		echo '</div>';
	}
	wp_nonce_field( 'wp_amsawal_newuserhome', 'wp_amsawal_newuserhome_nonce' );
	echo '</div>';

	// ── Empty state (Nielsen #10 Help & Documentation) ──
	if ( $rendered === 0 ) {
		echo '<div class="duo-empty-state" role="status">';
		echo '  <pre class="duo-empty-state__art" aria-hidden="true">';
		echo '   _________________________'. "\n";
		echo '  |  📚  ___________        |'. "\n";
		echo "  |     /           \       |\n";
		echo '  |    |   ( vacío )|       |'. "\n";
		echo "  |     \___________/       |\n";
		echo "  |_________________________|\n";
		echo '  </pre>';
		echo '  <h2>' . esc_html__( 'Aún no hay cursos disponibles', WP_AMSAWAL_TEXTDOMAIN ) . '</h2>';
		echo '  <p>' . esc_html__( 'Cuando un administrador publique una página con el metadato "wp_amsawal_mb_course" aparecerá aquí automáticamente.', WP_AMSAWAL_TEXTDOMAIN ) . '</p>';
		echo '  <p class="duo-empty-state__hint">';
		echo wp_kses_post( __( 'Si eres administrador, revisa <a href="/wp-admin/edit.php?post_type=page">Páginas → Todas</a> y asigna el idioma del curso en la metabox.', WP_AMSAWAL_TEXTDOMAIN ) );
		echo '  </p>';
		echo '</div>';
	}

	echo '</form>';
	echo '</div>';
	return $content;
}




function wp_amsawal_get_courses () {
	if (is_admin()) return;
	global $post;
	$args = array(
		'sort_order' => 'asc',
		'sort_column' => 'post_title',
		'parent' => 0,
		'post_type' => 'page',
		'post_status' => 'publish',
		'meta_key' => 'wp_amsawal_mb_course'
	); 
	$pages = get_pages($args); 
	$courses = array();
	foreach ( $pages as $page ) {
		$custom_fields = get_post_custom($page->ID);
		if (!empty($custom_fields["wp_amsawal_mb_course"][0])) {
			$courses[] = $custom_fields["wp_amsawal_mb_course"][0];
		}
	}
	return $courses;
}

// F15-6: CSRF protection
// Todos los formularios deben incluir:
// wp_nonce_field('amsawal_action', 'amsawal_nonce');
// Y verificar en el handler:
// check_admin_referer('amsawal_action', 'amsawal_nonce');
