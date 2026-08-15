#!/usr/bin/env python3
"""Fase 18: Content Management - Course builder y H5P authoring tools"""

def apply_f18_1_course_builder():
    """F18-1: Course builder interface"""
    builder_code = """<?php
/**
 * F18-1: Course Builder Interface
 * Admin interface for creating and managing courses
 */

if (!defined('ABSPATH')) exit;

// Register custom post type for courses
function amsawal_register_course_post_type() {
    $labels = [
        'name' => 'Cursos',
        'singular_name' => 'Curso',
        'add_new' => 'Añadir nuevo',
        'add_new_item' => 'Añadir nuevo curso',
        'edit_item' => 'Editar curso',
        'new_item' => 'Nuevo curso',
        'view_item' => 'Ver curso',
        'search_items' => 'Buscar cursos',
        'not_found' => 'No se encontraron cursos',
        'menu_name' => 'Cursos Amsawal'
    ];
    
    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'menu_icon' => 'dashicons-welcome-learn-more',
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'cursos']
    ];
    
    register_post_type('amsawal_course', $args);
}

add_action('init', 'amsawal_register_course_post_type');

// Register custom post type for lessons
function amsawal_register_lesson_post_type() {
    $labels = [
        'name' => 'Lecciones',
        'singular_name' => 'Lección',
        'add_new' => 'Añadir nueva',
        'add_new_item' => 'Añadir nueva lección',
        'edit_item' => 'Editar lección',
        'new_item' => 'Nueva lección',
        'view_item' => 'Ver lección',
        'search_items' => 'Buscar lecciones',
        'not_found' => 'No se encontraron lecciones',
        'menu_name' => 'Lecciones Amsawal'
    ];
    
    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-book-alt',
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'lecciones']
    ];
    
    register_post_type('amsawal_lesson', $args);
}

add_action('init', 'amsawal_register_lesson_post_type');

// Add meta boxes for course settings
function amsawal_add_course_meta_boxes() {
    add_meta_box(
        'amsawal_course_settings',
        'Configuración del Curso',
        'amsawal_course_settings_callback',
        'amsawal_course',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'amsawal_add_course_meta_boxes');

function amsawal_course_settings_callback($post) {
    wp_nonce_field('amsawal_course_meta', 'amsawal_course_meta_nonce');
    
    $level = get_post_meta($post->ID, '_amsawal_course_level', true);
    $estimated_hours = get_post_meta($post->ID, '_amsawal_course_estimated_hours', true);
    $prerequisites = get_post_meta($post->ID, '_amsawal_course_prerequisites', true);
    $learning_objectives = get_post_meta($post->ID, '_amsawal_course_learning_objectives', true);
    
    ?>
    <p>
        <label for="course_level"><strong>Nivel:</strong></label><br>
        <select name="course_level" id="course_level">
            <option value="beginner" <?php selected($level, 'beginner'); ?>>Principiante</option>
            <option value="intermediate" <?php selected($level, 'intermediate'); ?>>Intermedio</option>
            <option value="advanced" <?php selected($level, 'advanced'); ?>>Avanzado</option>
        </select>
    </p>
    <p>
        <label for="estimated_hours"><strong>Horas estimadas:</strong></label><br>
        <input type="number" name="estimated_hours" id="estimated_hours" value="<?php echo esc_attr($estimated_hours); ?>" min="1" max="100">
    </p>
    <p>
        <label for="prerequisites"><strong>Requisitos previos:</strong></label><br>
        <textarea name="prerequisites" id="prerequisites" rows="3" style="width:100%;"><?php echo esc_textarea($prerequisites); ?></textarea>
    </p>
    <p>
        <label for="learning_objectives"><strong>Objetivos de aprendizaje:</strong></label><br>
        <textarea name="learning_objectives" id="learning_objectives" rows="4" style="width:100%;"><?php echo esc_textarea($learning_objectives); ?></textarea>
    </p>
    <?php
}

// Save course meta
function amsawal_save_course_meta($post_id) {
    if (!isset($_POST['amsawal_course_meta_nonce']) || 
        !wp_verify_nonce($_POST['amsawal_course_meta_nonce'], 'amsawal_course_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['course_level'])) {
        update_post_meta($post_id, '_amsawal_course_level', sanitize_text_field($_POST['course_level']));
    }
    
    if (isset($_POST['estimated_hours'])) {
        update_post_meta($post_id, '_amsawal_course_estimated_hours', absint($_POST['estimated_hours']));
    }
    
    if (isset($_POST['prerequisites'])) {
        update_post_meta($post_id, '_amsawal_course_prerequisites', sanitize_textarea_field($_POST['prerequisites']));
    }
    
    if (isset($_POST['learning_objectives'])) {
        update_post_meta($post_id, '_amsawal_course_learning_objectives', sanitize_textarea_field($_POST['learning_objectives']));
    }
}

add_action('save_post_amsawal_course', 'amsawal_save_course_meta');

// Add lessons to course
function amsawal_add_lesson_to_course($course_id, $lesson_id, $order = 0) {
    update_post_meta($lesson_id, '_amsawal_course_id', $course_id);
    update_post_meta($lesson_id, '_amsawal_lesson_order', $order);
    return true;
}

// Get course lessons
function amsawal_get_course_lessons($course_id) {
    return get_posts([
        'post_type' => 'amsawal_lesson',
        'meta_key' => '_amsawal_course_id',
        'meta_value' => $course_id,
        'orderby' => 'meta_value_num',
        'meta_query' => [
            [
                'key' => '_amsawal_course_id',
                'value' => $course_id
            ]
        ],
        'order' => 'ASC',
        'numberposts' => -1
    ]);
}

// AJAX handler for course builder
add_action('wp_ajax_amsawal_create_lesson', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $course_id = absint($_POST['course_id'] ?? 0);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $content = wp_kses_post($_POST['content'] ?? '');
    $order = absint($_POST['order'] ?? 0);
    
    if (empty($title)) {
        wp_send_json_error('Título requerido');
    }
    
    $lesson_id = wp_insert_post([
        'post_type' => 'amsawal_lesson',
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'draft',
        'post_author' => get_current_user_id()
    ]);
    
    if (is_wp_error($lesson_id)) {
        wp_send_json_error($lesson_id->get_error_message());
    }
    
    amsawal_add_lesson_to_course($course_id, $lesson_id, $order);
    
    wp_send_json_success(['lesson_id' => $lesson_id]);
});
"""
    
    with open('wp-amsawal-course-builder.php', 'w', encoding='utf-8') as f:
        f.write(builder_code)
    print("✅ F18-1: Course builder interface created")
    return True

