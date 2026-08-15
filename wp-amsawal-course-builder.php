<?php
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
