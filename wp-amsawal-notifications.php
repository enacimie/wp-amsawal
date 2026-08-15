<?php
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
        'data' => wp_json_encode($data),
        'is_read' => 0
    ], ['%d', '%s', '%d', '%s', '%d']);
    
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
        $ids = implode(',', array_fill(0, count($notification_ids), '%d'));
        $params = array_merge($notification_ids, array($user_id));
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET is_read = 1 WHERE id IN ($ids) AND user_id = %d",
            ...$params
        ));
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