def apply_f18_2_h5p_authoring():
    """F18-2: H5P authoring tools"""
    h5p_authoring = """<?php
/**
 * F18-2: H5P Authoring Tools
 * Simplified interface for creating H5P content
 */

if (!defined('ABSPATH')) exit;

// H5P content type templates
function amsawal_get_h5p_templates() {
    return [
        'flashcards' => [
            'name' => 'Tarjetas de Memoria',
            'library' => 'H5P.DialogCards',
            'default_params' => [
                'cards' => [
                    ['text' => 'Frente', 'answer' => 'Reverso']
                ],
                'behaviour' => [
                    'enableRetry' => 1,
                    'enableSolutionsButton' => 1
                ]
            ]
        ],
        'mcq' => [
            'name' => 'Opción Múltiple',
            'library' => 'H5P.MultiChoice',
            'default_params' => [
                'question' => 'Escribe tu pregunta aquí',
                'answers' => [
                    ['text' => 'Opción correcta', 'correct' => true],
                    ['text' => 'Opción incorrecta 1', 'correct' => false],
                    ['text' => 'Opción incorrecta 2', 'correct' => false]
                ],
                'behaviour' => [
                    'enableRetry' => 1,
                    'enableSolutionsButton' => 1
                ]
            ]
        ],
        'fill_blanks' => [
            'name' => 'Completa los espacios',
            'library' => 'H5P.Blanks',
            'default_params' => [
                'intro' => 'Completa los espacios en blanco',
                'texts' => [
                    ['text' => 'La capital de Marruecos es <strong>___</strong>.', 'answer' => [['text' => 'Rabat']]]
                ],
                'behaviour' => [
                    'enableRetry' => 1,
                    'enableSolutionsButton' => 1
                ]
            ]
        ],
        'memory_game' => [
            'name' => 'Juego de Memoria',
            'library' => 'H5P.MemoryGame',
            'default_params' => [
                'cards' => [
                    ['text' => 'Par 1'],
                    ['text' => 'Par 1']
                ],
                'behaviour' => [
                    'enableRetry' => 1
                ]
            ]
        ],
        'mark_words' => [
            'name' => 'Marca las palabras',
            'library' => 'H5P.MarkTheWords',
            'default_params' => [
                'intro' => 'Marca todas las palabras correctas',
                'text' => '<p>Escribe un texto con <span class="correct-answer">palabras correctas</span> y palabras incorrectas.</p>',
                'behaviour' => [
                    'enableRetry' => 1,
                    'enableSolutionsButton' => 1
                ]
            ]
        ]
    ];
}

// Create H5P content from template
function amsawal_create_h5p_from_template($template_name, $custom_params = []) {
    $templates = amsawal_get_h5p_templates();
    
    if (!isset($templates[$template_name])) {
        return new WP_Error('invalid_template', 'Plantilla no válida');
    }
    
    $template = $templates[$template_name];
    $params = array_replace_recursive($template['default_params'], $custom_params);
    
    global $wpdb;
    $table = $wpdb->prefix . 'h5p_contents';
    
    $wpdb->insert($table, [
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'title' => $template['name'] . ' - ' . date('Y-m-d H:i'),
        'library_id' => amsawal_get_library_id($template['library']),
        'parameters' => json_encode($params, JSON_UNESCAPED_UNICODE),
        'filtered' => '',
        'slug' => sanitize_title($template['name'] . '-' . time()),
        'embed_type' => 'div',
        'disable' => 0,
        'content_type' => $template['name']
    ]);
    
    if ($wpdb->last_error) {
        return new WP_Error('db_error', $wpdb->last_error);
    }
    
    return $wpdb->insert_id;
}

// Get H5P library ID by name
function amsawal_get_library_id($library_name) {
    global $wpdb;
    $table = $wpdb->prefix . 'h5p_libraries';
    
    $library_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE name = %s AND runnable = 1 ORDER BY major_version DESC, minor_version DESC LIMIT 1",
        str_replace('H5P.', '', $library_name)
    ));
    
    return $library_id ?: 1; // Default to first library if not found
}

// Update H5P content
function amsawal_update_h5p_content($h5p_id, $params) {
    global $wpdb;
    $table = $wpdb->prefix . 'h5p_contents';
    
    $updated = $wpdb->update($table,
        [
            'parameters' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'updated_at' => current_time('mysql')
        ],
        ['id' => $h5p_id],
        ['%s', '%s'],
        ['%d']
    );
    
    if ($updated === false) {
        return new WP_Error('update_failed', $wpdb->last_error);
    }
    
    return true;
}

// Delete H5P content
function amsawal_delete_h5p_content($h5p_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'h5p_contents';
    
    $wpdb->delete($table, ['id' => $h5p_id], ['%d']);
    
    return true;
}

// AJAX handlers
add_action('wp_ajax_amsawal_get_h5p_templates', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    wp_send_json_success(amsawal_get_h5p_templates());
});

add_action('wp_ajax_amsawal_create_h5p', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $template = sanitize_text_field($_POST['template'] ?? '');
    $custom_params = json_decode(stripslashes($_POST['params'] ?? '{}'), true);
    
    $h5p_id = amsawal_create_h5p_from_template($template, $custom_params);
    
    if (is_wp_error($h5p_id)) {
        wp_send_json_error($h5p_id->get_error_message());
    }
    
    wp_send_json_success(['h5p_id' => $h5p_id]);
});

add_action('wp_ajax_amsawal_update_h5p', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $h5p_id = absint($_POST['h5p_id'] ?? 0);
    $params = json_decode(stripslashes($_POST['params'] ?? '{}'), true);
    
    $result = amsawal_update_h5p_content($h5p_id, $params);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Contenido actualizado');
});

add_action('wp_ajax_amsawal_delete_h5p', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('delete_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $h5p_id = absint($_POST['h5p_id'] ?? 0);
    amsawal_delete_h5p_content($h5p_id);
    
    wp_send_json_success('Contenido eliminado');
});
"""
    
    with open('wp-amsawal-h5p-authoring.php', 'w', encoding='utf-8') as f:
        f.write(h5p_authoring)
    print("✅ F18-2: H5P authoring tools created")
    return True

