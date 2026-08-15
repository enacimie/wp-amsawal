<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * wp-amsawal-modelscope-images.php — Wrapper PHP para API de generación de imágenes de ModelScope
 *
 * Usa la API asíncrona de ModelScope para generar imágenes con IA.
 * Perfecto para crear imágenes de flashcards automáticamente.
 *
 * @package Amsawal
 * @since   0.0.4
 */

if (!defined('WPINC')) { die; }

/**
 * Configuración de ModelScope
 */
define('MODELSCOPE_BASE_URL', 'https://api-inference.modelscope.ai/');
if (!defined('MODELSCOPE_API_KEY')) {
    define('MODELSCOPE_API_KEY', getenv('MODELSCOPE_API_KEY') ?: '');
}
define('MODELSCOPE_DEFAULT_MODEL', 'Tongyi-MAI/Z-Image-Turbo');

/**
 * Genera una imagen usando la API de ModelScope (asíncrono)
 *
 * @param string $prompt  Descripción de la imagen a generar
 * @param string $model   Modelo a usar (default: Z-Image-Turbo)
 * @param int    $timeout Timeout en segundos (default: 300)
 * @return array|WP_Error ['image_url' => string, 'image_data' => string (base64)] o WP_Error
 */
function wp_amsawal_modelscope_generate_image($prompt, $model = MODELSCOPE_DEFAULT_MODEL, $timeout = 300) {
    // 1. Iniciar tarea asíncrona
    $response = wp_remote_post(
        MODELSCOPE_BASE_URL . 'v1/images/generations',
        array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . MODELSCOPE_API_KEY,
                'Content-Type' => 'application/json',
                'X-ModelScope-Async-Mode' => 'true',
            ),
            'body' => wp_json_encode(array(
                'model' => $model,
                'prompt' => $prompt,
            ), JSON_UNESCAPED_UNICODE),
        )
    );

    if (is_wp_error($response)) {
        return new WP_Error('modelscope_request_failed', 'Error iniciando tarea: ' . $response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!isset($body['task_id'])) {
        return new WP_Error('modelscope_no_task_id', 'Respuesta inválida de ModelScope: ' . wp_remote_retrieve_body($response));
    }

    $task_id = $body['task_id'];

    // 2. Polling hasta que la tarea termine
    $start_time = time();
    $poll_interval = 5; // segundos

    while (time() - $start_time < $timeout) {
        sleep($poll_interval);

        $status_response = wp_remote_get(
            MODELSCOPE_BASE_URL . 'v1/tasks/' . $task_id,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . MODELSCOPE_API_KEY,
                    'Content-Type' => 'application/json',
                    'X-ModelScope-Task-Type' => 'image_generation',
                ),
            )
        );

        if (is_wp_error($status_response)) {
            continue; // Reintentar
        }

        $status_body = json_decode(wp_remote_retrieve_body($status_response), true);
        $task_status = $status_body['task_status'] ?? '';

        if ($task_status === 'SUCCEED') {
            // 3. Descargar la imagen
            if (!isset($status_body['output_images'][0])) {
                return new WP_Error('modelscope_no_image_url', 'Tarea completada pero sin URL de imagen');
            }

            $image_url = $status_body['output_images'][0];

            $image_response = wp_remote_get($image_url, array('timeout' => 60));
            
            if (is_wp_error($image_response)) {
                return new WP_Error('modelscope_image_download_failed', 'Error descargando imagen: ' . $image_response->get_error_message());
            }

            $image_data = wp_remote_retrieve_body($image_response);
            $base64_image = base64_encode($image_data);

            return array(
                'image_url' => $image_url,
                'image_data' => $base64_image,
                'task_id' => $task_id,
            );
        }

        if ($task_status === 'FAILED') {
            $error_msg = $status_body['message'] ?? 'Error desconocido';
            return new WP_Error('modelscope_task_failed', 'Generación de imagen falló: ' . $error_msg);
        }

        // Continuar esperando
    }

    return new WP_Error('modelscope_timeout', 'Timeout esperando generación de imagen');
}

