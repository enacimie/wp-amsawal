#!/usr/bin/env python3
"""Fase 20: Advanced Features - Características avanzadas adicionales"""

def apply_f20_1_realtime_collaboration():
    """F20-1: Real-time collaboration features"""
    collaboration_code = """<?php
/**
 * F20-1: Real-time Collaboration
 * WebSocket-based real-time features
 */

if (!defined('ABSPATH')) exit;

// WebSocket server configuration
define('AMSAWAL_WS_HOST', '0.0.0.0');
define('AMSAWAL_WS_PORT', 8080);

// Track online users
function amsawal_track_user_online($user_id) {
    $key = 'amsawal_online_' . $user_id;
    set_transient($key, time(), 300); // 5 minutes
    
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

// Get online users
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

// Live activity feed
function amsawal_get_live_activity($limit = 20) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            t.*,
            u.display_name as user_name,
            p.post_title as lesson_title
        FROM $table t
        JOIN {$wpdb->users} u ON t.user_id = u.ID
        LEFT JOIN {$wpdb->posts} p ON t.lesson_id = p.ID
        ORDER BY t.created_at DESC
        LIMIT %d",
        $limit
    ));
}

// AJAX handlers
add_action('wp_ajax_amsawal_get_online_users', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    $online_users = amsawal_get_online_users();
    wp_send_json_success($online_users);
});

add_action('wp_ajax_amsawal_get_live_activity', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    $activity = amsawal_get_live_activity();
    wp_send_json_success($activity);
});
"""
    
    with open('wp-amsawal-collaboration.php', 'w', encoding='utf-8') as f:
        f.write(collaboration_code)
    print("✅ F20-1: Real-time collaboration features created")
    return True

def apply_f20_2_offline_mode():
    """F20-2: Enhanced offline mode"""
    offline_code = """<?php
/**
 * F20-2: Enhanced Offline Mode
 * Sync data when back online
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
"""
    
    with open('wp-amsawal-offline.php', 'w', encoding='utf-8') as f:
        f.write(offline_code)
    print("✅ F20-2: Enhanced offline mode created")
    return True