def apply_f18_3_bulk_content_creation():
    """F18-3: Bulk content creation"""
    bulk_code = """<?php
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
        $exercises = amsawal_generate_exercise($lesson_id, $exercise_type, $difficulty);
        
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
"""
    
    with open('wp-amsawal-bulk-content.php', 'w', encoding='utf-8') as f:
        f.write(bulk_code)
    print("✅ F18-3: Bulk content creation created")
    return True

def apply_f18_4_content_versioning():
    """F18-4: Content versioning system"""
    versioning_code = """<?php
/**
 * F18-4: Content Versioning System
 * Track changes to lessons and H5P content
 */

if (!defined('ABSPATH')) exit;

// Database table for content versions
function amsawal_create_versions_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        content_type VARCHAR(50) NOT NULL,
        content_id BIGINT UNSIGNED NOT NULL,
        version INT NOT NULL DEFAULT 1,
        content_data LONGTEXT NOT NULL,
        author_id BIGINT UNSIGNED NOT NULL,
        change_summary TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY content_lookup (content_type, content_id),
        KEY author_id (author_id)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('after_switch_theme', 'amsawal_create_versions_table');
amsawal_create_versions_table();

// Save version
function amsawal_save_content_version($content_type, $content_id, $content_data, $change_summary = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    
    // Get current version
    $current_version = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(version) FROM $table WHERE content_type = %s AND content_id = %d",
        $content_type, $content_id
    ));
    
    $new_version = ($current_version ?: 0) + 1;
    
    $wpdb->insert($table, [
        'content_type' => $content_type,
        'content_id' => $content_id,
        'version' => $new_version,
        'content_data' => json_encode($content_data, JSON_UNESCAPED_UNICODE),
        'author_id' => get_current_user_id(),
        'change_summary' => $change_summary
    ]);
    
    return $new_version;
}

// Get version history
function amsawal_get_version_history($content_type, $content_id, $limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT v.*, u.display_name as author_name
         FROM $table v
         LEFT JOIN {$wpdb->users} u ON v.author_id = u.ID
         WHERE v.content_type = %s AND v.content_id = %d
         ORDER BY v.version DESC
         LIMIT %d",
        $content_type, $content_id, $limit
    ));
}

// Restore version
function amsawal_restore_version($content_type, $content_id, $version) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_content_versions';
    
    $version_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE content_type = %s AND content_id = %d AND version = %d",
        $content_type, $content_id, $version
    ));
    
    if (!$version_data) {
        return new WP_Error('not_found', 'Versión no encontrada');
    }
    
    $content_data = json_decode($version_data->content_data, true);
    
    // Restore based on content type
    if ($content_type === 'lesson') {
        wp_update_post([
            'ID' => $content_id,
            'post_content' => $content_data['post_content'] ?? '',
            'post_title' => $content_data['post_title'] ?? ''
        ]);
    } elseif ($content_type === 'h5p') {
        amsawal_update_h5p_content($content_id, $content_data);
    }
    
    return true;
}

// Hook into lesson updates
add_action('save_post_amsawal_lesson', function($post_id, $post, $update) {
    if (!$update) return; // Only track updates, not new posts
    
    $content_data = [
        'post_title' => $post->post_title,
        'post_content' => $post->post_content
    ];
    
    amsawal_save_content_version('lesson', $post_id, $content_data, 'Actualización manual');
}, 10, 3);

// AJAX handlers
add_action('wp_ajax_amsawal_get_version_history', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $content_type = sanitize_text_field($_POST['type'] ?? '');
    $content_id = absint($_POST['id'] ?? 0);
    
    $history = amsawal_get_version_history($content_type, $content_id);
    wp_send_json_success($history);
});

add_action('wp_ajax_amsawal_restore_version', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $content_type = sanitize_text_field($_POST['type'] ?? '');
    $content_id = absint($_POST['id'] ?? 0);
    $version = absint($_POST['version'] ?? 0);
    
    $result = amsawal_restore_version($content_type, $content_id, $version);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Versión restaurada');
});
"""
    
    with open('wp-amsawal-versioning.php', 'w', encoding='utf-8') as f:
        f.write(versioning_code)
    print("✅ F18-4: Content versioning system created")
    return True

