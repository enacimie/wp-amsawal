#!/usr/bin/env python3
"""Fase 17: Social Features - Amigos, desafíos y mensajería"""

def apply_f17_1_friends_system():
    """F17-1: Friends system"""
    friends_code = """<?php
/**
 * F17-1: Friends System
 * Add, remove, and manage friends
 */

if (!defined('ABSPATH')) exit;

// Database table for friends
function amsawal_create_friends_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_friends';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        friend_id BIGINT UNSIGNED NOT NULL,
        status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_friend (user_id, friend_id),
        KEY user_id (user_id),
        KEY friend_id (friend_id)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('after_switch_theme', 'amsawal_create_friends_table');
amsawal_create_friends_table();

// Send friend request
function amsawal_send_friend_request($user_id, $friend_id) {
    if ($user_id === $friend_id) {
        return new WP_Error('self_friend', 'No puedes enviarte una solicitud a ti mismo');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_friends';
    
    // Check if already friends or pending
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE (user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)",
        $user_id, $friend_id, $friend_id, $user_id
    ));
    
    if ($existing) {
        return new WP_Error('already_exists', 'Ya existe una solicitud o son amigos');
    }
    
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'friend_id' => $friend_id,
        'status' => 'pending'
    ]);
    
    // Send notification
    amsawal_send_notification($friend_id, 'friend_request', [
        'from_user_id' => $user_id,
        'message' => get_user_by('id', $user_id)->display_name . ' te envió una solicitud de amistad'
    ]);
    
    return true;
}

// Accept friend request
function amsawal_accept_friend_request($user_id, $friend_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_friends';
    
    $updated = $wpdb->update($table,
        ['status' => 'accepted', 'updated_at' => current_time('mysql')],
        ['user_id' => $friend_id, 'friend_id' => $user_id, 'status' => 'pending'],
        ['%s', '%s'],
        ['%d', '%d', '%s']
    );
    
    if ($updated === false) {
        return new WP_Error('update_failed', 'Error al aceptar la solicitud');
    }
    
    // Send notification
    amsawal_send_notification($friend_id, 'friend_accepted', [
        'from_user_id' => $user_id,
        'message' => get_user_by('id', $user_id)->display_name . ' aceptó tu solicitud de amistad'
    ]);
    
    return true;
}

// Remove friend
function amsawal_remove_friend($user_id, $friend_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_friends';
    
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table WHERE (user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)",
        $user_id, $friend_id, $friend_id, $user_id
    ));
    
    return true;
}

// Get friends list
function amsawal_get_friends($user_id, $status = 'accepted') {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_friends';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, u.user_email, f.status, f.created_at
         FROM $table f
         JOIN {$wpdb->users} u ON (f.friend_id = u.ID AND f.user_id = %d) OR (f.user_id = u.ID AND f.friend_id = %d)
         WHERE f.status = %s
         ORDER BY f.updated_at DESC",
        $user_id, $user_id, $status
    ));
}

// AJAX handlers
add_action('wp_ajax_amsawal_send_friend_request', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $friend_id = absint($_POST['friend_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $result = amsawal_send_friend_request($user_id, $friend_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Solicitud enviada');
});

add_action('wp_ajax_amsawal_accept_friend_request', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $friend_id = absint($_POST['friend_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $result = amsawal_accept_friend_request($user_id, $friend_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Amigo añadido');
});

add_action('wp_ajax_amsawal_remove_friend', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $friend_id = absint($_POST['friend_id'] ?? 0);
    $user_id = get_current_user_id();
    
    amsawal_remove_friend($user_id, $friend_id);
    wp_send_json_success('Amigo eliminado');
});

add_action('wp_ajax_amsawal_get_friends', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $user_id = get_current_user_id();
    $friends = amsawal_get_friends($user_id);
    
    wp_send_json_success($friends);
});
"""
    
    with open('wp-amsawal-friends.php', 'w', encoding='utf-8') as f:
        f.write(friends_code)
    print("✅ F17-1: Friends system created")
    return True

