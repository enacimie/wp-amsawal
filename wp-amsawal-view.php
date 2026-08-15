<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wp_amsawal_hide_admin_bar(){
    if ( current_user_can( 'manage_options' ) ) return true;
    return false;
}
add_filter( 'show_admin_bar', 'wp_amsawal_hide_admin_bar' );

// Add body class for immersive Duolingo shell targeting (more robust than :has())
add_filter( 'body_class', 'wp_amsawal_body_class' );
function wp_amsawal_body_class( $classes ) {
    if ( is_admin() ) return $classes;
    if ( wp_doing_ajax() ) return $classes;

    global $post;
    
    // Siempre añadir en páginas de perfil /i/username/
    if ( get_query_var( 'amsawal_profile' ) ) {
        $classes[] = 'amsawal-immersive';
        return $classes;
    }
    
    if ( ! $post ) return $classes;

    $type = get_post_meta( $post->ID, 'wp_amsawal_mb_typeh5p', true );
    $front_id = get_option( 'page_on_front' );

    // Learning path pages (front page with course children or lesson/activity pages)
    if ( $post->ID == $front_id || ! empty( $type ) ) {
        $classes[] = 'amsawal-immersive';
    }

    return $classes;
}



function wp_amsawal_get_between_two ($content, $start, $end) {
	if(!empty(explode($start, $content)[1])) {
		return explode($end, explode($start, $content)[1])[0];
	}
	else {
		return null;
	}
}


add_action('wp', 'wp_amsawal_redirect', 3);
function wp_amsawal_redirect($content) {
	$allowed_pages = array (
		'registro',
		'activar',
		'politica-de-privacidad'
	);
	if (!is_user_logged_in()) {
		$pagename = get_query_var('pagename');  
		$active = 'activar/';
		global $wp;
		$current_slug = add_query_arg( array(), $wp->request );
		if (!in_array($pagename, $allowed_pages) && substr($current_slug, 0, strlen($active)) !== $active) {
			$url = wp_login_url(home_url( '/' ));
			wp_redirect( $url);
			exit;
		}
	}
	return $content;
}

add_action('wp_login', 'wp_amsawal_redirect_login');
function wp_amsawal_redirect_login() {
	$url =  home_url( '/' );
	wp_redirect( $url);
	exit;
}

add_action('wp_logout', 'wp_amsawal_redirect_logout');
function wp_amsawal_redirect_logout() {
	$url = wp_login_url(home_url( '/' ));
	wp_redirect( $url);
	exit;
}

