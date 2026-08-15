<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * wp-amsawal-ai-tutor.php — Tutor Virtual (chat por curso con contexto de lección)
 *
 * Extraido de wp-amsawal-ai.php para reducir el tamano del archivo principal.
 * Contiene: contexto del tutor, constructor de prompts, historial (transients),
 * widget flotante en el footer, y endpoints AJAX (ask + clear).
 *
 * @package Amsawal
 * @since   0.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/*───────────────────────────────────────────────────────────────────────
 * 8. TUTOR VIRTUAL — Chat por curso con contexto de lección
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Devuelve el contexto actual del tutor: curso, lección (si está en una),
 * vocabulario y un historial reciente de la conversación.
 *
 * Se cachea por request con static para no recalcular en cada llamada.
 */
function wp_amsawal_tutor_get_context( $course = null, $lesson_id = 0 ) {
	$course = $course ? sanitize_title( $course ) : '';
	if ( ! $course ) {
		$front_id = function_exists( 'wp_amsawal_get_user_homepage' )
			? wp_amsawal_get_user_homepage()
			: 0;
		if ( $front_id ) {
			$course = (string) get_post_meta( $front_id, 'wp_amsawal_mb_course', true );
		}
	}
	if ( ! $course ) {
		$course = 'tamazight';
	}

	$lesson_num  = 0;
	$lesson_title = '';
	$lesson_syllabus = null;

	// Si se pasa lesson_id explícito
	if ( $lesson_id ) {
		$lesson_num   = (int) get_post_meta( $lesson_id, 'wp_amsawal_mb_lesson', true );
		$lesson_title = get_the_title( $lesson_id );
	}

	// Si estamos en una página de lección, sacar el número de la meta
	global $post;
	if ( ! $lesson_num && $post && is_singular( 'page' ) ) {
		$lesson_num = (int) get_post_meta( $post->ID, 'wp_amsawal_mb_lesson', true );
		if ( $lesson_num > 0 ) {
			$lesson_title = $post->post_title;
		}
	}

	// Cargar el temario completo de la lección
	$vocab     = array();
	$questions = array();
	$section_info = null;
	if ( $lesson_num > 0 ) {
		$lesson_syllabus = wp_amsawal_get_lesson_syllabus( $lesson_num );
		if ( $lesson_syllabus ) {
			$vocab        = $lesson_syllabus['vocabulary'];
			$questions    = $lesson_syllabus['questions'];
			$section_info = $lesson_syllabus['section_info'];
			if ( ! $lesson_title ) {
				$lesson_title = $lesson_syllabus['title'];
			}
		}
	}

	return array(
		'course'        => $course,
		'course_label'  => ucfirst( str_replace( '-', ' ', $course ) ),
		'lesson_num'    => $lesson_num,
		'lesson_title'  => $lesson_title,
		'vocabulary'    => $vocab,
		'questions'     => $questions,
		'section_info'  => $section_info,
	);
}


/**
 * Construye el system prompt (rol de profesor) del tutor con RAG estricto.
 *
 * Solo el temario oficial (vocabulario + preguntas + guía) entra en el
 * contexto. El modelo está instruido a NO usar conocimiento general y
 * a rechazar preguntas fuera del temario.
 */