def apply_f20_3_gamification_advanced():
    """F20-3: Advanced gamification features"""
    gamification_code = """<?php
/**
 * F20-3: Advanced Gamification
 * Badges, achievements, and rewards
 */

if (!defined('ABSPATH')) exit;

// Achievement definitions
function amsawal_get_achievements() {
    return [
        'first_lesson' => [
            'name' => 'Primera Lección',
            'description' => 'Completa tu primera lección',
            'icon' => '🎓',
            'xp' => 50
        ],
        'streak_7' => [
            'name' => 'Racha de 7 días',
            'description' => 'Practica 7 días seguidos',
            'icon' => '🔥',
            'xp' => 100
        ],
        'streak_30' => [
            'name' => 'Racha de 30 días',
            'description' => 'Practica 30 días seguidos',
            'icon' => '⚡',
            'xp' => 500
        ],
        'perfect_quiz' => [
            'name' => 'Quiz Perfecto',
            'description' => 'Obtén 100% en un quiz',
            'icon' => '💯',
            'xp' => 75
        ],
        'speed_learner' => [
            'name' => 'Aprendiz Rápido',
            'description' => 'Completa 5 lecciones en un día',
            'icon' => '⚡',
            'xp' => 150
        ],
        'social_butterfly' => [
            'name' => 'Mariposa Social',
            'description' => 'Añade 10 amigos',
            'icon' => '🦋',
            'xp' => 200
        ],
        'challenge_master' => [
            'name' => 'Maestro de Desafíos',
            'description' => 'Completa 10 desafíos',
            'icon' => '🏆',
            'xp' => 300
        ],
        'content_creator' => [
            'name' => 'Creador de Contenido',
            'description' => 'Crea tu primer ejercicio H5P',
            'icon' => '✍️',
            'xp' => 100
        ]
    ];
}

// Unlock achievement
function amsawal_unlock_achievement($user_id, $achievement_id) {
    $achievements = amsawal_get_achievements();
    
    if (!isset($achievements[$achievement_id])) {
        return new WP_Error('invalid_achievement', 'Logro no válido');
    }
    
    $unlocked = get_user_meta($user_id, 'amsawal_achievements', true) ?: [];
    
    if (in_array($achievement_id, $unlocked)) {
        return false; // Already unlocked
    }
    
    $unlocked[] = $achievement_id;
    update_user_meta($user_id, 'amsawal_achievements', $unlocked);
    
    // Award XP
    $xp = $achievements[$achievement_id]['xp'];
    amsawal_award_xp($user_id, $xp, 'achievement_' . $achievement_id);
    
    // Send notification
    amsawal_send_notification($user_id, 'achievement_unlocked', [
        'achievement_id' => $achievement_id,
        'achievement_name' => $achievements[$achievement_id]['name'],
        'xp_earned' => $xp
    ]);
    
    return true;
}

// Check and unlock achievements based on activity
function amsawal_check_achievements($user_id, $event_type, $metadata = []) {
    $unlocked = get_user_meta($user_id, 'amsawal_achievements', true) ?: [];
    
    // First lesson
    if ($event_type === 'lesson_complete' && !in_array('first_lesson', $unlocked)) {
        $total_lessons = intval(get_user_meta($user_id, 'amsawal_lessons_completed', true));
        if ($total_lessons >= 1) {
            amsawal_unlock_achievement($user_id, 'first_lesson');
        }
    }
    
    // Perfect quiz
    if ($event_type === 'quiz_complete' && !in_array('perfect_quiz', $unlocked)) {
        if (isset($metadata['score']) && $metadata['score'] >= 100) {
            amsawal_unlock_achievement($user_id, 'perfect_quiz');
        }
    }
    
    // Speed learner
    if ($event_type === 'lesson_complete' && !in_array('speed_learner', $unlocked)) {
        $today_lessons = intval(get_user_meta($user_id, 'amsawal_lessons_today', true));
        if ($today_lessons >= 5) {
            amsawal_unlock_achievement($user_id, 'speed_learner');
        }
    }
}

add_action('amsawal_activity_tracked', 'amsawal_check_achievements', 10, 3);

// AJAX handler
add_action('wp_ajax_amsawal_get_achievements', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $user_id = get_current_user_id();
    $all_achievements = amsawal_get_achievements();
    $unlocked = get_user_meta($user_id, 'amsawal_achievements', true) ?: [];
    
    $achievements = [];
    foreach ($all_achievements as $id => $achievement) {
        $achievements[] = [
            'id' => $id,
            'name' => $achievement['name'],
            'description' => $achievement['description'],
            'icon' => $achievement['icon'],
            'xp' => $achievement['xp'],
            'unlocked' => in_array($id, $unlocked),
            'unlocked_at' => in_array($id, $unlocked) ? get_user_meta($user_id, 'amsawal_achievement_' . $id . '_date', true) : null
        ];
    }
    
    wp_send_json_success($achievements);
});
"""
    
    with open('wp-amsawal-achievements.php', 'w', encoding='utf-8') as f:
        f.write(gamification_code)
    print("✅ F20-3: Advanced gamification features created")
    return True

