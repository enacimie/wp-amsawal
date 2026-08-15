<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('h5p_alter_library_styles', 'wp_amsawal_h5pmods_alter_styles', 10, 3);
function wp_amsawal_h5pmods_alter_styles(&$styles, $libraries, $embed_type) {
	$styles[] = (object) array(
		'path' => plugins_url('css/wp-amsawal-style-h5p.css',__FILE__ ),
		'version' => '?ver=' . filemtime(plugin_dir_path(__FILE__) . 'css/wp-amsawal-style-h5p.css')
	);
}


/**
 * Wrapper Duolingo para páginas de actividad H5P.
 *
 * Envuelve el contenido H5P en una tarjeta con breadcrumbs,
 * icono del tipo de actividad, header de lección y skeleton loader.
 */
add_filter( 'the_content', 'wp_amsawal_h5p_activity_wrapper', 12 );
function wp_amsawal_h5p_activity_wrapper( $content ) {
	if ( is_admin() || wp_doing_ajax() || is_front_page() ) return $content;
	if ( ! is_singular( 'page' ) ) return $content;

	global $post;
	if ( ! $post ) return $content;

	$type = get_post_meta( $post->ID, 'wp_amsawal_mb_typeh5p', true );
	if ( empty( $type ) ) return $content;

	// Extraer H5P content ID del contenido RAW del post (antes de shortcodes)
	$h5p_id = 0;
	if ( preg_match( '/\[h5p\s+id=["\']?(\d+)["\']?/', $post->post_content, $m ) ) {
		$h5p_id = intval( $m[1] );
	}

	// Si no hay H5P ID en el contenido raw, no envolver
	if ( ! $h5p_id ) return $content;

	// Behavior events: lesson and quiz started.
	$viewer_id = get_current_user_id();
	if ( $viewer_id ) {
		do_action( 'amsawal_lesson_start', $viewer_id, $post->ID );
		do_action( 'amsawal_quiz_start', $viewer_id, $h5p_id );
	}

	$lesson_num  = (int) get_post_meta( $post->ID, 'wp_amsawal_mb_lesson', true );
	$course_name = (string) get_post_meta( $post->ID, 'wp_amsawal_mb_course', true );

	// Icono según tipo de actividad.
	$icons = array(
		'flashcards'      => '🗂️',
		'dialogcards'     => '🃏',
		'dictation'       => '🎙️',
		'memory'          => '🧠',
		'fill-blanks'     => '✍️',
		'mark-the-words'  => '🔍',
		'multiple-choice' => '🔘',
		'drag-drop'       => '🖐️',
		'true-false'      => '✅',
		'speak-the-words' => '🗣️',
		'lesson'          => '📖',
		'test'            => '🎯',
	);
	$icon = isset( $icons[ $type ] ) ? $icons[ $type ] : '📝';

	// Etiqueta legible del tipo.
	$type_labels = array(
		'flashcards'      => __( 'Flashcards', WP_AMSAWAL_TEXTDOMAIN ),
		'dialogcards'     => __( 'Tarjetas de diálogo', WP_AMSAWAL_TEXTDOMAIN ),
		'dictation'       => __( 'Dictado', WP_AMSAWAL_TEXTDOMAIN ),
		'memory'          => __( 'Memoria', WP_AMSAWAL_TEXTDOMAIN ),
		'fill-blanks'     => __( 'Rellenar huecos', WP_AMSAWAL_TEXTDOMAIN ),
		'mark-the-words'  => __( 'Marcar palabras', WP_AMSAWAL_TEXTDOMAIN ),
		'multiple-choice' => __( 'Opción múltiple', WP_AMSAWAL_TEXTDOMAIN ),
		'drag-drop'       => __( 'Arrastrar y soltar', WP_AMSAWAL_TEXTDOMAIN ),
		'true-false'      => __( 'Verdadero/Falso', WP_AMSAWAL_TEXTDOMAIN ),
		'speak-the-words' => __( 'Pronunciación', WP_AMSAWAL_TEXTDOMAIN ),
		'lesson'          => __( 'Lección', WP_AMSAWAL_TEXTDOMAIN ),
		'test'            => __( 'Test', WP_AMSAWAL_TEXTDOMAIN ),
	);
	$label = isset( $type_labels[ $type ] ) ? $type_labels[ $type ] : $type;

	$subtitle = '';
	if ( $course_name ) {
		// Get the course title instead of slug
		$course_page = get_page_by_path( $course_name );
		$course_title = $course_page ? get_the_title( $course_page->ID ) : $course_name;
		$subtitle .= esc_html( $course_title );
	}
	if ( $lesson_num > 0 ) {
		$subtitle .= ( $subtitle ? ' · ' : '' ) . sprintf(
			/* translators: %d: lesson number */
			__( 'Lección %d', WP_AMSAWAL_TEXTDOMAIN ),
			$lesson_num
		);
	}

	$wrapper  = '<div class="duo-container" id="duo-main-content" tabindex="-1" style="padding-top:90px;"' . ( $h5p_id ? ' data-h5p-content-id="' . $h5p_id . '"' : '' ) . '>';
	$wrapper .= '<div class="h5p-card">';
	$wrapper .= '<div class="h5p-card__header">';
	$wrapper .= '<div class="h5p-card__icon" aria-hidden="true">' . $icon . '</div>';
	$wrapper .= '<div class="h5p-card__info">';
	$wrapper .= '<h2 class="h5p-card__title">' . esc_html( get_the_title() ) . '</h2>';
	$wrapper .= '<p class="h5p-card__subtitle">' . esc_html( $label ) . ( $subtitle ? ' — ' . $subtitle : '' ) . '</p>';
	$wrapper .= '</div></div>';

	return $wrapper . $content . '</div></div>';
}

