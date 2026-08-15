<?php
/**
 * WP Amsawal — Abilities API Integration
 *
 * Registers Amsawal capabilities with the WordPress Abilities API (WP 6.9+).
 * Allows AI agents and external systems to discover and execute:
 *   - AI content generation (flashcards, dictation, etc.)
 *   - AI essay evaluation
 *   - AI tutor queries
 *   - Content translation (Tarifit ↔ Spanish/English/French/Arabic)
 *   - Learning analytics queries
 *   - Leaderboard data retrieval
 *
 * @package WP_Amsawal
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WPINC' ) ) {
    die;
}

defined( 'ABSPATH' ) || exit;

// Guard: Abilities API requires WP 6.9+
if ( ! function_exists( 'wp_register_ability' ) ) {
	return;
}

/* ═══════════════════════════════════════════════════════════════
 * CATEGORIES
 * ═══════════════════════════════════════════════════════════════ */

add_action( 'wp_abilities_api_categories_init', 'wp_amsawal_register_ability_categories' );

function wp_amsawal_register_ability_categories() {
	wp_register_ability_category( 'amsawal-learning', array(
		'label'       => __( 'Amsawal Learning', 'wp-amsawal' ),
		'description' => __( 'Learning path, progress, and course operations for the Tamazight learning platform.', 'wp-amsawal' ),
	) );

	wp_register_ability_category( 'amsawal-ai', array(
		'label'       => __( 'Amsawal AI', 'wp-amsawal' ),
		'description' => __( 'AI-powered features: content generation, essay evaluation, tutoring, and adaptive testing.', 'wp-amsawal' ),
	) );

	wp_register_ability_category( 'amsawal-translation', array(
		'label'       => __( 'Amsawal Translation', 'wp-amsawal' ),
		'description' => __( 'Automatic translation of course content between Tamazight (Tarifit), Spanish, English, French, and Arabic.', 'wp-amsawal' ),
	) );

	wp_register_ability_category( 'amsawal-analytics', array(
		'label'       => __( 'Amsawal Analytics', 'wp-amsawal' ),
		'description' => __( 'Quantitative and qualitative learning analytics, engagement metrics, retention data, and AI insights.', 'wp-amsawal' ),
	) );

	wp_register_ability_category( 'amsawal-social', array(
		'label'       => __( 'Amsawal Social', 'wp-amsawal' ),
		'description' => __( 'Leaderboard, gamification data, and social features.', 'wp-amsawal' ),
	) );
}

/* ═══════════════════════════════════════════════════════════════
 * ABILITIES
 * ═══════════════════════════════════════════════════════════════ */

add_action( 'wp_abilities_api_init', 'wp_amsawal_register_abilities' );