function wp_amsawal_tutor_build_system_prompt( $context ) {
	$course = $context['course_label'];

	// === LECCIÓN Y SECCIÓN ACTUAL ===
	$lesson_block = "El usuario aún no ha abierto una lección concreta del curso de {$course}.";
	if ( ! empty( $context['lesson_title'] ) ) {
		$lesson_block = "Lección actual: «{$context['lesson_title']}».";
		if ( ! empty( $context['section_info']['title'] ) ) {
			$lesson_block .= "\nSección: {$context['section_info']['title']} — {$context['section_info']['desc']}.";
		}
	}

	// === GUÍA CULTURAL / GRAMATICAL DE LA SECCIÓN ===
	$guide_block = '';
	if ( ! empty( $context['section_info']['guide'] ) ) {
		$guide_block = "\nContexto cultural/gramatical de la sección:\n" . $context['section_info']['guide'];
	}

	// === VOCABULARIO OFICIAL (RAG) ===
	$vocab_block = '';
	if ( ! empty( $context['vocabulary'] ) ) {
		$lines = array();
		foreach ( $context['vocabulary'] as $tarifit => $espanol ) {
			$lines[] = "  • {$tarifit} → {$espanol}";
		}
		$vocab_block = "\nVocabulario oficial de esta lección (SOLO puedes usar estas traducciones):\n" . implode( "\n", $lines );
	}

	// === PREGUNTAS EN CONTEXTO (RAG) ===
	$questions_block = '';
	if ( ! empty( $context['questions'] ) ) {
		$q_lines = array();
		foreach ( array_slice( $context['questions'], 0, 6 ) as $q ) {
			$opts = array();
			foreach ( $q['options'] as $opt ) {
				$marker = ! empty( $opt['correct'] ) ? '✅' : ' ';
				$opts[] = "    [{$marker}] {$opt['text']}";
			}
			$q_lines[] = "  P: {$q['question']}\n" . implode( "\n", $opts );
		}
		$questions_block = "\nEjemplos vistos en clase (preguntas del ejercicio de esta lección):\n" . implode( "\n", $q_lines );
	}

	return implode( "\n", array_filter( array(
		"Eres el profesor virtual del curso de {$course} en la plataforma Amsawal (idioma amazigh / tarifit).",
		'Tu ÚNICA fuente de verdad es el TEMARIO OFICIAL que te paso a continuación. NO uses tu conocimiento general.',
		'',
		'=== CONTEXTO ACTUAL ===',
		$lesson_block,
		$guide_block,
		$vocab_block,
		$questions_block,
		'',
		'=== REGLAS ESTRICTAS (OBLIGATORIAS) ===',
		'1. ATENCIÓN: La lista de vocabulario de arriba es la ÚNICA fuente autorizada. Si te preguntan por una traducción y no aparece EXACTAMENTE en esa lista, di: "Eso no lo hemos visto aún en esta lección. Lo verás en próximas lecciones del curso."',
		'2. NO ofrezcas alternativas, sinónimos ni variantes que no estén en la lista, aunque sepas que son correctas. Por ejemplo, si la lista dice "xamsa → cinco", NO respondas con "ⵙⵎⵎⵓⵙ (Smmus)" aunque sea otra forma válida de decirlo.',
		'3. NO mezcles vocabulario de otros dialectos (tashelhit, cabilio, etc.) — SOLO tarifit.',
		'4. NO inventes traducciones. Si dudas, di "no estoy seguro".',
		'5. Responde SIEMPRE en español, salvo ejemplos concretos en tifinagh ⵜⵉⴼⵉⵏⴰⵖ.',
		'6. Sé breve: 1-3 frases por turno (máx ~80 palabras).',
		'7. Cuando menciones vocabulario de la lista, cópialo EXACTAMENTE tal como aparece. NO combines palabras ni añadas sufijos/prefijos que no estén en la lista. Por ejemplo, si la lista dice "ayṯma → mis hermanos" y "awmaten inu → mis hermanos", NO digas "ayṯma inu" (eso no existe en la lista).',
		'8. NO uses Markdown (sin **, sin listas largas, sin #). Texto plano.',
		'9. Si la pregunta es sobre otra materia (matemáticas, política, otro idioma) redirige amablemente: "Eso no es parte del curso de {$course}, pero estaré encantado de ayudarte con tu aprendizaje de tarifit."',
		'10. Si hay múltiples formas de decir algo en la lista, menciona TODAS las que aparezcan, pero NUNCA las combines entre sí.',
		'',
		'Recuerda: el usuario es un estudiante, no un experto. Sé paciente, motivador y claro.',
	) ) );
}


/**
 * Construye el array de mensajes para chat multi-turn.
 * Formato OpenAI: [{ role: system|user|assistant, content: string }, …]
 */
