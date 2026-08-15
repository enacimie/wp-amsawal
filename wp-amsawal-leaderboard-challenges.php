<?php
/**
 * F17-4: Leaderboard Challenges
 * Weekly and monthly competitions
 */

if (!defined('ABSPATH')) exit;

// Get weekly leaderboard
function amsawal_get_weekly_leaderboard($limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    $start_of_week = date('Y-m-d', strtotime('monday this week'));
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            user_id,
            u.display_name,
            COUNT(*) as activity_count,
            SUM(CASE WHEN interaction_type = 'lesson_complete' THEN 1 ELSE 0 END) as lessons_completed,
            SUM(CASE WHEN interaction_type = 'quiz_complete' THEN 1 ELSE 0 END) as quizzes_completed
        FROM $table t
        JOIN {$wpdb->users} u ON t.user_id = u.ID
        WHERE created_at >= %s
        GROUP BY user_id
        ORDER BY activity_count DESC
        LIMIT %d",
        $start_of_week, $limit
    ));
}

// Get monthly leaderboard
function amsawal_get_monthly_leaderboard($limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    $start_of_month = date('Y-m-01');
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            user_id,
            u.display_name,
            COUNT(*) as activity_count,
            SUM(CASE WHEN interaction_type = 'lesson_complete' THEN 1 ELSE 0 END) as lessons_completed,
            SUM(CASE WHEN interaction_type = 'quiz_complete' THEN 1 ELSE 0 END) as quizzes_completed
        FROM $table t
        JOIN {$wpdb->users} u ON t.user_id = u.ID
        WHERE created_at >= %s
        GROUP BY user_id
        ORDER BY activity_count DESC
        LIMIT %d",
        $start_of_month, $limit
    ));
}

// Get user rank
function amsawal_get_user_rank($user_id, $period = 'weekly') {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    if ($period === 'weekly') {
        $start_date = date('Y-m-d', strtotime('monday this week'));
    } else {
        $start_date = date('Y-m-01');
    }
    
    // Get user's activity count
    $user_activity = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND created_at >= %s",
        $user_id, $start_date
    ));
    
    // Get rank
    $rank = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) + 1 FROM (
            SELECT user_id, COUNT(*) as count 
            FROM $table 
            WHERE created_at >= %s 
            GROUP BY user_id 
            HAVING count > %d
        ) as ranked",
        $start_date, $user_activity
    ));
    
    return intval($rank);
}

// AJAX handler
add_action('wp_ajax_amsawal_get_leaderboard', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }

    $period = sanitize_text_field($_POST['period'] ?? 'weekly');
    $limit = absint($_POST['limit'] ?? 10);
    
    if ($period === 'monthly') {
        $leaderboard = amsawal_get_monthly_leaderboard($limit);
    } else {
        $leaderboard = amsawal_get_weekly_leaderboard($limit);
    }
    
    $user_rank = 0;
    if (is_user_logged_in()) {
        $user_rank = amsawal_get_user_rank(get_current_user_id(), $period);
    }
    
    wp_send_json_success([
        'leaderboard' => $leaderboard,
        'user_rank' => $user_rank
    ]);
});
