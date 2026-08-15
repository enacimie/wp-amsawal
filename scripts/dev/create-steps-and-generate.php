<?php
/**
 * Crea páginas step (wrappers de actividades) para las 6 lecciones
 * del curso Tarifit Básico, y genera el contenido IA para cada una.
 * 
 * Uso: wp eval-file wp-content/plugins/wp-amsawal/create-steps-and-generate.php --allow-root
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

if (!defined('ABSPATH')) die("Ejecutar via wp-cli\n");

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║   CREANDO PÁGINAS STEP Y GENERANDO CONTENIDO IA          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$activity_types = ['flashcards', 'multiple-choice', 'fill-blanks'];

// Mapeo de lecciones con su vocabulario
$lessons_map = [
    109 => [
        'title' => 'Saludos',
        'num' => 1,
        'vocab' => [
            "ⴰⵣⵓⵍ (azul)" => "hola",
            "ⵜⴰⵏⵎⵎⵉⵔⵜ (tanemmirt)" => "gracias",
            "ⵉⵙⵎ ⵉⵏⵓ (ism inu)" => "me llamo",
            "ⵎⴰⵏⵣⴰⴽⵉⵏ (manzakin)" => "¿cómo estás?",
            "ⵍⴰⵢⵢⵓⵔ (layyur)" => "luna",
            "ⴰⵢⵜ ⵎⴰ (ayit ma)" => "hermano",
            "ⵓⵍⵜⵎⴰ (ultma)" => "hermana"
        ]
    ],
    110 => [
        'title' => 'Números',
        'num' => 2,
        'vocab' => [
            "ⵢⵉⵊⵊ (yijj)" => "uno",
            "ⵙⵉⵏ (sin)" => "dos",
            "ⴽⵕⴰⴹ (krad)" => "tres",
            "ⴽⴽⵓⵥ (kuz)" => "cuatro",
            "ⵙⵎⵎⵓⵙ (smmus)" => "cinco",
            "ⵙⴹⵉⵚ (sdis)" => "seis",
            "ⵙⴰ (sa)" => "siete"
        ]
    ],
    111 => [
        'title' => 'Familia',
        'num' => 3,
        'vocab' => [
            "ⴰⵢⵜ ⵎⴰ (ayit ma)" => "hermano",
            "ⵓⵍⵜⵎⴰ (ultma)" => "hermana",
            "ⴱⴰⴱⴰ (baba)" => "papá",
            "ⵉⵎⵎⴰ (imma)" => "mamá",
            "ⵎⵎⵉ (mmi)" => "hijo",
            "ⵢⵉⵍⵍⵉ (yilli)" => "hija",
            "ⵊⴷⴷⵉ (jddi)" => "abuelo"
        ]
    ],
    112 => [
        'title' => 'Colores',
        'num' => 4,
        'vocab' => [
            "ⴰⵣⴳⵣⴰⵡ (azgzaw)" => "verde",
            "ⴰⵣⴳⴳⵯⴰⵖ (azggʷaɣ)" => "rojo",
            "ⴰⵏⵉⵍⵉ (anili)" => "azul",
            "ⴰⵡⵔⴰⵖ (awraɣ)" => "amarillo",
            "ⴰⵎⵍⵉⵍ (amlil)" => "blanco",
            "ⴰⵙⵟⵟⴰⵢ (asṭṭay)" => "negro",
            "ⴰⵅⵟⵟⴰⵢ (axṭṭay)" => "gris"
        ]
    ],
    113 => [
        'title' => 'Comida',
        'num' => 5,
        'vocab' => [
            "ⴰⵖⵔⵓⵎ (aɣrum)" => "pan",
            "ⴰⵢⵔⵎⴰⵏ (ayrman)" => "leche",
            "ⴰⵎⴰⵏ (aman)" => "agua",
            "ⵜⵉⵔⵏⵉ (tirni)" => "dátil",
            "ⴰⵜⴰⵢ (atay)" => "té",
            "ⵉⵖⵉ (iɣi)" => "suero de leche",
            "ⵓⵍⵉ (uli)" => "aceite de oliva"
        ]
    ],
    114 => [
        'title' => 'Naturaleza',
        'num' => 6,
        'vocab' => [
            "ⴰⵊⴳⴷⵉⵍ (ajgdil)" => "montaña",
            "ⴰⵖⵓⵍⵉ (aɣuli)" => "río",
            "ⵜⴰⵖⵓⵍⵉ (taɣuli)" => "mar",
            "ⵜⴰⵡⵓⵔⵉ (tawuri)" => "sol",
            "ⴰⵢⵢⵓⵔ (ayyur)" => "luna",
            "ⵉⵜⵔⵉ (itri)" => "estrella",
            "ⴰⴷⵖⴰⵔ (adɣar)" => "cielo"
        ]
    ],
];

$parent_labels = [
    'flashcards' => 'Tarjetas',
    'multiple-choice' => 'Test',
    'fill-blanks' => 'Rellena'
];

$total_steps = 0;
$total_generated = 0;

foreach ($lessons_map as $lesson_id => $info) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📖 Lección {$info['num']}: {$info['title']} (ID: $lesson_id)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // 1. Asegurar que la lección tenga vocabulario guardado
    update_post_meta($lesson_id, 'wp_amsawal_mb_vocabulary', $info['vocab']);
    
    // 2. Limpiar actividades previas (step pages existentes)
    $existing_steps = get_children([
        'post_parent' => $lesson_id,
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1
    ]);
    foreach ($existing_steps as $step_page) {
        wp_delete_post($step_page->ID, true);
        echo "   🗑️  Borrada step anterior: {$step_page->post_title} (ID: {$step_page->ID})\n";
    }
    
    // 3. Crear una página step para cada tipo de actividad
    $step_counter = 1;
    foreach ($activity_types as $type) {
        $step_title = "{$parent_labels[$type]}: {$info['title']}";
        
        // Crear la página step
        $step_id = wp_insert_post([
            'post_title'   => $step_title,
            'post_content' => '', // vacío - el hook lo llena con H5P
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_parent'  => $lesson_id,
            'menu_order'   => $step_counter,
        ]);
        
        if (is_wp_error($step_id)) {
            echo "   ❌ Error creando step $type: " . $step_id->get_error_message() . "\n";
            continue;
        }
        
        // Metas necesarias para el plugin
        update_post_meta($step_id, 'wp_amsawal_mb_typeh5p', $type);
        update_post_meta($step_id, 'wp_amsawal_mb_lesson', $info['num']);
        update_post_meta($step_id, 'wp_amsawal_mb_step', $step_counter);
        update_post_meta($step_id, 'wp_amsawal_mb_course', 'tamazight');
        
        echo "   ✅ Step creado: $step_title (ID: $step_id)\n";
        $total_steps++;
        
        // 4. Generar contenido IA (si no existe ya)
        $existing_content = wp_amsawal_ai_get_content($lesson_id, $type, 0);
        if ($existing_content) {
            echo "      ⏭️  Contenido ya existía\n";
        } else {
            echo "      🤖 Generando contenido IA para $type...\n";
            
            $context = [
                'lesson_title' => "{$info['title']}",
                'course' => 'tamazight',
                'level' => 1,
                'language' => 'Tamazight (Tarifit)',
                'vocabulary' => $info['vocab'],
                'activities' => [$type],
            ];
            
            $result = wp_amsawal_ai_generate_lesson($lesson_id, $context, 0);
            
            if ($result['generated'] > 0) {
                echo "      ✅ Contenido generado ({$result['generated']} actividades)\n";
                $total_generated++;
            } else {
                echo "      ❌ Falló: " . implode(', ', $result['errors']) . "\n";
            }
        }
        
        $step_counter++;
        sleep(1); // Ser amable con la API
    }
    
    echo "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✨ RESUMEN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "   Páginas step creadas: $total_steps\n";
echo "   Contenidos IA generados: $total_generated\n";
echo "   Lecciones: " . count($lessons_map) . "\n\n";

echo "🔗 Prueba ahora:\n";
echo "   http://localhost:8080/tarifit-basico/\n";
echo "   http://localhost:8080/cursos-disponibles/\n\n";