add_filter( 'the_content', 'wp_amsawal_show_front_page' );
function wp_amsawal_show_front_page($content) {

	if (is_admin()) {
		return $content;
	}
	// Skip rendering for AJAX, REST API, or forced raw output (?f=1).
	if ( wp_doing_ajax() || defined( 'REST_REQUEST' ) ) return $content;
	if ( isset( $_GET['f'] ) && ! empty( $_GET['f'] ) ) return $content;

	// Prevent recursion when this filter processes sub-pages.
	static $is_processing = false;
	if ($is_processing) {
		return $content;
	}

	global $post;
	$currentid = get_the_ID();
	$front_id = get_option('page_on_front');
	$user_front_id = wp_amsawal_get_user_homepage();
	if(filter_var($user_front_id, FILTER_VALIDATE_INT)) {
		$front_id = $user_front_id;
	}
	if ($currentid != $front_id) {
		return $content;
	}
	
	// Mark as processing to prevent recursion.
	$is_processing = true;
	$page_config = array(
		'post_type' => 'page',
		'post_parent' => $currentid,
		'order' => 'ASC',
		'posts_per_page' => -1,
		'orderby'   => 'meta_value',
		'meta_key'  => 'wp_amsawal_mb_step',
		'meta_type' => 'NUMERIC'
		);

	$pages = new WP_Query($page_config);
	$max_lesson = 0;

	if ( $pages->have_posts() ) {
		$allpages = array();
    	while ( $pages->have_posts() ) {
			$pages->the_post();
			$url = get_permalink();
			$custom_fields = get_post_custom($post->ID);

			$h5pid = wp_amsawal_get_between_two (get_the_content(), '[h5p id="', '"]');

			$allpages[] = (object) [
				'id' => $post->ID, 
				'url' => esc_url( $url ), 
				'typeh5p' => isset($custom_fields["wp_amsawal_mb_typeh5p"][0]) ? $custom_fields["wp_amsawal_mb_typeh5p"][0] : '',
				'lesson' => isset($custom_fields["wp_amsawal_mb_lesson"][0]) ? $custom_fields["wp_amsawal_mb_lesson"][0] : 0,
				'course' => isset($custom_fields["wp_amsawal_mb_course"][0]) ? $custom_fields["wp_amsawal_mb_course"][0] : '',
				'step' => isset($custom_fields["wp_amsawal_mb_step"][0]) ? $custom_fields["wp_amsawal_mb_step"][0] : 0,
				'title' => get_the_title(),
				'content' => apply_filters('the_content', $post->post_content),
				'h5pid' => $h5pid
			];
			if ($max_lesson < (isset($custom_fields["wp_amsawal_mb_lesson"][0]) ? $custom_fields["wp_amsawal_mb_lesson"][0] : 0)) {
				$max_lesson = isset($custom_fields["wp_amsawal_mb_lesson"][0]) ? $custom_fields["wp_amsawal_mb_lesson"][0] : 0;
			}
		}

		// Verificar que tenemos páginas antes de continuar
		if (empty($allpages)) {
			return $content;
		}

		if ( ! function_exists( 'gamipress_get_user_points' ) ) {
			return $content;
		}

		global $wpdb;
		$userid = wp_get_current_user()->ID;
		
		// Calculate current rank based on completed lessons (not GamiPress ranks)
		$completed_items = get_user_meta( $userid, '_amsawal_completed_items', true );
		
		// Count how many unique lessons have been completed
		$completed_lessons_set = array();
		if ( is_array( $completed_items ) ) {
			foreach ( $completed_items as $item ) {
				// Match H5P content IDs (e.g., "h5p-142")
				if ( strpos( $item, 'h5p-' ) === 0 ) {
					$h5p_id = (int) substr( $item, 4 );
					// Find which lesson this H5P belongs to
					foreach ( $allpages as $page ) {
						if ( $page->h5pid && (int) $page->h5pid === $h5p_id ) {
							$completed_lessons_set[ $page->lesson ] = true;
							break;
						}
					}
				}
			}
		}
		
		$current_rank = count( $completed_lessons_set ) + 1; // Next lesson to complete
		
		$total_lessons = (int)$max_lesson;
		$completed_lessons = max(0, (int)$current_rank - 1);
		$progress_pct = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;

		$current_points = function_exists( 'gamipress_get_user_points' ) ? (int) gamipress_get_user_points($userid, 'monedas') : 0;

		// Duolingo-style header with progress
		$content .= '<div class="duo-container" id="duo-main-content" tabindex="-1">';

		// Compact progress bar + coins (right above the path, doesn't distract)
		$content .= '<div class="duo-home-progress">';
		$content .= '<div class="duo-home-progress-main">';
		$content .= '<span class="duo-home-progress-label">' . esc_html( $completed_lessons ) . '/' . esc_html( $total_lessons ) . '</span>';
		$content .= '<div class="duo-home-progress-bar-wrapper" role="progressbar" aria-label="' . esc_attr__( 'Progreso del curso', WP_AMSAWAL_TEXTDOMAIN ) . '" aria-valuemin="0" aria-valuemax="' . esc_attr( $total_lessons ) . '" aria-valuenow="' . esc_attr( $completed_lessons ) . '"><div class="duo-home-progress-bar" style="width: ' . esc_attr( $progress_pct ) . '%"></div></div>';
		$content .= '</div>';
		$content .= '<span class="duo-home-coins">🪙 <strong>' . esc_html( $current_points ) . '</strong></span>';
		$content .= '</div>';

		// Quick quests link (compact, links to /quests/ page)
		$streak = (int) get_user_meta( $userid, '_wp_amsawal_streak_days', true );
		$content .= '<a href="' . esc_url( site_url( '/quests/' ) ) . '" class="duo-home-quests-link">';
		$content .= '<span class="duo-home-quests-icon">🔥</span>';
		$content .= '<span class="duo-home-quests-text">';
		$content .= '<strong>' . $streak . '</strong> ' . esc_html__( 'días de racha', WP_AMSAWAL_TEXTDOMAIN );
		$content .= ' · ';
		$content .= esc_html__( 'Ir a desafíos', WP_AMSAWAL_TEXTDOMAIN );
		$content .= '</span></a>';

		// ── Personalized Recommendations (F5-6) ──
		$content .= '<section class="duo-recommend-section" aria-labelledby="duo-recommend-heading" style="display:none">';
		$content .= '<div class="duo-recommend-header">';
		$content .= '<h2 id="duo-recommend-heading">🌟 ' . esc_html__( 'Recomendado para ti', WP_AMSAWAL_TEXTDOMAIN ) . '</h2>';
		$content .= '<span class="duo-recommend-subtitle">' . esc_html__( 'Basado en tu progreso', WP_AMSAWAL_TEXTDOMAIN ) . '</span>';
		$content .= '</div>';
		$content .= '<div class="duo-recommend-list" id="duo-recommend-list" role="list" aria-live="polite"></div>';
		$content .= '</section>';

		// The Path
		$content .= '<div class="duo-path">';
		
		// Section Headers basados en módulos
		$module_boundaries = [
			1 => [
				'title' => 'Sección 1', 
				'desc' => 'Alfabeto y Fonología',
				'guide' => '<h3>El Alfabeto Tifinagh</h3>
<p>El tifinagh (ⵜⵉⴼⵉⵏⴰⵖ) es el sistema de escritura ancestral de los amazigh. En esta sección aprenderás las vocales y consonantes básicas del tarifit.</p>
<h4>Conceptos clave:</h4>
<ul>
<li><strong>Vocales:</strong> ⴰ (a), ⵉ (i), ⵓ (u)</li>
<li><strong>Escritura:</strong> De izquierda a derecha</li>
<li><strong>Cada letra tiene un sonido único</strong></li>
</ul>
<h4>Consejo:</h4>
<p>Practica trazando cada letra en el aire mientras repites su sonido en voz alta.</p>'
			],
			5 => [
				'title' => 'Sección 2', 
				'desc' => 'Saludos y Presentaciones',
				'guide' => '<h3>Saludos en Tarifit</h3>
<p>Los saludos son fundamentales en la cultura amazigh. Son más que palabras: son una forma de reconocer al otro.</p>
<h4>Saludos básicos:</h4>
<ul>
<li><strong>ⴰⵣⵓⵍ (Azul):</strong> Hola / Paz</li>
<li><strong>ⵎⴰⵏⵉⴽ (Manik):</strong> ¿Cómo estás?</li>
<li><strong>ⵓⵔ ⵉⵍⵍⵉ (Ur illi):</strong> Estoy bien</li>
</ul>
<h4>Presentarse:</h4>
<p><strong>ⵙⵎⵉⵖ... (Smigh...):</strong> Me llamo...</p>
<h4>Consejo cultural:</h4>
<p>En la cultura amazigh, los saludos suelen ser largos y preguntar por la familia, la salud y el bienestar.</p>'
			],
			9 => [
				'title' => 'Sección 3', 
				'desc' => 'Números y Tiempo',
				'guide' => '<h3>Números en Tarifit</h3>
<p>El sistema numérico amazigh tiene sus propias raíces, aunque también ha adoptado formas del árabe.</p>
<h4>Números básicos (1-10):</h4>
<ul>
<li>1: ⵢⵓⵏ (yun)</li>
<li>2: ⵙⵉⵏ (sin)</li>
<li>3: ⴽⵕⴰⴹ (kraḍ)</li>
<li>4: ⴽⴽⵓⵥ (kkuẓ)</li>
<li>5: ⵙⵎⵎⵓⵙ (smmus)</li>
</ul>
<h4>El tiempo:</h4>
<p><strong>ⴰⵙⵙ (Ass):</strong> Día<br>
<strong>ⵉⵎⴰⵙⵙ (Imass):</strong> Noche<br>
<strong>ⴰⵢⵢⵓⵔ (Ayyur):</strong> Luna/Mes</p>
<h4>Consejo:</h4>
<p>Practica contando objetos a tu alrededor en tarifit.</p>'
			],
			12 => [
				'title' => 'Sección 4', 
				'desc' => 'Familia y Personas',
				'guide' => '<h3>La Familia en la Cultura Amazigh</h3>
<p>La familia (ⵜⴰⵡⴰⵛⵓⵍⵜ - tawacult) es el núcleo de la sociedad amazigh. Los lazos familiares son fuertes y el respeto a los mayores es fundamental.</p>
<h4>Familia cercana:</h4>
<ul>
<li><strong>ⴱⴰⴱⴰ (Baba):</strong> Padre</li>
<li><strong>ⵢⵉⵎⵎⴰ (Yimma):</strong> Madre</li>
<li><strong>ⵓⵎⴰ (Uma):</strong> Hermano</li>
<li><strong>ⵓⵍⵜⵎⴰ (Ultma):</strong> Hermana</li>
</ul>
<h4>Concepto cultural:</h4>
<p>En la cultura amazigh, los abuelos son considerados los guardianes de la sabiduría y las tradiciones.</p>
<h4>Consejo:</h4>
<p>Practica presentando a tu familia en tarifit.</p>'
			],
			16 => [
				'title' => 'Sección 5', 
				'desc' => 'Adjetivos y Descripciones',
				'guide' => '<h3>Adjetivos en Tarifit</h3>
<p>Los adjetivos en tarifit concuerdan en género y número con el sustantivo que modifican.</p>
<h4>Adjetivos básicos:</h4>
<ul>
<li><strong>ⴰⵎⵇⵇⵔⴰⵏ (Ameqqran):</strong> Grande</li>
<li><strong>ⴰⵎⵥⵢⴰⵏ (Ameẓyan):</strong> Pequeño</li>
<li><strong>ⴰⵎⵍⵉⵃ (Ameliḥ):</strong> Bonito</li>
<li><strong>ⴰⵎⵖⵍⵉ (Amɣli):</strong> Rápido</li>
</ul>
<h4>Colores:</h4>
<ul>
<li><strong>ⴰⵣⴳⵣⴰⵡ (Azgzaw):</strong> Verde</li>
<li><strong>ⴰⵣⴳⴳⵯⴰⵖ (Azggaɣ):</strong> Rojo</li>
<li><strong>ⴰⵎⵍⵍⴰⵍ (Amllal):</strong> Blanco</li>
</ul>
<h4>Concordancia:</h4>
<p>Los adjetivos cambian según el género:
- Masculino: ⴰⵎⵇⵇⵔⴰⵏ (ameqqran)
- Femenino: ⵜⴰⵎⵇⵇⵔⴰⵏⵜ (tameqqrant)</p>
<h4>Consejo:</h4>
<p>Describe objetos a tu alrededor usando adjetivos en tarifit.</p>'
			],
		];
		
		// Section end lessons
		$section_ends = [4, 8, 11, 15, 19];
		
		$test_page = null;
		for ($i = 1; $i <= $max_lesson; $i++) {
			// Mostrar header de sección si corresponde
			if (isset($module_boundaries[$i])) {
				$section_info = $module_boundaries[$i];
				$section_end = $section_ends[array_search($i, array_keys($module_boundaries))];
				$section_complete = ($current_rank > $section_end);
				$section_key = 'section-' . $i;
				
				$content .= '<div class="duo-unit-header' . ($section_complete ? ' duo-unit-header--complete' : '') . '">';
				$content .= '<div class="duo-unit-info">';
				$content .= '<h2 class="duo-unit-title">' . esc_html($section_info['title']) . '</h2>';
				$content .= '<p class="duo-unit-desc">' . esc_html($section_info['desc']) . '</p>';
				$content .= '</div>';
				
				if ($section_complete) {
					$content .= '<button class="duo-unit-guide-btn duo-unit-guide-btn--complete" data-section="' . esc_attr($section_key) . '" data-guide="' . esc_attr(wp_kses_post($section_info['guide'])) . '">' . wp_amsawal_nav_icon('check', 'sm') . ' <span>COMPLETADA</span> ' . wp_amsawal_nav_icon('book', 'sm') . '</button>';
				} else {
					$content .= '<button class="duo-unit-guide-btn" data-section="' . esc_attr($section_key) . '" data-guide="' . esc_attr(wp_kses_post($section_info['guide'])) . '">' . wp_amsawal_nav_icon('book', 'sm') . ' <span>GUÍA</span></button>';
				}
				$content .= '</div>';
			}
			
			$lesson_activities = array();
			$lesson_info = null;
			$lesson_test = null;
			
			foreach ($allpages as $page) {
				if ($page->lesson == $i) {
					if ($page->typeh5p == "lesson") {
						$lesson_info = $page;
						// Buscar en el contenido raw usando get_post
						$raw_post = get_post($page->id);
						if ($raw_post && strpos($raw_post->post_content, '[h5p id=') !== false) {
							$lesson_activities[] = $page;
						}
					}
					elseif ($page->typeh5p == "test") $lesson_test = $page;
					else $lesson_activities[] = $page;
				}
			}
			
			$node_status = ($i > $current_rank) ? 'locked' : (($i == $current_rank) ? 'current' : 'completed');
			$label = 'Lección '.$i;

			// Zig-Zag pattern: center → left → center → right → repeat
			$positions = ['center', 'left', 'center', 'right'];
			$pos_index = ($i - 1) % 4;
			$offset = 'duo-node--' . $positions[$pos_index];

			$lesson_url = '';
			if (($node_status === 'current' || $node_status === 'completed') && !empty($lesson_activities)) {
				$lesson_url = get_permalink($lesson_activities[0]->id);
			}

			if ($lesson_url) {
				$content .= '<a class="duo-node duo-node--'.$node_status.' '.$offset.'" data-lesson="'.$i.'" href="'.esc_url($lesson_url).'">';
			} else {
				$content .= '<div class="duo-node duo-node--'.$node_status.' '.$offset.'" data-lesson="'.$i.'">';
			}
			$content .= '<div class="duo-node-circle" role="button" aria-label="' . esc_attr($label) . '" tabindex="0">';
			if ($node_status == 'completed') $content .= '<span class="duo-node-check" aria-hidden="true">✓</span><span class="screen-reader-text">Completado</span>';
			else if ($node_status == 'locked') $content .= wp_amsawal_nav_icon('lock', 'sm');
			else $content .= '<span class="duo-node-icon">🔤</span>';
			$content .= '</div>';
			$content .= '<div class="duo-node-label">'.$label.'</div>';

			$lesson_xp = 10;
			$xp_status = '';
			if ($node_status === 'completed') {
				$xp_status = '--earned';
			} elseif ($node_status === 'current') {
				$xp_status = '--available';
			}
			if ($xp_status !== '') {
				$content .= '<div class="duo-node-xp duo-node-xp' . $xp_status . '">+' . $lesson_xp . ' XP</div>';
			}

			if ($lesson_url) {
				$content .= '</a>';
			} else {
				$content .= '</div>';
			}
		}
		$content .= '</div>'; // .duo-path
		$content .= '</div>'; // .duo-container
	}

	wp_reset_postdata();
	wp_reset_query();
	
	// Reset recursion flag.
	$is_processing = false;
	return $content;
}