def apply_f17_2_challenges_system():
    """F17-2: Challenges system"""
    challenges_code = """<?php
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
    ]);
    
    $challenge_id = $wpdb->insert_id;
    
    // Add participants
    $all_participants = array_unique(array_merge([$creator_id], $participant_ids));
    
    foreach ($all_participants as $user_id) {
        $wpdb->insert($participants_table, [
            'challenge_id' => $challenge_id,
            'user_id' => $user_id,
            'progress' => 0,
            'completed' => 0
        ]);
        
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
"""
    
    with open('wp-amsawal-challenges.php', 'w', encoding='utf-8') as f:
        f.write(challenges_code)
    print("✅ F17-2: Challenges system created")
    return True

def apply_f17_3_messaging_system():
    """F17-3: Messaging system"""
    messaging_code = """<?php
/**
 * F17-3: Messaging System
 * Send and receive messages between friends
 */

if (!defined('ABSPATH')) exit;

// Database table for messages
function amsawal_create_messages_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_messages';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sender_id BIGINT UNSIGNED NOT NULL,
        receiver_id BIGINT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY sender_id (sender_id),
        KEY receiver_id (receiver_id),
        KEY is_read (is_read)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('after_switch_theme', 'amsawal_create_messages_table');
amsawal_create_messages_table();

// Send message
function amsawal_send_message($sender_id, $receiver_id, $message) {
    if (empty($message) || strlen($message) > 1000) {
        return new WP_Error('invalid_message', 'Mensaje no válido (1-1000 caracteres)');
    }
    
    // Check if they are friends
    if (!amsawal_are_friends($sender_id, $receiver_id)) {
        return new WP_Error('not_friends', 'Solo puedes enviar mensajes a tus amigos');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_messages';
    
    $wpdb->insert($table, [
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'message' => sanitize_textarea_field($message),
        'is_read' => 0
    ]);
    
    // Send notification
    amsawal_send_notification($receiver_id, 'new_message', [
        'from_user_id' => $sender_id,
        'message_preview' => mb_substr($message, 0, 50)
    ]);
    
    return true;
}

// Check if users are friends
function amsawal_are_friends($user_id, $friend_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_friends';
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table 
         WHERE ((user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)) 
         AND status = 'accepted'",
        $user_id, $friend_id, $friend_id, $user_id
    ));
    
    return $result !== null;
}

// Get conversations
function amsawal_get_conversations($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_messages';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            CASE 
                WHEN sender_id = %d THEN receiver_id 
                ELSE sender_id 
            END as other_user_id,
            u.display_name,
            MAX(created_at) as last_message_at,
            (SELECT message FROM $table WHERE (sender_id = m.sender_id AND receiver_id = m.receiver_id) OR (sender_id = m.receiver_id AND receiver_id = m.sender_id) ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT COUNT(*) FROM $table WHERE receiver_id = %d AND sender_id = other_user_id AND is_read = 0) as unread_count
        FROM $table m
        JOIN {$wpdb->users} u ON u.ID = other_user_id
        WHERE sender_id = %d OR receiver_id = %d
        GROUP BY other_user_id
        ORDER BY last_message_at DESC",
        $user_id, $user_id, $user_id, $user_id
    ));
}

// Get messages from conversation
function amsawal_get_messages($user_id, $other_user_id, $limit = 50) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_messages';
    
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table 
         WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d)
         ORDER BY created_at DESC 
         LIMIT %d",
        $user_id, $other_user_id, $other_user_id, $user_id, $limit
    ));
    
    // Mark as read
    $wpdb->update($table,
        ['is_read' => 1],
        ['receiver_id' => $user_id, 'sender_id' => $other_user_id, 'is_read' => 0],
        ['%d'],
        ['%d', '%d', '%d']
    );
    
    return array_reverse($messages);
}

// AJAX handlers
add_action('wp_ajax_amsawal_send_message', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $receiver_id = absint($_POST['receiver_id'] ?? 0);
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $sender_id = get_current_user_id();
    
    $result = amsawal_send_message($sender_id, $receiver_id, $message);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Mensaje enviado');
});

add_action('wp_ajax_amsawal_get_conversations', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $user_id = get_current_user_id();
    $conversations = amsawal_get_conversations($user_id);
    
    wp_send_json_success($conversations);
});

add_action('wp_ajax_amsawal_get_messages', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $other_user_id = absint($_POST['other_user_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $messages = amsawal_get_messages($user_id, $other_user_id);
    
    wp_send_json_success($messages);
});
"""
    
    with open('wp-amsawal-messaging.php', 'w', encoding='utf-8') as f:
        f.write(messaging_code)
    print("✅ F17-3: Messaging system created")
    return True

