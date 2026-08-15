<?php
/**
 * F19-5: Monitoring and Logging
 * Track application health and errors
 */

if (!defined('ABSPATH')) exit;

// Custom log file
define('AMSAWAL_LOG_FILE', WP_CONTENT_DIR . '/uploads/amsawal.log');

// Log function
function amsawal_log($message, $level = 'INFO') {
    $timestamp = current_time('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message";

    // Primary log channel: WordPress debug.log (always writable)
    error_log($log_entry);

    // Secondary: custom log file (best-effort, may fail on read-only filesystems)
    if (is_writable(dirname(AMSAWAL_LOG_FILE)) || is_writable(AMSAWAL_LOG_FILE)) {
        @file_put_contents(AMSAWAL_LOG_FILE, $log_entry . PHP_EOL, FILE_APPEND);
    }
}

// Error handler
function amsawal_error_handler($errno, $errstr, $errfile, $errline) {
    $message = "Error $errno: $errstr in $errfile on line $errline";
    amsawal_log($message, 'ERROR');
    
    // Send notification for critical errors
    if ($errno === E_ERROR || $errno === E_CORE_ERROR) {
        amsawal_send_error_notification($message);
    }
    
    return false; // Let WordPress handle it too
}

set_error_handler('amsawal_error_handler');

// Exception handler
function amsawal_exception_handler($exception) {
    $message = "Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine();
    amsawal_log($message, 'CRITICAL');
    
    amsawal_send_error_notification($message);
}

set_exception_handler('amsawal_exception_handler');

// Send error notification to admin
function amsawal_send_error_notification($message) {
    $admin_email = get_option('admin_email');
    
    wp_mail(
        $admin_email,
        '[Amsawal] Error Alert',
        "An error occurred on your site:\n\n$message\n\nTime: " . current_time('Y-m-d H:i:s')
    );
}

// Health check endpoint
add_action('wp_ajax_amsawal_health_check', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $health = [
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'mysql_version' => ( $GLOBALS['wpdb']->dbh instanceof mysqli ) ? mysqli_get_server_info($GLOBALS['wpdb']->dbh) : 'unknown',
        'disk_space' => disk_free_space(ABSPATH),
        'memory_usage' => memory_get_usage(true),
        'log_size' => file_exists(AMSAWAL_LOG_FILE) ? filesize(AMSAWAL_LOG_FILE) : 0,
        'last_error' => get_transient('amsawal_last_error') ?: 'None'
    ];
    
    wp_send_json_success($health);
});

/// AJAX handler to view logs
add_action('wp_ajax_amsawal_view_logs', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $lines = absint($_POST['lines'] ?? 100);
    
    if (!file_exists(AMSAWAL_LOG_FILE)) {
        wp_send_json_success(['logs' => []]);
    }
    
    $logs = file(AMSAWAL_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_slice($logs, -$lines);
    
    wp_send_json_success(['logs' => $logs]);
});
