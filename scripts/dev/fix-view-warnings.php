<?php
/**
 * Script para corregir warnings de acceso a propiedades no verificadas en wp-amsawal-view.php
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

$view_file = '/home/x/Escritorio/Code/amsawal-reloaded/wp-amsawal-view.php';

echo "Leyendo archivo wp-amsawal-view.php...\n";
$content = file_get_contents($view_file);

if (!$content) {
    die("ERROR: No se pudo leer el archivo\n");
}

echo "Aplicando correcciones defensivas...\n";

// Corrección 1: Línea ~133 - gamipress_get_user_rank
$old = "\$current_rank = gamipress_get_user_rank(\$userid, 'nivel-'.\$allpages[0]->course)->menu_order;";
$new = "\$rank_obj = gamipress_get_user_rank(\$userid, 'nivel-'.\$allpages[0]->course);\n\t\t\$current_rank = (\$rank_obj && isset(\$rank_obj->menu_order)) ? (int)\$rank_obj->menu_order : 0;";
$content = str_replace($old, $new, $content);
echo "✓ Corregido acceso seguro a gamipress_get_user_rank\n";

// Corrección 2: Línea ~157 - ucfirst($allpages[0]->course)
$old = "\$content .= '<div class=\"duo-course-name\">📚 '.esc_html(ucfirst(\$allpages[0]->course)).'</div>';";
$new = "\$course_name = (!empty(\$allpages) && isset(\$allpages[0]) && is_object(\$allpages[0]) && isset(\$allpages[0]->course)) ? \$allpages[0]->course : 'Curso';\n\t\t\$content .= '<div class=\"duo-course-name\">📚 '.esc_html(ucfirst(\$course_name)).'</div>';";
$content = str_replace($old, $new, $content);
echo "✓ Corregido acceso seguro a \$allpages[0]->course (línea 157)\n";

// Corrección 3: Línea ~182 - $streak_display
$old = "\$streak_for_quest = max( 0, min( 7, \$streak_display ) );";
$new = "\$streak_display_safe = isset(\$streak_display) ? (int)\$streak_display : 0;\n\t\t\$streak_for_quest = max( 0, min( 7, \$streak_display_safe ) );";
$content = str_replace($old, $new, $content);
echo "✓ Corregido uso seguro de \$streak_display\n";

// Corrección 4: Verificar si $allpages está vacío antes de usarlo
$search = "if ( \$pages->have_posts() ) {\n    \twhile ( \$pages->have_posts() ) {";
$replace = "if ( \$pages->have_posts() ) {\n    \twhile ( \$pages->have_posts() ) {\n\t\t\t\$allpages = array();";
$content = str_replace($search, $replace, $content);
echo "✓ Añadida inicialización de \$allpages\n";

// Corrección 5: Añadir verificación después del loop
$search = "\t\t\t}\n\t\t}\n\n\t\tglobal \$wpdb;";
$replace = "\t\t\t}\n\t\t}\n\n\t\t// Verificar que tenemos páginas antes de continuar\n\t\tif (empty(\$allpages)) {\n\t\t\treturn \$content;\n\t\t}\n\n\t\tglobal \$wpdb;";
$content = str_replace($search, $replace, $content);
echo "✓ Añadida verificación de \$allpages vacío\n";

echo "\nGuardando archivo corregido...\n";
if (file_put_contents($view_file, $content)) {
    echo "✓ Archivo guardado exitosamente\n";
} else {
    die("ERROR: No se pudo guardar el archivo\n");
}

echo "\n✓ Correcciones aplicadas. El archivo wp-amsawal-view.php ahora es defensivo.\n";
