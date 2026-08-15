<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * wp-amsawal-translate.php — Traducción automática de cursos
 *
 * Traduce contenidos de cursos (lecciones, actividades) a otros idiomas
 * usando IA. Soporta:
 *   - Traducción de contenido de páginas (the_content)
 *   - Traducción de metadata (título, vocabulario)
 *   - Cache en postmeta para no re-traducir
 *   - Regeneración bajo demanda
 *
 * Idiomas soportados: Español, Inglés, Francés, Árabe, Rifeño (Tarifit)
 *
 * @package Amsawal
 * @since   0.0.4-genai
 */

if ( ! defined( 'WPINC' ) ) { die; }

/*───────────────────────────────────────────────────────────────────────
 * 1. LANGUAGE CONFIG
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Get supported translation languages.
 *
 * @return array  [code => label]
 */
function wp_amsawal_translate_get_languages() {
	return array(
		'es' => 'ES Español',
		'en' => 'GB English',
		'fr' => 'FR Français',
		'ar' => 'SA العربية',
		'tzm' => 'MA Tamazight (Tarifit)',
		'rif' => 'MA Rifeño',
	);
}

/**
 * Get the source language for a course.
 *
 * @param int $course_id
 * @return string  Language code
 */
function wp_amsawal_translate_get_source_language( $course_id ) {
	$source = get_post_meta( $course_id, '_wp_amsawal_source_lang', true );
	return $source ? $source : 'tzm'; // Default: Tamazight
}

/*───────────────────────────────────────────────────────────────────────
 * 2. TRANSLATION STORAGE — Cache in postmeta
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Meta key for storing translated content.
 *
 * @param int   $post_id  ID del post traducido
 * @param string $lang    Código de idioma destino
 * @return string
 */
function wp_amsawal_translate_meta_key( $post_id, $lang ) {
	return '_wp_amsawal_trans_' . sanitize_key( $lang ) . '_' . intval( $post_id );
}

/**
 * Save a translation.
 *
 * @param int    $post_id
 * @param string $lang    Target language code
 * @param array  $data    { title, content, vocabulary }
 * @return bool
 */
function wp_amsawal_translate_save( $post_id, $lang, $data ) {
	$key = wp_amsawal_translate_meta_key( $post_id, $lang );
	return update_post_meta( $post_id, $key, wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) );
}

/**
 * Get a translation.
 *
 * @param int    $post_id
 * @param string $lang
 * @return array|null  Decoded data or null
 */
function wp_amsawal_translate_get( $post_id, $lang ) {
	$key = wp_amsawal_translate_meta_key( $post_id, $lang );
	$json = get_post_meta( $post_id, $key, true );
	if ( empty( $json ) ) return null;
	return json_decode( $json, true );
}

/**
 * Delete a translation (for regeneration).
 *
 * @param int    $post_id
 * @param string $lang
 * @return bool
 */
function wp_amsawal_translate_delete( $post_id, $lang ) {
	$key = wp_amsawal_translate_meta_key( $post_id, $lang );
	return delete_post_meta( $post_id, $key );
}

/*───────────────────────────────────────────────────────────────────────
 * 3. AI TRANSLATION PROMPT
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Build translation prompt.
 *
 * @param string $source_lang  Source language code
 * @param string $target_lang  Target language code
 * @param string $content      Text to translate
 * @param array  $context      Optional context (title, vocabulary)
 * @return string
 */
function wp_amsawal_translate_build_prompt( $source_lang, $target_lang, $content, $context = array() ) {
	$source_label = wp_amsawal_translate_get_language_label( $source_lang );
	$target_label = wp_amsawal_translate_get_language_label( $target_lang );

	$vocab_block = '';
	if ( ! empty( $context['vocabulary'] ) && is_array( $context['vocabulary'] ) ) {
		$vocab_block = "\nVOCABULARIO DEL CURSO (no traducir, mantener original):\n";
		foreach ( $context['vocabulary'] as $word ) {
			$vocab_block .= "- $word\n";
		}
	}

	$prompt = <<<PROMPT
Eres un traductor profesional. Traduce el siguiente texto de {$source_label} a {$target_lang}.

{$vocab_block}

Reglas:
1. Mantén el formato HTML original (si lo hay).
2. No traduzcas el vocabulario del curso listado arriba.
3. Conserva emojis y caracteres especiales.
4. Devuelve SOLO el texto traducido, sin explicaciones.

Texto a traducir:
{$content}

Traducción:
PROMPT;

	return $prompt;
}

/**
 * Get human-readable language label.
 *
 * @param string $code
 * @return string
 */
