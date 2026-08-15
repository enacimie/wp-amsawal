<?php
/**
 * F18-4: Content Versioning System
 * Track changes to lessons and H5P content
 */

if (!defined('ABSPATH')) exit;

// Database table for content versions
function amsawal_create_versions_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        content_type VARCHAR(50) NOT NULL,
        content_id BIGINT UNSIGNED NOT NULL,
        version INT NOT NULL DEFAULT 1,
        content_data LONGTEXT NOT NULL,
        author_id BIGINT UNSIGNED NOT NULL,
        change_summary TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY content_lookup (content_type, content_id),
        KEY author_id (author_id)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('after_switch_theme', 'amsawal_create_versions_table');
amsawal_create_versions_table();

// Save version
function amsawal_save_content_version($content_type, $content_id, $content_data, $change_summary = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    
    // Get current version
    $current_version = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(version) FROM $table WHERE content_type = %s AND content_id = %d",
        $content_type, $content_id
    ));
    
    $new_version = ($current_version ?: 0) + 1;
    
    $wpdb->insert($table, [
        'content_type' => $content_type,
        'content_id' => $content_id,
        'version' => $new_version,
        'content_data' => wp_json_encode($content_data, JSON_UNESCAPED_UNICODE),
        'author_id' => get_current_user_id(),
        'change_summary' => $change_summary
    ], ['%s', '%d', '%d', '%s', '%d', '%s']);
    
    return $new_version;
}

// Get version history
function amsawal_get_version_history($content_type, $content_id, $limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT v.*, u.display_name as author_name
         FROM $table v
         LEFT JOIN {$wpdb->users} u ON v.author_id = u.ID
         WHERE v.content_type = %s AND v.content_id = %d
         ORDER BY v.version DESC
         LIMIT %d",
        $content_type, $content_id, $limit
    ));
}

// Restore version
function amsawal_restore_version($content_type, $content_id, $version) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    
    $version_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE content_type = %s AND content_id = %d AND version = %d",
        $content_type, $content_id, $version
    ));
    
    if (!$version_data) {
        return new WP_Error('not_found', 'Versión no encontrada');
    }
    
    $content_data = json_decode($version_data->content_data, true);
    
    // Restore based on content type
    if ($content_type === 'lesson') {
        wp_update_post([
            'ID' => $content_id,
            'post_content' => $content_data['post_content'] ?? '',
            'post_title' => $content_data['post_title'] ?? ''
        ]);
    } elseif ($content_type === 'h5p') {
        amsawal_update_h5p_content($content_id, $content_data);
    }
    
    return true;
}

// Hook into lesson updates
add_action('save_post_page', function($post_id, $post, $update) {
    if (!$update) return;
    if (wp_is_post_revision($post_id)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    // Check if this page is an amsawal lesson
    $lesson_num = get_post_meta($post_id, 'wp_amsawal_mb_lesson', true);
    if (empty($lesson_num)) return;
    
    $content_data = [
        'post_title' => $post->post_title,
        'post_content' => $post->post_content
    ];
    
    amsawal_save_content_version('lesson', $post_id, $content_data, 'Actualizaci\u00f3n de lecci\u00f3n');
}, 10, 3);

// AJAX handlers
add_action('wp_ajax_amsawal_get_version_history', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $content_type = sanitize_text_field($_POST['type'] ?? '');
    $content_id = absint($_POST['id'] ?? 0);
    
    $history = amsawal_get_version_history($content_type, $content_id);
    wp_send_json_success($history);
});

add_action('wp_ajax_amsawal_restore_version', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $content_type = sanitize_text_field($_POST['type'] ?? '');
    $content_id = absint($_POST['id'] ?? 0);
    $version = absint($_POST['version'] ?? 0);
    
    $result = amsawal_restore_version($content_type, $content_id, $version);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Versión restaurada');
});