def apply_f18_5_content_analytics():
    """F18-5: Content analytics and insights"""
    analytics_code = """<?php
/**
 * F18-5: Content Analytics and Insights
 * Track which content performs best
 */

if (!defined('ABSPATH')) exit;

// Get lesson performance metrics
function amsawal_get_lesson_metrics($lesson_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    $metrics = new stdClass();
    
    // Total starts
    $metrics->starts = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE lesson_id = %d AND event_type = 'lesson_start'",
        $lesson_id
    )));
    
    // Total completions
    $metrics->completions = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE lesson_id = %d AND event_type = 'lesson_complete'",
        $lesson_id
    )));
    
    // Completion rate
    $metrics->completion_rate = $metrics->starts > 0 ? round(($metrics->completions / $metrics->starts) * 100, 2) : 0;
    
    // Average quiz score
    $metrics->avg_quiz_score = floatval($wpdb->get_var($wpdb->prepare(
        "SELECT AVG(JSON_EXTRACT(metadata, '$.score')) 
         FROM $table 
         WHERE lesson_id = %d AND event_type = 'quiz_complete'",
        $lesson_id
    )));
    
    // Time spent (if tracked)
    $metrics->avg_time_spent = floatval($wpdb->get_var($wpdb->prepare(
        "SELECT AVG(JSON_EXTRACT(metadata, '$.time_spent')) 
         FROM $table 
         WHERE lesson_id = %d AND event_type = 'lesson_complete'",
        $lesson_id
    )));
    
    return $metrics;
}

// Get top performing lessons
function amsawal_get_top_lessons($course_id, $limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            lesson_id,
            p.post_title,
            COUNT(CASE WHEN event_type = 'lesson_start' THEN 1 END) as starts,
            COUNT(CASE WHEN event_type = 'lesson_complete' THEN 1 END) as completions,
            ROUND(COUNT(CASE WHEN event_type = 'lesson_complete' THEN 1 END) / 
                  NULLIF(COUNT(CASE WHEN event_type = 'lesson_start' THEN 1 END), 0) * 100, 2) as completion_rate
        FROM $table t
        JOIN {$wpdb->posts} p ON t.lesson_id = p.ID
        WHERE p.post_parent = %d OR p.ID = %d
        GROUP BY lesson_id, p.post_title
        ORDER BY completions DESC
        LIMIT %d",
        $course_id, $course_id, $limit
    ));
}

// Get struggling lessons (low completion rate)
function amsawal_get_struggling_lessons($course_id, $min_starts = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            lesson_id,
            p.post_title,
            COUNT(CASE WHEN event_type = 'lesson_start' THEN 1 END) as starts,
            COUNT(CASE WHEN event_type = 'lesson_complete' THEN 1 END) as completions,
            ROUND(COUNT(CASE WHEN event_type = 'lesson_complete' THEN 1 END) / 
                  NULLIF(COUNT(CASE WHEN event_type = 'lesson_start' THEN 1 END), 0) * 100, 2) as completion_rate
        FROM $table t
        JOIN {$wpdb->posts} p ON t.lesson_id = p.ID
        WHERE p.post_parent = %d OR p.ID = %d
        GROUP BY lesson_id, p.post_title
        HAVING starts >= %d AND completion_rate < 50
        ORDER BY completion_rate ASC",
        $course_id, $course_id, $min_starts
    ));
}

// AJAX handler
add_action('wp_ajax_amsawal_get_content_analytics', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $course_id = absint($_POST['course_id'] ?? 0);
    
    if ($lesson_id) {
        $metrics = amsawal_get_lesson_metrics($lesson_id);
        wp_send_json_success($metrics);
    } elseif ($course_id) {
        $top = amsawal_get_top_lessons($course_id);
        $struggling = amsawal_get_struggling_lessons($course_id);
        wp_send_json_success(['top' => $top, 'struggling' => $struggling]);
    } else {
        wp_send_json_error('ID requerido');
    }
});
"""
    
    with open('wp-amsawal-content-analytics.php', 'w', encoding='utf-8') as f:
        f.write(analytics_code)
    print("✅ F18-5: Content analytics created")
    return True

# Ejecutar todas las mejoras de gestión de contenido
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 18 - Content Management...\n")
    
    apply_f18_1_course_builder()
    apply_f18_2_h5p_authoring()
    apply_f18_3_bulk_content_creation()
    apply_f18_4_content_versioning()
    apply_f18_5_content_analytics()
    
    print("\n✨ Mejoras de gestión de contenido completadas")
