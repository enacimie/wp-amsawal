<?php
/**
 * Generador completo del curso Tamazight con actividades H5P + IA
 * 
 * Este script:
 * 1. Corrige las meta keys de vocabulario
 * 2. Genera actividades H5P para cada lección usando IA
 * 3. Crea contenidos H5P reales
 * 4. Muestra progreso en tiempo real
 * 
 * Uso: wp eval-file --allow-root --url=http://localhost:8080 generate-full-course.php
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
    die("Este script debe ejecutarse via wp-cli\n");
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║             CURSO TAMAZIGHT - GENERADOR COMPLETO         ║\n";
echo "║         Vocabulario + Actividades H5P + Imágenes         ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Configuración
$course_id = 108;
$activity_types = ['flashcards', 'multiple-choice', 'fill-blanks'];
$generate_images = false; // ModelScope requiere API key configurada

// Datos de lecciones con vocabulario Tifinagh-Español
$lessons_data = [
    109 => [
        'title' => 'Lección 1: Saludos',
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
        'title' => 'Lección 2: Números',
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
        'title' => 'Lección 3: Familia',
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
        'title' => 'Lección 4: Colores',
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
        'title' => 'Lección 5: Comida',
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
        'title' => 'Lección 6: Naturaleza',
        'vocab' => [
            "ⴰⵊⴳⴷⵉⵍ (ajgdil)" => "montaña",
            "ⴰⵖⵓⵍⵉ (aɣuli)" => "río",
            "ⵜⴰⵖⵓⵍⵉ (taɣuli)" => "mar",
            "ⵜⴰⵡⵓⵔⵉ (tawuri)" => "sol",
            "ⴰⵢⵢⵓⵔ (ayyur)" => "luna",
            "ⵉⵜⵔⵉ (itri)" => "estrella",
            "ⴰⴷⵖⴰⵔ (adɣar)" => "cielo"
        ]
    ]
];

// Función para mostrar progreso
function show_progress($current, $total, $message) {
    $percent = round(($current / $total) * 100);
    $bar_width = 40;
    $filled = round(($current / $total) * $bar_width);
    $empty = $bar_width - $filled;
    
    $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
    printf("\r[%s] %3d%% (%d/%d) %s", $bar, $percent, $current, $total, $message);
    
    if ($current === $total) {
        echo "\n";
    }
}

// PASO 1: Configurar vocabulario correcto para cada lección
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📚 PASO 1: Configurando vocabulario para 6 lecciones\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$total_lessons = count($lessons_data);
$current = 0;

foreach ($lessons_data as $lesson_id => $data) {
    $current++;
    
    // Actualizar título
    wp_update_post([
        'ID' => $lesson_id,
        'post_title' => $data['title']
    ]);
    
    // Configurar meta keys correctas
    update_post_meta($lesson_id, 'wp_amsawal_mb_typeh5p', 'lesson');
    update_post_meta($lesson_id, 'wp_amsawal_mb_course', 'tamazight');
    update_post_meta($lesson_id, 'wp_amsawal_mb_lesson', $current);
    update_post_meta($lesson_id, 'wp_amsawal_mb_step', $current);
    update_post_meta($lesson_id, 'wp_amsawal_mb_vocabulary', $data['vocab']);
    
    show_progress($current, $total_lessons, "Configurando {$data['title']}");
}

echo "\n\n✅ Vocabulario configurado correctamente\n\n";

// PASO 2: Generar actividades H5P con IA
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🤖 PASO 2: Generando actividades H5P con IA (Pioneer AI)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$total_activities = $total_lessons * count($activity_types);
$current_activity = 0;
$total_generated = 0;
$total_h5p_created = 0;

foreach ($lessons_data as $lesson_id => $data) {
    echo "\n📖 {$data['title']} (ID: $lesson_id)\n";
    echo "   Vocabulario: " . count($data['vocab']) . " palabras\n";
    
    foreach ($activity_types as $activity_type) {
        $current_activity++;
        show_progress($current_activity, $total_activities, "Generando $activity_type...");
        
        try {
            $context = [
                'lesson_title' => $data['title'],
                'course' => 'tamazight',
                'level' => 1,
                'language' => 'tzm',
                'vocabulary' => $data['vocab'],
                'activities' => [$activity_type]
            ];
            
            $result = wp_amsawal_ai_generate_lesson($lesson_id, $context);
            
            if (is_array($result) && isset($result['generated'])) {
                $total_generated += $result['generated'];
                if (isset($result['h5p_count'])) {
                    $total_h5p_created += $result['h5p_count'];
                }
            }
            
        } catch (Exception $e) {
            // Continuar con la siguiente actividad
        }
        
        usleep(100000); // 0.1s de pausa entre requests
    }
}

echo "\n\n✅ Actividades generadas: $total_generated\n";
echo "✅ Contenidos H5P creados: $total_h5p_created\n\n";

// PASO 3: Generar imágenes (opcional)
if ($generate_images && function_exists('wp_amsawal_modelscope_generate_flashcard_images_batch')) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎨 PASO 3: Generando imágenes con ModelScope\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $total_images = 0;
    
    foreach ($lessons_data as $lesson_id => $data) {
        echo "Generando imágenes para {$data['title']}...\n";
        
        // Construir estructura de cards para generación de imágenes
        $cards = [];
        $i = 0;
        foreach ($data['vocab'] as $tif => $esp) {
            $cards[] = [
                'text' => $tif,
                'answer' => $esp,
                'hint' => '',
                'image' => null,
                '_index' => $i++
            ];
        }
        
        $img_result = wp_amsawal_modelscope_generate_flashcard_images_batch($lesson_id, $cards, $data['title']);
        if (is_array($img_result) && isset($img_result['generated'])) {
            $total_images += $img_result['generated'];
        }
        
        echo "\n";
    }
    
    echo "✅ Imágenes generadas: $total_images\n\n";
}

// RESUMEN FINAL
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMEN DEL CURSO                     ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📊 Estadísticas:\n";
echo "   • Curso ID: $course_id\n";
echo "   • Lecciones: $total_lessons\n";
echo "   • Actividades generadas: $total_generated\n";
echo "   • Contenidos H5P: $total_h5p_created\n";
if ($generate_images) {
    echo "   • Imágenes: $total_images\n";
}
echo "\n";

echo "🔗 Acceso:\n";
echo "   • Admin: http://localhost:8080/wp-admin/post.php?post=$course_id&action=edit\n";
echo "   • Frontend: http://localhost:8080/cursos-disponibles/\n";
echo "\n";

echo "✨ ¡Curso Tamazight listo para usar!\n\n";
