<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * wp-amsawal-studio.php — AI Creator Studio
 *
 * Panel unificado de administración para creación de contenido con IA.
 * Incluye: generador de cursos desde prompt, dashboard de contenido,
 * editor inline con preview, y generación batch mejorada.
 *
 * @package Amsawal
 * @since   0.0.4-studio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/*───────────────────────────────────────────────────────────────────────
 * 1. ADMIN MENU — Replace old AI tab with Studio
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'admin_menu', 'wp_amsawal_studio_menu', 20 );
function wp_amsawal_studio_menu() {
	add_submenu_page(
		'wp-amsawal-admin',
		__( 'AI Creator Studio', 'amsawal' ),
		'📱 ' . __( 'AI Studio', 'amsawal' ),
		'manage_options',
		'wp-amsawal-studio',
		'wp_amsawal_studio_page'
	);
}


/*───────────────────────────────────────────────────────────────────────
 * 2. MAIN STUDIO PAGE
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_studio_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permiso denegado', 'amsawal' ) );
	}

	$ajax_url   = admin_url( 'admin-ajax.php' );
	$gen_nonce  = wp_create_nonce( 'wp_amsawal_studio_generate' );
	$map_nonce  = wp_create_nonce( 'wp_amsawal_studio_map' );
	$save_nonce = wp_create_nonce( 'wp_amsawal_studio_save' );
	$regen_nonce = wp_create_nonce( 'wp_amsawal_ai_regenerate' );

	// Gather existing courses for dashboard.
	$course_pages = get_posts( array(
		'post_type'      => 'page',
		'post_parent'    => 0,
		'post_status'    => 'publish',
		'numberposts'    => -1,
		'meta_key'       => 'wp_amsawal_mb_course',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );
	$courses_json = array();
	$total_lessons = 0;
	$total_activities = 0;

	// Define h5p_types early so we can use it for counting
	$h5p_types = array(
		'flashcards'       => '️ Flashcards',
		'dialogcards'      => '📋 Dialogcards',
		'dictation'        => ' Dictation',
		'memory'           => '💡 Memory',
		'fill-blanks'      => ' Fill blanks',
		'mark-the-words'   => '🔍 Mark the words',
		'multiple-choice'  => ' Multiple choice',
		'drag-drop'        => '↔️ Drag & drop',
		'true-false'       => '✅ True/False',
		'speak-the-words'  => '️ Speak the words',
		'essay'            => '✏️ Essay',
		'adaptest'         => '📍 Adaptest',
	);

	foreach ( $course_pages as $cp ) {
		$cn = get_post_meta( $cp->ID, 'wp_amsawal_mb_course', true );
		if ( ! empty( $cn ) ) {
			$courses_json[] = array( 'id' => $cp->ID, 'name' => $cn, 'title' => $cp->post_title );
			
			// Count lessons for this course by meta
			$lessons = get_posts( array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'meta_query'     => array(
					array( 'key' => 'wp_amsawal_mb_course', 'value' => $cn ),
					array( 'key' => 'wp_amsawal_mb_lesson', 'value' => '', 'compare' => '!=' ),
				),
			) );
			$lesson_count = count( $lessons );
			$total_lessons += $lesson_count;
			
			// Count H5P activities for this course
			global $wpdb;
			$activity_count = 0;
			$lesson_ids = wp_list_pluck( $lessons, 'ID' );
			if ( ! empty( $lesson_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $lesson_ids ), '%d' ) );
				$h5p_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(DISTINCT h.id) FROM {$wpdb->prefix}h5p_contents h
					 JOIN {$wpdb->posts} p ON h.id = (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '_amsawal_h5p_id' LIMIT 1)
					 WHERE p.ID IN ($placeholders)",
					$lesson_ids
				) );
				// Simpler: count posts with H5P shortcode
				$h5p_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID IN ($placeholders) AND post_content LIKE '%[h5p%'",
					$lesson_ids
				) );
				$activity_count = intval( $h5p_count );
			}
			$total_activities += $activity_count;
		}
	}
	
	// Calculate coverage
	$total_possible = $total_lessons * count( $h5p_types );
	$coverage = $total_possible > 0 ? round( $total_activities * 100 / $total_possible ) : 0;

	?>
	<style>
	/* ── AI Creator Studio — Admin Styles ── */
	.duo-studio { max-width: 1200px; margin: 20px auto 40px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
	.duo-studio-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
	.duo-studio-header h1 { font-size: 1.8rem; margin: 0; }
	.duo-studio-header p { color: #666; margin: 0; }

	/* Tabs */
	.duo-studio-tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e5e5; margin-bottom: 24px; }
	.duo-studio-tab { padding: 12px 20px; cursor: pointer; font-weight: 700; font-size: 0.95rem; color: #777; border: none; background: none; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: color 0.15s, border-color 0.15s; }
	.duo-studio-tab:hover { color: #3c3c3c; }
	.duo-studio-tab.active { color: #1cb0f6; border-bottom-color: #1cb0f6; }
	.duo-studio-panel { display: none; }
	.duo-studio-panel.active { display: block; }

	/* Cards */
	.duo-studio-card { background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
	.duo-studio-card h3 { margin: 0 0 12px; font-size: 1.1rem; }
	.duo-studio-card p.desc { color: #666; font-size: 0.9rem; margin: 0 0 16px; }

	/* Form elements */
	.duo-studio label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 0.9rem; }
	.duo-studio input[type="text"],
	.duo-studio input[type="number"],
	.duo-studio textarea,
	.duo-studio select { width: 100%; padding: 10px 12px; border: 2px solid #e5e5e5; border-radius: 8px; font-size: 0.95rem; font-family: inherit; box-sizing: border-box; transition: border-color 0.15s; }
	.duo-studio input:focus, .duo-studio textarea:focus, .duo-studio select:focus { border-color: #1cb0f6; outline: none; }
	.duo-studio .field { margin-bottom: 16px; }
	.duo-studio .field-row { display: flex; gap: 16px; }
	.duo-studio .field-row .field { flex: 1; }

	/* Buttons */
	.duo-studio-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: transform 0.1s, box-shadow 0.1s, opacity 0.15s; font-family: inherit; }
	.duo-studio-btn:active { transform: translateY(2px); }
	.duo-studio-btn:disabled { opacity: 0.5; cursor: wait; }
	.duo-studio-btn--primary { background: #1cb0f6; color: #fff; box-shadow: 0 4px 0 #1899d6; }
	.duo-studio-btn--primary:active { box-shadow: 0 0 0 transparent; }
	.duo-studio-btn--success { background: #58cc02; color: #fff; box-shadow: 0 4px 0 #46a302; }
	.duo-studio-btn--success:active { box-shadow: 0 0 0 transparent; }
	.duo-studio-btn--secondary { background: #f7f7f7; color: #3c3c3c; box-shadow: 0 4px 0 rgba(0,0,0,0.06); }
	.duo-studio-btn--secondary:active { box-shadow: 0 0 0 transparent; }
	.duo-studio-btn--ghost { background: transparent; color: #777; box-shadow: none; }
	.duo-studio-btn--ghost:hover { background: #f7f7f7; }
	.duo-studio-btn--sm { padding: 6px 14px; font-size: 0.8rem; }

	/* Progress */
	.duo-studio-progress { background: #e5e5e5; border-radius: 12px; height: 20px; overflow: hidden; margin: 12px 0; }
	.duo-studio-progress-fill { background: linear-gradient(90deg, #58cc02, #1cb0f6); height: 100%; width: 0%; transition: width 0.3s; border-radius: 12px; }
	.duo-studio-progress-text { display: flex; justify-content: space-between; font-size: 0.85em; color: #666; }

	/* Log */
	.duo-studio-log { max-height: 300px; overflow-y: auto; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 8px; padding: 12px; font-family: monospace; font-size: 0.85em; }
	.duo-studio-log .log-success { color: #58cc02; }
	.duo-studio-log .log-error { color: #ff4b4b; }
	.duo-studio-log .log-info { color: #1cb0f6; }
	.duo-studio-log .log-warn { color: #ff9600; }

	/* Dashboard grid */
	.duo-studio-dash-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
	.duo-studio-dash-card { background: #fff; border: 2px solid #e5e5e5; border-radius: 12px; padding: 16px; transition: border-color 0.15s, box-shadow 0.15s; }
	.duo-studio-dash-card:hover { border-color: #1cb0f6; box-shadow: 0 4px 12px rgba(28,176,246,0.1); }
	.duo-studio-dash-card h4 { margin: 0 0 8px; }
	.duo-studio-dash-card .course-meta { font-size: 0.85em; color: #666; margin-bottom: 12px; }

	/* Content map table */
	.duo-studio-map { width: 100%; border-collapse: collapse; margin-top: 12px; }
	.duo-studio-map th, .duo-studio-map td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e5e5e5; font-size: 0.9rem; }
	.duo-studio-map th { background: #f7f7f7; font-weight: 700; position: sticky; top: 0; }
	.duo-studio-map tr:hover { background: #f0f8ff; }
	.duo-studio-map .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
	.duo-studio-map .badge--ok { background: #eef9e8; color: #46a302; }
	.duo-studio-map .badge--missing { background: #fff0f0; color: #c80000; }
	.duo-studio-map .badge--partial { background: #fff3cd; color: #856404; }

	/* Inline editor */
	.duo-studio-editor { background: #fff; border: 2px solid #1cb0f6; border-radius: 12px; padding: 20px; margin-top: 16px; display: none; }
	.duo-studio-editor.active { display: block; }
	.duo-studio-editor textarea { font-family: monospace; font-size: 0.85rem; min-height: 200px; }
	.duo-studio-editor-actions { display: flex; gap: 8px; margin-top: 12px; }

	/* Preview */
	.duo-studio-preview { background: #f9f9f9; border: 1px dashed #e5e5e5; border-radius: 8px; padding: 16px; margin-top: 12px; }
	.duo-studio-preview h5 { margin: 0 0 8px; color: #666; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }

	/* Stats bar */
	.duo-studio-stats { display: flex; gap: 24px; padding: 12px 20px; background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; margin-bottom: 20px; }
	.duo-studio-stat { text-align: center; }
	.duo-studio-stat-value { font-size: 1.8rem; font-weight: 800; color: #3c3c3c; }
	.duo-studio-stat-label { font-size: 0.8rem; color: #777; text-transform: uppercase; letter-spacing: 0.05em; }

	/* Course generator steps */
	.duo-studio-steps { display: flex; gap: 8px; margin-bottom: 20px; }
	.duo-studio-step { flex: 1; padding: 10px; text-align: center; border-radius: 8px; background: #f7f7f7; font-weight: 700; font-size: 0.85rem; color: #777; }
	.duo-studio-step.active { background: #1cb0f6; color: #fff; }
	.duo-studio-step.done { background: #58cc02; color: #fff; }

	/* Responsive */
	@media (max-width: 782px) {
		.duo-studio-dash-grid { grid-template-columns: 1fr; }
		.duo-studio-stats { flex-wrap: wrap; gap: 12px; }
		.duo-studio .field-row { flex-direction: column; gap: 0; }
	}
	</style>

	<div class="wrap duo-studio">
		<div class="duo-studio-header">
			<div>
				<h1>📱 AI Creator Studio</h1>
				<p><?php esc_html_e( 'Genera, edita y gestiona contenido educativo con inteligencia artificial', 'amsawal' ); ?></p>
			</div>
		</div>

		<!-- Stats bar -->
		<div class="duo-studio-stats" id="duo-studio-stats">
			<div class="duo-studio-stat">
				<div class="duo-studio-stat-value" id="stat-courses"><?php echo count( $courses_json ); ?></div>
				<div class="duo-studio-stat-label"><?php esc_html_e( 'Cursos', 'amsawal' ); ?></div>
			</div>
			<div class="duo-studio-stat">
				<div class="duo-studio-stat-value" id="stat-lessons"><?php echo esc_html( $total_lessons ?: '—' ); ?></div>
				<div class="duo-studio-stat-label"><?php esc_html_e( 'Lecciones', 'amsawal' ); ?></div>
			</div>
			<div class="duo-studio-stat">
				<div class="duo-studio-stat-value" id="stat-activities"><?php echo esc_html( $total_activities ?: '—' ); ?></div>
				<div class="duo-studio-stat-label"><?php esc_html_e( 'Actividades', 'amsawal' ); ?></div>
			</div>
			<div class="duo-studio-stat">
				<div class="duo-studio-stat-value" id="stat-coverage"><?php echo esc_html( $coverage > 0 ? $coverage . '%' : '—' ); ?></div>
				<div class="duo-studio-stat-label"><?php esc_html_e( 'Cobertura', 'amsawal' ); ?></div>
			</div>
		</div>

		<!-- Tabs -->
		<nav class="duo-studio-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Secciones del AI Studio', 'amsawal' ); ?>">
			<button class="duo-studio-tab active" role="tab" aria-selected="true" aria-controls="panel-create" id="tab-create">🚀 <?php esc_html_e( 'Crear Curso', 'amsawal' ); ?></button>
			<button class="duo-studio-tab" role="tab" aria-selected="false" aria-controls="panel-dashboard" id="tab-dashboard">📊 <?php esc_html_e( 'Dashboard', 'amsawal' ); ?></button>
			<button class="duo-studio-tab" role="tab" aria-selected="false" aria-controls="panel-editor" id="tab-editor">✏️️ <?php esc_html_e( 'Editor', 'amsawal' ); ?></button>
			<button class="duo-studio-tab" role="tab" aria-selected="false" aria-controls="panel-batch" id="tab-batch">⚡ <?php esc_html_e( 'Batch', 'amsawal' ); ?></button>
		</nav>

		<!-- ═══════════════════════════════════════════
		     PANEL 1: CREATE COURSE FROM PROMPT
		     ═══════════════════════════════════════════ -->
		<div class="duo-studio-panel active" id="panel-create" role="tabpanel" aria-labelledby="tab-create">
			<div class="duo-studio-card">
				<h3>🚀 <?php esc_html_e( 'Generar curso completo con IA', 'amsawal' ); ?></h3>
				<p class="desc"><?php esc_html_e( 'Describe el tema del curso y la IA generará la estructura completa: lecciones, vocabulario y actividades. Podrás revisar y editar antes de crear las páginas.', 'amsawal' ); ?></p>

				<div class="duo-studio-steps" id="create-steps">
					<div class="duo-studio-step active" data-step="1">1. <?php esc_html_e( 'Configurar', 'amsawal' ); ?></div>
					<div class="duo-studio-step" data-step="2">2. <?php esc_html_e( 'Revisar', 'amsawal' ); ?></div>
					<div class="duo-studio-step" data-step="3">3. <?php esc_html_e( 'Crear', 'amsawal' ); ?></div>
				</div>

				<!-- Step 1: Configuration -->
				<div id="create-step-1">
					<div class="field-row">
						<div class="field">
							<label for="studio-topic"><?php esc_html_e( 'Tema del curso', 'amsawal' ); ?></label>
							<input type="text" id="studio-topic" placeholder="<?php esc_attr_e( 'Ej: Saludos y presentaciones en Tamazight', 'amsawal' ); ?>" />
						</div>
						<div class="field">
							<label for="studio-course-name"><?php esc_html_e( 'Nombre del curso', 'amsawal' ); ?></label>
							<input type="text" id="studio-course-name" placeholder="<?php esc_attr_e( 'Ej: Tamazight Básico 1', 'amsawal' ); ?>" />
						</div>
					</div>
					<div class="field-row">
						<div class="field">
							<label for="studio-lessons"><?php esc_html_e( 'Número de lecciones', 'amsawal' ); ?></label>
							<input type="number" id="studio-lessons" value="5" min="1" max="20" />
						</div>
						<div class="field">
							<label for="studio-level"><?php esc_html_e( 'Nivel', 'amsawal' ); ?></label>
							<select id="studio-level">
								<option value="1"><?php esc_html_e( 'Principiante (A1)', 'amsawal' ); ?></option>
								<option value="2"><?php esc_html_e( 'Elemental (A2)', 'amsawal' ); ?></option>
								<option value="3"><?php esc_html_e( 'Intermedio (B1)', 'amsawal' ); ?></option>
								<option value="4"><?php esc_html_e( 'Intermedio alto (B2)', 'amsawal' ); ?></option>
								<option value="5"><?php esc_html_e( 'Avanzado (C1)', 'amsawal' ); ?></option>
							</select>
						</div>
					</div>
					<div class="field">
						<label><?php esc_html_e( 'Tipos de actividad por lección', 'amsawal' ); ?></label>
						<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-top:4px;">
							<?php foreach ( $h5p_types as $tkey => $tlabel ) : ?>
								<label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:400;">
									<input type="checkbox" class="studio-type-cb" value="<?php echo esc_attr( $tkey ); ?>" <?php echo in_array( $tkey, array( 'flashcards', 'multiple-choice', 'fill-blanks' ) ) ? 'checked' : ''; ?> />
									<?php echo esc_html( $tlabel ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="field">
						<label for="studio-extra"><?php esc_html_e( 'Instrucciones adicionales (opcional)', 'amsawal' ); ?></label>
						<textarea id="studio-extra" rows="3" placeholder="<?php esc_attr_e( 'Ej: Enfócate en vocabulario cotidiano, incluye expresiones de cortesía...', 'amsawal' ); ?>"></textarea>
					</div>
					<div style="margin-top:16px;">
						<button type="button" class="duo-studio-btn duo-studio-btn--primary" id="studio-generate-structure">
							📱 <?php esc_html_e( 'Generar estructura con IA', 'amsawal' ); ?>
						</button>
						<span id="studio-generate-status" style="margin-left:12px; font-size:0.9rem;"></span>
					</div>
				</div>

				<!-- Step 2: Review (populated via JS) -->
				<div id="create-step-2" style="display:none;">
					<div id="studio-review-area"></div>
					<div style="margin-top:16px; display:flex; gap:8px;">
						<button type="button" class="duo-studio-btn duo-studio-btn--secondary" id="studio-back-to-config">← <?php esc_html_e( 'Volver', 'amsawal' ); ?></button>
						<button type="button" class="duo-studio-btn duo-studio-btn--success" id="studio-create-pages">
							✅ <?php esc_html_e( 'Crear páginas en WordPress', 'amsawal' ); ?>
						</button>
					</div>
				</div>

				<!-- Step 3: Creation progress -->
				<div id="create-step-3" style="display:none;">
					<h3>⏳ <?php esc_html_e( 'Creando curso...', 'amsawal' ); ?></h3>
					<div class="duo-studio-progress"><div class="duo-studio-progress-fill" id="create-progress-fill"></div></div>
					<div class="duo-studio-progress-text">
						<span id="create-progress-text">0 / 0</span>
						<span id="create-progress-item">—</span>
					</div>
					<div class="duo-studio-log" id="create-log"></div>
					<div id="create-done" style="display:none; margin-top:16px;">
						<button type="button" class="duo-studio-btn duo-studio-btn--primary" onclick="location.reload();">🔄 <?php esc_html_e( 'Recargar', 'amsawal' ); ?></button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-amsawal-studio' ) ); ?>" class="duo-studio-btn duo-studio-btn--secondary"><?php esc_html_e( 'Ver Dashboard', 'amsawal' ); ?></a>
					</div>
				</div>
			</div>
		</div>

		<!-- ═══════════════════════════════════════════
		     PANEL 2: CONTENT DASHBOARD
		     ═══════════════════════════════════════════ -->
		<div class="duo-studio-panel" id="panel-dashboard" role="tabpanel" aria-labelledby="tab-dashboard">
			<div class="duo-studio-card">
				<h3>📊 <?php esc_html_e( 'Mapa de contenido', 'amsawal' ); ?></h3>
				<p class="desc"><?php esc_html_e( 'Vista global del contenido generado por curso y lección. Haz clic en un curso para ver el detalle.', 'amsawal' ); ?></p>

				<?php if ( empty( $courses_json ) ) : ?>
					<p style="color:#856404; background:#fff3cd; padding:12px; border-radius:8px;">
						⚠️️ <?php esc_html_e( 'No hay cursos creados aún. Ve a la pestaña "Crear Curso" para generar uno con IA.', 'amsawal' ); ?>
					</p>
				<?php else : ?>
					<div class="duo-studio-dash-grid" id="duo-dash-grid">
						<?php foreach ( $courses_json as $c ) :
							$lessons = wp_amsawal_ai_get_course_lessons( $c['id'] );
							$lesson_count = count( $lessons );
							$activity_count = 0;
							$types_covered = array();
							foreach ( $lessons as $lesson ) {
								foreach ( array_keys( $h5p_types ) as $ht ) {
									$content = wp_amsawal_ai_get_content( $lesson['id'], $ht, 0 );
									if ( $content ) {
										$activity_count++;
										$types_covered[ $ht ] = true;
									}
								}
							}
							$total_possible = $lesson_count * count( $h5p_types );
							$coverage = $total_possible > 0 ? round( $activity_count * 100 / $total_possible ) : 0;
						?>
						<div class="duo-studio-dash-card" data-course-id="<?php echo esc_attr( $c['id'] ); ?>">
							<h4><?php echo esc_html( $c['title'] ); ?></h4>
							<div class="course-meta">
								📖 <?php echo esc_html( $c['name'] ); ?> ·
								<?php echo esc_html( sprintf( '%d %s', $lesson_count, _n( 'lección', 'lecciones', $lesson_count, 'amsawal' ) ) ); ?> ·
								<?php echo esc_html( sprintf( '%d %s', $activity_count, _n( 'actividad', 'actividades', $activity_count, 'amsawal' ) ) ); ?>
							</div>
							<div class="duo-studio-progress" style="margin:8px 0;"><div class="duo-studio-progress-fill" style="width:<?php echo esc_attr( $coverage ); ?>%"></div></div>
							<div style="display:flex; justify-content:space-between; align-items:center;">
								<span style="font-size:0.85em; color:#666;"><?php echo esc_html( $coverage ); ?>% <?php esc_html_e( 'cobertura', 'amsawal' ); ?></span>
								<button type="button" class="duo-studio-btn duo-studio-btn--ghost duo-studio-btn--sm studio-view-map" data-course-id="<?php echo esc_attr( $c['id'] ); ?>" data-course-name="<?php echo esc_attr( $c['name'] ); ?>">
									<?php esc_html_e( 'Ver detalle →', 'amsawal' ); ?>
								</button>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<!-- Detail view (loaded via AJAX) -->
				<div id="duo-dash-detail" style="display:none;">
					<button type="button" class="duo-studio-btn duo-studio-btn--ghost duo-studio-btn--sm" id="duo-dash-back">← <?php esc_html_e( 'Volver al resumen', 'amsawal' ); ?></button>
					<h3 id="duo-dash-detail-title" style="margin-top:12px;"></h3>
					<div id="duo-dash-detail-content"></div>
				</div>
			</div>
		</div>

		<!-- ═══════════════════════════════════════════
		     PANEL 3: INLINE EDITOR
		     ═══════════════════════════════════════════ -->
		<div class="duo-studio-panel" id="panel-editor" role="tabpanel" aria-labelledby="tab-editor">
			<div class="duo-studio-card">
				<h3>✏️️ <?php esc_html_e( 'Editor de contenido', 'amsawal' ); ?></h3>
				<p class="desc"><?php esc_html_e( 'Selecciona una lección y tipo de actividad para ver, editar o regenerar el contenido.', 'amsawal' ); ?></p>

				<div class="field-row">
					<div class="field">
						<label for="editor-course"><?php esc_html_e( 'Curso', 'amsawal' ); ?></label>
						<select id="editor-course">
							<option value=""><?php esc_html_e( '— Selecciona —', 'amsawal' ); ?></option>
							<?php foreach ( $courses_json as $c ) : ?>
								<option value="<?php echo esc_attr( $c['id'] ); ?>"><?php echo esc_html( $c['name'] . ' — ' . $c['title'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="field">
						<label for="editor-lesson"><?php esc_html_e( 'Lección', 'amsawal' ); ?></label>
						<select id="editor-lesson" disabled>
							<option value=""><?php esc_html_e( '— Selecciona un curso primero —', 'amsawal' ); ?></option>
						</select>
					</div>
				</div>
				<div class="field-row">
					<div class="field">
						<label for="editor-type"><?php esc_html_e( 'Tipo de actividad', 'amsawal' ); ?></label>
						<select id="editor-type">
							<?php foreach ( $h5p_types as $tkey => $tlabel ) : ?>
								<option value="<?php echo esc_attr( $tkey ); ?>"><?php echo esc_html( $tlabel ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="field" style="display:flex; align-items:flex-end;">
						<button type="button" class="duo-studio-btn duo-studio-btn--primary" id="editor-load"><?php esc_html_e( 'Cargar contenido', 'amsawal' ); ?></button>
					</div>
				</div>

				<!-- Editor area -->
				<div class="duo-studio-editor" id="editor-area">
					<h4 id="editor-title" style="margin:0 0 12px;"></h4>
					<label for="editor-json"><?php esc_html_e( 'Contenido (JSON)', 'amsawal' ); ?></label>
					<textarea id="editor-json" rows="12"></textarea>
					<div class="duo-studio-editor-actions">
						<button type="button" class="duo-studio-btn duo-studio-btn--success" id="editor-save"><span aria-hidden="true">💾</span> <?php esc_html_e( 'Guardar cambios', 'amsawal' ); ?></button>
						<button type="button" class="duo-studio-btn duo-studio-btn--primary" id="editor-regenerate">📱 <?php esc_html_e( 'Regenerar con IA', 'amsawal' ); ?></button>
						<button type="button" class="duo-studio-btn duo-studio-btn--ghost" id="editor-preview-btn">👁️ <?php esc_html_e( 'Vista previa', 'amsawal' ); ?></button>
					</div>
					<div class="duo-studio-preview" id="editor-preview" style="display:none;">
						<h5><?php esc_html_e( 'Vista previa del contenido', 'amsawal' ); ?></h5>
						<div id="editor-preview-content"></div>
					</div>
					<div id="editor-status" style="margin-top:8px; font-size:0.9rem;"></div>
				</div>
			</div>
		</div>

		<!-- ═══════════════════════════════════════════
		     PANEL 4: BATCH GENERATION (improved)
		     ═══════════════════════════════════════════ -->
		<div class="duo-studio-panel" id="panel-batch" role="tabpanel" aria-labelledby="tab-batch">
			<div class="duo-studio-card">
				<h3>⚡ <?php esc_html_e( 'Generación batch por curso', 'amsawal' ); ?></h3>
				<p class="desc"><?php esc_html_e( 'Genera contenido para todas las lecciones de un curso existente. Selecciona curso, lecciones y tipos de actividad.', 'amsawal' ); ?></p>

				<div class="field">
					<label for="batch-course"><?php esc_html_e( 'Curso', 'amsawal' ); ?></label>
					<select id="batch-course">
						<option value=""><?php esc_html_e( '— Selecciona un curso —', 'amsawal' ); ?></option>
						<?php foreach ( $courses_json as $c ) : ?>
							<option value="<?php echo esc_attr( $c['id'] ); ?>"><?php echo esc_html( $c['name'] . ' — ' . $c['title'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div id="batch-lessons-card" style="display:none;">
					<div class="field">
						<label><?php esc_html_e( 'Lecciones', 'amsawal' ); ?></label>
						<div style="display:flex; gap:8px; margin-bottom:8px;">
							<button type="button" class="duo-studio-btn duo-studio-btn--ghost duo-studio-btn--sm" id="batch-select-all"><?php esc_html_e( 'Todas', 'amsawal' ); ?></button>
							<button type="button" class="duo-studio-btn duo-studio-btn--ghost duo-studio-btn--sm" id="batch-deselect-all"><?php esc_html_e( 'Ninguna', 'amsawal' ); ?></button>
						</div>
						<div id="batch-lessons-list"></div>
					</div>

					<div class="field">
						<label><?php esc_html_e( 'Tipos de actividad', 'amsawal' ); ?></label>
						<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px;">
							<?php foreach ( $h5p_types as $tkey => $tlabel ) : ?>
								<label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:400;">
									<input type="checkbox" class="batch-type-cb" value="<?php echo esc_attr( $tkey ); ?>" <?php echo in_array( $tkey, array( 'flashcards', 'multiple-choice', 'fill-blanks' ) ) ? 'checked' : ''; ?> />
									<?php echo esc_html( $tlabel ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div style="margin-top:16px;">
						<p style="color:#856404; background:#fff3cd; padding:12px; border-radius:8px; margin-bottom:12px;">
							⚠️️ <?php esc_html_e( 'Cada actividad tarda ~90-150s en CPU. No cierres la pestaña.', 'amsawal' ); ?>
							<?php esc_html_e( 'Total estimado:', 'amsawal' ); ?> <span id="batch-estimated">—</span>
						</p>
						<button type="button" class="duo-studio-btn duo-studio-btn--success" id="batch-generate">
							🚀 <?php esc_html_e( 'Generar todo', 'amsawal' ); ?>
						</button>
						<button type="button" class="duo-studio-btn duo-studio-btn--secondary" id="batch-pause" style="display:none;">
							⏸ <?php esc_html_e( 'Pausar', 'amsawal' ); ?>
						</button>
					</div>

					<div id="batch-progress-card" style="display:none; margin-top:16px;">
						<div class="duo-studio-progress"><div class="duo-studio-progress-fill" id="batch-progress-fill"></div></div>
						<div class="duo-studio-progress-text">
							<span id="batch-progress-text">0 / 0</span>
							<span id="batch-progress-item">—</span>
						</div>
						<div style="display:flex; gap:8px; margin:8px 0;">
							<span style="font-size:0.85em; color:#58cc02;" id="batch-count-ok">✅ 0</span>
							<span style="font-size:0.85em; color:#ff4b4b;" id="batch-count-fail">❌ 0</span>
							<span style="font-size:0.85em; color:#777;" id="batch-count-skip">⏭ 0</span>
						</div>
						<div class="duo-studio-log" id="batch-log"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
	(function($) {
		'use strict';

		var ajaxUrl  = <?php echo wp_json_encode( $ajax_url ); ?>;
		var nonces   = <?php echo wp_json_encode( array(
			'generate' => $gen_nonce,
			'map'      => $map_nonce,
			'save'     => $save_nonce,
			'regen'    => $regen_nonce,
		) ); ?>;
		var courses  = <?php echo wp_json_encode( $courses_json ); ?>;
		var h5pTypes = <?php echo wp_json_encode( array_keys( $h5p_types ) ); ?>;
		var h5pLabels = <?php echo wp_json_encode( $h5p_types ); ?>;

		/* ── Tab switching ── */
		$('.duo-studio-tab').on('click', function() {
			var panel = $(this).attr('aria-controls');
			$('.duo-studio-tab').removeClass('active').attr('aria-selected', 'false');
			$(this).addClass('active').attr('aria-selected', 'true');
			$('.duo-studio-panel').removeClass('active');
			$('#' + panel).addClass('active');
			if (panel === 'panel-dashboard') loadDashboardStats();
		});

		/* ────────────────────────────────────────
		   PANEL 1: CREATE COURSE FROM PROMPT
		   ──────────────────────────────────────── */
		var generatedStructure = null;

		$('#studio-generate-structure').on('click', function() {
			var topic      = $('#studio-topic').val().trim();
			var courseName = $('#studio-course-name').val().trim();
			var numLessons = parseInt($('#studio-lessons').val()) || 5;
			var level      = $('#studio-level').val();
			var extra      = $('#studio-extra').val().trim();
			var types      = [];
			$('.studio-type-cb:checked').each(function() { types.push($(this).val()); });

			if (!topic) { alert('<?php echo esc_js( __( 'Introduce un tema para el curso.', 'amsawal' ) ); ?>'); return; }
			if (!courseName) courseName = topic;
			if (types.length === 0) { alert('<?php echo esc_js( __( 'Selecciona al menos un tipo de actividad.', 'amsawal' ) ); ?>'); return; }

			var btn = $(this);
			btn.prop('disabled', true).text('⏳ Generando...');
			$('#studio-generate-status').text('<?php echo esc_js( __( 'La IA está generando la estructura del curso...', 'amsawal' ) ); ?>').css('color', '#1cb0f6');

			$.ajax({
				url: ajaxUrl,
				method: 'POST',
				data: {
					action: 'wp_amsawal_studio_generate',
					_ajax_nonce: nonces.generate,
					topic: topic,
					course_name: courseName,
					num_lessons: numLessons,
					level: level,
					extra: extra,
					types: JSON.stringify(types)
				},
				timeout: 600000, // 10 min
				success: function(resp) {
					btn.prop('disabled', false).text('📱 <?php echo esc_js( __( 'Generar estructura con IA', 'amsawal' ) ); ?>');
					if (resp.success && resp.data && resp.data.structure) {
						generatedStructure = resp.data.structure;
						showReviewStep(generatedStructure);
						$('#studio-generate-status').text('✅ ' + (resp.data.message || '<?php echo esc_js( __( 'Estructura generada', 'amsawal' ) ); ?>')).css('color', '#58cc02');
					} else {
					var msg = (resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Error desconocido', 'amsawal' ) ); ?>';
					$('#studio-generate-status').text('❌ ' + msg).css('color', '#ff4b4b');
					}
				},
				error: function(xhr, status, err) {
					btn.prop('disabled', false).text('📱 <?php echo esc_js( __( 'Generar estructura con IA', 'amsawal' ) ); ?>');
					$('#studio-generate-status').text('❌ Error: ' + (err || status)).css('color', '#ff4b4b');
				}
			});
		});

		function showReviewStep(structure) {
			$('#create-step-1').hide();
			$('#create-step-2').show();
			$('#create-steps .duo-studio-step').eq(0).removeClass('active').addClass('done');
			$('#create-steps .duo-studio-step').eq(1).addClass('active');

			var html = '<div style="margin-bottom:16px;">';
			html += '<h4>' + escapeHtml(structure.course_name || '') + '</h4>';
			html += '<p style="color:#666;">' + (structure.lessons ? structure.lessons.length : 0) + ' <?php echo esc_js( __( 'lecciones', 'amsawal' ) ); ?> · ' + (structure.types ? structure.types.length : 0) + ' <?php echo esc_js( __( 'tipos de actividad', 'amsawal' ) ); ?></p>';
			html += '</div>';

			if (structure.lessons) {
				$.each(structure.lessons, function(i, lesson) {
					html += '<div class="duo-studio-dash-card" style="margin-bottom:8px;">';
					html += '<h4 style="margin:0 0 4px;">📖 ' + escapeHtml(lesson.title || ('<?php echo esc_js( __( 'Lección', 'amsawal' ) ); ?> ' + (i+1))) + '</h4>';
					if (lesson.description) html += '<p style="color:#666; font-size:0.9rem; margin:0 0 8px;">' + escapeHtml(lesson.description) + '</p>';
					if (lesson.vocabulary && lesson.vocabulary.length) {
						html += '<div style="margin-bottom:8px;"><strong>✏️ <?php echo esc_js( __( 'Vocabulario:', 'amsawal' ) ); ?></strong> ';
						html += '<span style="color:#666;">' + escapeHtml(lesson.vocabulary.join(', ')) + '</span></div>';
					}
					html += '<div class="field"><label><?php echo esc_js( __( 'Editar vocabulario (una palabra por línea)', 'amsawal' ) ); ?></label>';
					html += '<textarea class="studio-vocab-edit" data-lesson="' + i + '" rows="3" style="width:100%; font-family:monospace; font-size:0.85rem;">';
					if (lesson.vocabulary) html += escapeHtml(lesson.vocabulary.join('\n'));
					html += '</textarea></div>';
					html += '</div>';
				});
			}

			$('#studio-review-area').html(html);
		}

		$('#studio-back-to-config').on('click', function() {
			$('#create-step-2').hide();
			$('#create-step-1').show();
			$('#create-steps .duo-studio-step').eq(0).removeClass('done').addClass('active');
			$('#create-steps .duo-studio-step').eq(1).removeClass('active');
		});

		$('#studio-create-pages').on('click', function() {
			if (!generatedStructure) return;

			// Collect edited vocabulary
			$('.studio-vocab-edit').each(function() {
				var idx = $(this).data('lesson');
				var lines = $(this).val().split('\n').filter(function(l) { return l.trim() !== ''; });
				if (generatedStructure.lessons && generatedStructure.lessons[idx]) {
					generatedStructure.lessons[idx].vocabulary = lines;
				}
			});

			$('#create-step-2').hide();
			$('#create-step-3').show();
			$('#create-steps .duo-studio-step').eq(1).removeClass('active').addClass('done');
			$('#create-steps .duo-studio-step').eq(2).addClass('active');

			createCourseFromStructure(generatedStructure);
		});

		function createCourseFromStructure(structure) {
			var queue = [];
			var types = structure.types || ['flashcards'];

			if (structure.lessons) {
				$.each(structure.lessons, function(i, lesson) {
					$.each(types, function(j, type) {
						queue.push({ lesson: lesson, type: type, index: i });
					});
				});
			}

			var total = queue.length;
			var ok = 0, fail = 0;
			$('#create-log').empty();

			function processNext(idx) {
				if (idx >= total) {
					$('#create-progress-fill').css('width', '100%');
					$('#create-progress-text').text(total + ' / ' + total);
					$('#create-progress-item').text('✅ <?php echo esc_js( __( '¡Completado!', 'amsawal' ) ); ?>');
					createLog('🏆 <strong><?php echo esc_js( __( 'Curso creado exitosamente', 'amsawal' ) ); ?></strong> — ' + ok + ' ✅ ' + fail + ' ❌', ok > 0 ? 'success' : 'error');
					$('#create-done').show();
					return;
				}

				var item = queue[idx];
				$('#create-progress-text').text(idx + ' / ' + total);
				$('#create-progress-fill').css('width', (idx / total * 100) + '%');
				$('#create-progress-item').text(item.type + ' → Lección ' + (item.index + 1));

				$.ajax({
					url: ajaxUrl,
					method: 'POST',
					data: {
						action: 'wp_amsawal_studio_generate',
						_ajax_nonce: nonces.generate,
						mode: 'create_and_generate',
						course_name: structure.course_name,
						lesson_title: item.lesson.title,
						lesson_num: (item.index + 1),
						lesson_description: item.lesson.description || '',
						vocabulary: JSON.stringify(item.lesson.vocabulary || []),
						type: item.type,
						level: structure.level || 1
					},
					timeout: 360000,
					success: function(resp) {
						if (resp.success) {
							ok++;
							createLog('✅ ' + item.type + ' → ' + item.lesson.title, 'success');
						} else {
							fail++;
							var msg = resp.data && resp.data.message ? resp.data.message : '<?php echo esc_js( __( 'Error', 'amsawal' ) ); ?>';
							createLog('❌ ' + item.type + ' → ' + item.lesson.title + ': ' + msg, 'error');
						}
					},
					error: function(xhr, status) {
						fail++;
						createLog('❌ ' + item.type + ' → ' + item.lesson.title + ': ' + status, 'error');
					},
					complete: function() {
						setTimeout(function() { processNext(idx + 1); }, 300);
					}
				});
			}

			processNext(0);
		}

		function createLog(msg, cls) {
			var color = cls === 'success' ? '#58cc02' : (cls === 'error' ? '#ff4b4b' : '#1cb0f6');
			$('#create-log').append('<div style="color:' + color + '; padding:2px 0; border-bottom:1px solid #f0f0f0;">' + msg + '</div>');
			$('#create-log').scrollTop($('#create-log')[0].scrollHeight);
		}

		/* ────────────────────────────────────────
		   PANEL 2: CONTENT DASHBOARD
		   ──────────────────────────────────────── */
		function loadDashboardStats() {
			var totalLessons = 0, totalActivities = 0;
			$('.duo-studio-dash-card').each(function() {
				var meta = $(this).find('.course-meta').text();
				var lessonMatch = meta.match(/(\d+)\s*lecci/);
				var actMatch = meta.match(/(\d+)\s*actividad/);
				if (lessonMatch) totalLessons += parseInt(lessonMatch[1]);
				if (actMatch) totalActivities += parseInt(actMatch[1]);
			});
			$('#stat-lessons').text(totalLessons || '—');
			$('#stat-activities').text(totalActivities || '—');

			var totalPossible = totalLessons * h5pTypes.length;
			var coverage = totalPossible > 0 ? Math.round(totalActivities * 100 / totalPossible) : 0;
			$('#stat-coverage').text(coverage + '%');
		}

		$('.studio-view-map').on('click', function() {
			var courseId = $(this).data('course-id');
			var courseName = $(this).data('course-name');
			$('#duo-dash-grid').hide();
			$('#duo-dash-detail').show();
			$('#duo-dash-detail-title').text('📖 ' + courseName);

			$.post(ajaxUrl, {
				action: 'wp_amsawal_studio_map',
				_ajax_nonce: nonces.map,
				course_id: courseId
			}, function(resp) {
				if (resp.success) {
					renderContentMap(resp.data);
				} else {
					$('#duo-dash-detail-content').html('<p style="color:#c00;">❌ ' + (resp.data && resp.data.message || '<?php echo esc_js( __( 'Error', 'amsawal' ) ); ?>') + '</p>');
				}
			}).fail(function() {
				$('#duo-dash-detail-content').html('<p style="color:#c00;">❌ <?php echo esc_js( __( 'Error de red', 'amsawal' ) ); ?></p>');
			});
		});

		$('#duo-dash-back').on('click', function() {
			$('#duo-dash-detail').hide();
			$('#duo-dash-grid').show();
		});

		function renderContentMap(data) {
			if (!data.lessons || data.lessons.length === 0) {
				$('#duo-dash-detail-content').html('<p><?php echo esc_js( __( 'No hay lecciones en este curso.', 'amsawal' ) ); ?></p>');
				return;
			}

			var html = '<div style="overflow-x:auto;"><table class="duo-studio-map">';
			html += '<thead><tr><th><?php echo esc_js( __( 'Lección', 'amsawal' ) ); ?></th>';
			$.each(h5pTypes, function(i, t) {
				html += '<th style="text-align:center; font-size:0.8rem;">' + (h5pLabels[t] || t).split(' ')[0] + '</th>';
			});
			html += '</tr></thead><tbody>';

			$.each(data.lessons, function(i, lesson) {
				html += '<tr>';
				html += '<td><strong>' + escapeHtml(lesson.title) + '</strong><br><small style="color:#666;">L' + lesson.lesson_num + '</small></td>';
				$.each(h5pTypes, function(j, t) {
					var has = lesson.content && lesson.content[t];
					var badge = has ? '<span class="badge badge--ok">✅</span>' : '<span class="badge badge--missing">❌</span>';
					html += '<td style="text-align:center;">' + badge + '</td>';
				});
				html += '</tr>';
			});

			html += '</tbody></table></div>';
			$('#duo-dash-detail-content').html(html);
		}

		/* ────────────────────────────────────────
		   PANEL 3: INLINE EDITOR
		   ──────────────────────────────────────── */
		var editorLessons = [];

		$('#editor-course').on('change', function() {
			var courseId = $(this).val();
			var $lesson = $('#editor-lesson');
			$lesson.empty().prop('disabled', true).append('<option value="">⏳ Cargando...</option>');

			if (!courseId) {
				$lesson.empty().append('<option value=""><?php echo esc_js( __( '— Selecciona un curso primero —', 'amsawal' ) ); ?></option>');
				return;
			}

			$.post(ajaxUrl, {
				action: 'wp_amsawal_ai_get_lessons',
				_ajax_nonce: nonces.regen,
				course_id: courseId
			}, function(resp) {
				$lesson.empty();
				if (resp.success && resp.data.lessons.length > 0) {
					editorLessons = resp.data.lessons;
					$.each(editorLessons, function(i, l) {
						$lesson.append('<option value="' + l.id + '">L' + l.lesson_num + ': ' + escapeHtml(l.title) + '</option>');
					});
					$lesson.prop('disabled', false);
				} else {
					$lesson.append('<option value=""><?php echo esc_js( __( 'Sin lecciones', 'amsawal' ) ); ?></option>');
				}
			});
		});

		$('#editor-load').on('click', function() {
			var lessonId = $('#editor-lesson').val();
			var type     = $('#editor-type').val();
			if (!lessonId) return;

			$('#editor-status').text('⏳ <?php echo esc_js( __( 'Cargando...', 'amsawal' ) ); ?>').css('color', '#1cb0f6');

			$.post(ajaxUrl, {
				action: 'wp_amsawal_studio_map',
				_ajax_nonce: nonces.map,
				course_id: $('#editor-course').val(),
				lesson_id: lessonId,
				type: type,
				mode: 'get_content'
			}, function(resp) {
				if (resp.success && resp.data.content) {
					$('#editor-json').val(JSON.stringify(resp.data.content, null, 2));
					$('#editor-title').text('L' + (resp.data.lesson_num || '?') + ': ' + (resp.data.lesson_title || '') + ' — ' + (h5pLabels[type] || type));
					$('#editor-area').addClass('active');
					$('#editor-preview').hide();
					$('#editor-status').text('✅ <?php echo esc_js( __( 'Contenido cargado', 'amsawal' ) ); ?>').css('color', '#58cc02');
				} else {
					$('#editor-json').val('');
					$('#editor-title').text(type);
					$('#editor-area').addClass('active');
					$('#editor-status').text('⚠️️ <?php echo esc_js( __( 'No hay contenido para este tipo. Puedes generarlo con IA.', 'amsawal' ) ); ?>').css('color', '#ff9600');
				}
			});
		});

		$('#editor-save').on('click', function() {
			var lessonId = $('#editor-lesson').val();
			var type     = $('#editor-type').val();
			var json     = $('#editor-json').val();

			var parsed;
			try { parsed = JSON.parse(json); } catch(e) {
				$('#editor-status').text('❌ JSON inválido: ' + e.message).css('color', '#ff4b4b');
				return;
			}

			$('#editor-status').text('⏳ <?php echo esc_js( __( 'Guardando...', 'amsawal' ) ); ?>').css('color', '#1cb0f6');

			$.post(ajaxUrl, {
				action: 'wp_amsawal_studio_save',
				_ajax_nonce: nonces.save,
				lesson_id: lessonId,
				type: type,
				content: JSON.stringify(parsed)
			}, function(resp) {
				if (resp.success) {
					$('#editor-status').text('✅ <?php echo esc_js( __( 'Contenido guardado', 'amsawal' ) ); ?>').css('color', '#58cc02');
				} else {
					$('#editor-status').text('❌ ' + (resp.data && resp.data.message || '<?php echo esc_js( __( 'Error', 'amsawal' ) ); ?>')).css('color', '#ff4b4b');
				}
			});
		});

		$('#editor-regenerate').on('click', function() {
			var lessonId = $('#editor-lesson').val();
			var type     = $('#editor-type').val();
			if (!lessonId) return;

			var btn = $(this);
			btn.prop('disabled', true).text('⏳ Regenerando...');
			$('#editor-status').text('⏳ <?php echo esc_js( __( 'La IA está generando contenido...', 'amsawal' ) ); ?>').css('color', '#1cb0f6');

			$.post(ajaxUrl, {
				action: 'wp_amsawal_ai_regenerate',
				_ajax_nonce: nonces.regen,
				lesson_id: lessonId,
				type: type,
				vocabulary: '[]'
			}, function(resp) {
				btn.prop('disabled', false).text('📱 <?php echo esc_js( __( 'Regenerar con IA', 'amsawal' ) ); ?>');
				if (resp.success) {
					$('#editor-status').text('✅ ' + (resp.data.message || '<?php echo esc_js( __( 'Regenerado', 'amsawal' ) ); ?>')).css('color', '#58cc02');
					// Reload content
					$('#editor-load').click();
				} else {
					$('#editor-status').text('❌ ' + (resp.data && resp.data.message || '<?php echo esc_js( __( 'Error', 'amsawal' ) ); ?>')).css('color', '#ff4b4b');
				}
			}).fail(function() {
				btn.prop('disabled', false).text('📱 <?php echo esc_js( __( 'Regenerar con IA', 'amsawal' ) ); ?>');
				$('#editor-status').text('❌ <?php echo esc_js( __( 'Error de red', 'amsawal' ) ); ?>').css('color', '#ff4b4b');
			});
		});

		$('#editor-preview-btn').on('click', function() {
			var json = $('#editor-json').val();
			var type = $('#editor-type').val();
			try {
				var data = JSON.parse(json);
				$('#editor-preview-content').html(renderPreview(type, data));
				$('#editor-preview').show();
			} catch(e) {
				$('#editor-preview-content').html('<p style="color:#c00;">JSON inválido</p>');
				$('#editor-preview').show();
			}
		});

		function renderPreview(type, data) {
			var html = '';
			switch(type) {
				case 'flashcards':
				case 'dialogcards':
					if (data.cards) {
						html += '<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:8px;">';
						$.each(data.cards, function(i, c) {
							html += '<div style="padding:12px; background:#fff; border:2px solid #e5e5e5; border-radius:12px; text-align:center;">';
							html += '<div style="font-weight:700; font-size:1.1rem;">' + escapeHtml(c.text || '') + '</div>';
							html += '<hr style="border:none; border-top:1px solid #e5e5e5; margin:8px 0;">';
							html += '<div style="color:#58cc02; font-weight:600;">' + escapeHtml(c.answer || '') + '</div>';
							html += '</div>';
						});
						html += '</div>';
					}
					break;
				case 'multiple-choice':
					if (data.question) html += '<p style="font-weight:600;">' + escapeHtml(data.question) + '</p>';
					if (data.options) {
						$.each(data.options, function(i, o) {
							var mark = (i === data.correct) ? ' ✅' : '';
							html += '<div style="padding:8px 12px; border:2px solid #e5e5e5; border-radius:8px; margin-bottom:4px;">' + escapeHtml(o) + mark + '</div>';
						});
					}
					break;
				case 'fill-blanks':
					if (data.text) html += '<p>' + escapeHtml(data.text).replace(/\*(.*?)\*/g, '<strong style="color:#1cb0f6;">[$1]</strong>') + '</p>';
					break;
				case 'true-false':
					if (data.question) html += '<p style="font-weight:600;">' + escapeHtml(data.question) + '</p>';
					html += '<p>' + (data.correct ? '✅ Verdadero' : '❌ Falso') + '</p>';
					break;
				case 'essay':
					if (data.prompt) html += '<p style="font-weight:600;">' + escapeHtml(data.prompt) + '</p>';
					if (data.rubric) html += '<p style="color:#666;">📋 ' + escapeHtml(data.rubric) + '</p>';
					break;
				case 'adaptest':
					if (data.questions) {
						$.each(data.questions.slice(0, 3), function(i, q) {
							html += '<div style="margin-bottom:8px; padding:8px; background:#f9f9f9; border-radius:8px;">';
							html += '<p style="font-weight:600; margin:0 0 4px;">' + escapeHtml(q.question || '') + '</p>';
							if (q.options) $.each(q.options, function(j, o) {
								html += '<div style="padding:4px 8px;">' + (j === q.correct ? '✅ ' : '') + escapeHtml(o) + '</div>';
							});
							html += '</div>';
						});
						if (data.questions.length > 3) html += '<p style="color:#666;">... +' + (data.questions.length - 3) + ' <?php echo esc_js( __( 'más', 'amsawal' ) ); ?></p>';
					}
					break;
				default:
					html += '<pre style="font-size:0.8rem; max-height:200px; overflow:auto;">' + escapeHtml(JSON.stringify(data, null, 2)) + '</pre>';
			}
			return html;
		}

		/* ────────────────────────────────────────
		   PANEL 4: BATCH GENERATION (improved)
		   ──────────────────────────────────────── */
		var batchLessons = [];
		var batchPaused = false;
		var batchQueue = [];

		$('#batch-course').on('change', function() {
			var courseId = $(this).val();
			$('#batch-lessons-card').toggle(!!courseId);
			$('#batch-progress-card').hide();

			if (!courseId) return;

			$.post(ajaxUrl, {
				action: 'wp_amsawal_ai_get_lessons',
				_ajax_nonce: nonces.regen,
				course_id: courseId
			}, function(resp) {
				if (resp.success && resp.data.lessons.length > 0) {
					batchLessons = resp.data.lessons;
					var html = '';
					$.each(batchLessons, function(i, l) {
						html += '<label style="display:flex; align-items:center; gap:8px; padding:8px; border:1px solid #e5e5e5; border-radius:8px; margin-bottom:4px; cursor:pointer;">';
						html += '<input type="checkbox" class="batch-lesson-cb" value="' + l.id + '" data-num="' + l.lesson_num + '" checked />';
						html += '<strong>L' + l.lesson_num + ':</strong> ' + escapeHtml(l.title);
						html += '</label>';
					});
					$('#batch-lessons-list').html(html);
					updateBatchEstimate();
				} else {
					$('#batch-lessons-list').html('<p style="color:#c00;">⚠️️ <?php echo esc_js( __( 'No hay lecciones', 'amsawal' ) ); ?></p>');
				}
			});
		});

		function updateBatchEstimate() {
			var nl = $('.batch-lesson-cb:checked').length;
			var nt = $('.batch-type-cb:checked').length;
			var total = nl * nt;
			var mins = Math.round(total * 2);
			$('#batch-estimated').text(total > 0 ? (mins < 1 ? '< 1 min (' + total + ')' : '~' + mins + ' min (' + total + ')') : '—');
		}

		$(document).on('change', '.batch-lesson-cb, .batch-type-cb', updateBatchEstimate);
		$('#batch-select-all').on('click', function() { $('.batch-lesson-cb').prop('checked', true); updateBatchEstimate(); });
		$('#batch-deselect-all').on('click', function() { $('.batch-lesson-cb').prop('checked', false); updateBatchEstimate(); });

		$('#batch-generate').on('click', function() {
			var checkedL = $('.batch-lesson-cb:checked');
			var checkedT = $('.batch-type-cb:checked');
			if (checkedL.length === 0) { alert('<?php echo esc_js( __( 'Selecciona al menos una lección.', 'amsawal' ) ); ?>'); return; }
			if (checkedT.length === 0) { alert('<?php echo esc_js( __( 'Selecciona al menos un tipo.', 'amsawal' ) ); ?>'); return; }

			batchQueue = [];
			checkedL.each(function() {
				var id = $(this).val(), num = $(this).data('num'), title = $(this).parent().text().trim();
				checkedT.each(function() {
					batchQueue.push({ lesson_id: id, lesson_num: num, title: title, type: $(this).val() });
				});
			});

			batchPaused = false;
			$(this).prop('disabled', true).text('⏳ <?php echo esc_js( __( 'Generando...', 'amsawal' ) ); ?>');
			$('#batch-pause').show();
			$('#batch-progress-card').show();
			$('#batch-log').empty();
			$('#batch-count-ok').text('✅ 0');
			$('#batch-count-fail').text('❌ 0');
			$('#batch-count-skip').text('⏭ 0');

			processBatch(0, 0, 0, 0);
		});

		$('#batch-pause').on('click', function() {
			batchPaused = !batchPaused;
			$(this).text(batchPaused ? '▶️ <?php echo esc_js( __( 'Reanudar', 'amsawal' ) ); ?>' : '⏸ <?php echo esc_js( __( 'Pausar', 'amsawal' ) ); ?>');
		});

		function processBatch(idx, ok, fail, skip) {
			if (batchPaused) {
				setTimeout(function() { processBatch(idx, ok, fail, skip); }, 1000);
				return;
			}
			if (idx >= batchQueue.length) {
				$('#batch-progress-fill').css('width', '100%');
				$('#batch-progress-text').text(batchQueue.length + ' / ' + batchQueue.length);
				$('#batch-progress-item').text('✅ <?php echo esc_js( __( 'Completado', 'amsawal' ) ); ?>');
				batchLog('🏆 <strong><?php echo esc_js( __( 'Generación batch completada', 'amsawal' ) ); ?></strong> — ' + ok + ' ✅ ' + fail + ' ❌ ' + skip + ' ⏭', ok > 0 ? 'success' : 'info');
				$('#batch-generate').prop('disabled', false).text('🚀 <?php echo esc_js( __( 'Generar todo', 'amsawal' ) ); ?>');
				$('#batch-pause').hide();
				return;
			}

			var item = batchQueue[idx];
			$('#batch-progress-text').text(idx + ' / ' + batchQueue.length);
			$('#batch-progress-fill').css('width', (idx / batchQueue.length * 100) + '%');
			$('#batch-progress-item').text(item.type + ' → L' + item.lesson_num);

			$.ajax({
				url: ajaxUrl,
				method: 'POST',
				data: {
					action: 'wp_amsawal_ai_regenerate',
					_ajax_nonce: nonces.regen,
					lesson_id: item.lesson_id,
					type: item.type,
					vocabulary: '[]'
				},
				timeout: 360000,
				success: function(resp) {
					if (resp.success) {
						ok++;
						$('#batch-count-ok').text('✅ ' + ok);
						batchLog('✅ ' + item.type + ' → ' + item.title, 'success');
					} else {
						var msg = resp.data && resp.data.message || '<?php echo esc_js( __( 'Error', 'amsawal' ) ); ?>';
						if (msg.indexOf('ya existe') !== -1 || msg.indexOf('already') !== -1) {
							skip++;
							$('#batch-count-skip').text('⏭ ' + skip);
							batchLog('⏭ ' + item.type + ' → ' + item.title + ': ya existe', 'warn');
						} else {
							fail++;
							$('#batch-count-fail').text('❌ ' + fail);
							batchLog('❌ ' + item.type + ' → ' + item.title + ': ' + msg, 'error');
						}
					}
				},
				error: function(xhr, status) {
					fail++;
					$('#batch-count-fail').text('❌ ' + fail);
					batchLog('❌ ' + item.type + ' → ' + item.title + ': ' + status, 'error');
				},
				complete: function() {
					setTimeout(function() { processBatch(idx + 1, ok, fail, skip); }, 500);
				}
			});
		}

		function batchLog(msg, cls) {
			var color = cls === 'success' ? '#58cc02' : (cls === 'error' ? '#ff4b4b' : (cls === 'warn' ? '#ff9600' : '#1cb0f6'));
			$('#batch-log').append('<div style="color:' + color + '; padding:2px 0; border-bottom:1px solid #f0f0f0;">' + msg + '</div>');
			$('#batch-log').scrollTop($('#batch-log')[0].scrollHeight);
		}

		/* ── Utils ── */
		function escapeHtml(s) {
			return $('<span/>').text(s || '').html();
		}

		// Load stats on page load
		loadDashboardStats();

	})(jQuery);
	</script>
	<?php
}


/*───────────────────────────────────────────────────────────────────────
 * 3. AJAX: Generate course structure from prompt
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_wp_amsawal_studio_generate', 'wp_amsawal_studio_ajax_generate' );
function wp_amsawal_studio_ajax_generate() {
	@set_time_limit( 600 );
	@ignore_user_abort( true );

	check_ajax_referer( 'wp_amsawal_studio_generate', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'No autorizado', 'amsawal' ), 403 );
	}
	wp_amsawal_rate_limit_or_die( 'studio_generate', 10, 300 );

	$mode = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'structure';

	if ( $mode === 'create_and_generate' ) {
		// Mode: create a page + generate activity content for it.
		wp_amsawal_studio_create_and_generate();
		return;
	}

	// Mode: generate course structure (outline) from prompt.
	$topic      = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : '';
	$course_name = isset( $_POST['course_name'] ) ? sanitize_text_field( wp_unslash( $_POST['course_name'] ) ) : $topic;
	$num_lessons = isset( $_POST['num_lessons'] ) ? intval( $_POST['num_lessons'] ) : 5;
	$level       = isset( $_POST['level'] ) ? intval( $_POST['level'] ) : 1;
	$extra       = isset( $_POST['extra'] ) ? sanitize_textarea_field( wp_unslash( $_POST['extra'] ) ) : '';
	// Escape for safe embedding in the prompt string (prevents JSON breakage).
	$topic_safe       = addslashes( $topic );
	$course_name_safe = addslashes( $course_name );
	$extra_safe       = addslashes( $extra );
	$types       = isset( $_POST['types'] ) ? json_decode( wp_unslash( $_POST['types'] ), true ) : array( 'flashcards' );

	if ( ! is_array( $types ) ) $types = array( 'flashcards' );
	$num_lessons = max( 1, min( 20, $num_lessons ) );

	$types_str = implode( ', ', $types );

	$prompt = <<<PROMPT
Eres un diseñador instruccional experto en enseñanza de idiomas. Crea la estructura completa de un curso de Tamazight (Tarifit).

CURSO: "$course_name_safe"
TEMA: "$topic_safe"
NIVEL: $level (1=principiante, 5=avanzado)
LECCIONES: $num_lessons
TIPOS DE ACTIVIDAD: $types_str
$extra_safe

Genera SOLO un JSON válido con esta estructura exacta (sin markdown, sin explicaciones):
{
  "course_name": "$course_name_safe",
  "level": $level,
  "types": [$types_str_as_json],
  "lessons": [
    {
      "title": "Título de la lección",
      "description": "Breve descripción del contenido",
      "vocabulary": ["ⴰⵣⵓⵍ = Hola (azul)", "ⵎⴰⵏⵣⴰⴽⵉⵏ = ¿Cómo estás? (manzakin)"]
    }
  ]
}

Reglas:
1. JSON válido (sin comas finales).
2. El vocabulario debe estar en formato: ⵜⵉⴼⵉⵏⴰⵖ = Significado (transliteración).
3. Genera exactamente $num_lessons lecciones con progresión de dificultad.
4. Cada lección debe tener 5-10 palabras de vocabulario.
5. Las lecciones deben cubrir el tema "$topic_safe" de forma progresiva.
6. SOLO el JSON, nada más.
PROMPT;

	// Convertir $types_str para el JSON embebido.
	$types_json = wp_json_encode( $types );
	$prompt = str_replace( '$types_str_as_json', substr( $types_json, 1, -1 ), $prompt );

	$result = wp_amsawal_ai_query( $prompt, array(
		'temperature' => 0.4,
		'max_tokens'  => 2000,
		'timeout'     => 600,
	) );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	$data = wp_amsawal_ai_extract_json( $result );
	if ( ! $data || empty( $data['lessons'] ) ) {
		wp_amsawal_log( 'warning', 'Studio: invalid JSON from AI', array( 'raw' => substr( (string) $result, 0, 500 ) ) );
		wp_send_json_error( array( 'message' => __( 'La IA devolvió una estructura no válida. Inténtalo de nuevo.', 'amsawal' ) ) );
	}

	wp_amsawal_log( 'info', 'Studio: course structure generated', array(
		'course'   => $course_name,
		'lessons'  => count( $data['lessons'] ),
		'types'    => $types,
	) );

	wp_send_json_success( array(
		'message'   => sprintf( __( 'Estructura generada: %d lecciones', 'amsawal' ), count( $data['lessons'] ) ),
		'structure' => $data,
	) );
}


/**
 * Sub-handler: create a WP page + generate AI content for one activity.
 * Called from the creation queue in step 3.
 */
function wp_amsawal_studio_create_and_generate() {
	$course_name = isset( $_POST['course_name'] ) ? sanitize_text_field( wp_unslash( $_POST['course_name'] ) ) : '';
	$lesson_title = isset( $_POST['lesson_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lesson_title'] ) ) : '';
	$lesson_num   = isset( $_POST['lesson_num'] ) ? intval( $_POST['lesson_num'] ) : 1;
	$lesson_desc  = isset( $_POST['lesson_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lesson_description'] ) ) : '';
	$vocabulary   = isset( $_POST['vocabulary'] ) ? json_decode( wp_unslash( $_POST['vocabulary'] ), true ) : array();
	$type         = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
	$level        = isset( $_POST['level'] ) ? intval( $_POST['level'] ) : 1;

	if ( ! is_array( $vocabulary ) ) $vocabulary = array();
	if ( ! $lesson_title || ! $type ) {
		wp_send_json_error( array( 'message' => __( 'Faltan parámetros', 'amsawal' ) ) );
	}

	// Find or create the lesson page.
	$lesson_id = wp_amsawal_studio_find_or_create_lesson( $course_name, $lesson_title, $lesson_num );

	if ( ! $lesson_id ) {
		wp_send_json_error( array( 'message' => __( 'No se pudo crear la página de lección', 'amsawal' ) ) );
	}

	// Check if content already exists.
	$existing = wp_amsawal_ai_get_content( $lesson_id, $type, 0 );
	if ( $existing ) {
		wp_send_json_success( array( 'message' => __( 'Ya existe contenido para este tipo', 'amsawal' ), 'skipped' => true ) );
		return;
	}

	// Generate content with AI.
	$context = array(
		'activities'         => array( $type ),
		'vocabulary'         => $vocabulary,
		'lesson_title'       => $lesson_title,
		'course'             => $course_name,
		'language'           => 'Tamazight (Tarifit)',
		'level'              => $level,
		'extra_instructions' => $lesson_desc,
	);

	$result = wp_amsawal_ai_generate_lesson( $lesson_id, $context, 0 );

	if ( $result['generated'] > 0 ) {
		wp_send_json_success( array(
			'message' => sprintf( __( '%s generado para %s', 'amsawal' ), $type, $lesson_title ),
			'lesson'  => $lesson_title,
			'type'    => $type,
		) );
	} else {
		wp_send_json_error( array( 'message' => implode( '; ', $result['errors'] ) ) );
	}
}


/**
 * Find existing lesson page or create a new one.
 */
function wp_amsawal_studio_find_or_create_lesson( $course_name, $lesson_title, $lesson_num ) {
	// Find the course page.
	$course_pages = get_posts( array(
		'post_type'      => 'page',
		'post_parent'    => 0,
		'post_status'    => 'publish',
		'numberposts'    => 1,
		'meta_query'     => array( array( 'key' => 'wp_amsawal_mb_course', 'value' => $course_name ) ),
	) );

	$course_id = 0;
	if ( ! empty( $course_pages ) ) {
		$course_id = $course_pages[0]->ID;
	} else {
		// Create the course page.
		$course_id = wp_insert_post( array(
			'post_title'   => $course_name,
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '',
		) );
		if ( $course_id && ! is_wp_error( $course_id ) ) {
			update_post_meta( $course_id, 'wp_amsawal_mb_course', $course_name );
		} else {
			return 0;
		}
	}

	// Check if lesson page already exists.
	$existing = get_posts( array(
		'post_type'   => 'page',
		'post_parent' => $course_id,
		'post_status' => 'publish',
		'numberposts' => 1,
		'meta_query'  => array(
			array( 'key' => 'wp_amsawal_mb_typeh5p', 'value' => 'lesson' ),
			array( 'key' => 'wp_amsawal_mb_lesson', 'value' => strval( $lesson_num ) ),
		),
	) );

	if ( ! empty( $existing ) ) {
		return $existing[0]->ID;
	}

	// Create the lesson page.
	$lesson_id = wp_insert_post( array(
		'post_title'   => $lesson_title,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_parent'  => $course_id,
		'post_content' => '',
		'menu_order'   => $lesson_num,
	) );

	if ( $lesson_id && ! is_wp_error( $lesson_id ) ) {
		update_post_meta( $lesson_id, 'wp_amsawal_mb_course', $course_name );
		update_post_meta( $lesson_id, 'wp_amsawal_mb_typeh5p', 'lesson' );
		update_post_meta( $lesson_id, 'wp_amsawal_mb_lesson', strval( $lesson_num ) );
		return $lesson_id;
	}

	return 0;
}


/*───────────────────────────────────────────────────────────────────────
 * 4. AJAX: Content map for dashboard
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_wp_amsawal_studio_map', 'wp_amsawal_studio_ajax_map' );
function wp_amsawal_studio_ajax_map() {
	check_ajax_referer( 'wp_amsawal_studio_map', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'No autorizado', 'amsawal' ), 403 );
	}

	$mode = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'course_map';

	if ( $mode === 'get_content' ) {
		// Return content for a specific lesson + type (for editor).
		$lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
		$type      = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
		if ( ! $lesson_id || ! $type ) {
			wp_send_json_error( array( 'message' => __( 'Faltan parámetros', 'amsawal' ) ) );
		}
		$content = wp_amsawal_ai_get_content( $lesson_id, $type, 0 );
		wp_send_json_success( array(
			'content'      => $content,
			'lesson_title' => get_the_title( $lesson_id ),
			'lesson_num'   => intval( get_post_meta( $lesson_id, 'wp_amsawal_mb_lesson', true ) ),
		) );
		return;
	}

	// Course map: list lessons + what content they have.
	$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
	if ( ! $course_id ) {
		wp_send_json_error( array( 'message' => __( 'Curso no especificado', 'amsawal' ) ) );
	}

	$lessons = wp_amsawal_ai_get_course_lessons( $course_id );
	$h5p_types = array( 'flashcards', 'dialogcards', 'dictation', 'memory', 'fill-blanks', 'mark-the-words', 'multiple-choice', 'drag-drop', 'true-false', 'speak-the-words', 'essay', 'adaptest' );

	$result = array();
	foreach ( $lessons as $lesson ) {
		$content_map = array();
		foreach ( $h5p_types as $ht ) {
			$c = wp_amsawal_ai_get_content( $lesson['id'], $ht, 0 );
			$content_map[ $ht ] = ! empty( $c );
		}
		$result[] = array(
			'id'         => $lesson['id'],
			'title'      => $lesson['title'],
			'lesson_num' => $lesson['lesson_num'],
			'content'    => $content_map,
		);
	}

	wp_send_json_success( array( 'lessons' => $result ) );
}


/*───────────────────────────────────────────────────────────────────────
 * 5. AJAX: Save edited content
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_wp_amsawal_studio_save', 'wp_amsawal_studio_ajax_save' );
function wp_amsawal_studio_ajax_save() {
	check_ajax_referer( 'wp_amsawal_studio_save', '_ajax_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'No autorizado', 'amsawal' ), 403 );
	}

	$lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
	$type      = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 0;
	$content   = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';

	if ( ! $lesson_id || ! $type || ! $content ) {
		wp_send_json_error( array( 'message' => __( 'Faltan parámetros', 'amsawal' ) ) );
	}

	$data = json_decode( $content, true );
	if ( ! $data ) {
		wp_send_json_error( array( 'message' => __( 'JSON inválido', 'amsawal' ) ) );
	}

	$saved = wp_amsawal_ai_store_content( $lesson_id, $type, $data, 0 );

	if ( $saved ) {
		wp_amsawal_log( 'info', 'Studio: content saved', array( 'lesson' => $lesson_id, 'type' => $type ) );
		wp_send_json_success( array( 'message' => __( 'Contenido guardado', 'amsawal' ) ) );
	} else {
		wp_send_json_error( array( 'message' => __( 'Error al guardar', 'amsawal' ) ) );
	}
}