function wp_amsawal_translate_get_language_label( $code ) {
	$langs = wp_amsawal_translate_get_languages();
	return isset( $langs[ $code ] ) ? $langs[ $code ] : $code;
}

/*───────────────────────────────────────────────────────────────────────
 * 4. TRANSLATION ENGINE
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Translate a single post's content.
 *
 * @param int    $post_id     Post to translate
 * @param string $target_lang Target language code
 * @param bool   $force       Force regeneration even if cached
 * @return array|WP_Error     { title, content, vocabulary } or error
 */
function wp_amsawal_translate_post( $post_id, $target_lang, $force = false ) {
	// Check cache
	if ( ! $force ) {
		$cached = wp_amsawal_translate_get( $post_id, $target_lang );
		if ( $cached ) return $cached;
	}

	$post = get_post( $post_id );
	if ( ! $post ) return new WP_Error( 'invalid_post', 'Post no encontrado' );

	$source_lang = wp_amsawal_translate_get_source_language( $post->post_parent );
	$vocabulary = get_post_meta( $post_id, 'wp_amsawal_mb_vocabulary', true );

	// Translate title
	$title_prompt = wp_amsawal_translate_build_prompt( $source_lang, $target_lang, $post->post_title, compact( 'vocabulary' ) );
	$title_raw = wp_amsawal_ai_query( $title_prompt );
	if ( is_wp_error( $title_raw ) ) return $title_raw;
	$title = trim( $title_raw ?? '' );

	// Translate content
	$content_prompt = wp_amsawal_translate_build_prompt( $source_lang, $target_lang, $post->post_content, compact( 'vocabulary' ) );
	$content_raw = wp_amsawal_ai_query( $content_prompt );
	if ( is_wp_error( $content_raw ) ) return $content_raw;
	$content = trim( $content_raw ?? '' );

	$result = array(
		'title'       => $title,
		'content'     => $content,
		'vocabulary'  => is_array( $vocabulary ) ? $vocabulary : array(),
		'target_lang' => $target_lang,
		'source_lang' => $source_lang,
		'timestamp'   => current_time( 'mysql' ),
	);

	// Cache
	wp_amsawal_translate_save( $post_id, $target_lang, $result );

	return $result;
}

/**
 * Translate all lessons in a course.
 *
 * @param int    $course_id   Course page ID
 * @param string $target_lang Target language
 * @param bool   $force       Force regeneration
 * @return array  { success: int, errors: array }
 */
function wp_amsawal_translate_course( $course_id, $target_lang, $force = false ) {
	$lessons = get_posts( array(
		'post_parent' => $course_id,
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby'     => 'menu_order',
	) );

	$result = array( 'success' => 0, 'errors' => array() );

	foreach ( $lessons as $lesson ) {
		$translated = wp_amsawal_translate_post( $lesson->ID, $target_lang, $force );
		if ( is_wp_error( $translated ) ) {
			$result['errors'][] = array(
				'post_id' => $lesson->ID,
				'title'   => $lesson->post_title,
				'error'   => $translated->get_error_message(),
			);
		} else {
			$result['success']++;
		}
	}

	return $result;
}

/*───────────────────────────────────────────────────────────────────────
 * 5. AJAX ENDPOINTS
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Translate a single post via AJAX.
 */