function wp_amsawal_register_abilities() {
	/* ── AI: Generate H5P Content ─────────────────────────── */
	wp_register_ability( 'amsawal/generate-content', array(
		'label'               => __( 'Generate H5P Content', 'wp-amsawal' ),
		'description'         => __( 'Generate interactive H5P learning activities (flashcards, dictation, multiple-choice, etc.) for a lesson using AI.', 'wp-amsawal' ),
		'category'            => 'amsawal-ai',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'lesson_id'  => array(
					'type'        => 'integer',
					'description' => __( 'The lesson ID to generate content for.', 'wp-amsawal' ),
				),
				'type'       => array(
					'type'        => 'string',
					'description' => __( 'H5P content type (flashcards, dialogcards, dictation, memory, fill-blanks, mark-the-words, multiple-choice, drag-drop, true-false, speak-the-words, essay, adaptest).', 'wp-amsawal' ),
				),
				'vocabulary' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Optional vocabulary list to inject into AI generation.', 'wp-amsawal' ),
				),
			),
			'required'   => array( 'lesson_id', 'type' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'generated' => array(
					'type'        => 'integer',
					'description' => __( 'Number of H5P items generated.', 'wp-amsawal' ),
				),
				'h5p_ids'   => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => __( 'Created H5P content IDs.', 'wp-amsawal' ),
				),
				'errors'    => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Any errors during generation.', 'wp-amsawal' ),
				),
			),
		),
		'execute_callback'    => 'wp_amsawal_ability_generate_content',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	/* ── AI: Evaluate Essay ───────────────────────────────── */
	wp_register_ability( 'amsawal/evaluate-essay', array(
		'label'               => __( 'Evaluate Essay', 'wp-amsawal' ),
		'description'         => __( 'Evaluate a student essay in Tamazight/Spanish using AI and return feedback, corrections, and a score.', 'wp-amsawal' ),
		'category'            => 'amsawal-ai',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'text' => array(
					'type'        => 'string',
					'description' => __( 'The essay text to evaluate.', 'wp-amsawal' ),
				),
			),
			'required'   => array( 'text' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'feedback'       => array( 'type' => 'string', 'description' => __( 'AI feedback on the essay.', 'wp-amsawal' ) ),
				'corrected_text' => array( 'type' => 'string', 'description' => __( 'Corrected version of the text.', 'wp-amsawal' ) ),
				'score'          => array( 'type' => 'integer', 'description' => __( 'Score from 0 to 100.', 'wp-amsawal' ) ),
				'coins'          => array( 'type' => 'integer', 'description' => __( 'Coins earned for the attempt.', 'wp-amsawal' ) ),
			),
		),
		'execute_callback'    => 'wp_amsawal_ability_evaluate_essay',
		'permission_callback' => function () {
			return is_user_logged_in();
		},
	) );

	/* ── AI: Tutor Chat ──────────────────────────────────── */
	wp_register_ability( 'amsawal/tutor-chat', array(
		'label'               => __( 'AI Tutor Chat', 'wp-amsawal' ),
		'description'         => __( 'Ask the AI tutor a question about the current course content. The tutor has context about the lesson and vocabulary.', 'wp-amsawal' ),
		'category'            => 'amsawal-ai',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'message' => array(
					'type'        => 'string',
					'description' => __( 'The student question or message.', 'wp-amsawal' ),
				),
				'course'  => array(
					'type'        => 'string',
					'description' => __( 'Course slug for context (e.g. "tarifit-1").', 'wp-amsawal' ),
				),
			),
			'required'   => array( 'message' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'reply'   => array( 'type' => 'string', 'description' => __( 'The AI tutor reply.', 'wp-amsawal' ) ),
				'course'  => array( 'type' => 'string', 'description' => __( 'Course context used.', 'wp-amsawal' ) ),
				'lesson'  => array( 'type' => 'string', 'description' => __( 'Lesson context detected.', 'wp-amsawal' ) ),
			),
		),
		'execute_callback'    => 'wp_amsawal_ability_tutor_chat',
		'permission_callback' => function () {
			return is_user_logged_in();
		},
	) );

	/* ── Translation: Translate Post ──────────────────────── */
	wp_register_ability( 'amsawal/translate-post', array(
		'label'               => __( 'Translate Post', 'wp-amsawal' ),
		'description'         => __( 'Translate a WordPress post (lesson content) to a target language using AI. Supports Tamazight, Spanish, English, French, Arabic.', 'wp-amsawal' ),
		'category'            => 'amsawal-translation',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'post_id'     => array(
					'type'        => 'integer',
					'description' => __( 'The post ID to translate.', 'wp-amsawal' ),
				),
				'target_lang' => array(
					'type'        => 'string',
					'description' => __( 'Target language code (es, en, fr, ar, tzm, rif).', 'wp-amsawal' ),
				),
				'force'       => array(
					'type'        => 'boolean',
					'description' => __( 'Force re-translation even if cached version exists.', 'wp-amsawal' ),
				),
			),
			'required'   => array( 'post_id', 'target_lang' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'title'       => array( 'type' => 'string', 'description' => __( 'Translated title.', 'wp-amsawal' ) ),
				'content'     => array( 'type' => 'string', 'description' => __( 'Translated content.', 'wp-amsawal' ) ),
				'vocabulary'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Extracted vocabulary list.', 'wp-amsawal' ),
				),
				'source_lang' => array( 'type' => 'string', 'description' => __( 'Detected source language.', 'wp-amsawal' ) ),
			),
		),
		'execute_callback'    => 'wp_amsawal_ability_translate_post',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	/* ── Translation: Translate Course ────────────────────── */
	wp_register_ability( 'amsawal/translate-course', array(
		'label'               => __( 'Translate Course', 'wp-amsawal' ),
		'description'         => __( 'Translate all lessons in a course to a target language using AI.', 'wp-amsawal' ),
		'category'            => 'amsawal-translation',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'course_id'   => array(
					'type'        => 'integer',
					'description' => __( 'The course (term) ID to translate.', 'wp-amsawal' ),
				),
				'target_lang' => array(
					'type'        => 'string',
					'description' => __( 'Target language code (es, en, fr, ar, tzm, rif).', 'wp-amsawal' ),
				),
				'force'       => array(
					'type'        => 'boolean',
					'description' => __( 'Force re-translation of all lessons.', 'wp-amsawal' ),
				),
			),
			'required'   => array( 'course_id', 'target_lang' ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'integer', 'description' => __( 'Number of lessons translated successfully.', 'wp-amsawal' ) ),
				'errors'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Any errors during translation.', 'wp-amsawal' ),
				),
			),
		),
		'execute_callback'    => 'wp_amsawal_ability_translate_course',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	/* ── Analytics: Engagement Metrics ────────────────────── */
	wp_register_ability( 'amsawal/engagement-metrics', array(
		'label'               => __( 'Get Engagement Metrics', 'wp-amsawal' ),
		'description'         => __( 'Retrieve user engagement metrics: total interactions, active days, accuracy, engagement scores.', 'wp-amsawal' ),
		'category'            => 'amsawal-analytics',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'start_date' => array( 'type' => 'string', 'description' => __( 'Start date (YYYY-MM-DD).', 'wp-amsawal' ) ),
				'end_date'   => array( 'type' => 'string', 'description' => __( 'End date (YYYY-MM-DD).', 'wp-amsawal' ) ),
				'user_ids'   => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => __( 'Optional user IDs to filter by.', 'wp-amsawal' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'Engagement metrics per user: total_interactions, active_days, avg_accuracy, engagement_score.', 'wp-amsawal' ),
		),
		'execute_callback'    => 'wp_amsawal_ability_engagement_metrics',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	/* ── Analytics: Retention Metrics ────────────────────── */
	wp_register_ability( 'amsawal/retention-metrics', array(
		'label'               => __( 'Get Retention Metrics', 'wp-amsawal' ),
		'description'         => __( 'Retrieve user retention data: active users, retention rates, cohort analysis.', 'wp-amsawal' ),
		'category'            => 'amsawal-analytics',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'start_date' => array( 'type' => 'string', 'description' => __( 'Start date (YYYY-MM-DD).', 'wp-amsawal' ) ),
				'end_date'   => array( 'type' => 'string', 'description' => __( 'End date (YYYY-MM-DD).', 'wp-amsawal' ) ),
				'period'     => array( 'type' => 'string', 'description' => __( 'Aggregation period (day, week, month).', 'wp-amsawal' ) ),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'Retention metrics: total_users, active_users, retention_rate, cohort_data.', 'wp-amsawal' ),
		),
		'execute_callback'    => 'wp_amsawal_ability_retention_metrics',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	/* ── Analytics: AI Insights ────────────────────────────── */
	wp_register_ability( 'amsawal/ai-insights', array(
		'label'               => __( 'Get AI Insights', 'wp-amsawal' ),
		'description'         => __( 'Get AI-generated qualitative insights about learning patterns, strengths, weaknesses, and pedagogical recommendations.', 'wp-amsawal' ),
		'category'            => 'amsawal-analytics',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'start_date' => array( 'type' => 'string', 'description' => __( 'Start date (YYYY-MM-DD).', 'wp-amsawal' ) ),
				'end_date'   => array( 'type' => 'string', 'description' => __( 'End date (YYYY-MM-DD).', 'wp-amsawal' ) ),
				'user_id'    => array( 'type' => 'integer', 'description' => __( 'Optional user ID for individual insights.', 'wp-amsawal' ) ),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'AI insights: engagement_assessment, learning_patterns, strengths, weaknesses, pedagogical_recommendations.', 'wp-amsawal' ),
		),
		'execute_callback'    => 'wp_amsawal_ability_ai_insights',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	/* ── Social: Leaderboard ─────────────────────────────── */
	wp_register_ability( 'amsawal/get-leaderboard', array(
		'label'               => __( 'Get Leaderboard', 'wp-amsawal' ),
		'description'         => __( 'Retrieve leaderboard data for a course or overall XP ranking.', 'wp-amsawal' ),
		'category'            => 'amsawal-social',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'type'    => array(
					'type'        => 'string',
					'description' => __( 'Leaderboard type: "monedas" for overall XP, or a course slug.', 'wp-amsawal' ),
				),
				'limit'   => array(
					'type'        => 'integer',
					'description' => __( 'Max entries to return (1-20).', 'wp-amsawal' ),
				),
				'friends' => array(
					'type'        => 'boolean',
					'description' => __( 'Filter to friends-only leaderboard.', 'wp-amsawal' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'array',
			'description' => __( 'Leaderboard entries: id, name, avatar_url, xp, position, is_me.', 'wp-amsawal' ),
		),
		'execute_callback'    => 'wp_amsawal_ability_get_leaderboard',
		'permission_callback' => function () {
			return is_user_logged_in();
		},
	) );

	/* ── Learning: Summary Metrics ────────────────────────── */
	wp_register_ability( 'amsawal/summary-metrics', array(
		'label'               => __( 'Get Summary Metrics', 'wp-amsawal' ),
		'description'         => __( 'Retrieve platform summary: total interactions, unique users, average score, lesson completion rate.', 'wp-amsawal' ),
		'category'            => 'amsawal-analytics',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'start_date' => array( 'type' => 'string', 'description' => __( 'Start date (YYYY-MM-DD).', 'wp-amsawal' ) ),
				'end_date'   => array( 'type' => 'string', 'description' => __( 'End date (YYYY-MM-DD).', 'wp-amsawal' ) ),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'Summary: total_interactions, unique_users, average_score, average_duration, lesson_completion_rate.', 'wp-amsawal' ),
		),
		'execute_callback'    => 'wp_amsawal_ability_summary_metrics',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );
}