function wp_amsawal_show_modal ($title = '', $message = '', $social = '', $course = false, $video = false, $target = 'amsawalGamipress') {
	$target_attr = esc_attr( $target );
	$title_html  = esc_html( $title );
	$message_html = wp_kses_post( $message );
	echo '	<div class="modal fade" id="'.$target_attr.'" tabindex="-1" role="dialog" aria-labelledby="'.$target_attr.'Title" aria-modal="true" aria-hidden="true">';
	echo '
	  <div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
		  <div class="modal-header">
		    <h5 class="modal-title" id="'.$target_attr.'Title">'.$title_html.'</h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="'.esc_attr__( 'Cerrar', WP_AMSAWAL_TEXTDOMAIN ).'" autofocus>
				<span aria-hidden="true">&times;</span>
			</button>';
		    echo '
		  </div>
		  <div class="modal-body">
		    '.$message_html;
		if (!$video) {
			echo '
			<a title="'.esc_attr__( 'Compartir en Twitter', WP_AMSAWAL_TEXTDOMAIN ).'" href="https://twitter.com/intent/tweet?text='.urlencode($social).'&amp;url='.urlencode(home_url('/')).'" target="_blank" rel="noopener noreferrer" style="float: right;">' . wp_amsawal_nav_icon('share', 'lg') . '</a>
			';
		}

		echo'  </div>';

		if ($course && !$video) {
			echo '
				<div class="modal-footer">
				<a href="'.esc_url( site_url() ).'" class="btn btn-primary">'.esc_html__( 'Continuar', WP_AMSAWAL_TEXTDOMAIN ).'</a>
				</div>
			';
		}
		elseif ($course && $video) {
			echo '
				<div class="modal-footer">
				<a href="'.esc_url( site_url() ).'?f=1" class="btn btn-primary">'.esc_html__( 'Volver', WP_AMSAWAL_TEXTDOMAIN ).'</a>
				</div>
			';
		}
		echo '
		</div>
	  </div>
	</div>';

	if (!$video) {
	echo '
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#'.$target_attr.'").modal(\'show\');
			});
		</script>
		';
	}
}