function wp_amsawal_tutor_build_messages( $context, $user_message, $history = array() ) {
	$messages = array(
		array( 'role' => 'system', 'content' => wp_amsawal_tutor_build_system_prompt( $context ) ),
	);

	// Historial reciente (últimos 6 turnos = 3 intercambios user+assistant) para no
	// disparar costes ni contexto innecesario.
	$recent = array_slice( $history, -6 );
	foreach ( $recent as $turn ) {
		$role    = isset( $turn['role'] ) ? (string) $turn['role'] : '';
		$content = isset( $turn['content'] ) ? (string) $turn['content'] : '';
		if ( '' === $content ) {
			continue;
		}
		// Solo aceptamos roles válidos.
		if ( ! in_array( $role, array( 'user', 'assistant' ), true ) ) {
			$role = 'user';
		}
		$messages[] = array( 'role' => $role, 'content' => $content );
	}

	$messages[] = array( 'role' => 'user', 'content' => $user_message );

	return $messages;
}


/**
 * Backwards-compatible: construya un prompt plano (legacy Ollama completion).
 * Solo se usa si en el futuro hace falta para un backend sin chat template.
 */
function wp_amsawal_tutor_build_prompt( $context, $user_message, $history = array() ) {
	$system = wp_amsawal_tutor_build_system_prompt( $context );

	$history_block = '';
	if ( ! empty( $history ) ) {
		$recent = array_slice( $history, -4 );
		$parts  = array();
		foreach ( $recent as $turn ) {
			$role    = isset( $turn['role'] ) && 'assistant' === $turn['role'] ? 'Tutor' : 'Alumno';
			$content = isset( $turn['content'] ) ? wp_trim_words( (string) $turn['content'], 25, '…' ) : '';
			if ( '' !== $content ) {
				$parts[] = $role . ': «' . $content . '»';
			}
		}
		if ( ! empty( $parts ) ) {
			$history_block = "\nConversación previa (NO continuar):\n" . implode( "\n", $parts ) . "\n";
		}
	}

	return "{$system}{$history_block}\nAlumno: {$user_message}\nTutor:";
}


/**
 * Recupera el historial reciente del tutor (transient, 1 día).
 */
function wp_amsawal_tutor_get_history( $user_id, $course ) {
	$key = wp_amsawal_tutor_history_key( $user_id, $course );
	$h   = get_transient( $key );
	return is_array( $h ) ? $h : array();
}

function wp_amsawal_tutor_save_history( $user_id, $course, $history ) {
	$key = wp_amsawal_tutor_history_key( $user_id, $course );
	// Cap a 20 turnos (10 user + 10 assistant) para no crecer sin límite.
	if ( count( $history ) > 20 ) {
		$history = array_slice( $history, -20 );
	}
	set_transient( $key, $history, DAY_IN_SECONDS );
}

function wp_amsawal_tutor_clear_history( $user_id, $course ) {
	$key = wp_amsawal_tutor_history_key( $user_id, $course );
	delete_transient( $key );
}

function wp_amsawal_tutor_history_key( $user_id, $course ) {
	return 'wp_amsawal_tutor_' . intval( $user_id ) . '_' . sanitize_key( $course );
}


/* Render: botón flotante + panel ─────────────────────────────────────────── */

