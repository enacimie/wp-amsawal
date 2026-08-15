<?php
/**
 * Vincula el contenido AI de las lecciones a las páginas step
 * 
 * Las páginas step (121-138) necesitan el meta wp_amsawal_mb_lesson_content
 * que apunte al H5P ID correspondiente. Este script copia esos IDs desde
 * las metas _wp_amsawal_ai_{lesson_id}_{type}_0_h5pid de las lecciones.
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

echo "\n=== Vinculando contenido AI a páginas step ===\n\n";

// Mapeo de step pages a sus lecciones y tipos
$steps_map = [
    121 => ['lesson_id' => 109, 'type' => 'flashcards'],
    122 => ['lesson_id' => 109, 'type' => 'multiple-choice'],
    123 => ['lesson_id' => 109, 'type' => 'fill-blanks'],
    
    124 => ['lesson_id' => 110, 'type' => 'flashcards'],
    125 => ['lesson_id' => 110, 'type' => 'multiple-choice'],
    126 => ['lesson_id' => 110, 'type' => 'fill-blanks'],
    
    127 => ['lesson_id' => 111, 'type' => 'flashcards'],
    128 => ['lesson_id' => 111, 'type' => 'multiple-choice'],
    129 => ['lesson_id' => 111, 'type' => 'fill-blanks'],
    
    130 => ['lesson_id' => 112, 'type' => 'flashcards'],
    131 => ['lesson_id' => 112, 'type' => 'multiple-choice'],
    132 => ['lesson_id' => 112, 'type' => 'fill-blanks'],
    
    133 => ['lesson_id' => 113, 'type' => 'flashcards'],
    134 => ['lesson_id' => 113, 'type' => 'multiple-choice'],
    135 => ['lesson_id' => 113, 'type' => 'fill-blanks'],
    
    136 => ['lesson_id' => 114, 'type' => 'flashcards'],
    137 => ['lesson_id' => 114, 'type' => 'multiple-choice'],
    138 => ['lesson_id' => 114, 'type' => 'fill-blanks'],
];

$success_count = 0;
$error_count = 0;

foreach ($steps_map as $step_id => $info) {
    $lesson_id = $info['lesson_id'];
    $type = $info['type'];
    
    // Obtener el H5P ID desde la meta de la lección
    $meta_key = "_wp_amsawal_ai_{$lesson_id}_{$type}_0_h5pid";
    $h5p_id = get_post_meta($lesson_id, $meta_key, true);
    
    if (!$h5p_id) {
        echo "❌ Step $step_id: No se encontró H5P ID en lección $lesson_id ($type)\n";
        $error_count++;
        continue;
    }
    
    // Actualizar el meta de la página step
    update_post_meta($step_id, 'wp_amsawal_mb_lesson_content', $h5p_id);
    
    echo "✅ Step $step_id: Vinculado a H5P ID $h5p_id (lección $lesson_id, $type)\n";
    $success_count++;
}

echo "\n=== Resumen ===\n";
echo "Éxitos: $success_count\n";
echo "Errores: $error_count\n";
echo "Total: " . count($steps_map) . "\n\n";

echo "Ahora las páginas step deberían renderizar el contenido H5P correctamente.\n";
echo "Prueba: http://localhost:8080/cursos-disponibles/\n\n";
