<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', 'wp_amsawal_admin_setup_menu');
function wp_amsawal_admin_setup_menu() {
	add_menu_page( 'WP Amsawal Admin Page', 'Amsawal', 'manage_options', 'wp-amsawal-admin', 'wp_amsawal_admin_setup_menu_init', plugins_url('images/yaz_icon.png',__FILE__ ), '25');
	add_submenu_page(
		'wp-amsawal-admin',
		__( 'Registro de eventos', WP_AMSAWAL_TEXTDOMAIN ),
		__( '📋 Registro', WP_AMSAWAL_TEXTDOMAIN ),
		'manage_options',
		'wp-amsawal-log',
		'wp_amsawal_admin_log_page'
	);
}
 
function wp_amsawal_admin_setup_menu_init() {

	echo '<div class="wrap">
	<h1>' . wp_amsawal_nav_icon('sliders', 'lg') . ' Amsawal</h1></div>';

	wp_amsawal_homepage_changed();

	echo '

	<ul class="nav nav-tabs" id="myTab" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" id="list-tab" data-toggle="tab" href="#list" role="tab" aria-controls="list" aria-selected="true">Listado</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="new-tab" data-toggle="tab" href="#newactivity" role="tab" aria-controls="newactivity" aria-selected="false">Nueva Actividad</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="new-tab" data-toggle="tab" href="#newcourse" role="tab" aria-controls="newcourse" aria-selected="false">Nuevo Curso</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="false">Inicio</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="' . esc_url( admin_url( 'admin.php?page=wp-amsawal-studio' ) ) . '">📱 AI Studio</a>
		</li>
	</ul>

	<div class="tab-content" id="myTabContent">
	';
	echo '<div class="tab-pane fade show active" id="list" role="tabpanel" aria-labelledby="list-tab">';
	//wp_amsawal_admin_all_pages(wp_amsawal_get_allpages_child(get_option( 'page_on_front' )));
	wp_amsawal_admin_all_pages(wp_amsawal_get_allpages_child(null));
	echo '</div>';
	echo '<div class="tab-pane fade" id="newactivity" role="tabpanel" aria-labelledby="new-tab">';
	wp_amsawal_admin_new_page (wp_amsawal_get_allpages_child(), 'activity');
	echo '</div>';
	echo '<div class="tab-pane fade" id="newcourse" role="tabpanel" aria-labelledby="new-tab">';
	wp_amsawal_admin_new_page (wp_amsawal_get_allpages_child(), 'course');
	echo '</div>';
	echo '<div class="tab-pane fade" id="home" role="tabpanel" aria-labelledby="home-tab">';
	wp_amsawal_homepage (wp_amsawal_get_allpages_child());
	echo '</div>';

	echo '</div>';
}

function wp_amsawal_get_allpages_child ($parent = 0) {
	global $post; 
	$page_config = array(
		'post_type' => 'page',
		'post_parent' => $parent,
		'order' => 'ASC',
		'posts_per_page' => -1
		);
	$pages = new WP_Query($page_config);
	if ( $pages->have_posts() ) {
    	while ( $pages->have_posts() ) {
			$pages->the_post();
			$url = get_permalink();	
			$custom_fields = get_post_custom($post->ID);
			$imageID = get_post_thumbnail_id( $post->ID );
			$image = wp_get_attachment_image_src( $imageID, 'thumbnail' );
			$object = (object) [
								'id' => $post->ID, 
								'url' => esc_url( $url ), 
								'typeh5p' => (isset($custom_fields["wp_amsawal_mb_typeh5p"][0]) ? $custom_fields["wp_amsawal_mb_typeh5p"][0] : null), 
								'lesson' => (isset($custom_fields["wp_amsawal_mb_lesson"][0]) ? $custom_fields["wp_amsawal_mb_lesson"][0] : null), 
								'course' => (isset($custom_fields["wp_amsawal_mb_course"][0]) ? $custom_fields["wp_amsawal_mb_course"][0] : null), 
								'step' => (isset($custom_fields["wp_amsawal_mb_step"][0]) ? $custom_fields["wp_amsawal_mb_step"][0] : null), 
								'video' => (isset($custom_fields["wp_amsawal_mb_video"][0]) ? $custom_fields["wp_amsawal_mb_video"][0] : null), 
								'image' => (isset($image[0]) ? $image[0] : null), 
								'title' => get_the_title(),
								'content' => apply_filters('the_content', $post->post_content)
								];
			if (!empty($object->course)) {
				$allpages[] = $object;
			}
			elseif (isset($parent) && ($object->title == "Cursos disponibles")) {
				$allpages[] = $object;
			}
		}
	}
	wp_reset_postdata();
	wp_reset_query();
	return $allpages;
}

