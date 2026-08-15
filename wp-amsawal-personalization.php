<?php
if (!defined('ABSPATH')) exit;

function amsawal_get_user_level($user_id) {
    if (function_exists('gamipress_get_user_rank_id')) {
        $rank_id = gamipress_get_user_rank_id($user_id, 'nivel');
        if ($rank_id) {
            $priority = get_post_meta($rank_id, '_gamipress_priority', true);
            if ($priority) {
                return (int) $priority;
            }
        }
    }
    return 1;
}

function amsawal_get_learning_profile($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) as total_activities,
            COUNT(CASE WHEN interaction_type = 'lesson_complete' THEN 1 END) as lessons_completed,
            COUNT(CASE WHEN interaction_type = 'quiz_complete' THEN 1 END) as quizzes_completed,
            AVG(CASE WHEN interaction_type = 'quiz_complete' THEN JSON_EXTRACT(result_data, '$.score') END) as avg_score
        FROM $table WHERE user_id = %d",
        $user_id
    ));
    $preferred_time = $wpdb->get_var($wpdb->prepare(
        "SELECT HOUR(created_at) as hour, COUNT(*) as count
         FROM $table WHERE user_id = %d
         GROUP BY HOUR(created_at) ORDER BY count DESC LIMIT 1",
        $user_id
    ));
    $first_activity = $wpdb->get_var($wpdb->prepare(
        "SELECT created_at FROM $table WHERE user_id = %d ORDER BY created_at ASC LIMIT 1",
        $user_id
    ));
    $days_active = $first_activity ? ceil((time() - strtotime($first_activity)) / 86400) : 0;
    $learning_pace = $days_active > 0 ? $stats->lessons_completed / $days_active : 0;
    return [
        'total_activities' => intval($stats->total_activities),
        'lessons_completed' => intval($stats->lessons_completed),
        'quizzes_completed' => intval($stats->quizzes_completed),
        'avg_score' => floatval($stats->avg_score),
        'preferred_hour' => intval($preferred_time),
        'learning_pace' => round($learning_pace, 2),
        'level' => amsawal_get_user_level($user_id),
        'xp' => (int) get_user_meta($user_id, '_amsawal_xp', true) ?: 0,
        'streak' => (int) get_user_meta($user_id, '_wp_amsawal_streak_days', true) ?: 0
    ];
}

function amsawal_get_personalized_recommendations($user_id) {
    $profile = amsawal_get_learning_profile($user_id);
    $recommendations = [];
    $current_hour = intval(date('H'));
    if (abs($current_hour - $profile['preferred_hour']) <= 2) {
        $recommendations[] = ['type' => 'timing', 'message' => '\u00a1Es tu hora pico de aprendizaje! Aprovecha ahora.'];
    }
    if ($profile['streak'] >= 3 && $profile['streak'] < 7) {
        $recommendations[] = ['type' => 'streak', 'message' => '\u00a1Llevas ' . $profile['streak'] . ' d\u00edas! Completa 7 para un logro especial.'];
    }
    if ($profile['avg_score'] < 70 && $profile['quizzes_completed'] > 5) {
        $recommendations[] = ['type' => 'practice', 'message' => 'Te recomendamos repasar lecciones anteriores para fortalecer tu base.'];
    }
    $xp_for_next_level = $profile['level'] * 100;
    $xp_needed = $xp_for_next_level - ($profile['xp'] % $xp_for_next_level);
    if ($xp_needed <= 50) {
        $recommendations[] = ['type' => 'level_up', 'message' => '\u00a1Solo te faltan ' . $xp_needed . ' XP para subir de nivel!'];
    }
    return $recommendations;
}

add_action('wp_ajax_amsawal_get_profile', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesi\u00f3n');
    }
    $user_id = get_current_user_id();
    $profile = amsawal_get_learning_profile($user_id);
    $recommendations = amsawal_get_personalized_recommendations($user_id);
    wp_send_json_success(['profile' => $profile, 'recommendations' => $recommendations]);
});
