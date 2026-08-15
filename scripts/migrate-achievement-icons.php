<?php
/**
 * Script de migración: Convertir emojis UTF-8 a Dashicons en achievement icons
 * 
 * Este script convierte los iconos de logros almacenados como emojis UTF-8
 * en la base de datos a Dashicons (HTML span elements)
 */

if (!defined('ABSPATH')) {
    require_once dirname(dirname(__DIR__)) . '/wp-load.php';
}

global $wpdb;

// Mapeo de emojis a Dashicons
$emoji_to_dashicon = [
    '🎓' => 'dashicons-welcome-learn-more',
    '📚' => 'dashicons-book',
    '📖' => 'dashicons-book-alt',
    '👨‍🎓' => 'dashicons-welcome-learn-more',
    '🔤' => 'dashicons-editor-spellcheck',
    '👋' => 'dashicons-smiley',
    '🔢' => 'dashicons-editor-ol',
    '👨‍👩‍👧' => 'dashicons-groups',
    '🎨' => 'dashicons-art',
    '🔥' => 'dashicons-star-filled',
    '⚡' => 'dashicons-performance',
    '🌟' => 'dashicons-star-filled',
    '👑' => 'dashicons-awards',
    '🥉' => 'dashicons-awards',
    '🥈' => 'dashicons-awards',
    '🥇' => 'dashicons-awards',
    '💎' => 'dashicons-star-filled',
    '🏅' => 'dashicons-awards',
];

echo "Iniciando migración de iconos de logros...\n\n";

// Obtener todos los achievement icons con emojis
$achievements = $wpdb->get_results("
    SELECT p.ID, p.post_title, pm.meta_value as icon
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_amsawal_achievement_icon'
");

$migrated = 0;
$skipped = 0;

foreach ($achievements as $ach) {
    $icon = trim($ach->icon);
    
    // Verificar si ya es un Dashicon (comienza con <span)
    if (strpos($icon, '<span') !== false) {
        echo "⏭️  Saltando '{$ach->post_title}' - ya es Dashicon\n";
        $skipped++;
        continue;
    }
    
    // Convertir emoji a Dashicon
    if (isset($emoji_to_dashicon[$icon])) {
        $new_icon = '<span class="dashicons ' . $emoji_to_dashicon[$icon] . '" aria-hidden="true"></span>';
        
        // Actualizar en la base de datos
        $updated = update_post_meta($ach->ID, '_amsawal_achievement_icon', $new_icon);
        
        if ($updated) {
            echo "✅ Migado '{$ach->post_title}': {$icon} → {$emoji_to_dashicon[$icon]}\n";
            $migrated++;
        } else {
            echo "❌ Error al migrar '{$ach->post_title}'\n";
        }
    } else {
        echo "⚠️  Icono no reconocido en '{$ach->post_title}': {$icon}\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Migrados: {$migrated}\n";
echo "Saltados: {$skipped}\n";
echo "Total procesados: " . count($achievements) . "\n";

// Limpiar cache de transients
delete_transient('amsawal_achievements_catalog_v1');
echo "\n✓ Cache de achievements limpiado\n";
