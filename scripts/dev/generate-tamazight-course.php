<?php
// Course generation script - executed via wp eval


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

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  GENERADOR DE CURSO DE TAMAZIGHT - AMSAWAL AI STUDIO    ║\n";
echo "║  Powered by Pioneer AI (qwen3.7-max)                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Configuration
$course_config = [
    'name' => 'Tarifit Básico',
    'description' => 'Curso introductorio de lengua Tarifit (Tamazight rifeño)',
    'level' => 1,
    'language' => 'Tamazight (Tarifit)',
    'total_lessons' => 6,
    'activities_per_lesson' => ['flashcards', 'memory', 'multiple-choice', 'fill-blanks'],
];

// Define lessons structure
$lessons_structure = [
    [
        'number' => 1,
        'title' => 'Saludos y Presentaciones',
        'description' => 'Aprende a saludar y presentarte en Tarifit',
        'vocabulary' => [
            'ⵣⵓⵍ (zul)' => 'Hola',
            'ⵣⵓⵍ ⴼⵍⵍⴰⵎ (zul fellam)' => 'Hola (a mujer)',
            'ⵣⵓⵍ ⴼⵍⵍⴰⴽ (zul fellak)' => 'Hola (a hombre)',
            'ⵉⵙⵎ ⵉⵏⵓ (ism inu)' => 'Mi nombre es',
            'ⵎⴰⵏⵣⴰⴽⵉⵏ (manzakin)' => '¿Cómo estás?',
            'ⵍⴰⵢⵢⵓⵔ (layyur)' => 'Bien',
            'ⵜⵉⴼⵓⵍⵜ (tifult)' => 'Gracias',
        ],
    ],
    [
        'number' => 2,
        'title' => 'Números del 1 al 10',
        'description' => 'Aprende los números básicos en Tarifit',
        'vocabulary' => [
            'ⵢⵉⵊⵊ (yijj)' => 'Uno (1)',
            'ⵙⵉⵏ (sin)' => 'Dos (2)',
            'ⴽⵕⴰⴹ (kraḍ)' => 'Tres (3)',
            'ⴽⴽⵓⵥ (kkuẓ)' => 'Cuatro (4)',
            'ⵙⵎⵎⵓⵙ (smmus)' => 'Cinco (5)',
            'ⵙⴹⵉⵚ (sḍiṣ)' => 'Seis (6)',
            'ⵙⴰ (sa)' => 'Siete (7)',
            'ⵜⴰⵎ (tam)' => 'Ocho (8)',
            'ⵜⵥⴰ (tẓa)' => 'Nueve (9)',
            'ⵎⵔⴰⵡ (mraw)' => 'Diez (10)',
        ],
    ],
    [
        'number' => 3,
        'title' => 'La Familia',
        'description' => 'Vocabulario sobre miembros de la familia',
        'vocabulary' => [
            'ⴱⴰⴱⴰ (baba)' => 'Papá',
            'ⵉⵎⵎⴰ (imma)' => 'Mamá',
            'ⵜⴰⵎⵖⴰⵔⵜ (tamɣart)' => 'Abuela',
            'ⵎⵎⵉ (mmi)' => 'Hijo',
            'ⵉⵍⵍⵉ (illi)' => 'Hija',
            'ⴰⴳⵎⴰ (agma)' => 'Hermano',
            'ⵓⵍⵜⵎⴰ (ultma)' => 'Hermana',
            'ⵜⴰⵡⴰⵛⵓⵍⵜ (tawashult)' => 'Familia',
        ],
    ],
    [
        'number' => 4,
        'title' => 'Colores',
        'description' => 'Aprende los colores en Tarifit',
        'vocabulary' => [
            'ⴰⵣⴳⵣⴰⵡ (azgzaw)' => 'Verde',
            'ⴰⵣⴳⴳⵯⴰⵖ (azggwaɣ)' => 'Rojo',
            'ⴰⵏⵉⵍⵉ (anili)' => 'Azul',
            'ⴰⵡⵔⴰⵖ (awragh)' => 'Amarillo',
            'ⴰⵎⵍⵉⵍ (amlil)' => 'Blanco',
            'ⴰⵙⵟⵟⴰⵢ (asṭṭay)' => 'Negro',
            'ⴰⵅⵟⵟⴰⵢ (axṭṭay)' => 'Marrón',
        ],
    ],
    [
        'number' => 5,
        'title' => 'Los Días de la Semana',
        'description' => 'Aprende los días de la semana',
        'vocabulary' => [
            'ⴰⵢⵏⴰⵙ (aynas)' => 'Lunes',
            'ⴰⵙⵉⵏⴰⵙ (asinas)' => 'Martes',
            'ⴰⴽⵕⴰⵙ (akṛas)' => 'Miércoles',
            'ⴰⴽⵡⴰⵙ (akwas)' => 'Jueves',
            'ⴰⵙⵉⵎⵡⴰⵙ (asimwas)' => 'Viernes',
            'ⴰⵙⵉⴹⵢⴰⵙ (asiḍyas)' => 'Sábado',
            'ⴰⵙⴰⵎⴰⵙ (asamas)' => 'Domingo',
        ],
    ],
    [
        'number' => 6,
        'title' => 'Comida y Bebida',
        'description' => 'Vocabulario básico de alimentación',
        'vocabulary' => [
            'ⵓⵜⵛⵉ (utcí)' => 'Comida',
            'ⴰⵖⵔⵓⵎ (aghrum)' => 'Pan',
            'ⵜⵉⴼⵉⵢⵉ (tifiyyi)' => 'Carne',
            'ⴰⵢⵔⵎⴰⵏ (ayrman)' => 'Leche',
            'ⴰⵎⴰⵏ (aman)' => 'Agua',
            'ⴰⵜⴰⵢ (atayy)' => 'Té',
            'ⵜⴰⵣⵉⵜⵓⵏⵜ (tazitunt)' => 'Aceituna',
            'ⴰⵣⵉⵜ (azít)' => 'Aceite',
        ],
    ],
];

