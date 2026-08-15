<?php
/**
 * F18-3: Bulk Content Creation
 * Create multiple lessons/exercises at once
 */

if (!defined('ABSPATH')) exit;

// Bulk create lessons from CSV
function amsawal_bulk_create_lessons($course_id, $csv_data) {
    if (!current_user_can('edit_posts')) {
        return new WP_Error('permission', 'Acceso denegado');
    }
    
    $lessons = [];
    $errors = [];
    
    foreach ($csv_data as $index => $row) {
        $title = sanitize_text_field($row['title'] ?? '');
        $content = wp_kses_post($row['content'] ?? '');
        $order = absint($row['order'] ?? ($index + 1));
        
        if (empty($title)) {
            $errors[] = "Fila {$index}: Título vacío";
            continue;
        }
        
        $lesson_id = wp_insert_post([
            'post_type' => 'amsawal_lesson',
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_author' => get_current_user_id()
        ]);
        
        if (is_wp_error($lesson_id)) {
            $errors[] = "Fila {$index}: " . $lesson_id->get_error_message();
            continue;
        }
        
        amsawal_add_lesson_to_course($course_id, $lesson_id, $order);
        $lessons[] = $lesson_id;
    }
    
    return [
        'created' => count($lessons),
        'errors' => $errors,
        'lesson_ids' => $lessons
    ];
}

// Bulk generate H5P exercises
function amsawal_bulk_generate_exercises($lesson_ids, $exercise_type, $difficulty = 0.5) {
    if (!current_user_can('edit_posts')) {
        return new WP_Error('permission', 'Acceso denegado');
    }
    
    $results = [];
    
    foreach ($lesson_ids as $lesson_id) {
        $exercises = wp_amsawal_ai_generate_lesson($lesson_id, ['type' => $exercise_type, 'difficulty' => $difficulty]);

        if (is_wp_error($exercises)) {
            $results[$lesson_id] = ['success' => false, 'error' => $exercises->get_error_message()];
            continue;
        }

        $results[$lesson_id] = ['success' => true, 'count' => count($exercises)];
    }
    
    return $results;
}

// AJAX handler for bulk creation
add_action('wp_ajax_amsawal_bulk_create_lessons', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $course_id = absint($_POST['course_id'] ?? 0);
    $csv_data = json_decode(stripslashes($_POST['csv_data'] ?? '[]'), true);
    
    if (empty($csv_data)) {
        wp_send_json_error('Datos vacíos');
    }
    
    $result = amsawal_bulk_create_lessons($course_id, $csv_data);
    wp_send_json_success($result);
});

add_action('wp_ajax_amsawal_bulk_generate_exercises', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $lesson_ids = array_map('absint', $_POST['lesson_ids'] ?? []);
    $exercise_type = sanitize_text_field($_POST['type'] ?? 'mcq');
    $difficulty = floatval($_POST['difficulty'] ?? 0.5);
    
    if (empty($lesson_ids)) {
        wp_send_json_error('No hay lecciones seleccionadas');
    }
    
    $results = amsawal_bulk_generate_exercises($lesson_ids, $exercise_type, $difficulty);
    wp_send_json_success($results);
});