add_action( 'wp_footer', 'wp_amsawal_tutor_render_widget' );
function wp_amsawal_tutor_render_widget() {
	if ( is_admin() || wp_doing_ajax() ) return;
	if ( ! is_user_logged_in() ) return;
	$userid = get_current_user_id();
	$context = wp_amsawal_tutor_get_context();
	$history = wp_amsawal_tutor_get_history( $userid, $context['course'] );
	$nonce   = wp_create_nonce( 'wp_amsawal_tutor' );
	?>
	<div id="duo-tutor" class="duo-tutor" data-course="<?php echo esc_attr( $context['course'] ); ?>" hidden>
		<header class="duo-tutor-header">
			<strong>💬 Tutor de <?php echo esc_html( $context['course_label'] ); ?></strong>
			<?php if ( $context['lesson_title'] ) : ?>
				<small> · <?php echo esc_html( $context['lesson_title'] ); ?></small>
			<?php endif; ?>
			<button type="button" class="duo-tutor-close" aria-label="Cerrar tutor">&times;</button>
		</header>
		<div class="duo-tutor-log" role="log" aria-live="polite">
			<?php if ( empty( $history ) ) : ?>
				<div class="duo-tutor-empty">¡Hola! Soy tu tutor. Pregúntame lo que quieras sobre esta lección: vocabulario, pronunciación, gramática, cultura…</div>
			<?php else : ?>
				<?php foreach ( $history as $turn ) :
					$role    = isset( $turn['role'] ) ? $turn['role'] : 'user';
					$content = isset( $turn['content'] ) ? (string) $turn['content'] : '';
					$bubble  = 'assistant' === $role ? 'duo-tutor-msg--assistant' : 'duo-tutor-msg--user';
					?>
					<div class="duo-tutor-msg <?php echo esc_attr( $bubble ); ?>">
						<?php echo nl2br( esc_html( $content ) ); ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<form class="duo-tutor-form">
			<label for="duo-tutor-input" class="screen-reader-text">Mensaje al tutor</label>
			<textarea
				id="duo-tutor-input"
				class="duo-tutor-input"
				rows="2"
				placeholder="Escribe tu pregunta… (Enter envía, Shift+Enter nueva línea)"
				aria-label="Mensaje al tutor"
				required></textarea>
			<button type="submit" class="duo-tutor-send" aria-label="Enviar">
				<span class="duo-tutor-send-label">Enviar</span>
			</button>
			<button type="button" class="duo-tutor-clear" aria-label="Borrar historial">🗑️️</button>
		</form>
		<small class="duo-tutor-status" role="status" aria-live="polite"></small>
		<input type="hidden" class="duo-tutor-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<input type="hidden" class="duo-tutor-ajaxurl" value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" />
		<input type="hidden" class="duo-tutor-course" value="<?php echo esc_attr( $context['course'] ); ?>" />
	</div>
	<button type="button" id="duo-tutor-toggle" class="duo-tutor-toggle" aria-label="Abrir tutor de <?php echo esc_attr( $context['course_label'] ); ?>" aria-expanded="false">
		<span class="duo-tutor-toggle__icon" aria-hidden="true">💬</span>
	</button>
	<?php
}


/**
 * Valida y corrige la respuesta del tutor para evitar combinaciones incorrectas
 * de palabras Tarifit que no están en el vocabulario oficial.
 *
 * @param string $reply Respuesta del modelo
 * @param array  $vocabulary Vocabulario oficial de la lección
 * @return string Respuesta corregida
 */
function wp_amsawal_tutor_validate_vocabulary( $reply, $vocabulary ) {
	if ( empty( $vocabulary ) || empty( $reply ) ) {
		return $reply;
	}

	// Paso 1: corregir combinaciones incorrectas de posesivos
	$combinaciones_invalidas = array(
		'/\bayṯma\s+inu\b/iu' => 'ayṯma',
		'/\byessma\s+inu\b/iu' => 'yessma',
		'/\buma\s+inu\b/iu' => 'uma',
		'/\bwučma\s+inu\b/iu' => 'wučma',
		'/\bgma\s+inu\b/iu'  => 'gma',
		'/\bnečč\s+inu\b/iu' => 'nečč',
	);
	foreach ( $combinaciones_invalidas as $patron => $reemplazo ) {
		$reply = preg_replace( $patron, $reemplazo, $reply );
	}

	// Paso 2: advertir si el modelo usa palabras Tarifit no autorizadas
	$palabras_oficiales = array_keys( $vocabulary );
	$palabras_lower = array();
	foreach ( $palabras_oficiales as $palabra ) {
		$palabras_lower[ mb_strtolower( $palabra ) ] = true;
	}

	preg_match_all('/\b[a-zɛɣḥṛṯčḍṣṭẓäëïöüāēīōū]+\b/iu', $reply, $matches);
	$advertencias = array();
	foreach ($matches[0] as $p) {
		$l = mb_strtolower($p);
		if (mb_strlen($l) > 2 && preg_match('/[ɣḥṛṯčḍṣṭẓɛ]/iu', $l)) {
			if (!isset($palabras_lower[$l])) {
				$advertencias[] = $p;
			}
		}
	}

	if (!empty($advertencias)) {
		$reply .= "\n\n⚠️ Esta respuesta incluye palabras fuera del vocabulario oficial: " . implode(', ', array_unique($advertencias)) . '.';
	}

	return $reply;
}