echo "📚 Configuración del curso:\n";
echo "   • Name: {$course_config['name']}\n";
echo "   • Nivel: {$course_config['level']} (Principiante)\n";
echo "   • Lecciones: {$course_config['total_lessons']}\n";
echo "   • Actividades por lección: " . implode(', ', $course_config['activities_per_lesson']) . "\n\n";

// Check if AI functions are available
if (!function_exists('wp_amsawal_ai_generate_lesson')) {
    echo "❌ Error: Las funciones de IA no están disponibles.\n";
    echo "   Asegúrate de que el plugin Amsawal está activo.\n";
    exit(1);
}

// ============================================
// STEP 1: Create main course page
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📖 STEP 1: Creating main course page\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check if course already exists
$existing_course = get_page_by_title($course_config['name'], OBJECT, 'page');

if ($existing_course) {
    echo "⚠️  Course already exists (ID: {$existing_course->ID})\n";
    echo "   Using existing course...\n\n";
    $course_id = $existing_course->ID;
} else {
    $course_id = wp_insert_post([
        'post_title'   => $course_config['name'],
        'post_content' => $course_config['description'],
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_parent'  => 0,
        'menu_order'   => 0,
    ]);
    
    if (is_wp_error($course_id)) {
        echo "❌ Error creating course: " . $course_id->get_error_message() . "\n";
        exit(1);
    }
    
    // Set course metadata
    update_post_meta($course_id, 'wp_amsawal_mb_course', 'tarifit-basico');
    update_post_meta($course_id, 'wp_amsawal_mb_type', 'course');
    update_post_meta($course_id, 'wp_amsawal_mb_level', $course_config['level']);
    
    echo "✅ Course created (ID: $course_id)\n\n";
}

// ============================================
// STEP 2: Create lessons
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📖 STEP 2: Creating {$course_config['total_lessons']} lessons\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$lesson_ids = [];