add_action( 'wp_ajax_wp_amsawal_translate_post', 'wp_amsawal_translate_ajax_post' );
function wp_amsawal_translate_ajax_post() {
	check_ajax_referer( 'wp_amsawal_translate', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permiso denegado', WP_AMSAWAL_TEXTDOMAIN ) ) );
	}
	// Rate-limit: 10 traducciones / 5 min por admin (coste alto).
	wp_amsawal_rate_limit_or_die( 'translate_post', 10, 300 );

	$post_id   = intval( $_POST['post_id'] );
	$target    = sanitize_key( $_POST['target_lang'] );
	$force     = ! empty( $_POST['force'] );

	$languages = wp_amsawal_translate_get_languages();
	if ( ! isset( $languages[ $target ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Idioma no soportado', WP_AMSAWAL_TEXTDOMAIN ) ) );
	}

	$result = wp_amsawal_translate_post( $post_id, $target, $force );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array(
		'message' => __( 'Traducción completada', WP_AMSAWAL_TEXTDOMAIN ),
		'data'    => $result,
	) );
}

/**
 * Translate an entire course via AJAX.
 */
add_action( 'wp_ajax_wp_amsawal_translate_course', 'wp_amsawal_translate_ajax_course' );
function wp_amsawal_translate_ajax_course() {
	check_ajax_referer( 'wp_amsawal_translate', '_ajax_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permiso denegado', WP_AMSAWAL_TEXTDOMAIN ) ) );
	}
	// Rate-limit: 3 cursos / 10 min por admin (un curso puede ser 10+ posts).
	wp_amsawal_rate_limit_or_die( 'translate_course', 3, 600 );

	$course_id = intval( $_POST['course_id'] );
	$target    = sanitize_key( $_POST['target_lang'] );
	$force     = ! empty( $_POST['force'] );

	$languages = wp_amsawal_translate_get_languages();
	if ( ! isset( $languages[ $target ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Idioma no soportado', WP_AMSAWAL_TEXTDOMAIN ) ) );
	}

	$result = wp_amsawal_translate_course( $course_id, $target, $force );
	wp_send_json_success( $result );
}

/*───────────────────────────────────────────────────────────────────────
 * 6. FRONTEND — Language switcher + translated content render
 *───────────────────────────────────────────────────────────────────────*/

/**
 * AJAX: Set user's preferred language for course content.
 */
add_action( 'wp_ajax_wp_amsawal_set_language', 'wp_amsawal_ajax_set_language' );
function wp_amsawal_ajax_set_language() {
	check_ajax_referer( 'wp_amsawal_set_language', '_ajax_nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'No autorizado', WP_AMSAWAL_TEXTDOMAIN ), 403 );
	}

	$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
	$languages = wp_amsawal_translate_get_languages();
	if ( ! isset( $languages[ $lang ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Idioma no soportado', WP_AMSAWAL_TEXTDOMAIN ) ) );
	}

	update_user_meta( get_current_user_id(), '_wp_amsawal_preferred_lang', $lang );
	wp_send_json_success( array( 'lang' => $lang ) );
}

/**
 * Filter the_content: replace with translated version when user has a
 * preferred language different from the default (tzm = Tamazight).
 *
 * Hooks at priority 25 (after AI render at 20, before breadcrumbs at 99).
 *
 * IMPORTANT: Must run AFTER the AI activity render (priority 20) so that
 * translated content appears on activity pages (flashcards, dictation,
 * etc.) instead of being replaced by the H5P/activity renderer.
 */
add_filter( 'the_content', 'wp_amsawal_translate_render_content', 25 );
function wp_amsawal_translate_render_content( $content ) {
	if ( is_admin() || wp_doing_ajax() || ! is_singular( 'page' ) ) return $content;

	global $post;
	if ( ! $post ) return $content;

	$type = get_post_meta( $post->ID, 'wp_amsawal_mb_typeh5p', true );
	if ( empty( $type ) ) return $content;

	$user_id = get_current_user_id();
	if ( ! $user_id ) return $content;

	$preferred = get_user_meta( $user_id, '_wp_amsawal_preferred_lang', true );
	if ( ! $preferred || $preferred === 'tzm' ) return $content; // Default = no translation needed

	// Check for cached translation
	$translated = wp_amsawal_translate_get( $post->ID, $preferred );
	if ( ! $translated ) return $content;

	$rendered = '<div class="duo-translated-content" data-lang="' . esc_attr( $preferred ) . '">';

	if ( ! empty( $translated['title'] ) ) {
		$rendered .= '<h1 class="duo-translated-title">' . esc_html( $translated['title'] ) . '</h1>';
	}

	if ( ! empty( $translated['content'] ) ) {
		$rendered .= '<div class="duo-translated-body">' . wp_kses_post( $translated['content'] ) . '</div>';
	}

	$rendered .= '<div class="duo-translated-badge">🌐 ' . esc_html( sprintf(
		/* translators: %s: language name */
		__( 'Traducido al %s por IA', WP_AMSAWAL_TEXTDOMAIN ),
		wp_amsawal_translate_get_language_label( $preferred )
	) ) . '</div></div>';

	return $rendered . $content;
}

/*───────────────────────────────────────────────────────────────────────
 * 7. ADMIN UI — Translation panel
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'admin_menu', 'wp_amsawal_translate_add_admin_page' );
function wp_amsawal_translate_add_admin_page() {
	add_submenu_page(
		'wp-amsawal-admin',
		'Traducir Cursos',
		'🌐 Traducir',
		'manage_options',
		'wp-amsawal-translate',
		'wp_amsawal_translate_admin_page'
	);
}

function wp_amsawal_translate_admin_page() {
	$courses = get_posts( array(
		'post_type'      => 'page',
		'post_parent'    => 0,
		'post_status'    => 'publish',
		'numberposts'    => -1,
		'meta_key'       => 'wp_amsawal_mb_course',
	) );

	$languages = wp_amsawal_translate_get_languages();
	$nonce = wp_create_nonce( 'wp_amsawal_translate' );
	$ajax_url = admin_url( 'admin-ajax.php' );

	?>
	<div class="wrap duo-ai-admin">
		<h2>🌐 Traducción automática de cursos</h2>
		<p class="description">Traduce el contenido de tus cursos a otros idiomas usando IA.</p>
		<hr />

		<div class="card" style="max-width:600px; padding:16px; margin-bottom:20px;">
			<h3>📚 1. Selecciona el curso</h3>
			<select id="duo-trans-course" class="widefat" style="margin-bottom:12px;">
				<option value="">— Selecciona un curso —</option>
				<?php foreach ( $courses as $cp ) :
					$course_name = get_post_meta( $cp->ID, 'wp_amsawal_mb_course', true );
				?>
					<option value="<?php echo esc_attr( $cp->ID ); ?>"><?php echo esc_html( $course_name ?: $cp->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="card" id="duo-trans-lang-card" style="max-width:600px; padding:16px; margin-bottom:20px; display:none;">
			<h3>🌐 2. Idioma destino</h3>
			<?php foreach ( $languages as $code => $label ) : ?>
				<label style="display:flex; align-items:center; gap:8px; padding:6px 0; cursor:pointer;">
					<input type="radio" name="duo-trans-lang" value="<?php echo esc_attr( $code ); ?>" />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
			<label style="display:flex; align-items:center; gap:8px; padding:6px 0;">
				<input type="checkbox" id="duo-trans-force" />
				<strong>Forzar regeneración</strong> (ignorar cache)
			</label>
		</div>

		<div id="duo-trans-go-card" style="max-width:600px; margin-bottom:20px; display:none;">
			<p style="color:#856404; background:#fff3cd; padding:12px; border-radius:4px; margin-bottom:12px;">
				⚠️️ Cada lección tarda ~30-60s en traducir. No cierres la pestaña.
			</p>
			<button type="button" id="duo-trans-go" class="button button-primary" style="font-size:1.1em; padding:12px 24px;">
				🚀 Traducir curso
			</button>
		</div>

		<div id="duo-trans-progress" style="max-width:600px; display:none; margin-bottom:20px;">
			<h3>⏳ Progreso</h3>
			<div style="background:#e0e0e0; border-radius:12px; height:24px; overflow:hidden;">
				<div id="duo-trans-bar" style="background:linear-gradient(90deg, #1cb0f6, #58cc02); height:100%; width:0%; transition:width 0.3s; border-radius:12px;"></div>
			</div>
			<div id="duo-trans-log" style="max-height:300px; overflow-y:auto; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; padding:12px; margin-top:12px; font-family:monospace; font-size:0.85em;"></div>
		</div>
	</div>

	<script>
	jQuery(function($) {
		var course = '', lang = '', force = false;

		$('#duo-trans-course').on('change', function() {
			course = $(this).val();
			$('#duo-trans-lang-card').toggle(!!course);
			$('#duo-trans-go-card').hide();
		});

		$(document).on('change', 'input[name="duo-trans-lang"]', function() {
			lang = $(this).val();
			$('#duo-trans-go-card').toggle(!!lang);
		});

		$('#duo-trans-force').on('change', function() {
			force = $(this).is(':checked');
		});

		$('#duo-trans-go').on('click', function() {
			if (!course || !lang) return;

			$(this).prop('disabled', true).text('⏳ Traduciendo...');
			$('#duo-trans-progress').show();
			$('#duo-trans-log').empty();

			$.ajax({
				url: '<?php echo esc_url( $ajax_url ); ?>',
				method: 'POST',
				data: {
					action: 'wp_amsawal_translate_course',
					course_id: course,
					target_lang: lang,
					force: force ? '1' : '0',
					_ajax_nonce: '<?php echo esc_js( $nonce ); ?>'
				},
				timeout: 600000, // 10 min
				success: function(resp) {
					if (resp.success) {
						var d = resp.data;
						$('#duo-trans-bar').css('width', '100%');
						$('#duo-trans-log').append('<div style="color:#58cc02;">✅ Completado: ' + d.success + ' lecciones traducidas</div>');
						if (d.errors && d.errors.length) {
							d.errors.forEach(function(e) {
								$('#duo-trans-log').append('<div style="color:#ff4b4b;">❌ ' + e.title + ': ' + e.error + '</div>');
							});
						}
					} else {
						$('#duo-trans-log').append('<div style="color:#ff4b4b;">❌ Error: ' + (resp.data && resp.data.message || 'desconocido') + '</div>');
					}
				},
				error: function() {
					$('#duo-trans-log').append('<div style="color:#ff4b4b;">❌ Error de red</div>');
				},
				complete: function() {
					$('#duo-trans-go').prop('disabled', false).text('🚀 Traducir curso');
				}
			});
		});
	});
	</script>
	<?php
}
