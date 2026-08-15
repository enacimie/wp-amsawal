<?php
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
    ], ['%d', '%d', '%s']);
    
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
