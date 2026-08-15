<?php
/**
 * F18-5: Content Analytics and Insights
 * Track which content performs best
 */

if (!defined('ABSPATH')) exit;

// Get lesson performance metrics
function amsawal_get_lesson_metrics($lesson_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    $metrics = new stdClass();
    
    // Total starts
    $metrics->starts = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE content_id = %d AND interaction_type = 'lesson_start'",
        $lesson_id
    )));
    
    // Total completions
    $metrics->completions = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE content_id = %d AND interaction_type = 'lesson_complete'",
        $lesson_id
    )));
    
    // Completion rate
    $metrics->completion_rate = $metrics->starts > 0 ? round(($metrics->completions / $metrics->starts) * 100, 2) : 0;
    
    // Average quiz score
    $metrics->avg_quiz_score = floatval($wpdb->get_var($wpdb->prepare(
        "SELECT AVG(JSON_EXTRACT(result_data, '$.score')) 
         FROM $table 
         WHERE content_id = %d AND interaction_type = 'quiz_complete'",
        $lesson_id
    )));
    
    // Time spent (if tracked)
    $metrics->avg_time_spent = floatval($wpdb->get_var($wpdb->prepare(
        "SELECT AVG(JSON_EXTRACT(result_data, '$.time_spent')) 
         FROM $table 
         WHERE content_id = %d AND interaction_type = 'lesson_complete'",
        $lesson_id
    )));
    
    return $metrics;
}

// Get top performing lessons
function amsawal_get_top_lessons($course_id, $limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            t.content_id AS lesson_id,
            p.post_title,
            COUNT(CASE WHEN t.interaction_type = 'lesson_start' THEN 1 END) as starts,
            COUNT(CASE WHEN t.interaction_type = 'lesson_complete' THEN 1 END) as completions,
            ROUND(COUNT(CASE WHEN t.interaction_type = 'lesson_complete' THEN 1 END) / 
                  NULLIF(COUNT(CASE WHEN t.interaction_type = 'lesson_start' THEN 1 END), 0) * 100, 2) as completion_rate
        FROM $table t
        JOIN {$wpdb->posts} p ON t.content_id = p.ID
        WHERE p.post_parent = %d OR p.ID = %d
        GROUP BY t.content_id, p.post_title
        ORDER BY completions DESC
        LIMIT %d",
        $course_id, $course_id, $limit
    ));
}

// Get struggling lessons (low completion rate)
function amsawal_get_struggling_lessons($course_id, $min_starts = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            t.content_id AS lesson_id,
            p.post_title,
            COUNT(CASE WHEN t.interaction_type = 'lesson_start' THEN 1 END) as starts,
            COUNT(CASE WHEN t.interaction_type = 'lesson_complete' THEN 1 END) as completions,
            ROUND(COUNT(CASE WHEN t.interaction_type = 'lesson_complete' THEN 1 END) / 
                  NULLIF(COUNT(CASE WHEN t.interaction_type = 'lesson_start' THEN 1 END), 0) * 100, 2) as completion_rate
        FROM $table t
        JOIN {$wpdb->posts} p ON t.content_id = p.ID
        WHERE p.post_parent = %d OR p.ID = %d
        GROUP BY t.content_id, p.post_title
        HAVING starts >= %d AND completion_rate < 50
        ORDER BY completion_rate ASC",
        $course_id, $course_id, $min_starts
    ));
}

// AJAX handler
add_action('wp_ajax_amsawal_get_content_analytics', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $course_id = absint($_POST['course_id'] ?? 0);
    
    if ($lesson_id) {
        $metrics = amsawal_get_lesson_metrics($lesson_id);
        wp_send_json_success($metrics);
    } elseif ($course_id) {
        $top = amsawal_get_top_lessons($course_id);
        $struggling = amsawal_get_struggling_lessons($course_id);
        wp_send_json_success(['top' => $top, 'struggling' => $struggling]);
    } else {
        wp_send_json_error('ID requerido');
    }
});
