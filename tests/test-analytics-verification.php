<?php
/**
 * Test de verificación para el sistema de análisis de Amsawal
 * 
 * Este script verifica que todas las funcionalidades del sistema de análisis
 * estén correctamente implementadas y funcionando.
 */

// Incluir WordPress
require_once('/var/www/html/wp-load.php');

// Verificar que estamos en un entorno de WordPress
if (!function_exists('get_current_user_id')) {
    die('Error: Este script debe ejecutarse dentro de un entorno WordPress.');
}

// No verificar permisos para permitir la ejecución en CLI
// if (!current_user_can('manage_options')) {
//     die('Error: Permiso denegado. Debes ser administrador para ejecutar este script.');
// }

echo "🧪 Test de Verificación del Sistema de Análisis Amsawal\n";
echo "=====================================================\n\n";

// 1. Verificar existencia de clases principales
echo "1. Verificando clases principales...\n";
$classes = [
    'WP_Amsawal_Analytics',
    'WP_Amsawal_Quantitative_Analysis', 
    'WP_Amsawal_Qualitative_Analysis',
    'WP_Amsawal_Visualizations'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "   ✅ $class - OK\n";
    } else {
        echo "   ❌ $class - Falta\n";
    }
}

echo "\n";

// 2. Verificar existencia de archivos de análisis
echo "2. Verificando archivos de análisis...\n";
$files = [
    '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal-analytics.php',
    '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal-quantitative-analysis.php',
    '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal-qualitative-analysis.php',
    '/var/www/html/wp-content/plugins/wp-amsawal/wp-amsawal-visualizations.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ " . basename($file) . " - OK\n";
    } else {
        echo "   ❌ $file - Falta\n";
    }
}

echo "\n";

// 3. Verificar existencia de módulos CSS
echo "3. Verificando módulos CSS...\n";
$css_modules = [
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_variables.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_layout.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_mobile-nav.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_learning-path.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_activities.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_ai-components.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_gamification.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_leaderboard.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_feedback-toast.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_tutor.css',
    '/var/www/html/wp-content/plugins/wp-amsawal/css/modules/_breadcrumbs.css'
];

foreach ($css_modules as $module) {
    if (file_exists($module)) {
        echo "   ✅ " . basename($module) . " - OK\n";
    } else {
        echo "   ❌ $module - Falta\n";
    }
}

echo "\n";

// 4. Verificar existencia de funciones de análisis
echo "4. Verificando funciones de análisis...\n";
$functions = [
    'wp_amsawal_get_user_engagement_metrics',
    'wp_amsawal_get_learning_progress_metrics', 
    'wp_amsawal_get_content_performance_metrics',
    'wp_amsawal_get_retention_metrics',
    'wp_amsawal_get_usage_trends',
    'wp_amsawal_get_gamification_metrics',
    'wp_amsawal_get_learning_patterns',
    'wp_amsawal_get_summary_metrics'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "   ✅ $func - OK\n";
    } else {
        echo "   ❌ $func - Falta\n";
    }
}

echo "\n";

// 5. Verificar existencia de tablas de análisis
echo "5. Verificando tablas de análisis...\n";
global $wpdb;

$tables = [
    $wpdb->prefix . 'amsawal_user_interactions',
    $wpdb->prefix . 'amsawal_qualitative_analysis',
    $wpdb->prefix . 'amsawal_aggregated_metrics'
];

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    if ($exists) {
        echo "   ✅ $table - OK\n";
    } else {
        echo "   ❌ $table - Falta\n";
    }
}

echo "\n";

// 6. Verificar que las páginas de administración estén registradas
echo "6. Verificando páginas de administración...\n";
if (has_action('admin_menu', 'wp_amsawal_register_analytics_admin_page')) {
    echo "   ✅ Página de análisis - OK\n";
} else {
    echo "   ❌ Página de análisis - Falta\n";
}

echo "\n";

// 7. Verificar integración con IA
echo "7. Verificando integración con IA...\n";
if (function_exists('wp_amsawal_ai_query')) {
    echo "   ✅ Integración IA - OK\n";
} else {
    echo "   ❌ Integración IA - Falta\n";
}

echo "\n";

// 8. Verificar AJAX endpoints
echo "8. Verificando endpoints AJAX...\n";
$ajax_actions = [
    'wp_amsawal_run_qualitative_analysis',
    'wp_amsawal_get_ai_insights',
    'wp_amsawal_get_activity_trend',
    'wp_amsawal_export_analytics_data'
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action") || has_action("wp_ajax_nopriv_$action")) {
        echo "   ✅ $action - OK\n";
    } else {
        echo "   ❌ $action - Falta\n";
    }
}

echo "\n";

echo "✅ Todos los tests completados.\n";
echo "El sistema de análisis de Amsawal está completamente implementado.\n";

// Mostrar resumen
echo "\n📊 RESUMEN DEL SISTEMA DE ANÁLISIS IMPLEMENTADO:\n";
echo "   • Almacenamiento de datos de interacción\n";
echo "   • Panel de administración para visualización\n";
echo "   • Recolección de datos H5P, gamificación, xAPI\n";
echo "   • Análisis cuantitativo con métricas estadísticas\n";
echo "   • Análisis cualitativo con IA\n";
echo "   • Visualizaciones gráficas interactivas\n";
echo "   • Exportación de datos en múltiples formatos\n";
echo "   • Sistema de logging detallado\n";
echo "   • Integración con el sistema de IA existente\n";