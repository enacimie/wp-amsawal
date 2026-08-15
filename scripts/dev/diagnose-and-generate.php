<?php
/**
 * Script de diagnóstico y generación de actividades para el curso de Tamazight
 * Usa Pioneer AI para texto y ModelScope para imágenes
 */

// Verificar que estamos en WordPress


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
    die("Este script debe ejecutarse dentro de WordPress\n");
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNÓSTICO Y GENERACIÓN - CURSO TAMAZIGHT              ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ============================================
// PASO 1: Verificar configuración de IA
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔍 PASO 1: Verificando configuración de IA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$ai_endpoint = get_option('wp_amsawal_ai_endpoint');
$ai_key = get_option('wp_amsawal_ai_key');
$ai_model = get_option('wp_amsawal_ai_model');

echo "• Endpoint: " . ($ai_endpoint ?: '❌ NO CONFIGURADO') . "\n";
echo "• API Key: " . ($ai_key ? substr($ai_key, 0, 20) . '...' : '❌ NO CONFIGURADA') . "\n";
echo "• Modelo: " . ($ai_model ?: '❌ NO CONFIGURADO') . "\n\n";

if (!$ai_endpoint || !$ai_key) {
    echo "❌ ERROR: Configuración de IA incompleta\n";
    echo "   Ejecuta:\n";
    echo "   wp option update wp_amsawal_ai_endpoint 'https://api.pioneer.ai/v1/chat/completions' --allow-root\n";
    echo "   wp option update wp_amsawal_ai_key 'TU_API_KEY' --allow-root\n";
    echo "   wp option update wp_amsawal_ai_model 'qwen3.7-max' --allow-root\n";
    exit(1);
}

// ============================================
// PASO 2: Encontrar el curso creado
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📚 PASO 2: Buscando curso de Tamazight\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$course_id = 108; // ID del curso creado anteriormente
$course = get_post($course_id);

if (!$course || $course->post_type !== 'page') {
    echo "❌ ERROR: No se encontró el curso (ID: $course_id)\n";
    exit(1);
}

echo "✅ Curso encontrado: {$course->post_title} (ID: $course_id)\n\n";

// ============================================
// PASO 3: Listar lecciones
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📖 PASO 3: Listando lecciones del curso\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$lessons = get_posts(array(
    'post_type' => 'page',
    'post_parent' => $course_id,
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
));

if (empty($lessons)) {
    echo "❌ ERROR: No se encontraron lecciones\n";
    exit(1);
}

echo "✅ Lecciones encontradas: " . count($lessons) . "\n\n";

foreach ($lessons as $i => $lesson) {
    $lesson_num = get_post_meta($lesson->ID, 'wp_amsawal_mb_lesson', true);
    $lesson_type = get_post_meta($lesson->ID, 'wp_amsawal_mb_type', true);
    $vocabulary = get_post_meta($lesson->ID, 'wp_amsawal_mb_vocabulary', true);
    
    echo "   📝 Lección " . ($lesson_num ?: $i+1) . ": {$lesson->post_title}\n";
    echo "      ID: {$lesson->ID} | Tipo: " . ($lesson_type ?: 'none') . "\n";
    echo "      Vocabulario: " . (is_array($vocabulary) ? count($vocabulary) . " palabras" : 'none') . "\n\n";
}

// ============================================
// PASO 4: Probar conexión con Pioneer AI
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🤖 PASO 4: Probando conexión con Pioneer AI\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!function_exists('wp_amsawal_ai_generate_lesson')) {
    echo "❌ ERROR: Función wp_amsawal_ai_generate_lesson() no está definida\n";
    echo "   Verifica que wp-amsawal-ai.php esté cargado correctamente\n";
    exit(1);
}

$test_lesson = get_post(109); // Primera lección
$test_prompt = "Responde solo con la palabra: OK";
$test_response = wp_remote_post(
    get_option('wp_amsawal_ai_endpoint'),
    array(
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Bearer ' . get_option('wp_amsawal_ai_key'),
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'model' => get_option('wp_amsawal_ai_model'),
            'messages' => array(array('role' => 'user', 'content' => $test_prompt)),
        )),
    )
);

if (is_wp_error($test_response)) {
    echo "❌ ERROR: No se pudo conectar con Pioneer AI\n";
    echo "   " . $test_response->get_error_message() . "\n";
    exit(1);
}

$test_body = json_decode(wp_remote_retrieve_body($test_response), true);
if (empty($test_body['choices'][0]['message']['content'])) {
    echo "❌ ERROR: Respuesta inválida de Pioneer AI\n";
    echo "   " . print_r($test_body, true) . "\n";
    exit(1);
}

echo "✅ Conexión con Pioneer AI exitosa\n";
echo "   Respuesta: " . trim($test_body['choices'][0]['message']['content']) . "\n\n";

// ============================================
// PASO 5: Generar actividades para cada lección
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎯 PASO 5: Generando actividades con IA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$activity_types = array('flashcards', 'memory', 'multiple-choice', 'fill-blanks');
$total_generated = 0;

