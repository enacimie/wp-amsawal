<?php
/**
 * Fix H5P rendering and lesson_activities issues
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

echo "<h1>Fixing H5P and Lesson Activities</h1>";

// Fix 1: Deregister H5P's jQuery to avoid conflicts
echo "<h2>Fix 1: Remove duplicate jQuery</h2>";
add_action('wp_enqueue_scripts', function() {
    // Deregister H5P's jQuery in favor of WordPress's jQuery
    wp_deregister_script('h5p-core-js-jquery-js');
    echo "<p>✅ Deregistered H5P jQuery</p>";
}, 1);

// Fix 2: Debug lesson_activities
echo "<h2>Fix 2: Debug lesson_activities</h2>";

// Simulate the front page query
$front_id = get_option('page_on_front');
echo "<p>Front page ID: {$front_id}</p>";

$page_config = array(
    'post_type' => 'page',
    'post_parent' => $front_id,
    'order' => 'ASC',
    'posts_per_page' => -1,
    'orderby'   => 'meta_value',
    'meta_key'  => 'wp_amsawal_mb_step',
    'meta_type' => 'NUMERIC'
);

$pages = new WP_Query($page_config);
echo "<p>Found {$pages->found_posts} pages</p>";

$allpages = array();
if ($pages->have_posts()) {
    while ($pages->have_posts()) {
        $pages->the_post();
        $custom_fields = get_post_custom(get_the_ID());
        
        $page_data = (object) [
            'id' => get_the_ID(),
            'typeh5p' => isset($custom_fields["wp_amsawal_mb_typeh5p"][0]) ? $custom_fields["wp_amsawal_mb_typeh5p"][0] : '',
            'lesson' => isset($custom_fields["wp_amsawal_mb_lesson"][0]) ? $custom_fields["wp_amsawal_mb_lesson"][0] : 0,
            'content' => get_the_content(),
        ];
        
        $allpages[] = $page_data;
        
        echo "<div style='border: 1px solid #ccc; padding: 5px; margin: 2px 0;'>";
        echo "ID: {$page_data->id} | Type: {$page_data->typeh5p} | Lesson: {$page_data->lesson} | ";
        echo "Has H5P: " . (strpos($page_data->content, '[h5p id=') !== false ? 'YES' : 'NO');
        echo "</div>";
    }
    wp_reset_postdata();
}

// Test lesson_activities for lesson 1
echo "<h2>Testing lesson_activities for Lesson 1</h2>";
$lesson_activities = array();
$i = 1;

foreach ($allpages as $page) {
    if ($page->lesson == $i) {
        echo "<p>Found page for lesson {$i}: ID {$page->id}, Type: {$page->typeh5p}</p>";
        
        if ($page->typeh5p == "lesson") {
            $raw_post = get_post($page->id);
            echo "<p>Raw post content: " . substr($raw_post->post_content, 0, 100) . "</p>";
            
            if ($raw_post && strpos($raw_post->post_content, '[h5p id=') !== false) {
                $lesson_activities[] = $page;
                echo "<p>✅ Added to lesson_activities</p>";
            } else {
                echo "<p>❌ No H5P shortcode found</p>";
            }
        }
    }
}

echo "<p>lesson_activities count: " . count($lesson_activities) . "</p>";

if (!empty($lesson_activities)) {
    echo "<p>✅ lesson_activities is NOT empty</p>";
    echo "<p>First activity ID: {$lesson_activities[0]->id}</p>";
} else {
    echo "<p>❌ lesson_activities is EMPTY</p>";
}

echo "<h2>Summary</h2>";
echo "<p>Total pages found: " . count($allpages) . "</p>";
echo "<p>Lesson 1 activities: " . count($lesson_activities) . "</p>";
