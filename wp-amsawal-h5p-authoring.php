<?php
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
        'parameters' => wp_json_encode($params, JSON_UNESCAPED_UNICODE),
        'filtered' => '',
        'slug' => sanitize_title($template['name'] . '-' . time()),
        'embed_type' => 'div',
        'disable' => 0,
        'content_type' => $template['name']
    ], ['%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s']);
    
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