def apply_f17_4_leaderboard_challenges():
    """F17-4: Leaderboard challenges"""
    leaderboard_code = """<?php
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
            SUM(CASE WHEN event_type = 'lesson_complete' THEN 1 ELSE 0 END) as lessons_completed,
            SUM(CASE WHEN event_type = 'quiz_complete' THEN 1 ELSE 0 END) as quizzes_completed
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
            SUM(CASE WHEN event_type = 'lesson_complete' THEN 1 ELSE 0 END) as lessons_completed,
            SUM(CASE WHEN event_type = 'quiz_complete' THEN 1 ELSE 0 END) as quizzes_completed
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
"""
    
    with open('wp-amsawal-leaderboard-challenges.php', 'w', encoding='utf-8') as f:
        f.write(leaderboard_code)
    print("✅ F17-4: Leaderboard challenges created")
    return True

def apply_f17_5_social_notifications():
    """F17-5: Social notifications system"""
    notifications_code = """<?php
/**
 * F17-5: Social Notifications System
 * Manage notifications for social features
 */

if (!defined('ABSPATH')) exit;

// Database table for notifications
function amsawal_create_notifications_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_notifications';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL,
        from_user_id BIGINT UNSIGNED NULL,
        data TEXT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY is_read (is_read),
        KEY type (type)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('after_switch_theme', 'amsawal_create_notifications_table');
amsawal_create_notifications_table();

// Send notification
function amsawal_send_notification($user_id, $type, $data = []) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_notifications';
    
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'type' => $type,
        'from_user_id' => $data['from_user_id'] ?? null,
        'data' => json_encode($data),
        'is_read' => 0
    ]);
    
    return $wpdb->insert_id;
}

// Get notifications
function amsawal_get_notifications($user_id, $limit = 20, $unread_only = false) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_notifications';
    
    $where = $unread_only ? "WHERE user_id = %d AND is_read = 0" : "WHERE user_id = %d";
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT n.*, u.display_name as from_user_name
         FROM $table n
         LEFT JOIN {$wpdb->users} u ON n.from_user_id = u.ID
         $where
         ORDER BY n.created_at DESC
         LIMIT %d",
        $user_id, $limit
    ));
}

// Mark as read
function amsawal_mark_notifications_read($user_id, $notification_ids = []) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_notifications';
    
    if (empty($notification_ids)) {
        // Mark all as read
        $wpdb->update($table,
            ['is_read' => 1],
            ['user_id' => $user_id, 'is_read' => 0],
            ['%d'],
            ['%d', '%d']
        );
    } else {
        // Mark specific as read
        $ids = implode(',', array_map('absint', $notification_ids));
        $wpdb->query("UPDATE $table SET is_read = 1 WHERE id IN ($ids) AND user_id = $user_id");
    }
    
    return true;
}

// Get unread count
function amsawal_get_unread_count($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_notifications';
    
    return intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_read = 0",
        $user_id
    )));
}

// AJAX handlers
add_action('wp_ajax_amsawal_get_notifications', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $user_id = get_current_user_id();
    $unread_only = isset($_POST['unread_only']) && $_POST['unread_only'] === 'true';
    
    $notifications = amsawal_get_notifications($user_id, 20, $unread_only);
    $unread_count = amsawal_get_unread_count($user_id);
    
    wp_send_json_success([
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
});

add_action('wp_ajax_amsawal_mark_notifications_read', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $user_id = get_current_user_id();
    $notification_ids = array_map('absint', $_POST['ids'] ?? []);
    
    amsawal_mark_notifications_read($user_id, $notification_ids);
    wp_send_json_success('Notificaciones marcadas como leídas');
});
"""
    
    with open('wp-amsawal-notifications.php', 'w', encoding='utf-8') as f:
        f.write(notifications_code)
    print("✅ F17-5: Social notifications system created")
    return True

# Ejecutar todas las mejoras sociales
if __name__ == '__main__':
    print(" Aplicando mejoras Fase 17 - Social Features...\n")
    
    apply_f17_1_friends_system()
    apply_f17_2_challenges_system()
    apply_f17_3_messaging_system()
    apply_f17_4_leaderboard_challenges()
    apply_f17_5_social_notifications()
    
    print("\n✨ Mejoras sociales completadas")