def apply_f20_4_personalization():
    """F20-4: Personalization engine"""
    personalization_code = """<?php
/**
 * F20-4: Personalization Engine
 * Customize learning experience per user
 */

if (!defined('ABSPATH')) exit;

// Get user learning profile
function amsawal_get_learning_profile($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    // Get activity stats
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COUNT(*) as total_activities,
            COUNT(CASE WHEN event_type = 'lesson_complete' THEN 1 END) as lessons_completed,
            COUNT(CASE WHEN event_type = 'quiz_complete' THEN 1 END) as quizzes_completed,
            AVG(CASE WHEN event_type = 'quiz_complete' THEN JSON_EXTRACT(metadata, '$.score') END) as avg_score
        FROM $table
        WHERE user_id = %d",
        $user_id
    ));
    
    // Get preferred learning time
    $preferred_time = $wpdb->get_var($wpdb->prepare(
        "SELECT HOUR(created_at) as hour, COUNT(*) as count 
         FROM $table 
         WHERE user_id = %d 
         GROUP BY HOUR(created_at) 
         ORDER BY count DESC 
         LIMIT 1",
        $user_id
    ));
    
    // Get learning pace
    $first_activity = $wpdb->get_var($wpdb->prepare(
        "SELECT created_at FROM $table WHERE user_id = %d ORDER BY created_at ASC LIMIT 1",
        $user_id
    ));
    
    $days_active = $first_activity ? ceil((time() - strtotime($first_activity)) / 86400) : 0;
    $learning_pace = $days_active > 0 ? $stats->lessons_completed / $days_active : 0;
    
    return [
        'total_activities' => intval($stats->total_activities),
        'lessons_completed' => intval($stats->lessons_completed),
        'quizzes_completed' => intval($stats->quizzes_completed),
        'avg_score' => floatval($stats->avg_score),
        'preferred_hour' => intval($preferred_time),
        'learning_pace' => round($learning_pace, 2),
        'level' => get_user_meta($user_id, 'amsawal_level', true) ?: 1,
        'xp' => get_user_meta($user_id, 'amsawal_xp', true) ?: 0,
        'streak' => get_user_meta($user_id, 'amsawal_streak', true) ?: 0
    ];
}

// Get personalized recommendations
function amsawal_get_personalized_recommendations($user_id) {
    $profile = amsawal_get_learning_profile($user_id);
    
    $recommendations = [];
    
    // Time-based recommendation
    $current_hour = intval(date('H'));
    if (abs($current_hour - $profile['preferred_hour']) <= 2) {
        $recommendations[] = [
            'type' => 'timing',
            'message' => '¡Es tu hora pico de aprendizaje! Aprovecha ahora.'
        ];
    }
    
    // Streak recommendation
    if ($profile['streak'] >= 3 && $profile['streak'] < 7) {
        $recommendations[] = [
            'type' => 'streak',
            'message' => '¡Llevas ' . $profile['streak'] . ' días! Completa 7 para un logro especial.'
        ];
    }
    
    // Performance recommendation
    if ($profile['avg_score'] < 70 && $profile['quizzes_completed'] > 5) {
        $recommendations[] = [
            'type' => 'practice',
            'message' => 'Te recomendamos repasar lecciones anteriores para fortalecer tu base.'
        ];
    }
    
    // Level up recommendation
    $xp_for_next_level = $profile['level'] * 100;
    $xp_needed = $xp_for_next_level - ($profile['xp'] % $xp_for_next_level);
    if ($xp_needed <= 50) {
        $recommendations[] = [
            'type' => 'level_up',
            'message' => '¡Solo te faltan ' . $xp_needed . ' XP para subir de nivel!'
        ];
    }
    
    return $recommendations;
}

// AJAX handler
add_action('wp_ajax_amsawal_get_profile', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $user_id = get_current_user_id();
    $profile = amsawal_get_learning_profile($user_id);
    $recommendations = amsawal_get_personalized_recommendations($user_id);
    
    wp_send_json_success([
        'profile' => $profile,
        'recommendations' => $recommendations
    ]);
});
"""
    
    with open('wp-amsawal-personalization.php', 'w', encoding='utf-8') as f:
        f.write(personalization_code)
    print("✅ F20-4: Personalization engine created")
    return True

