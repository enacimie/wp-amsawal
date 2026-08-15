<?php
/**
 * Online Presence & Live Activity
 * WebSocket-based online tracking and activity feed
 */

if (!defined('ABSPATH')) exit;

// Track online users
function amsawal_track_user_online($user_id) {
    $key = 'amsawal_online_' . $user_id;
    set_transient($key, time(), 300);
    update_user_meta($user_id, 'amsawal_last_active', current_time('mysql'));
}

add_action('wp_login', function($user_login, $user) {
    amsawal_track_user_online($user->ID);
}, 10, 2);

add_action('wp', function() {
    if (is_user_logged_in()) {
        amsawal_track_user_online(get_current_user_id());
    }
});

function amsawal_get_online_users() {
    global $wpdb;
    $online_users = [];
    $users = get_users(['fields' => ['ID', 'display_name']]);
    foreach ($users as $user) {
        $key = 'amsawal_online_' . $user->ID;
        $last_seen = get_transient($key);
        if ($last_seen && (time() - $last_seen) < 300) {
            $online_users[] = [
                'id' => $user->ID,
                'name' => $user->display_name,
                'last_active' => get_user_meta($user->ID, 'amsawal_last_active', true)
            ];
        }
    }
    return $online_users;
}

function amsawal_get_live_activity($limit = 20) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, u.display_name as user_name
         FROM $table t
         JOIN {$wpdb->users} u ON t.user_id = u.ID
         ORDER BY t.created_at DESC
         LIMIT %d",
        $limit
    ));
}

add_action('wp_ajax_amsawal_get_online_users', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    wp_send_json_success(amsawal_get_online_users());
});

add_action('wp_ajax_amsawal_get_live_activity', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    wp_send_json_success(amsawal_get_live_activity());
});