/* ═══════════════════════════════════════════════════════════════
 * EXECUTE CALLBACKS
 * ═══════════════════════════════════════════════════════════════ */

/**
 * Generate H5P content for a lesson.
 */
function wp_amsawal_ability_generate_content( $input ) {
	$lesson_id  = absint( $input['lesson_id'] ?? 0 );
	$type       = sanitize_text_field( $input['type'] ?? '' );
	$vocabulary = isset( $input['vocabulary'] ) && is_array( $input['vocabulary'] )
		? array_map( 'sanitize_text_field', $input['vocabulary'] )
		: array();

	if ( ! $lesson_id || ! $type ) {
		return new WP_Error( 'amsawal_missing_params', __( 'lesson_id and type are required.', 'wp-amsawal' ) );
	}

	$context = array(
		'lesson_title' => get_the_title( $lesson_id ),
		'course'      => wp_amsawal_ai_get_lesson_course( $lesson_id ) ?: 'tarifit-1',
		'vocabulary'  => $vocabulary ?: wp_amsawal_ai_get_lesson_vocabulary( $lesson_id ),
	);

	return wp_amsawal_ai_generate_lesson( $lesson_id, $context, get_current_user_id() );
}

/**
 * Evaluate a student essay.
 */
function wp_amsawal_ability_evaluate_essay( $input ) {
	$text = wp_kses_post( $input['text'] ?? '' );

	if ( empty( $text ) ) {
		return new WP_Error( 'amsawal_empty_text', __( 'Essay text is required.', 'wp-amsawal' ) );
	}

	$prompt = wp_amsawal_ai_build_prompt( 'essay', array(
		'lesson_title' => __( 'Free writing', 'wp-amsawal' ),
		'course'       => 'tarifit-1',
		'vocabulary'   => array(),
	) );

	$raw  = wp_amsawal_ai_query( $prompt . "\n\nTexto del estudiante:\n" . $text, array(
		'temperature' => 0.3,
	) );
	$data = wp_amsawal_ai_extract_json( $raw );

	if ( ! $data ) {
		return new WP_Error( 'amsawal_ai_error', __( 'AI could not evaluate the essay.', 'wp-amsawal' ) );
	}

	return array(
		'feedback'       => $data['feedback'] ?? '',
		'corrected_text' => $data['corrected_text'] ?? $text,
		'score'          => absint( $data['score'] ?? 0 ),
		'coins'          => absint( $data['coins'] ?? 0 ),
	);
}