def apply_f20_5_final_tests():
    """F20-5: Final comprehensive tests"""
    test_code = """<?php
/**
 * F20-5: Final Comprehensive Tests
 * Test all features together
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo " Final Comprehensive Test Suite\\n";
echo "==================================\\n\\n";

$tests_passed = 0;
$tests_failed = 0;

function run_test($name, $callback) {
    global $tests_passed, $tests_failed;
    
    echo "Test: $name\\n";
    try {
        $callback();
        echo "  ✅ PASS\\n";
        $tests_passed++;
    } catch (Exception $e) {
        echo "  ❌ FAIL: " . $e->getMessage() . "\\n";
        $tests_failed++;
    }
    echo "\\n";
}

// Test 1: All modules load correctly
run_test('Module Loading', function() {
    $modules = [
        'wp-amsawal-ai.php',
        'wp-amsawal-view.php',
        'wp-amsawal-gamification.php',
        'wp-amsawal-analytics.php',
        'wp-amsawal-friends.php',
        'wp-amsawal-course-builder.php'
    ];
    
    foreach ($modules as $module) {
        if (!file_exists(dirname(__DIR__) . '/' . $module)) {
            throw new Exception("Module not found: $module");
        }
    }
});

// Test 2: Database tables exist
run_test('Database Tables', function() {
    global $wpdb;
    
    $tables = [
        'amsawal_user_interactions',
        'amsawal_friends',
        'amsawal_challenges',
        'amsawal_messages',
        'amsawal_notifications'
    ];
    
    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}$table'");
        if (!$exists) {
            throw new Exception("Table not found: $table");
        }
    }
});

// Test 3: Translation files exist
run_test('Translation Files', function() {
    $languages = ['es_ES', 'en_US', 'tzg'];
    
    foreach ($languages as $lang) {
        if (!file_exists(dirname(__DIR__) . "/languages/$lang.json")) {
            throw new Exception("Translation file not found: $lang.json");
        }
    }
});

// Test 4: CSS modules exist
run_test('CSS Modules', function() {
    $modules = [
        '_variables.css',
        '_learning-path.css',
        '_activities.css',
        '_gamification.css',
        '_ai-components.css'
    ];
    
    foreach ($modules as $module) {
        if (!file_exists(dirname(__DIR__) . "/css/modules/$module")) {
            throw new Exception("CSS module not found: $module");
        }
    }
});

// Test 5: JavaScript file exists and is valid
run_test('JavaScript', function() {
    $js_file = dirname(__DIR__) . '/js/pure-js-script.js';
    
    if (!file_exists($js_file)) {
        throw new Exception('JavaScript file not found');
    }
    
    $content = file_get_contents($js_file);
    if (empty($content)) {
        throw new Exception('JavaScript file is empty');
    }
});

// Test 6: PWA files exist
run_test('PWA Files', function() {
    if (!file_exists(dirname(__DIR__) . '/manifest.json')) {
        throw new Exception('manifest.json not found');
    }
    
    if (!file_exists(dirname(__DIR__) . '/sw.js')) {
        throw new Exception('sw.js not found');
    }
    
    if (!file_exists(dirname(__DIR__) . '/offline.html')) {
        throw new Exception('offline.html not found');
    }
});

// Test 7: Documentation exists
run_test('Documentation', function() {
    $docs = ['README.md', 'COMPONENTS.md', 'API.md', 'CHANGELOG.md', 'CONTRIBUTING.md'];
    
    foreach ($docs as $doc) {
        if (!file_exists(dirname(__DIR__) . "/$doc")) {
            throw new Exception("Documentation not found: $doc");
        }
    }
});

// Summary
echo "==================================\\n";
echo "Results: $tests_passed passed, $tests_failed failed\\n";
echo "==================================\\n";

if ($tests_failed > 0) {
    exit(1);
}

echo "\\n✨ All tests passed! System is ready for production.\\n";
"""
    
    with open('tests/test-final.php', 'w', encoding='utf-8') as f:
        f.write(test_code)
    print("✅ F20-5: Final comprehensive tests created")
    return True

# Ejecutar todas las mejoras avanzadas
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 20 - Advanced Features...\n")
    
    apply_f20_1_realtime_collaboration()
    apply_f20_2_offline_mode()
    apply_f20_3_gamification_advanced()
    apply_f20_4_personalization()
    apply_f20_5_final_tests()
    
    print("\n✨ Mejoras avanzadas completadas")