/**
 * Renderiza un toast no bloqueante en la esquina superior derecha.
 *
 * Se acumula en un contenedor #duo-toast-stack que se imprime al final del
 * the_content (vía wp_amsawal_print_toast_stack en wp_footer).
 *
 * @param string $title    Título corto del toast
 * @param string $message  Mensaje (puede contener HTML limitado como <img>)
 * @param string $type     success | info | warning | level | coin
 * @param string $share    Texto para Twitter (opcional)
 * @param int    $duration Milisegundos antes de auto-cerrar (0 = no cerrar)
 */
function wp_amsawal_show_toast( $title = '', $message = '', $type = 'success', $share = '', $duration = 4500 ) {
	static $printed_stack = false;
	if ( ! $printed_stack ) {
		$printed_stack = true;
		echo '<div id="duo-toast-stack" class="duo-toast-stack" role="region" aria-label="'.esc_attr__( 'Notificaciones', WP_AMSAWAL_TEXTDOMAIN ).'" aria-live="polite"></div>';
	}
	$id    = 'duo-toast-' . wp_unique_id();
	$type  = in_array( $type, array( 'success', 'info', 'warning', 'level', 'coin', 'achievement' ), true ) ? $type : 'info';
	$share_url = '';
	if ( ! empty( $share ) ) {
		$share_url = 'https://twitter.com/intent/tweet?text=' . urlencode( $share ) . '&amp;url=' . urlencode( home_url( '/' ) );
	}
	$share_html = $share_url
		? '<a class="duo-toast-share" href="' . esc_url( $share_url ) . '" target="_blank" rel="noopener noreferrer" aria-label="'.esc_attr__( 'Compartir en Twitter', WP_AMSAWAL_TEXTDOMAIN ).'">' . wp_amsawal_nav_icon('share', 'sm') . '</a>'
		: '';
	$dur_attr = $duration > 0 ? ' data-duration="' . intval( $duration ) . '"' : '';
	echo '<div id="' . esc_attr( $id ) . '" class="duo-toast duo-toast--' . esc_attr( $type ) . '"' . $dur_attr . ' role="status">';
	echo '  <div class="duo-toast-body">';
	echo '    <div class="duo-toast-title">' . esc_html( $title ) . '</div>';
	echo '    <div class="duo-toast-message">' . wp_kses_post( $message ) . '</div>';
	echo '  </div>';
	echo '  <div class="duo-toast-actions">';
	echo    $share_html;
	echo '    <button type="button" class="duo-toast-close" aria-label="' . esc_attr__( 'Cerrar notificación', WP_AMSAWAL_TEXTDOMAIN ) . '">&times;</button>';
	echo '  </div>';
	echo '</div>';
}

add_action( 'login_form', 'wp_amsawal_show_activate_link' );
function wp_amsawal_show_activate_link() {
	echo '<a href="'.esc_url( site_url('/activar') ).'" class="activate_amsawal">'.esc_html__( 'Activar cuenta', WP_AMSAWAL_TEXTDOMAIN ).'</a>';
	return;
}


/**
 * Breadcrumbs jerárquicos: Home › Curso › Lección › Peldaño actual
 *
 * Se muestra en páginas con `wp_amsawal_mb_typeh5p` definido.
 * La página actual puede ser una lección, una actividad o un test.
 * Se omiten en la front page (es ahí donde ya está el path completo).
 */
// F15-3: Output escaping audit
// Todos los outputs deben usar:
// - esc_html() para texto en HTML
// - esc_attr() para atributos HTML
// - esc_url() para URLs
// - wp_kses_post() para HTML permitido
// - intval() para números