/**
 * AI tutor chat.
 */
function wp_amsawal_ability_tutor_chat( $input ) {
	$message = sanitize_textarea_field( $input['message'] ?? '' );
	$course  = sanitize_text_field( $input['course'] ?? 'tarifit-1' );

	if ( empty( $message ) ) {
		return new WP_Error( 'amsawal_empty_message', __( 'Message is required.', 'wp-amsawal' ) );
	}

	$user_id  = get_current_user_id();
	$context  = wp_amsawal_tutor_get_context( $course, null );
	$history  = wp_amsawal_tutor_get_history( $user_id, $course );
	$messages = wp_amsawal_tutor_build_messages( $context, $message, $history );

	$reply = wp_amsawal_ai_query( '', array(
		'messages'    => $messages,
		'temperature' => 0.5,
		'max_tokens'  => 400,
	) );

	if ( is_wp_error( $reply ) ) {
		return $reply;
	}

	// Limpieza de bloques think y chat-template tokens por si acaso.
	$reply = (string) preg_replace( '/<think>.*?<\/think>/su', '', $reply );
	$reply = (string) preg_replace( '/<\|.*$/su', '', $reply );
	$reply = trim( (string) preg_replace( '/\n+\s*(Pregunta|Alumno|Tutor|User|Assistant|System|Human)\s*:.*$/su', '', $reply ) );

	$history[] = array( 'role' => 'user', 'content' => $message );
	$history[] = array( 'role' => 'assistant', 'content' => $reply );
	wp_amsawal_tutor_save_history( $user_id, $course, $history );

	return array(
		'reply'  => $reply,
		'course' => $course,
		'lesson' => $context['lesson_title'] ?? '',
	);
}

