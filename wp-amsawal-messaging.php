<?php
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
    ], ['%d', '%d', '%s', '%d']);
    
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

    if (!current_user_can('read')) {
        wp_send_json_error('No tienes permisos');
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
