<?php
/**
 * Integration Tests - Key User Workflows
 */

require_once dirname(__DIR__) . '/wp-load.php';

class IntegrationTest {
    private $passed = 0;
    private $failed = 0;
    
    public function test($name, $callback) {
        echo "Test: $name\n";
        try {
            $callback($this);
        } catch (Exception $e) {
            $this->failed++;
            echo "  ❌ EXCEPTION: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    public function assert($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo "  ✅ $message\n";
        } else {
            $this->failed++;
            echo "   $message\n";
        }
    }
    
    public function summary() {
        echo "===================\n";
        echo "Integration Tests: {$this->passed} passed, {$this->failed} failed\n";
        echo "===================\n";
    }
}

$test = new IntegrationTest();

// Test 1: Learning path renders correctly
$test->test('Learning path rendering', function($t) {
    global $wpdb;
    
    // Check if course exists
    $course = get_post(11); // Module 1
    $t->assert($course !== null, 'Module 1 exists');
    
    // Check if lessons exist
    $lessons = get_posts([
        'post_type' => 'page',
        'post_parent' => 11,
        'numberposts' => -1
    ]);
    $t->assert(count($lessons) > 0, 'Lessons exist under Module 1');
});

// Test 2: H5P content exists for lessons
$test->test('H5P content availability', function($t) {
    global $wpdb;
    
    $h5p_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}h5p_contents");
    $t->assert($h5p_count > 0, "H5P contents exist ($h5p_count found)");
    
    // Check specific lesson H5P
    $lesson_1 = get_post(11);
    if ($lesson_1) {
        $t->assert(strpos($lesson_1->post_content, '[h5p') !== false, 'Lesson 1 has H5P shortcode');
    }
});

// Test 3: User meta for gamification
$test->test('Gamification data structure', function($t) {
    $user_id = 1; // admin
    
    $xp = get_user_meta($user_id, 'amsawal_xp', true);
    $t->assert(is_numeric($xp), 'User XP is numeric');
    
    $level = get_user_meta($user_id, 'amsawal_level', true);
    $t->assert(is_numeric($level), 'User level is numeric');
    
    $streak = get_user_meta($user_id, 'amsawal_streak', true);
    $t->assert(is_numeric($streak), 'User streak is numeric');
});

// Test 4: CSS files are accessible
$test->test('CSS file accessibility', function($t) {
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    $css_files = [
        'css/wp-amsawal-style-h5p.css',
        'css/modules/_variables.css',
        'css/modules/_learning-path.css'
    ];
    
    foreach ($css_files as $file) {
        $path = dirname(__DIR__) . '/' . $file;
        $t->assert(file_exists($path), "$file exists");
    }
});

// Test 5: JS files are accessible
$test->test('JS file accessibility', function($t) {
    $js_files = [
        'js/pure-js-script.js'
    ];
    
    foreach ($js_files as $file) {
        $path = dirname(__DIR__) . '/' . $file;
        $t->assert(file_exists($path), "$file exists");
    }
});

$test->summary();