/**
 * Translate a single post.
 */
function wp_amsawal_ability_translate_post( $input ) {
	$post_id     = absint( $input['post_id'] ?? 0 );
	$target_lang = sanitize_text_field( $input['target_lang'] ?? '' );
	$force       = ! empty( $input['force'] );

	if ( ! $post_id || ! $target_lang ) {
		return new WP_Error( 'amsawal_missing_params', __( 'post_id and target_lang are required.', 'wp-amsawal' ) );
	}

	$result = wp_amsawal_translate_post( $post_id, $target_lang, $force );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return $result;
}

/**
 * Translate an entire course.
 */
function wp_amsawal_ability_translate_course( $input ) {
	$course_id   = absint( $input['course_id'] ?? 0 );
	$target_lang = sanitize_text_field( $input['target_lang'] ?? '' );
	$force       = ! empty( $input['force'] );

	if ( ! $course_id || ! $target_lang ) {
		return new WP_Error( 'amsawal_missing_params', __( 'course_id and target_lang are required.', 'wp-amsawal' ) );
	}

	return wp_amsawal_translate_course( $course_id, $target_lang, $force );
}

/**
 * Get engagement metrics.
 */
function wp_amsawal_ability_engagement_metrics( $input ) {
	$start_date = sanitize_text_field( $input['start_date'] ?? date( 'Y-m-d', strtotime( '-30 days' ) ) );
	$end_date   = sanitize_text_field( $input['end_date'] ?? date( 'Y-m-d' ) );
	$user_ids   = isset( $input['user_ids'] ) && is_array( $input['user_ids'] )
		? array_map( 'absint', $input['user_ids'] )
		: array();

	return wp_amsawal_get_user_engagement_metrics( $start_date, $end_date, $user_ids );
}

/**
 * Get retention metrics.
 */
function wp_amsawal_ability_retention_metrics( $input ) {
	$start_date = sanitize_text_field( $input['start_date'] ?? date( 'Y-m-d', strtotime( '-90 days' ) ) );
	$end_date   = sanitize_text_field( $input['end_date'] ?? date( 'Y-m-d' ) );
	$period     = sanitize_text_field( $input['period'] ?? 'week' );

	return wp_amsawal_get_retention_metrics( $start_date, $end_date, $period );
}

/**
 * Get AI-powered qualitative insights.
 */
function wp_amsawal_ability_ai_insights( $input ) {
	$start_date = sanitize_text_field( $input['start_date'] ?? date( 'Y-m-d', strtotime( '-30 days' ) ) );
	$end_date   = sanitize_text_field( $input['end_date'] ?? date( 'Y-m-d' ) );
	$user_id    = isset( $input['user_id'] ) ? absint( $input['user_id'] ) : null;

	return wp_amsawal_get_ai_insights( $start_date, $end_date, $user_id );
}

/**
 * Get leaderboard data.
 */
function wp_amsawal_ability_get_leaderboard( $input ) {
	$type    = sanitize_text_field( $input['type'] ?? 'monedas' );
	$limit   = min( absint( $input['limit'] ?? 10 ), 20 );
	$friends = ! empty( $input['friends'] );

	return wp_amsawal_leaderboard_data( $type, $limit, $friends );
}

/**
 * Get summary metrics.
 */
function wp_amsawal_ability_summary_metrics( $input ) {
	$start_date = sanitize_text_field( $input['start_date'] ?? date( 'Y-m-d', strtotime( '-30 days' ) ) );
	$end_date   = sanitize_text_field( $input['end_date'] ?? date( 'Y-m-d' ) );

	return wp_amsawal_get_summary_metrics( $start_date, $end_date );
}