/**
 * Genera imagen para una flashcard específica
 *
 * @param string $word_text    Texto de la palabra (ej: "ⴰⵣⵓⵍ")
 * @param string $word_meaning Significado (ej: "Hola")
 * @param string $style        Estilo de imagen (default: "educational illustration")
 * @return array|WP_Error      ['image_url' => string, 'image_data' => string]
 */
function wp_amsawal_modelscope_generate_flashcard_image($word_text, $word_meaning, $style = 'educational illustration, clean, colorful') {
    $prompt = sprintf(
        '%s, representing the concept "%s" (meaning: %s), flat design, vibrant colors, suitable for language learning',
        $style,
        $word_meaning,
        $word_text
    );

    return wp_amsawal_modelscope_generate_image($prompt);
}

/**
 * Genera imágenes para todas las flashcards de una lección
 *
 * @param int   $lesson_id  ID de la lección
 * @param array $cards      Array de tarjetas [['text' => '', 'answer' => ''], ...]
 * @return array            ['generated' => int, 'errors' => array, 'image_ids' => array]
 */
function wp_amsawal_modelscope_generate_flashcard_images_batch($lesson_id, $cards) {
    $result = array(
        'generated' => 0,
        'errors' => array(),
        'image_ids' => array(),
    );

    foreach ($cards as $index => $card) {
        $word_text = $card['text'] ?? '';
        $word_meaning = $card['answer'] ?? '';

        if (empty($word_text) || empty($word_meaning)) {
            $result['errors'][] = "Tarjeta $index: texto o significado vacío";
            continue;
        }

        // echo \"<span aria-hidden='true'>🎨</span> Generating image for: $word_text ($word_meaning)...\n";

        $image_result = wp_amsawal_modelscope_generate_flashcard_image($word_text, $word_meaning);

        if (is_wp_error($image_result)) {
            $result['errors'][] = "Tarjeta $index: " . $image_result->get_error_message();
            echo "   <span aria-hidden='true'>❌</span> Error: " . $image_result->get_error_message() . "\n";
            continue;
        }

        // Guardar imagen como attachment en WordPress
        $upload_dir = wp_upload_dir();
        $filename = sanitize_file_name("flashcard-{$lesson_id}-{$index}.jpg");
        $filepath = $upload_dir['path'] . '/' . $filename;

        $image_data = base64_decode($image_result['image_data']);
        if (file_put_contents($filepath, $image_data) === false) {
            $result['errors'][] = "Tarjeta $index: error guardando imagen";
            echo "   <span aria-hidden='true'>❌</span> Error guardando archivo\n";
            continue;
        }

        // Crear attachment en WordPress
        $attachment = array(
            'post_mime_type' => 'image/jpeg',
            'post_title' => sanitize_title($word_text . ' - flashcard'),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attach_id = wp_insert_attachment($attachment, $filepath, $lesson_id);

        if (is_wp_error($attach_id)) {
            $result['errors'][] = "Tarjeta $index: error creando attachment";
            echo "   <span aria-hidden='true'>❌</span> Error creando attachment\n";
            continue;
        }

        // Generar metadatos de imagen
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        $image_url = wp_get_attachment_url($attach_id);
        
        // Actualizar la tarjeta con la URL de la imagen
        $cards[$index]['image'] = $image_url;

        $result['generated']++;
        $result['image_ids'][$index] = $attach_id;

        // echo \"   <span aria-hidden='true'>✅</span> Generated (ID: $attach_id)\n";

        // Ser amable con la API
        sleep(1);
    }

    // Actualizar el contenido de flashcards con las imágenes
    if ($result['generated'] > 0) {
        $flashcard_data = array('cards' => $cards);
        wp_amsawal_ai_store_content($lesson_id, 'flashcards', $flashcard_data, 0);
    }

    return $result;
}

/**
 * Ejemplo de uso:
 * 
 * // Generar una imagen
 * $result = wp_amsawal_modelscope_generate_image('Un gato dorado');
 * 
 * // Generar imágenes para flashcards
 * $cards = [
 *     ['text' => 'ⴰⵣⵓⵍ', 'answer' => 'Hola'],
 *     ['text' => 'ⵜⵉⵏⴰ', 'answer' => 'Perro'],
 * ];
 * $result = wp_amsawal_modelscope_generate_flashcard_images_batch(123, $cards);
 */
