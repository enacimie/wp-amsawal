<?php
/**
 * F16-1: AI Essay Evaluation System
 */

if (!defined('ABSPATH')) exit;

function amsawal_evaluate_essay($essay_text, $lesson_id, $user_id) {
    if (empty($essay_text) || strlen($essay_text) < 50) {
        return new WP_Error('too_short', 'El ensayo es demasiado corto (mínimo 50 caracteres)');
    }
    
    $lesson = get_post($lesson_id);
    if (!$lesson) {
        return new WP_Error('invalid_lesson', 'Lección no válida');
    }
    
    $prompt = sprintf(
        "Evalúa el siguiente ensayo de un estudiante de Tamazight (Tarifit).\n\nTema de la lección: %s\n\nEnsayo del estudiante:\n%s\n\nProporciona:\n1. Puntuación general (0-100)\n2. Gramática y ortografía (0-100)\n3. Vocabulario (0-100)\n4. Coherencia y estructura (0-100)\n5. Comentarios constructivos\n6. Sugerencias de mejora\n\nResponde en formato JSON:\n{\"overall_score\": 0-100, \"grammar_score\": 0-100, \"vocabulary_score\": 0-100, \"structure_score\": 0-100, \"feedback\": \"comentarios en español\", \"suggestions\": [\"sugerencia 1\", \"sugerencia 2\"]}",
        $lesson->post_title,
        $essay_text
    );
    
    $response = wp_amsawal_ai_query($prompt);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $evaluation = json_decode($response, true);
    
    if (!$evaluation) {
        return new WP_Error('parse_error', 'Error al procesar la evaluación');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_qualitative_analysis';
    
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'analysis_type' => 'essay_evaluation',
        'content' => $essay_text,
        'ai_response' => wp_json_encode($evaluation),
        'created_at' => current_time('mysql')
    ], ['%d', '%s', '%s', '%s', '%s']);
    
    $xp_earned = intval($evaluation['overall_score'] / 10);
    amsawal_award_xp($user_id, $xp_earned, 'essay_evaluation');

    do_action( 'wp_amsawal_essay_evaluated', $user_id, $essay_text, $evaluation );

    return $evaluation;
}

add_action('wp_ajax_amsawal_evaluate_essay', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $essay = sanitize_textarea_field($_POST['essay'] ?? '');
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $evaluation = amsawal_evaluate_essay($essay, $lesson_id, $user_id);
    
    if (is_wp_error($evaluation)) {
        wp_send_json_error($evaluation->get_error_message());
    }
    
    wp_send_json_success($evaluation);
});
