<?php
/**
 * F17-2: Challenges System
 * Create and participate in challenges with friends
 */

if (!defined('ABSPATH')) exit;

// Database table for challenges
function amsawal_create_challenges_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_challenges';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        creator_id BIGINT UNSIGNED NOT NULL,
        challenge_type VARCHAR(50) NOT NULL,
        target_value INT NOT NULL DEFAULT 1,
        duration_days INT NOT NULL DEFAULT 7,
        status ENUM('active', 'completed', 'expired') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ends_at DATETIME,
        PRIMARY KEY (id),
        KEY creator_id (creator_id),
        KEY status (status)
    ) $charset;";
    
    $participants_table = $wpdb->prefix . 'amsawal_challenge_participants';
    $sql2 = "CREATE TABLE IF NOT EXISTS $participants_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        challenge_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        progress INT DEFAULT 0,
        completed TINYINT(1) DEFAULT 0,
        completed_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY challenge_user (challenge_id, user_id),
        KEY challenge_id (challenge_id),
        KEY user_id (user_id)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);
}

add_action('after_switch_theme', 'amsawal_create_challenges_table');
amsawal_create_challenges_table();

// Create challenge
function amsawal_create_challenge($creator_id, $challenge_type, $target_value, $duration_days, $participant_ids = []) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_challenges';
    $participants_table = $wpdb->prefix . 'amsawal_challenge_participants';
    
    $ends_at = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
    
    $wpdb->insert($table, [
        'creator_id' => $creator_id,
        'challenge_type' => $challenge_type,
        'target_value' => $target_value,
        'duration_days' => $duration_days,
        'status' => 'active',
        'ends_at' => $ends_at
    ], ['%d', '%s', '%d', '%d', '%s', '%s']);
    
    $challenge_id = $wpdb->insert_id;
    
    // Add participants
    $all_participants = array_unique(array_merge([$creator_id], $participant_ids));
    
    foreach ($all_participants as $user_id) {
        $wpdb->insert($participants_table, [
            'challenge_id' => $challenge_id,
            'user_id' => $user_id,
            'progress' => 0,
            'completed' => 0
        ], ['%d', '%d', '%d', '%d']);
        
        // Send notification
        if ($user_id !== $creator_id) {
            amsawal_send_notification($user_id, 'challenge_invitation', [
                'from_user_id' => $creator_id,
                'challenge_id' => $challenge_id,
                'message' => get_user_by('id', $creator_id)->display_name . ' te invitó a un desafío'
            ]);
        }
    }
    
    return $challenge_id;
}

// Update challenge progress
function amsawal_update_challenge_progress($challenge_id, $user_id, $progress) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_challenge_participants';
    $challenges_table = $wpdb->prefix . 'amsawal_challenges';
    
    // Get challenge target
    $challenge = $wpdb->get_row($wpdb->prepare(
        "SELECT target_value FROM $challenges_table WHERE id = %d AND status = 'active'",
        $challenge_id
    ));
    
    if (!$challenge) {
        return new WP_Error('invalid_challenge', 'Desafío no válido o no activo');
    }
    
    $completed = $progress >= $challenge->target_value ? 1 : 0;
    
    $wpdb->update($table,
        [
            'progress' => $progress,
            'completed' => $completed,
            'completed_at' => $completed ? current_time('mysql') : null
        ],
        ['challenge_id' => $challenge_id, 'user_id' => $user_id],
        ['%d', '%d', '%s'],
        ['%d', '%d']
    );
    
    // Award XP if completed
    if ($completed) {
        amsawal_award_xp($user_id, 50, 'challenge_completed');
    }
    
    return true;
}

// Get active challenges
function amsawal_get_active_challenges($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_challenges';
    $participants_table = $wpdb->prefix . 'amsawal_challenge_participants';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, p.progress, p.completed,
         (SELECT COUNT(*) FROM $participants_table WHERE challenge_id = c.id) as total_participants,
         (SELECT COUNT(*) FROM $participants_table WHERE challenge_id = c.id AND completed = 1) as completed_count
         FROM $table c
         JOIN $participants_table p ON c.id = p.challenge_id
         WHERE p.user_id = %d AND c.status = 'active'
         ORDER BY c.created_at DESC",
        $user_id
    ));
}

// AJAX handlers
add_action('wp_ajax_amsawal_create_challenge', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $challenge_type = sanitize_text_field($_POST['type'] ?? '');
    $target_value = absint($_POST['target'] ?? 1);
    $duration_days = absint($_POST['duration'] ?? 7);
    $participant_ids = array_map('absint', $_POST['participants'] ?? []);
    
    $creator_id = get_current_user_id();
    
    $challenge_id = amsawal_create_challenge($creator_id, $challenge_type, $target_value, $duration_days, $participant_ids);
    
    wp_send_json_success(['challenge_id' => $challenge_id]);
});

add_action('wp_ajax_amsawal_get_challenges', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $user_id = get_current_user_id();
    $challenges = amsawal_get_active_challenges($user_id);
    
    wp_send_json_success($challenges);
});

// Cron: expire challenges past their end date
function amsawal_expire_challenges() {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_challenges';
    $wpdb->query($wpdb->prepare(
        "UPDATE $table SET status = 'expired' WHERE status = 'active' AND ends_at < %s",
        current_time('mysql')
    ));
}
add_action('amsawal_daily_cron', 'amsawal_expire_challenges');

// Hook: auto-update challenge progress on lesson completion
add_action('amsawal_lesson_complete', function($user_id, $data) {
    global $wpdb;
    $pc = $wpdb->prefix . 'amsawal_challenge_participants';
    $ch = $wpdb->prefix . 'amsawal_challenges';
    $active = $wpdb->get_results($wpdb->prepare(
        "SELECT pc.challenge_id, pc.progress, c.target_value FROM $pc pc
         JOIN $ch c ON pc.challenge_id = c.id
         WHERE pc.user_id = %d AND c.status = 'active' AND c.challenge_type = 'lessons'",
        $user_id
    ) );
    foreach ($active as $row) {
        amsawal_update_challenge_progress($row->challenge_id, $user_id, $row->progress + 1);
    }
}, 10, 2);

add_action('amsawal_xp_awarded', function($user_id, $amount, $reason) {
    global $wpdb;
    $pc = $wpdb->prefix . 'amsawal_challenge_participants';
    $ch = $wpdb->prefix . 'amsawal_challenges';
    $active = $wpdb->get_results($wpdb->prepare(
        "SELECT pc.challenge_id, pc.progress, c.target_value FROM $pc pc
         JOIN $ch c ON pc.challenge_id = c.id
         WHERE pc.user_id = %d AND c.status = 'active' AND c.challenge_type = 'xp'",
        $user_id
    ));
    foreach ($active as $row) {
        amsawal_update_challenge_progress($row->challenge_id, $user_id, $row->progress + $amount);
    }
}, 10, 3);
