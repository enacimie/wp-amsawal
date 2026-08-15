<?php
/**
 * Verifica que todas las páginas step estén correctamente configuradas
 * y que el curso sea accesible.
 * 
 * Uso: wp eval-file wp-content/plugins/wp-amsawal/verify-course-structure.php --allow-root
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
echo "║   VERIFICACIÓN DE ESTRUCTURA DEL CURSO                   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// 1. Verificar el curso principal
$course_id = 108;
$course = get_post($course_id);

if (!$course) {
    die("❌ El curso ID $course_id no existe\n");
}

echo "📚 CURSO: {$course->post_title} (ID: $course_id)\n";
echo "   Slug: {$course->post_name}\n";
echo "   URL: " . get_permalink($course_id) . "\n";

// Metas del curso
$course_metas = [
    'wp_amsawal_mb_typeh5p',
    'wp_amsawal_mb_course',
    'wp_amsawal_mb_course_image',
];

echo "\n📋 Metas del curso:\n";
foreach ($course_metas as $meta) {
    $value = get_post_meta($course_id, $meta, true);
    $status = $value ? '✅' : '⚠️ ';
    echo "   $status $meta: " . ($value ?: '(vacío)') . "\n";
}

// 2. Listar todas las lecciones
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📖 LECCIONES Y STEPS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$lessons = get_posts([
    'post_type' => 'page',
    'post_parent' => $course_id,
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
]);

echo "Total de lecciones: " . count($lessons) . "\n\n";

$issues = [];
$ok_count = 0;

foreach ($lessons as $lesson) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📝 {$lesson->post_title} (ID: {$lesson->ID})\n";
    echo "   Slug: {$lesson->post_name}\n";
    
    // Metas de la lección
    $lesson_type = get_post_meta($lesson->ID, 'wp_amsawal_mb_typeh5p', true);
    $lesson_num = get_post_meta($lesson->ID, 'wp_amsawal_mb_lesson', true);
    $lesson_num_steps = get_post_meta($lesson->ID, 'wp_amsawal_mb_lesson_num_steps', true);
    
    echo "   Tipo: " . ($lesson_type ?: '(no definido)') . "\n";
    echo "   Número: " . ($lesson_num ?: '(no definido)') . "\n";
    echo "   Num steps: " . ($lesson_num_steps ?: '(no definido)') . "\n";
    
    if ($lesson_type !== 'lesson') {
        $issues[] = "Lección {$lesson->ID}: falta meta wp_amsawal_mb_typeh5p='lesson'";
    }
    
    // Buscar step pages hijos
    $steps = get_posts([
        'post_type' => 'page',
        'post_parent' => $lesson->ID,
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);
    
    echo "\n   Steps: " . count($steps) . "\n";
    
    if (count($steps) === 0) {
        $issues[] = "Lección {$lesson->ID} ({$lesson->post_title}): no tiene páginas step";
        continue;
    }
    
    foreach ($steps as $step) {
        $step_num = get_post_meta($step->ID, 'wp_amsawal_mb_step', true);
        $step_type = get_post_meta($step->ID, 'wp_amsawal_mb_typeh5p', true);
        $lesson_content = get_post_meta($step->ID, 'wp_amsawal_mb_lesson_content', true);
        
        echo "   ├─ Step $step_num: {$step->post_title} (ID: {$step->ID})\n";
        echo "   │  Tipo: " . ($step_type ?: '(no definido)') . "\n";
        echo "   │  H5P ID: " . ($lesson_content ?: '(no definido)') . "\n";
        echo "   │  URL: " . get_permalink($step->ID) . "\n";
        
        // Verificaciones
        $step_ok = true;
        
        if (!$step_num) {
            $issues[] = "Step {$step->ID}: falta meta wp_amsawal_mb_step";
            $step_ok = false;
        }
        
        if (!$step_type) {
            $issues[] = "Step {$step->ID}: falta meta wp_amsawal_mb_typeh5p";
            $step_ok = false;
        }
        
        if (!$lesson_content) {
            $issues[] = "Step {$step->ID} ({$step->post_title}): falta meta wp_amsawal_mb_lesson_content (H5P ID)";
            $step_ok = false;
        } else {
            // Verificar que el H5P existe
            $h5p = get_post($lesson_content);
            if (!$h5p || $h5p->post_type !== 'h5p') {
                $issues[] = "Step {$step->ID}: H5P ID {$lesson_content} no existe o no es tipo 'h5p'";
                $step_ok = false;
            } else {
                echo "   │  ✅ H5P encontrado: {$h5p->post_title}\n";
            }
        }
        
        if ($step_ok) {
            $ok_count++;
        }
    }
    
    echo "\n";
}

// 3. Resumen
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RESUMEN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "✅ Steps correctos: $ok_count\n";
echo "❌ Problemas encontrados: " . count($issues) . "\n\n";

if (count($issues) > 0) {
    echo "⚠️  LISTA DE PROBLEMAS:\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". $issue\n";
    }
    echo "\n";
}

// 4. Probar renderizado
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🧪 PRUEBA DE RENDERIZADO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (count($lessons) > 0) {
    $first_lesson = $lessons[0];
    $first_steps = get_posts([
        'post_type' => 'page',
        'post_parent' => $first_lesson->ID,
        'post_status' => 'publish',
        'numberposts' => 1,
    ]);
    
    if (count($first_steps) > 0) {
        $test_step = $first_steps[0];
        echo "Probando con: {$test_step->post_title} (ID: {$test_step->ID})\n";
        echo "URL: " . get_permalink($test_step->ID) . "\n\n";
        
        // Simular el filtro the_content
        $original_post = $GLOBALS['post'];
        $GLOBALS['post'] = $test_step;
        setup_postdata($test_step);
        
        $content = get_the_content();
        $rendered = apply_filters('the_content', $content);
        
        // Restaurar
        $GLOBALS['post'] = $original_post;
        
        // Verificar si contiene markers del plugin
        if (strpos($rendered, 'duo-amsawal-activity-wrapper') !== false) {
            echo "✅ El hook de renderizado SÍ está funcionando\n";
            echo "   Se encontró el wrapper de actividad en el HTML\n";
        } else {
            echo "❌ El hook de renderizado NO está funcionando\n";
            echo "   No se encontró el wrapper de actividad en el HTML\n";
            
            // Mostrar un fragmento del contenido renderizado
            $preview = substr(strip_tags($rendered), 0, 200);
            echo "\n   Preview del contenido renderizado:\n";
            echo "   \"$preview\"\n";
        }
    }
}

echo "\n";

// 5. Instrucciones finales
if (count($issues) === 0) {
    echo "✅ TODO CORRECTO\n\n";
    echo "El curso está listo. Prueba estas URLs:\n";
    echo "   • Curso: " . get_permalink($course_id) . "\n";
    
    if (count($lessons) > 0) {
        echo "   • Primera lección: " . get_permalink($lessons[0]->ID) . "\n";
    }
    
    echo "\n";
} else {
    echo "⚠️  HAY PROBLEMAS QUE RESOLVER\n\n";
    echo "Revisa la lista de problemas arriba y ejecuta los scripts de reparación necesarios.\n\n";
}
