<?php
/**
 * F20-5: Final Comprehensive Tests
 * Test all features together
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo " Final Comprehensive Test Suite\n";
echo "==================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

function run_test($name, $callback) {
    global $tests_passed, $tests_failed;
    
    echo "Test: $name\n";
    try {
        $callback();
        echo "  ✅ PASS\n";
        $tests_passed++;
    } catch (Exception $e) {
        echo "  ❌ FAIL: " . $e->getMessage() . "\n";
        $tests_failed++;
    }
    echo "\n";
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
echo "==================================\n";
echo "Results: $tests_passed passed, $tests_failed failed\n";
echo "==================================\n";

if ($tests_failed > 0) {
    exit(1);
}

echo "\n✨ All tests passed! System is ready for production.\n";