function wp_amsawal_homepage_changed () {
	if ( ! isset( $_POST['wp_amsawal_changehome'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! isset( $_POST['wp_amsawal_changehome_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['wp_amsawal_changehome_nonce'], 'wp_amsawal_changehome' ) ) return;

	$newhomepage = isset( $_POST['wp_amsawal_newhome'] )
		? intval( $_POST['wp_amsawal_newhome'] )
		: 0;
	if ( ! $newhomepage || ! get_post( $newhomepage ) ) return;
	update_option( 'page_on_front', $newhomepage );
	update_option( 'show_on_front', 'page' );
	echo '<div class="notice notice-success is-dismissible"><p>Página de inicio actualizada.</p></div>';
}

function wp_amsawal_ai_config_page() {
	if ( isset($_POST['wp_amsawal_ai_config_nonce']) && wp_verify_nonce($_POST['wp_amsawal_ai_config_nonce'], 'wp_amsawal_save_ai_config') && current_user_can('manage_options') ) {
		update_option('wp_amsawal_ai_endpoint', sanitize_text_field($_POST['ai_endpoint']));
		update_option('wp_amsawal_ai_key', sanitize_text_field($_POST['ai_key']));
		update_option('wp_amsawal_ai_model', sanitize_text_field($_POST['ai_model']));
		echo '<div class="notice notice-success is-dismissible"><p>Ajustes de IA guardados correctamente. Ahora el sistema usará estos valores dinámicos.</p></div>';
	}

	$endpoint = get_option('wp_amsawal_ai_endpoint', 'https://api-inference.modelscope.ai/v1/chat/completions');
	$key = get_option('wp_amsawal_ai_key', '');
	$model = get_option('wp_amsawal_ai_model', 'Qwen/Qwen3-Next-80B-A3B-Instruct');

	echo '
	<div class="wrap" style="max-width: 800px; margin-top: 20px;">
		<h2>⚙️️ Configuración del Motor de Inteligencia Artificial</h2>
		<p class="description">Aquí puedes cambiar dinámicamente el proveedor de IA (Ollama, OpenAI, Groq, OpenRouter, ModelScope) sin tener que editar el código o las variables de entorno. Los valores aquí guardados <strong>sobrescriben</strong> las constantes del sistema.</p>

		<form method="post" action="">
			' . wp_nonce_field('wp_amsawal_save_ai_config', 'wp_amsawal_ai_config_nonce', true, false) . '
			<table class="form-table">
				<tr>
					<th scope="row"><label for="ai_endpoint">Endpoint (URL)</label></th>
					<td>
						<input name="ai_endpoint" type="url" id="ai_endpoint" value="'.esc_attr($endpoint).'" class="regular-text" style="width: 100%;">
						<p class="description">
							<strong>Recomendado · ModelScope (gratis):</strong> <code>https://api-inference.modelscope.ai/v1/chat/completions</code><br>
							Ollama (local): <code>http://localhost:11434/api/generate</code> o <code>/api/chat</code><br>
							OpenAI: <code>https://api.openai.com/v1/chat/completions</code><br>
							Groq: <code>https://api.groq.com/openai/v1/chat/completions</code><br>
							OpenRouter: <code>https://openrouter.ai/api/v1/chat/completions</code>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ai_key">API Key (Token)</label></th>
					<td>
						<input name="ai_key" type="password" id="ai_key" value="'.esc_attr($key).'" class="regular-text" style="width: 100%;">
						<p class="description">Token del proveedor. Déjalo en blanco solo si usas Ollama local. Para ModelScope, regístrate en <a href="https://modelscope.ai" target="_blank" rel="noopener">modelscope.ai</a> y obtén un token gratuito.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ai_model">Modelo</label></th>
					<td>
						<input name="ai_model" type="text" id="ai_model" value="'.esc_attr($model).'" class="regular-text" style="width: 100%;">
						<p class="description">
							Ejemplos:<br>
							- ModelScope: <code>Qwen/Qwen3-Next-80B-A3B-Instruct</code> (gratis, recomendado para español)<br>
							- OpenAI: <code>gpt-4o-mini</code><br>
							- Groq: <code>llama3-8b-8192</code><br>
							- Ollama: <code>llama3.2:3b</code>
						</p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Cambios">
			</p>
		</form>
	</div>';
}

function wp_amsawal_homepage ($allpages) {
	global $post;
	$current_homepage = get_option( 'page_on_front' );
	$parent_pages = new WP_Query(array(
		'post_type' => 'page',
		'post_parent' => 0,
		'order' => 'ASC',
		'posts_per_page' => -1
	));
	$url = admin_url() .'admin.php?page=wp-amsawal-admin';
	$changehome_nonce = wp_create_nonce( 'wp_amsawal_changehome' );
	echo '
		<div class="tablenav-pages">
		 	<p>Actualmente tu página de inicio es: <strong><a href="'.esc_url(get_permalink($current_homepage)).'">'.esc_html(get_the_title($current_homepage)).'</a></strong><p>
			<form action="'.esc_url($url).'" id="wp_amsawal_hompage" method="post">
			<select id="wp_amsawal_newhome" name="wp_amsawal_newhome">
		';
		foreach ($allpages as &$page) {
				if (!empty($page->course) || ($page->title == "Cursos disponibles")) {
					echo '<option value="'.esc_attr($page->id).'" '.($page->id == $current_homepage ? "selected" : "").'>'.esc_html($page->title).'</option>';
				}
		}
	echo '
			</select>
			<button class="button-primary" type="submit">Cambiar página</button>
			<input type="hidden" name="wp_amsawal_changehome" id="wp_amsawal_changehome" value="true" />
			<input type="hidden" name="wp_amsawal_changehome_nonce" id="wp_amsawal_changehome_nonce" value="'.esc_attr($changehome_nonce).'" />
		</form>
		</div>
	';

}


add_filter( 'page_attributes_dropdown_pages_args', 'wp_amsawal_admin_new_page_set_parent', 10, 2 );
function wp_amsawal_admin_new_page_set_parent( $dropdown_args, $post ) {
	global $post;
	if (empty($_GET['wp_amsawal_parent_id'])) {
		$dropdown_args = array(
		'post_type'        => $post->post_type,
		'exclude_tree'     => $post->ID,
		'selected'         => $post->post_parent,
		'name'             => 'parent_id',
		'show_option_none' => __( '(no parent)' ),
		'sort_column'      => 'menu_order, post_title',
		'echo'             => 0,
	);
	}
	else {
		$parent_id = esc_attr( $_GET['wp_amsawal_parent_id']);
		$parent_title = get_the_title ($parent_id);
		$dropdown_args = array(
			'post_type'        => $post->post_type,
			'exclude_tree'     => $post->ID,
			'selected'         => $parent_id,
			'name'             => 'parent_id',
			'show_option_none' => __( '(no parent)' ),
			'sort_column'      => 'menu_order, post_title',
			'echo'             => 0,
		);
	}
	return $dropdown_args;
}

function wp_amsawal_admin_new_page ($allpages, $typepage) {
	$havecourses = false;
	echo '
	<div class="tablenav-pages">
	<form action="'.admin_url().'post-new.php" id="wp_amsawal_new_page" method="get">
		<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_course">Curso: </label></p>
		<p>
		';
		if ($typepage == 'activity') {
			echo '<select name="wp_amsawal_mb_course" id="wp_amsawal_mb_course">
				';
				foreach ($allpages as &$page) {
						if (!empty($page->course)) {
							echo '<option value="'.esc_attr($page->course).'" '.($page->id == $current_homepage ? "selected" : "").'>'.esc_html($page->course).'</option>';
							$havecourses = true;
						}
				}
			echo '</select>';
		}
		else {
		echo '<input type="text" name="wp_amsawal_mb_course" id="wp_amsawal_mb_course" value="'.esc_attr(isset($course) ? $course : '').'" />';
		}
		echo '</p>';
		if ($typepage == 'activity') {
			echo '<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_lesson">Lección: </label></p>
			<p>
			<input type="number" name="wp_amsawal_mb_lesson" id="wp_amsawal_mb_lesson" value="'.(isset($lesson) ? $lesson : null).'" />
			</p>
			<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_step">Peldaño: </label></p>
			<p>
			<input type="number" name="wp_amsawal_mb_step" id="wp_amsawal_mb_step" value="'.(isset($step) ? $step : null).'" />
			</p>
			<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_parent_id">Padre: </label></p>
			<p>
			<select name="wp_amsawal_parent_id" id="wp_amsawal_parent_id">
			';
		foreach ($allpages as &$page) {
				if (!empty($page->course)) {
					echo '<option value="'.esc_attr($page->id).'" '.($page->id == $current_homepage ? "selected" : "").'>'.esc_html($page->title).'</option>';
				}
			}
			echo '
				</select>
				</p>
				<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_typeh5p">Tipo: </label></p>
				<p>
				<select name="wp_amsawal_mb_typeh5p" id="wp_amsawal_mb_typeh5p">
					<option value="" style="min-width: 300px;">(no definido)</option>
					<option value="test">Test</option>
					<option value="lesson">Lesson</option>
					<option value="memory">Memory</option>
					<option value="dialogcards">Dialogcards</option>
					<option value="video">Video</option>
					<option value="presentation">Presentation</option>
					<option value="accordion">Accordion</option>
					<option value="flashcards">Flashcards</option>
				</select>
				</p>';
		}
		else {
			echo '<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_video">Vídeo (opcional): </label></p>
			<p>
			<input type="text" name="wp_amsawal_mb_video" id="wp_amsawal_mb_video" value="'.(isset($video) ? $video : null).'" />
			</p>';
		}
		echo '<p>';
		if ($typepage == 'activity') {
			echo '<button class="button-primary" type="submit" '.($havecourses ? '' : 'disabled').'>'.(!$havecourses ? 'Primero tienes que crear un curso' : 'Crear nueva lección').'</button>';
		}
		else {
			echo '<button class="button-primary" type="submit">Crear nuevo curso</button>';
		}
		echo'<input type="hidden" name="submitted" id="submitted" value="true" />
			<input type="hidden" id="post_type" name="post_type" value="page"> 
			</p>
	</form>
	</div>
	';
}


function wp_amsawal_admin_all_pages ($allpages) {
	echo '
	<script>
jQuery(document).ready( function () {
	var $table  = jQuery(\'#wp-amsawal-datatable\');
	var $loader = jQuery(\'.duo-dt-loader\');
	$table.on(\'init.dt processing.dt\', function (e, settings, processing) {
		if (processing) {
			$loader.attr(\'aria-hidden\', \'false\').show();
		} else {
			$loader.attr(\'aria-hidden\', \'true\').hide();
		}
	});
	$table.DataTable( {
		"order": [[ 3, "asc" ], [ 4, "asc" ] ],
		"language": {
			"url": "' . plugins_url('js/i18n/es-ES.json', __FILE__) . '"
		},
		"pageLength": 25,
		"initComplete": function () {
			$loader.attr(\'aria-hidden\', \'true\').hide();
		}
	} );
} );
	</script>
	<div class="duo-dt-loader" aria-hidden="true" role="status" aria-live="polite">
		<span class="duo-dt-spinner" aria-hidden="true"></span>
		<span class="duo-dt-label">Cargando listado…</span>
	</div>
	<table class="widefat table table-striped table-bordered" id="wp-amsawal-datatable">
	<thead>
		<tr>
			<th class="th-sm">Acciones</th>
			<th class="th-sm">Título</th>
			<th class="th-sm">Curso</th>
			<th class="th-sm">Lección</th>
			<th class="th-sm">Peldaño</th>
			<th class="th-sm">Tipo</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Acciones</th>
			<th>Título</th>
			<th>Curso</th>
			<th>Lección</th>
			<th>Peldaño</th>
			<th>Tipo</th>
		</tr>
	</tfoot>
	<tbody>
		';
	foreach ($allpages as &$page) {
		echo '
		   <tr>
			 <td>
				<a href="'.esc_url($page->url).'">' . wp_amsawal_nav_icon('eye', 'sm') . '</a>
				<a href="'.esc_url(admin_url('post.php?post='.intval($page->id).'&action=edit')).'">' . wp_amsawal_nav_icon('edit', 'sm') . '</a>
			</td>
			 <td>'.esc_html($page->title).'</td>
			 <td>'.esc_html($page->course).'</td>
			 <td>'.esc_html($page->lesson).'</td>
			 <td>'.esc_html($page->step).'</td>
			 <td>'.esc_html($page->typeh5p).'</td>
		   </tr>
			';
	}
	echo '
	</tbody>
	</table>
	';
}


/**
 * Página admin: Registro de eventos.
 * Muestra las últimas 200 entradas de `wp_amsawal_log` con filtro por nivel.
 */
function wp_amsawal_admin_log_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permiso denegado', WP_AMSAWAL_TEXTDOMAIN ) );
	}

	// Exportar log (CSV / JSON) si se solicita.
	if ( isset( $_GET['action'] ) && $_GET['action'] === 'export' && isset( $_GET['format'] ) ) {
		if ( ! check_admin_referer( 'wp_amsawal_log_export' ) ) {
			wp_die( esc_html__( 'Token de seguridad inválido', WP_AMSAWAL_TEXTDOMAIN ) );
		}
		$format = sanitize_key( $_GET['format'] );
		$log    = get_option( 'wp_amsawal_log', array() );
		$level  = isset( $_GET['level'] ) ? sanitize_key( $_GET['level'] ) : '';
		if ( $level ) {
			$log = array_values( array_filter( $log, function( $e ) use ( $level ) { return isset( $e['level'] ) && $e['level'] === $level; } ) );
		}
		$filename = 'amsawal-log-' . gmdate( 'Ymd-His' );
		nocache_headers();
		if ( $format === 'json' ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '.json"' );
			echo wp_json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		} elseif ( $format === 'csv' ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '.csv"' );
			$out = fopen( 'php://output', 'w' );
			// BOM para que Excel detecte UTF-8.
			fwrite( $out, "\xEF\xBB\xBF" );
			fputcsv( $out, array( 'time', 'level', 'user', 'message', 'context' ) );
			foreach ( $log as $e ) {
				fputcsv( $out, array(
					$e['time']    ?? '',
					$e['level']   ?? 'info',
					$e['user']    ?? '',
					$e['message'] ?? '',
					$e['context'] ?? '',
				) );
			}
			fclose( $out );
		} else {
			wp_die( esc_html__( 'Formato no soportado', WP_AMSAWAL_TEXTDOMAIN ) );
		}
		exit;
	}

	// Vaciar el log si se solicita.
	if ( isset( $_POST['wp_amsawal_clear_log'] ) && check_admin_referer( 'wp_amsawal_log_action' ) ) {
		update_option( 'wp_amsawal_log', array() );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Registro vaciado.', WP_AMSAWAL_TEXTDOMAIN ) . '</p></div>';
	}

	$log   = get_option( 'wp_amsawal_log', array() );
	$level = isset( $_GET['level'] ) ? sanitize_key( $_GET['level'] ) : '';
	if ( $level ) {
		$log = array_values( array_filter( $log, function( $e ) use ( $level ) { return isset( $e['level'] ) && $e['level'] === $level; } ) );
	}
	$log = array_reverse( $log ); // más recientes arriba

	echo '<div class="wrap"><h1>📋 ' . esc_html__( 'Registro de eventos', WP_AMSAWAL_TEXTDOMAIN ) . '</h1>';

	// Filtros por nivel.
	$levels = array( '' => __( 'Todos', WP_AMSAWAL_TEXTDOMAIN ), 'error' => __( 'Errores', WP_AMSAWAL_TEXTDOMAIN ), 'warning' => __( 'Avisos', WP_AMSAWAL_TEXTDOMAIN ), 'info' => __( 'Info', WP_AMSAWAL_TEXTDOMAIN ), 'debug' => __( 'Debug', WP_AMSAWAL_TEXTDOMAIN ) );
	echo '<p><nav class="subsubsub">';
	$first = true;
	foreach ( $levels as $key => $label ) {
		$url = add_query_arg( 'level', $key, admin_url( 'admin.php?page=wp-amsawal-log' ) );
		$cls = $key === $level ? ' class="current"' : '';
		echo ( $first ? '' : ' | ' ) . '<a href="' . esc_url( $url ) . '"' . $cls . '>' . esc_html( $label ) . '</a>';
		$first = false;
	}
	echo '</nav></p>';

	// Botón limpiar + exportar.
	echo '<form method="post" style="margin: 12px 0; display: inline-block;">';
	wp_nonce_field( 'wp_amsawal_log_action' );
	echo '<button type="submit" name="wp_amsawal_clear_log" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( '¿Vaciar el registro?', WP_AMSAWAL_TEXTDOMAIN ) ) . '\')">' . esc_html__( 'Vaciar registro', WP_AMSAWAL_TEXTDOMAIN ) . '</button>';
	echo '</form> ';

	// Botones exportar (CSV / JSON) con nonce y nivel preservado.
	$export_base = add_query_arg( array_merge( array( 'action' => 'export' ), $level ? array( 'level' => $level ) : array() ), admin_url( 'admin.php?page=wp-amsawal-log' ) );
	$export_csv  = wp_nonce_url( add_query_arg( 'format', 'csv', $export_base ), 'wp_amsawal_log_export' );
	$export_json = wp_nonce_url( add_query_arg( 'format', 'json', $export_base ), 'wp_amsawal_log_export' );
	echo '<a href="' . esc_url( $export_csv ) . '" class="button button-secondary">⬇️ ' . esc_html__( 'Exportar CSV', WP_AMSAWAL_TEXTDOMAIN ) . '</a> ';
	echo '<a href="' . esc_url( $export_json ) . '" class="button button-secondary">⬇️ ' . esc_html__( 'Exportar JSON', WP_AMSAWAL_TEXTDOMAIN ) . '</a>';

	if ( empty( $log ) ) {
		echo '<p>' . esc_html__( 'No hay entradas en el registro.', WP_AMSAWAL_TEXTDOMAIN ) . '</p></div>';
		return;
	}

	echo '<table class="widefat striped"><thead><tr>';
	echo '<th>' . esc_html__( 'Fecha', WP_AMSAWAL_TEXTDOMAIN ) . '</th>';
	echo '<th>' . esc_html__( 'Nivel', WP_AMSAWAL_TEXTDOMAIN ) . '</th>';
	echo '<th>' . esc_html__( 'Usuario', WP_AMSAWAL_TEXTDOMAIN ) . '</th>';
	echo '<th>' . esc_html__( 'Mensaje', WP_AMSAWAL_TEXTDOMAIN ) . '</th>';
	echo '<th>' . esc_html__( 'Contexto', WP_AMSAWAL_TEXTDOMAIN ) . '</th>';
	echo '</tr></thead><tbody>';

	$level_colors = array(
		'error'   => '#c80000',
		'warning' => '#ff9600',
		'info'    => '#1cb0f6',
		'debug'   => '#666',
	);
	foreach ( $log as $entry ) {
		$lvl  = isset( $entry['level'] ) ? $entry['level'] : 'info';
		$clr  = isset( $level_colors[ $lvl ] ) ? $level_colors[ $lvl ] : '#666';
		$ctx  = isset( $entry['context'] ) ? $entry['context'] : '';
		echo '<tr>';
		echo '<td><code>' . esc_html( $entry['time'] ) . '</code></td>';
		echo '<td><span style="color:' . esc_attr( $clr ) . '; font-weight:700;">' . esc_html( strtoupper( $lvl ) ) . '</span></td>';
		echo '<td>' . esc_html( (string) ( $entry['user'] ?? '-' ) ) . '</td>';
		echo '<td>' . esc_html( $entry['message'] ) . '</td>';
		echo '<td><small><code>' . esc_html( $ctx ) . '</code></small></td>';
		echo '</tr>';
	}
	echo '</tbody></table></div>';
}
