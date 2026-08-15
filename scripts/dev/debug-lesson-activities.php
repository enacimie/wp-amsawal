<?php
/**
 * Debug script to check lesson_activities and H5P rendering
 * Access: http://localhost:8080/?debug=lesson
 */


/*
 * Script de desarrollo. No ejecutar en producción.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Acceso denegado: se requieren permisos de administrador.' );
}

require_once '/var/www/html/wp-load.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug: Lesson Activities & H5P</h1>";

// Check H5P content ID 24
echo "<h2>H5P Content ID 24</h2>";
global $wpdb;
$h5p_content = $wpdb->get_row($wpdb->prepare(
    "SELECT id, title, library_id, parameters, filtered FROM {$wpdb->prefix}h5p_contents WHERE id = 24"
));

if ($h5p_content) {
    echo "<pre>";
    echo "ID: " . $h5p_content->id . "\n";
    echo "Title: " . $h5p_content->title . "\n";
    echo "Library ID: " . $h5p_content->library_id . "\n";
    echo "Parameters: " . $h5p_content->parameters . "\n";
    echo "Filtered: " . $h5p_content->filtered . "\n";
    echo "</pre>";
} else {
    echo "<p>H5P content ID 24 not found!</p>";
}

// Check lesson pages
echo "<h2>Lesson Pages</h2>";
$lessons = get_posts([
    'post_type' => 'page',
    'post_parent' => 5,
    'numberposts' => -1,
    'orderby' => 'meta_value',
    'meta_key' => 'wp_amsawal_mb_lesson',
    'meta_type' => 'NUMERIC',
    'order' => 'ASC'
]);

echo "<p>Found " . count($lessons) . " lessons</p>";

foreach ($lessons as $lesson) {
    $type = get_post_meta($lesson->ID, 'wp_amsawal_mb_typeh5p', true);
    $lesson_num = get_post_meta($lesson->ID, 'wp_amsawal_mb_lesson', true);
    $has_h5p = strpos($lesson->post_content, '[h5p id=') !== false;
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
    echo "<strong>Lesson {$lesson_num}</strong>: {$lesson->post_title} (ID: {$lesson->ID})<br>";
    echo "Type: {$type} | Has H5P shortcode: " . ($has_h5p ? 'YES' : 'NO') . "<br>";
    echo "Content: " . substr($lesson->post_content, 0, 100) . "...";
    echo "</div>";
}

// Test shortcode rendering
echo "<h2>H5P Shortcode Test</h2>";
$test_content = do_shortcode('[h5p id="24"]');
echo "<p>Shortcode output length: " . strlen($test_content) . "</p>";
echo "<div style='border: 2px solid red; padding: 20px;'>";
echo $test_content;
echo "</div>";

// Check if H5P plugin is active
echo "<h2>H5P Plugin Status</h2>";
if (class_exists('H5P_Plugin')) {
    echo "<p>H5P_Plugin class exists: YES</p>";
} else {
    echo "<p>H5P_Plugin class exists: NO</p>";
}

$active_plugins = get_option('active_plugins');
$h5p_active = false;
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'h5p') !== false) {
        $h5p_active = true;
        break;
    }
}
echo "<p>H5P plugin active: " . ($h5p_active ? 'YES' : 'NO') . "</p>";
