<?php
/**
 * Setup H5P Activities for Tarifit Course
 * Downloads and installs H5P libraries, then creates activities for each lesson
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

if (!defined('ABSPATH')) {
    die("This script must be run within WordPress\n");
}

echo "═══════════════════════════════════════════════════════════\n";
echo "🎮 SETUP H5P ACTIVITIES FOR TARIFIT COURSE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 1: Install H5P libraries using official H5P API
echo "📦 Step 1: Installing H5P libraries...\n\n";

require_once(ABSPATH . 'wp-content/plugins/h5p/h5p-php-library/autoload.php');

$h5p = H5P_Plugin::get_instance();
$interface = $h5p->get_interface();

// Download official H5P library packages
$libraries_urls = [
    'H5P.DialogCards' => 'https://h5p.org/sites/default/files/h5p/content/715/files/H5P.DialogCards-1.9.0.h5p',
    'H5P.MultiChoice' => 'https://h5p.org/sites/default/files/h5p/content/716/files/H5P.MultiChoice-1.16.0.h5p',
    'H5P.Blanks' => 'https://h5p.org/sites/default/files/h5p/content/717/files/H5P.Blanks-1.14.0.h5p'
];

$upload_dir = wp_upload_dir();
$h5p_temp_dir = $upload_dir['basedir'] . '/h5p';

if (!file_exists($h5p_temp_dir)) {
    mkdir($h5p_temp_dir, 0755, true);
}

foreach ($libraries_urls as $lib_name => $url) {
    echo "📥 Downloading $lib_name... ";
    
    $temp_file = $h5p_temp_dir . '/' . basename($url);
    $response = wp_remote_get($url, ['timeout' => 30]);
    
    if (is_wp_error($response)) {
        echo "❌ Error: " . $response->get_error_message() . "\n";
        continue;
    }
    
    file_put_contents($temp_file, wp_remote_retrieve_body($response));
    
    // Install using H5P plugin
    echo "⏳ Installing... ";
    
    try {
        // Use H5P's built-in installer
        $validator = new H5PValidator($interface);
        $storage = new H5PStorage($interface, new H5PFramework());
        
        $storage->savePackage(null, $temp_file);
        echo "✅ Installed\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    unlink($temp_file);
}

// Step 2: Get library IDs
echo "\n📚 Step 2: Getting library IDs...\n";

global $wpdb;

$dialog_cards_lib = $wpdb->get_var("
    SELECT id FROM {$wpdb->prefix}h5p_libraries 
    WHERE name = 'H5P.DialogCards' 
    ORDER BY major_version DESC, minor_version DESC 
    LIMIT 1
");

$multi_choice_lib = $wpdb->get_var("
    SELECT id FROM {$wpdb->prefix}h5p_libraries 
    WHERE name = 'H5P.MultiChoice' 
    ORDER BY major_version DESC, minor_version DESC 
    LIMIT 1
");

$blanks_lib = $wpdb->get_var("
    SELECT id FROM {$wpdb->prefix}h5p_libraries 
    WHERE name = 'H5P.Blanks' 
    ORDER BY major_version DESC, minor_version DESC 
    LIMIT 1
");

echo "  • Dialog Cards library ID: " . ($dialog_cards_lib ?: '❌ not found') . "\n";
echo "  • Multi Choice library ID: " . ($multi_choice_lib ?: '❌ not found') . "\n";
echo "  • Blanks library ID: " . ($blanks_lib ?: '❌ not found') . "\n";

if (!$dialog_cards_lib || !$multi_choice_lib || !$blanks_lib) {
    echo "\n❌ Some libraries are missing. Please install them manually:\n";
    echo "   Go to: WordPress Admin → H5P → Libraries\n";
    echo "   Upload the .h5p files from https://h5p.org/content-types\n";
    exit(1);
}

// Step 3: Create H5P activities for each lesson
echo "\n🎮 Step 3: Creating H5P activities for lessons...\n\n";

$lessons = $wpdb->get_results("
    SELECT p.ID, p.post_title, pm.meta_value as vocabulary
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'wp_amsawal_vocabulary'
    WHERE p.post_type = 'page'
    AND p.post_title LIKE 'Leiçon %'
    ORDER BY p.menu_order ASC
");

echo "📖 Found " . count($lessons) . " lessons\n\n";

$activities_created = 0;

foreach ($lessons as $lesson) {
    echo "📚 Processing: {$lesson->post_title}\n";
    
    $vocabulary = json_decode($lesson->vocabulary, true);
    
    if (empty($vocabulary)) {
        echo "  ⚠️  No vocabulary found, skipping...\n\n";
        continue;
    }
    
    // Create 3 activities per lesson: flashcards, multiple choice, fill blanks
    
    // 1. Dialog Cards (Flashcards)
    echo "  🃏 Creating Dialog Cards... ";
    
    $cards = [];
    foreach ($vocabulary as $word => $translation) {
        $cards[] = [
            'text' => $word,
            'answer' => $translation
        ];
    }
    
    $dialog_params = json_encode([
        'mode' => 'normal',
        'title' => $lesson->post_title . ' - Flashcards',
        'description' => 'Practice vocabulary',
        'cards' => $cards
    ]);
    
    $dialog_content_id = $wpdb->insert(
        $wpdb->prefix . 'h5p_contents',
        [
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'user_id' => 1,
            'title' => $lesson->post_title . ' - Flashcards',
            'library_id' => $dialog_cards_lib,
            'parameters' => $dialog_params,
            'filtered' => $dialog_params,
            'slug' => sanitize_title($lesson->post_title . '-flashcards'),
            'embed_type' => 'div',
            'disable' => 0
        ]
    );
    
    if ($dialog_content_id) {
        echo "✅ ID: $wpdb->insert_id\n";
        $activities_created++;
    } else {
        echo "❌ Error\n";
    }
    
    // 2. Multi Choice
    echo "  🎯 Creating Multi Choice... ";
    
    $questions = [];
    foreach ($vocabulary as $word => $translation) {
        // Get 3 random wrong answers
        $all_translations = array_values($vocabulary);
        $wrong_answers = [];
        
        while (count($wrong_answers) < 3 && count($all_translations) > 1) {
            $random = array_rand($all_translations);
            if ($all_translations[$random] !== $translation) {
                $wrong_answers[] = $all_translations[$random];
            }
            unset($all_translations[$random]);
            $all_translations = array_values($all_translations);
        }
        
        $answers = array_merge([$translation], $wrong_answers);
        shuffle($answers);
        
        $answers_data = [];
        foreach ($answers as $answer) {
            $answers_data[] = [
                'text' => $answer,
                'correct' => ($answer === $translation)
            ];
        }
        
        $questions[] = [
            'question' => "What does '$word' mean?",
            'answers' => $answers_data
        ];
    }
    
    $multi_params = json_encode([
        'questions' => $questions,
        'UI' => [
            'scoreBarLabel' => 'You got @score of @total',
            'checkAnswerButton' => 'Check',
            'tryAgainButton' => 'Retry'
        ]
    ]);
    
    $multi_content_id = $wpdb->insert(
        $wpdb->prefix . 'h5p_contents',
        [
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'user_id' => 1,
            'title' => $lesson->post_title . ' - Quiz',
            'library_id' => $multi_choice_lib,
            'parameters' => $multi_params,
            'filtered' => $multi_params,
            'slug' => sanitize_title($lesson->post_title . '-quiz'),
            'embed_type' => 'div',
            'disable' => 0
        ]
    );
    
    if ($multi_content_id) {
        echo "✅ ID: $wpdb->insert_id\n";
        $activities_created++;
    } else {
        echo "❌ Error\n";
    }
    
    // 3. Fill Blanks
    echo "  📝 Creating Fill Blanks... ";
    
    $fields = [];
    foreach ($vocabulary as $word => $translation) {
        $fields[] = [
            'text' => "$word = *$translation*",
            'solutions' => [$translation]
        ];
    }
    
    $blanks_params = json_encode([
        'fields' => $fields
    ]);
    
    $blanks_content_id = $wpdb->insert(
        $wpdb->prefix . 'h5p_contents',
        [
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'user_id' => 1,
            'title' => $lesson->post_title . ' - Fill Blanks',
            'library_id' => $blanks_lib,
            'parameters' => $blanks_params,
            'filtered' => $blanks_params,
            'slug' => sanitize_title($lesson->post_title . '-fill-blanks'),
            'embed_type' => 'div',
            'disable' => 0
        ]
    );
    
    if ($blanks_content_id) {
        echo "✅ ID: $wpdb->insert_id\n";
        $activities_created++;
    } else {
        echo "❌ Error\n";
    }
    
    echo "\n";
}

// Step 4: Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ SETUP COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📊 Summary:\n";
echo "  • Lessons processed: " . count($lessons) . "\n";
echo "  • Activities created: $activities_created\n";
echo "  • Activities per lesson: 3 (flashcards, quiz, fill blanks)\n";

echo "\n🎯 Next steps:\n";
echo "  1. Update lesson pages to embed H5P content\n";
echo "  2. Test activities at: http://localhost:8080/wp-admin/admin.php?page=h5p\n";
echo "  3. Verify Learning Path works correctly\n";

echo "\n✨ H5P activities setup complete!\n\n";