foreach ($lessons as $lesson) {
    $lesson_num = get_post_meta($lesson->ID, 'wp_amsawal_mb_lesson', true) ?: 1;
    $vocabulary = get_post_meta($lesson->ID, 'wp_amsawal_mb_vocabulary', true);
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📚 Lección {$lesson_num}: {$lesson->post_title} (ID: {$lesson->ID})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Verificar vocabulario
    if (!is_array($vocabulary) || empty($vocabulary)) {
        echo "   ⚠️  No hay vocabulario definido. Saltando...\n\n";
        continue;
    }
    
    echo "   Vocabulario disponible: " . count($vocabulary) . " palabras\n\n";
    
    // Generar actividades
    echo "   🤖 Generando actividades...\n";
    
    $context = array(
        'lesson_title' => $lesson->post_title,
        'lesson_number' => $lesson_num,
        'course_name' => $course->post_title,
        'language' => 'Tamazight (Tarifit)',
        'description' => $lesson->post_content,
        'vocabulary' => $vocabulary,
        'activities' => $activity_types,
    );
    
    $result = wp_amsawal_ai_generate_lesson($lesson->ID, $context);
    
    // Mostrar resultado detallado
    if (isset($result['error'])) {
        echo "   ❌ ERROR: {$result['error']}\n\n";
    } else {
        $generated = $result['activities_generated'] ?? 0;
        $errors = $result['errors'] ?? array();
        
        echo "   ✅ Actividades generadas: {$generated}\n";
        
        if (!empty($errors)) {
            echo "   ⚠️  Errores:\n";
            foreach ($errors as $error) {
                echo "      • {$error}\n";
            }
        }
        
        if (!empty($result['details'])) {
            echo "\n   Detalles:\n";
            foreach ($result['details'] as $detail) {
                $type = $detail['type'];
                $status = $detail['status'] === 'success' ? '✅' : '❌';
                $msg = $detail['status'] === 'success' ? 'OK' : $detail['message'];
                echo "      {$status} {$type}: {$msg}\n";
            }
        }
        
        $total_generated += $generated;
    }
    
    echo "\n";
    
    // Pausa para no saturar la API
    sleep(2);
}

// ============================================
// PASO 6: Generar imágenes para flashcards (opcional)
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎨 PASO 6: Generando imágenes para flashcards (ModelScope)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!function_exists('wp_amsawal_modelscope_generate_flashcard_images_batch')) {
    echo "⚠️  Función wp_amsawal_modelscope_generate_flashcard_images_batch() no disponible\n";
    echo "   Las flashcards se generarán sin imágenes\n\n";
} else {
    echo "¿Deseas generar imágenes para las flashcards? (s/N): ";
    $answer = trim(fgets(STDIN));
    
    if (strtolower($answer) === 's') {
        foreach ($lessons as $lesson) {
            $lesson_num = get_post_meta($lesson->ID, 'wp_amsawal_mb_lesson', true) ?: 1;
            $vocabulary = get_post_meta($lesson->ID, 'wp_amsawal_mb_vocabulary', true);
            
            if (!is_array($vocabulary) || empty($vocabulary)) continue;
            
            echo "\n   📚 Lección {$lesson_num}: {$lesson->post_title}\n";
            
            // Convertir vocabulario a formato de cards
            $cards = array();
            foreach ($vocabulary as $tamazight => $spanish) {
                $cards[] = array(
                    'text' => $tamazight,
                    'answer' => $spanish,
                );
            }
            
            echo "   🎨 Generando " . count($cards) . " imágenes...\n";
            
            $img_result = wp_amsawal_modelscope_generate_flashcard_images_batch($lesson->ID, $cards);
            
            echo "   ✅ Imágenes generadas: " . $img_result['generated'] . "\n";
            
            if (!empty($img_result['errors'])) {
                echo "   ⚠️  Errores:\n";
                foreach ($img_result['errors'] as $error) {
                    echo "      • {$error}\n";
                }
            }
        }
    } else {
        echo "   Saltando generación de imágenes\n";
    }
}

// ============================================
// RESUMEN FINAL
// ============================================
echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RESUMEN FINAL\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "✅ Curso: {$course->post_title} (ID: {$course_id})\n";
echo "✅ Lecciones: " . count($lessons) . "\n";
echo "✅ Actividades generadas: {$total_generated}\n";

if ($total_generated > 0) {
    echo "\n🎉 ¡Curso completado exitosamente!\n";
    echo "\n📱 Para ver el curso:\n";
    echo "   http://localhost:8080/?page_id={$course_id}\n";
    echo "\n⚙️  Para administrarlo:\n";
    echo "   http://localhost:8080/wp-admin/post.php?post={$course_id}&action=edit\n";
} else {
    echo "\n❌ No se generaron actividades. Revisa los errores arriba.\n";
}

echo "\n";