add_filter( 'the_content', 'wp_amsawal_h5p_results' );
function wp_amsawal_h5p_results ( $content ) {
	$pagename = get_query_var('pagename');  
	if ($pagename != "mis-resultados")  {
		return $content;
	}
	global $wpdb;
	$userid = wp_get_current_user()->ID;
	$hp5results = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}h5p_results WHERE user_id = %d ORDER BY finished DESC",
		$userid
	) );

	// Empty state.
	if ( empty( $hp5results ) ) {
		return '
		<div class="duo-container" style="padding-top:90px;" id="duo-main-content" tabindex="-1">
			<div class="duo-empty-state">
				<div class="duo-empty-state__art" aria-hidden="true"></div>
				<h2>📊 ' . esc_html__( 'Sin resultados todavía', WP_AMSAWAL_TEXTDOMAIN ) . '</h2>
				<p>' . esc_html__( 'Completa actividades H5P para ver tu progreso aquí. Cada lección cuenta.', WP_AMSAWAL_TEXTDOMAIN ) . '</p>
			</div>
		</div>';
	}

	// Batch-fetch all content titles: single query instead of N+1.
	$content_ids = array_unique( array_map( function( $r ) { return (int) $r->content_id; }, $hp5results ) );
	$content_map = array();
	if ( ! empty( $content_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $content_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, title FROM {$wpdb->prefix}h5p_contents WHERE id IN ($placeholders)",
			$content_ids
		) );
		foreach ( $rows as $row ) {
			$content_map[ (int) $row->id ] = $row->title;
		}
	}

	$total_score = 0;
	$total_items = 0;
	$cards = '';
	$best_score  = 0;
	$worst_score = 100;
	$total_seconds = 0;

	foreach ($hp5results as $hp5result) {
		$content_id = $hp5result->content_id;
		$title = isset( $content_map[ $content_id ] )
			? $content_map[ $content_id ]
			: sprintf( __( 'Actividad #%d', WP_AMSAWAL_TEXTDOMAIN ), $content_id );
		$max = max( 1, (int) $hp5result->max_score );
		$score = round( $hp5result->score * 100 / $max );
		$duration_sec = $hp5result->finished - $hp5result->opened;
		$duration = sprintf( '%d:%02d', floor( $duration_sec / 60 ), $duration_sec % 60 );

		$total_score += $score;
		$total_items++;
		$total_seconds += max( 0, $duration_sec );
		if ( $score > $best_score )  $best_score = $score;
		if ( $score < $worst_score ) $worst_score = $score;

		// Clasificación semántica.
		if ( $score >= 90 ) {
			$grade_icon = '⭐';
			$grade_label = __( 'Perfecto', WP_AMSAWAL_TEXTDOMAIN );
		} elseif ( $score >= 70 ) {
			$grade_icon = '👍';
			$grade_label = __( 'Bien', WP_AMSAWAL_TEXTDOMAIN );
		} elseif ( $score >= 50 ) {
			$grade_icon = '📖';
			$grade_label = __( 'Repasa', WP_AMSAWAL_TEXTDOMAIN );
		} else {
			$grade_icon = '💪';
			$grade_label = __( 'Sigue practicando', WP_AMSAWAL_TEXTDOMAIN );
		}

		$cards .= '<div class="duo-quest-card" style="max-width:100%;">';
		$cards .= '<div class="duo-quest-icon" aria-hidden="true">' . $grade_icon . '</div>';
		$cards .= '<div class="duo-quest-body">';
		$cards .= '<h3 class="duo-quest-title">' . esc_html( $title ) . '</h3>';
		$cards .= '<div class="duo-quest-progress">';
		$cards .= '<div class="duo-quest-progress-bar-wrapper"><div class="duo-quest-progress-bar" style="width:' . intval($score) . '%"></div></div>';
		$cards .= '<span class="duo-quest-progress-text">' . esc_html($score) . '%</span>';
		$cards .= '</div>';
		$cards .= '<p style="margin:0; font-size:0.8rem; color:var(--duo-text-muted);">⏱ ' . $duration . ' · ' . esc_html( $grade_label ) . '</p>';
		$cards .= '</div></div>';
	}

	// Summary header card.
	$avg = $total_items > 0 ? round( $total_score / $total_items ) : 0;
	$summary_class = '';
	if ( $avg >= 90 ) {
		$summary_icon = '🏆'; $summary_class = 'h5p-results-summary__score--perfect';
		$summary_text = __( '¡Excelente! Dominas el contenido.', WP_AMSAWAL_TEXTDOMAIN );
	} elseif ( $avg >= 70 ) {
		$summary_icon = '🌟'; $summary_class = 'h5p-results-summary__score--good';
		$summary_text = __( 'Vas muy bien. Sigue así.', WP_AMSAWAL_TEXTDOMAIN );
	} elseif ( $avg >= 50 ) {
		$summary_icon = '📚'; $summary_class = 'h5p-results-summary__score--average';
		$summary_text = __( 'Buen progreso. Repasa para mejorar.', WP_AMSAWAL_TEXTDOMAIN );
	} else {
		$summary_icon = '🌱'; $summary_class = 'h5p-results-summary__score--low';
		$summary_text = __( 'Cada intento cuenta. ¡No te rindas!', WP_AMSAWAL_TEXTDOMAIN );
	}

	$avg_duration = $total_items > 0 ? floor( $total_seconds / $total_items ) : 0;
	$avg_dur_str = sprintf( '%d:%02d', floor( $avg_duration / 60 ), $avg_duration % 60 );

	$summary  = '<div class="duo-container" id="duo-main-content" tabindex="-1" style="padding-top:90px;">';
	$summary .= '<div class="duo-courses-header"><h2>📊 ' . esc_html__( 'Mis Resultados', WP_AMSAWAL_TEXTDOMAIN ) . '</h2></div>';
	$summary .= '<div class="h5p-results-summary">';
	$summary .= '<div class="h5p-results-summary__icon" aria-hidden="true">' . $summary_icon . '</div>';
	$summary .= '<div class="h5p-results-summary__score ' . esc_attr($summary_class) . '">' . esc_html($avg) . '%</div>';
	$summary .= '<div class="h5p-results-summary__label">' . esc_html( $summary_text ) . '</div>';
	$summary .= '<div class="duo-results-stats">';
	$summary .= '<span>📝 ' . $total_items . ' ' . esc_html__( 'actividades', WP_AMSAWAL_TEXTDOMAIN ) . '</span>';
	$summary .= '<span>⏱ ~' . esc_html( $avg_dur_str ) . ' ' . esc_html__( 'promedio', WP_AMSAWAL_TEXTDOMAIN ) . '</span>';
	$summary .= '<span>📈 ' . $best_score . '% ' . esc_html__( 'máx', WP_AMSAWAL_TEXTDOMAIN ) . '</span>';
	$summary .= '</div></div>';

	// Section: recent activities.
	$summary .= '<section class="duo-quest-section"><div class="duo-quest-section-header"><h2>🕐 ' . esc_html__( 'Actividades recientes', WP_AMSAWAL_TEXTDOMAIN ) . '</h2></div>';
	$summary .= $cards;
	$summary .= '</section>';
	$summary .= '</div>';

	return $summary;
}


function wp_amsawal_h5p_status_by_id ($userid, $contentid) {
	$userid    = (int) $userid;
	$contentid = (int) $contentid;
	if ( $userid <= 0 || $contentid <= 0 ) {
		return 0;
	}

	// Transient cache: 5 min. H5P results change on completion.
	$cache_key = 'wp_amsawal_h5p_status_' . $userid . '_' . $contentid;
	$cached = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;
	$hp5results = $wpdb->get_results( $wpdb->prepare(
		"SELECT score, max_score FROM {$wpdb->prefix}h5p_results WHERE user_id = %d AND content_id = %d LIMIT 1",
		$userid,
		$contentid
	) );
    if (empty($hp5results)) {
		set_transient( $cache_key, 0, 5 * MINUTE_IN_SECONDS );
		return 0;
	}
	$row = $hp5results[0];
	$max = max( 1, (int) $row->max_score );
	$score = ( (int) $row->score ) * 10 / $max;

	$status = 0;
	if ($score == 10) {
		$status = 10;
	}
	elseif ($score >= 5) {
		$status = 5;
	}
	else {
		$status = 1;
	}

	set_transient( $cache_key, $status, 5 * MINUTE_IN_SECONDS );
	return $status;
}
