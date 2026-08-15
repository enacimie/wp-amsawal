<?php
/**
 * F19-6: Automated Backup System
 * Backup database and files
 */

if (!defined('ABSPATH')) exit;

// Create backup
function amsawal_create_backup($include_files = false) {
    if (!current_user_can('manage_options')) {
        return new WP_Error('permission', 'Acceso denegado');
    }
    
    global $wpdb;
    
    $backup_dir = WP_CONTENT_DIR . '/uploads/amsawal-backups/';
    
    if (!file_exists($backup_dir)) {
        wp_mkdir_p($backup_dir);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . 'amsawal-backup-' . $timestamp . '.sql';
    
    // Backup database
    $tables = $wpdb->get_col('SHOW TABLES');
    
    $output = "-- WP Amsawal Backup\n";
    $output .= "-- Generated: " . current_time('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        if (strpos($table, $wpdb->prefix) !== 0) continue;
        
        $output .= "DROP TABLE IF EXISTS `$table`;\n";

        $create = $wpdb->get_row("SHOW CREATE TABLE `$table`");
        if ( ! $create || ! isset( $create->{'Create Table'} ) ) {
            $output .= "-- WARNING: Could not retrieve CREATE TABLE for `$table`\n\n";
            continue;
        }
        $output .= $create->{'Create Table'} . ";\n\n";

        $rows = $wpdb->get_results("SELECT * FROM `$table`");
        foreach ($rows as $row) {
            $values = array_map(function($value) use ($wpdb) {
                if ( null === $value ) return 'NULL';
                return "'" . $wpdb->_real_escape($value) . "'";
            }, (array)$row);

            $output .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
        }
        $output .= "\n";
    }
    
    file_put_contents($backup_file, $output);
    
    // Backup files if requested
    if ($include_files) {
        $files_backup = $backup_dir . 'amsawal-files-' . $timestamp . '.zip';
        
        $zip = new ZipArchive();
        $zip->open($files_backup, ZipArchive::CREATE);
        
        $plugin_dir = WP_CONTENT_DIR . '/plugins/wp-amsawal/';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir)
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $zip->addFile($file->getRealPath(), substr($file->getRealPath(), strlen($plugin_dir)));
            }
        }
        
        $zip->close();
    }
    
    // Clean old backups (keep last 10)
    $backups = glob($backup_dir . '*.sql');
    if (count($backups) > 10) {
        sort($backups);
        for ($i = 0; $i < count($backups) - 10; $i++) {
            unlink($backups[$i]);
        }
    }
    
    return $backup_file;
}

// Schedule daily backups
if (!wp_next_scheduled('amsawal_daily_backup')) {
    wp_schedule_event(time(), 'daily', 'amsawal_daily_backup');
}

add_action('amsawal_daily_backup', function() {
    amsawal_create_backup(false);
});

// AJAX handler
add_action('wp_ajax_amsawal_create_backup', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    $include_files = isset($_POST['include_files']) && $_POST['include_files'] === 'true';
    
    $result = amsawal_create_backup($include_files);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(['backup_file' => basename($result)]);
});