/* AJAX ──────────────────────────────────────────────────────────────────── */

add_action( 'wp_ajax_wp_amsawal_tutor_ask', 'wp_amsawal_tutor_ajax_ask' );
function wp_amsawal_tutor_ajax_ask() {
	check_ajax_referer( 'wp_amsawal_tutor', '_ajax_nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'No autorizado', WP_AMSAWAL_TEXTDOMAIN ), 403 );
	}
	// Rate-limit: 20 preguntas / minuto por usuario (chat rápido).
	wp_amsawal_rate_limit_or_die( 'tutor_ask', 20, 60 );

	@set_time_limit( 180 );
	@ignore_user_abort( true );

	$user_id = get_current_user_id();
	$message = isset( $_POST['message'] ) ? trim( wp_unslash( $_POST['message'] ) ) : '';
	$course  = isset( $_POST['course'] ) ? sanitize_title( wp_unslash( $_POST['course'] ) ) : '';
	if ( '' === $message ) {
		wp_send_json_error( __( 'Mensaje vacío', WP_AMSAWAL_TEXTDOMAIN ) );
	}
	if ( mb_strlen( $message ) > 1000 ) {
		wp_send_json_error( 'Mensaje demasiado largo (máx 1000 caracteres)' );
	}

	$context = wp_amsawal_tutor_get_context( $course );
	$history = wp_amsawal_tutor_get_history( $user_id, $context['course'] );

	$messages = wp_amsawal_tutor_build_messages( $context, $message, $history );

	$opts = array(
		'model'        => get_option( 'wp_amsawal_ai_model', 'Qwen/Qwen3-Next-80B-A3B-Instruct' ),
		'temperature'  => 0.5,
		'max_tokens'   => 400,
		'timeout'      => 60,
		'messages'     => $messages,
	);
	$raw = wp_amsawal_ai_query( '', $opts );
	if ( is_wp_error( $raw ) ) {
		wp_send_json_error( 'Error del tutor: ' . $raw->get_error_message() );
	}

	$reply = trim( (string) $raw );
	// Limpieza de seguridad por si el modelo emite artefactos:
	// 1) Quitar todos los bloques <think>...</think> (Qwen3 los emite)
	$prev = null;
	while ( $prev !== $reply ) {
		$prev = $reply;
		$reply = (string) preg_replace( '/<think>.*?<\/think>/su', '', $reply );
	}
	// 2) Quitar tokens de chat template al final (<|endoftext|><|im_start|>...)
	$reply = preg_replace( '/<\|.*$/su', '', $reply );
	// 3) Quitar continuación inventada por el modelo (turnos que el modelo añadió)
	$reply = preg_replace( '/\n+\s*(Pregunta|Alumno|Tutor|User|Assistant|System|Human)\s*:.*$/su', '', $reply );
	$reply = trim( $reply );

	// 4) Validador de vocabulario: detectar combinaciones incorrectas de palabras Tarifit
	$reply = wp_amsawal_tutor_validate_vocabulary( $reply, $context['vocabulary'] );

	$history[] = array( 'role' => 'user',      'content' => $message );
	$history[] = array( 'role' => 'assistant', 'content' => $reply );
	wp_amsawal_tutor_save_history( $user_id, $context['course'], $history );

	wp_send_json_success( array(
		'reply'   => $reply,
		'course'  => $context['course'],
		'lesson'  => $context['lesson_title'],
	) );
}


add_action( 'wp_ajax_wp_amsawal_tutor_clear', 'wp_amsawal_tutor_ajax_clear' );
function wp_amsawal_tutor_ajax_clear() {
	check_ajax_referer( 'wp_amsawal_tutor', '_ajax_nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'No autorizado', 403 );
	}
	$user_id = get_current_user_id();
	$course  = isset( $_POST['course'] ) ? sanitize_title( wp_unslash( $_POST['course'] ) ) : '';
	if ( ! $course ) {
		$ctx = wp_amsawal_tutor_get_context( '' );
		$course = $ctx['course'];
	}
	wp_amsawal_tutor_clear_history( $user_id, $course );
	wp_send_json_success();
}
