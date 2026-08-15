<?php
/**
 * Offline Action Queue
 * Queue user actions when offline and sync when back online
 * 
 * NOTE: This is NOT a full PWA implementation. It does NOT include
 * a service worker, cache strategies, or offline content serving.
 * It only provides an action queue with online/offline detection.
 */

if (!defined('ABSPATH')) exit;

// Queue actions for offline sync
function amsawal_queue_offline_action($action, $data) {
    $queue = get_user_meta(get_current_user_id(), 'amsawal_offline_queue', true) ?: [];
    
    $queue[] = [
        'action' => $action,
        'data' => $data,
        'timestamp' => time(),
        'synced' => false
    ];
    
    update_user_meta(get_current_user_id(), 'amsawal_offline_queue', $queue);
    
    return count($queue);
}

// Sync queued actions
function amsawal_sync_offline_queue() {
    $user_id = get_current_user_id();
    $queue = get_user_meta($user_id, 'amsawal_offline_queue', true) ?: [];
    
    $synced = 0;
    $failed = 0;
    
    foreach ($queue as $index => $item) {
        if ($item['synced']) continue;
        
        try {
            // Process action
            do_action('amsawal_sync_' . $item['action'], $item['data'], $user_id);
            
            $queue[$index]['synced'] = true;
            $synced++;
        } catch (Exception $e) {
            $failed++;
        }
    }
    
    // Clean synced items older than 7 days
    $queue = array_filter($queue, function($item) {
        return !$item['synced'] || (time() - $item['timestamp']) < 604800;
    });
    
    update_user_meta($user_id, 'amsawal_offline_queue', array_values($queue));
    
    return ['synced' => $synced, 'failed' => $failed];
}

// AJAX handler for sync
add_action('wp_ajax_amsawal_sync_offline', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $result = amsawal_sync_offline_queue();
    wp_send_json_success($result);
});

// Enqueue offline detection script
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script('amsawal-pure-js', "
        // F20-2: Offline detection and sync
        window.addEventListener('online', function() {
            console.log('Back online - syncing...');
            
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=amsawal_sync_offline&nonce=' + amsawal_params.nonce
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Synced:', data.data.synced, 'Failed:', data.data.failed);
                }
            });
        });
        
        window.addEventListener('offline', function() {
            console.log('Offline mode - actions will be queued');
        });
    ");
});