foreach ($lessons_structure as $lesson) {
    $lesson_number = $lesson['number'];
    $lesson_title = "{$course_config['name']} - Lección {$lesson_number}: {$lesson['title']}";
    
    echo "📝 Lesson {$lesson_number}: {$lesson['title']}\n";
    
    // Check if lesson already exists
    $existing_lesson = get_page_by_title($lesson_title, OBJECT, 'page');
    
    if ($existing_lesson) {
        echo "   ⚠️  Already exists (ID: {$existing_lesson->ID})\n";
        $lesson_ids[$lesson_number] = $existing_lesson->ID;
    } else {
        // Create lesson page
        $lesson_id = wp_insert_post([
            'post_title'   => $lesson_title,
            'post_content' => $lesson['description'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_parent'  => $course_id,
            'menu_order'   => $lesson_number,
        ]);
        
        if (is_wp_error($lesson_id)) {
            echo "   ❌ Error: " . $lesson_id->get_error_message() . "\n";
            continue;
        }
        
        // Set lesson metadata
        update_post_meta($lesson_id, 'wp_amsawal_mb_course', 'tarifit-basico');
        update_post_meta($lesson_id, 'wp_amsawal_mb_lesson', $lesson_number);
        update_post_meta($lesson_id, 'wp_amsawal_mb_type', 'lesson');
        update_post_meta($lesson_id, 'wp_amsawal_mb_vocabulary', $lesson['vocabulary']);
        update_post_meta($lesson_id, 'wp_amsawal_mb_difficulty', min(5, $lesson_number));
        
        $lesson_ids[$lesson_number] = $lesson_id;
        echo "   ✅ Created (ID: $lesson_id)\n";
    }
}

echo "\n✅ Total lessons created: " . count($lesson_ids) . "\n\n";

// ============================================
// STEP 3: Generate activities with AI
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🤖 STEP 3: Generating activities with Pioneer AI\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$total_activities = 0;
$errors = [];

foreach ($lesson_ids as $lesson_number => $lesson_id) {
    $lesson = $lessons_structure[$lesson_number - 1];
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📚 Lesson {$lesson_number}: {$lesson['title']}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Prepare context for AI generation
    $context = [
        'lesson_title' => $lesson['title'],
        'lesson_number' => $lesson_number,
        'course_name' => $course_config['name'],
        'language' => $course_config['language'],
        'description' => $lesson['description'],
        'vocabulary' => $lesson['vocabulary'],
        'activities' => $course_config['activities_per_lesson'],
    ];
    
    // Generate activities using the plugin function
    $result = wp_amsawal_ai_generate_lesson($lesson_id, $context);
    
    if (isset($result['error'])) {
        echo "   ❌ Error: {$result['error']}\n";
        $errors[] = "Lesson {$lesson_number}: {$result['error']}";
    } else {
        $activities_generated = $result['activities_generated'] ?? 0;
        $total_activities += $activities_generated;
        
        echo "   ✅ {$activities_generated} activities generated\n";
        
        if (!empty($result['details'])) {
            foreach ($result['details'] as $activity) {
                $type = $activity['type'];
                $status = $activity['status'] === 'success' ? '✅' : '❌';
                echo "      {$status} {$type}\n";
            }
        }
    }
    
    echo "\n";
    
    // Be nice to the API
    sleep(1);
}

// ============================================
// FINAL SUMMARY
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 FINAL SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "✅ Course: {$course_config['name']} (ID: {$course_id})\n";
echo "✅ Lessons created: " . count($lesson_ids) . "\n";
echo "✅ Activities generated: {$total_activities}\n";

if (!empty($errors)) {
    echo "\n⚠️  Errors found:\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
}

echo "\n";
echo "🎉 Course successfully generated!\n";
echo "\n";
echo "To view the course in WordPress:\n";
echo "   http://localhost:8080/?page_id={$course_id}\n";
echo "\n";
echo "To administer it:\n";
echo "   http://localhost:8080/wp-admin/post.php?post={$course_id}&action=edit\n";
echo "\n";
