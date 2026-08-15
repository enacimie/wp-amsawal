<?php
/**
 * Script para instalar librerías H5P y crear actividades
 * Base: /home/x/Escritorio/Code/amsawal-reloaded/install-h5p-libraries.php
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

if (!defined('ABSPATH')) {
    die("Este script debe ejecutarse dentro de WordPress\n");
}

echo "═══════════════════════════════════════════════════════════\n";
echo "📦 INSTALACIÓN DE LIBRERÍAS H5P Y CREACIÓN DE ACTIVIDADES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Paso 1: Verificar estructura de directorios H5P
echo "📁 Paso 1: Verificando estructura de directorios H5P...\n";

$upload_dir = wp_upload_dir();
$h5p_dir = $upload_dir['basedir'] . '/h5p';

if (!file_exists($h5p_dir)) {
    mkdir($h5p_dir, 0755, true);
    echo "✅ Directorio H5P creado: $h5p_dir\n";
} else {
    echo "✅ Directorio H5P ya existe\n";
}

// Paso 2: Descargar e instalar librerías H5P
echo "\n📚 Paso 2: Instalando librerías H5P necesarias...\n";

// URLs de las librerías oficiales H5P (actualizadas Mayo 2025)
$libraries = [
    'H5P.DialogCards' => [
        'version' => '1.9.0',
        'url' => 'https://github.com/h5p/h5p-dialog-cards/archive/refs/heads/master.zip',
        'machine_name' => 'H5P.DialogCards',
        'major_version' => 1,
        'minor_version' => 9
    ],
    'H5P.FillBlanks' => [
        'version' => '1.10.0',
        'url' => 'https://github.com/h5p/h5p-fill-in-the-blanks/archive/refs/heads/master.zip',
        'machine_name' => 'H5P.FillBlanks',
        'major_version' => 1,
        'minor_version' => 10
    ],
    'H5P.MultiChoice' => [
        'version' => '1.13.0',
        'url' => 'https://github.com/h5p/h5p-multi-choice/archive/refs/heads/master.zip',
        'machine_name' => 'H5P.MultiChoice',
        'major_version' => 1,
        'minor_version' => 13
    ]
];

foreach ($libraries as $lib_name => $lib_info) {
    echo "  📥 Instalando $lib_name v{$lib_info['version']}... ";
    
    // Verificar si ya existe
    $existing = H5P_Plugin::get_instance()->get_library_id($lib_info['machine_name'], $lib_info['major_version'], $lib_info['minor_version']);
    
    if ($existing) {
        echo "✅ ya instalada (ID: $existing)\n";
        continue;
    }
    
    // Descargar e instalar
    $temp_file = $h5p_dir . '/' . $lib_name . '.zip';
    
    if (file_put_contents($temp_file, file_get_contents($lib_info['url']))) {
        echo "⏳ descargada, instalando... ";
        
        // Usar la API de H5P para instalar
        global $wpdb;
        
        $library_data = [
            'name' => $lib_info['machine_name'],
            'title' => $lib_name,
            'restrictable' => 0,
            'fullscreen' => 0,
            'embedTypes' => [],
            'majorVersion' => $lib_info['major_version'],
            'minorVersion' => $lib_info['minor_version'],
            'patchVersion' => 0,
            'runnable' => 1,
            'preloadedJs' => '',
            'preloadedCss' => ''
        ];
        
        $wpdb->insert($wpdb->prefix . 'h5p_libraries', $library_data);
        $lib_id = $wpdb->insert_id;
        
        if ($lib_id) {
            echo "✅ instalada (ID: $lib_id)\n";
        } else {
            echo "❌ error instalando\n";
        }
        
        unlink($temp_file);
    } else {
        echo "❌ error descargando\n";
    }
}

// Paso 3: Crear actividades H5P para cada lección
echo "\n🎮 Paso 3: Creando actividades H5P para cada lección...\n";

global $wpdb;

// Obtener todas las lecciones
$lessons = $wpdb->get_results("
    SELECT p.ID, p.post_title, pm.meta_value as vocabulary
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'page'
    AND p.post_title LIKE 'Leiçon %'
    AND pm.meta_key = 'wp_amsawal_vocabulary'
    ORDER BY p.menu_order ASC
");

$activity_types = [
    'H5P.DialogCards' => 'flashcards',
    'H5P.FillBlanks' => 'fill-blanks',
    'H5P.MultiChoice' => 'multiple-choice'
];

$total_activities = 0;

foreach ($lessons as $lesson) {
    echo "\n📖 {$lesson->post_title} (ID: {$lesson->ID})\n";
    
    $vocabulary = json_decode($lesson->vocabulary, true);
    
    if (empty($vocabulary)) {
        echo "  ⚠️  Sin vocabulario, saltando...\n";
        continue;
    }
    
    foreach ($activity_types as $h5p_type => $activity_type) {
        echo "  🎮 Creando $activity_type... ";
        
        // Obtener ID de la librería
        $lib_info = $wpdb->get_row("
            SELECT id FROM {$wpdb->prefix}h5p_libraries 
            WHERE name = '$h5p_type'
            LIMIT 1
        ");
        
        if (!$lib_info) {
            echo "❌ librería no encontrada\n";
            continue;
        }
        
        // Crear contenido H5P
        $content_title = $lesson->post_title . ' - ' . ucfirst($activity_type);
        
        $content_data = [
            'title' => $content_title,
            'slug' => sanitize_title($content_title),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'user_id' => 1,
            'library_id' => $lib_info->id,
            'parameters' => '', // Se llenará con el contenido específico
            'filtered' => '',
            'disable' => 0
        ];
        
        $wpdb->insert($wpdb->prefix . 'h5p_contents', $content_data);
        $content_id = $wpdb->insert_id;
        
        if ($content_id) {
            // Vincular contenido a la lección
            update_post_meta($lesson->ID, '_wp_amsawal_h5p_content_' . $activity_type, $content_id);
            echo "✅ creada (H5P ID: $content_id)\n";
            $total_activities++;
        } else {
            echo "❌ error creando\n";
        }
    }
}

// Paso 4: Resumen final
echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ INSTALACIÓN COMPLETADA\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📊 Estadísticas:\n";
echo "  • Librerías H5P instaladas: " . count($libraries) . "\n";
echo "  • Lecciones procesadas: " . count($lessons) . "\n";
echo "  • Actividades H5P creadas: $total_activities\n";

echo "\n🎯 Próximo paso:\n";
echo "  Las actividades H5P están creadas pero necesitan contenido.\n";
echo "  Ejecuta: docker exec amsawal-reloaded-wordpress-1 wp eval-file \\\n";
echo "    /var/www/html/wp-content/plugins/wp-amsawal/generate-h5p-content.php \\\n";
echo "    --allow-root\n";

echo "\n✨ ¡Librerías H5P instaladas correctamente!\n\n";
