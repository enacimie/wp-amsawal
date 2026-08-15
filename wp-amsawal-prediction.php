<?php
/**
 * F16-5: Learning Pace Estimation
 * Estimates course completion date using linear pace projection from historical data
 */

if (!defined('ABSPATH')) exit;

function amsawal_estimate_completion($user_id, $course_id) {
    global $wpdb;
    
    // Get course structure
    $lessons = get_posts([
        'post_type' => 'page',
        'post_parent' => $course_id,
        'numberposts' => -1,
        'fields' => 'ids'
    ]);
    
    $total_lessons = count($lessons);
    
    if ($total_lessons === 0) {
        return new WP_Error('no_lessons', 'Curso sin lecciones');
    }
    
    // Get user's completed lessons
    $completed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT JSON_EXTRACT(result_data, '$.content_id')) 
         FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE user_id = %d AND interaction_type = 'lesson_complete' AND JSON_EXTRACT(result_data, '$.content_id') IN (" . implode(',', array_map('absint', $lessons)) . ")",
        $user_id
    ));
    
    // Get user's activity history
    $activity = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(created_at) as date, COUNT(*) as count 
         FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE user_id = %d AND created_at >= DATE_SUB(%s, INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY date DESC",
        $user_id,
        current_time('Y-m-d')
    ));
    
    // Calculate average daily progress
    $total_days_active = count($activity);
    $total_lessons_completed = $completed;
    
    if ($total_days_active === 0) {
        return [
            'completion_percentage' => 0,
            'estimated_days_remaining' => null,
            'estimated_completion_date' => null,
            'daily_pace' => 0,
            'recommendation' => '¡Comienza tu primera lección hoy!'
        ];
    }
    
    $daily_pace = $total_lessons_completed / $total_days_active;
    $remaining_lessons = $total_lessons - $completed;
    $estimated_days = $daily_pace > 0 ? ceil($remaining_lessons / $daily_pace) : null;
    
    $completion_date = null;
    if ($estimated_days) {
        $completion_date = date('Y-m-d', strtotime("+{$estimated_days} days"));
    }
    
    // Generate recommendation
    $recommendation = '';
    if ($daily_pace < 1) {
        $recommendation = 'Intenta completar al menos 1 lección diaria para mantener el ritmo.';
    } elseif ($daily_pace < 2) {
        $recommendation = '¡Buen ritmo! Sigue así para completar el curso pronto.';
    } else {
        $recommendation = '¡Excelente progreso! Vas muy bien encaminado.';
    }
    
    return [
        'completion_percentage' => round(($completed / $total_lessons) * 100, 2),
        'completed_lessons' => $completed,
        'total_lessons' => $total_lessons,
        'estimated_days_remaining' => $estimated_days,
        'estimated_completion_date' => $completion_date,
        'daily_pace' => round($daily_pace, 2),
        'recommendation' => $recommendation
    ];
}

// AJAX handler
add_action('wp_ajax_amsawal_get_progress_estimateion', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $course_id = absint($_POST['course_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $estimateion = amsawal_estimate_completion($user_id, $course_id);
    
    if (is_wp_error($estimateion)) {
        wp_send_json_error($estimateion->get_error_message());
    }
    
    wp_send_json_success($estimateion);
});
