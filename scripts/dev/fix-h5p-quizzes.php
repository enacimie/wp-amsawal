<?php
/*
 * Script para corregir quizzes H5P y asignarlos a las lecciones correctas
 * Uso: docker exec amsawal-reloaded-wordpress-1 php /tmp/fix-h5p-quizzes.php
 */

// Cargar WordPress


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

require_once '/var/www/html/wp-load.php';

global $wpdb;

echo "=== CORRIGIENDO QUIZZES H5P ===\n\n";

// Paso 1: Eliminar quizzes existentes
echo "1. Eliminando quizzes existentes...\n";
$deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}h5p_contents");
echo "   ✅ Eliminados $deleted quizzes\n\n";

// Paso 2: Limpiar shortcodes en todas las lecciones
echo "2. Limpiando shortcodes de lecciones...\n";
$lessons = $wpdb->get_results("
    SELECT ID FROM wp_posts 
    WHERE post_type = 'page' 
    AND post_title LIKE 'Leiçon%'
");
foreach ($lessons as $lesson) {
    wp_update_post([
        'ID' => $lesson->ID,
        'post_content' => ''
    ]);
}
echo "   ✅ Shortcodes eliminados de " . count($lessons) . " lecciones\n\n";

// Paso 3: Definir vocabulario correcto para cada lección
$vocabulary_map = [
    16 => [
        'titulo' => 'Saluts Bàsics',
        'vocab' => [
            ['front' => 'Azul', 'back' => 'Hola'],
            ['front' => 'Labas', 'back' => 'Bé, estic bé'],
            ['front' => 'Tanemmirt', 'back' => 'Gràcies'],
            ['front' => 'Sslam ɣef-k', 'back' => 'La pau sigui amb tu (home)'],
            ['front' => 'Sslam ɣef-m', 'back' => 'La pau sigui amb tu (dona)'],
            ['front' => 'Ar tufat', 'back' => 'Fins després'],
            ['front' => 'Iyyaḍ', 'back' => 'Bona nit'],
            ['front' => 'Sabah el khir', 'back' => 'Bon dia']
        ]
    ],
    21 => [
        'titulo' => 'Numeros 0-10',
        'vocab' => [
            ['front' => '0', 'back' => 'iwen'],
            ['front' => '1', 'back' => 'iwen'],
            ['front' => '2', 'back' => 'sin'],
            ['front' => '3', 'back' => 'kraḍ'],
            ['front' => '4', 'back' => 'ukuẓ'],
            ['front' => '5', 'back' => 'semmus'],
            ['front' => '6', 'back' => 'sḍis'],
            ['front' => '7', 'back' => 'sa'],
            ['front' => '8', 'back' => 'tam'],
            ['front' => '9', 'back' => 'tẓa'],
            ['front' => '10', 'back' => 'mraw']
        ]
    ],
    25 => [
        'titulo' => 'Familha Pròcha',
        'vocab' => [
            ['front' => 'Baba', 'back' => 'Pare'],
            ['front' => 'Yemma', 'back' => 'Mare'],
            ['front' => 'Mmi', 'back' => 'Fill meu'],
            ['front' => 'Yelli', 'back' => 'Filla meva'],
            ['front' => 'Uma', 'back' => 'Germà'],
            ['front' => 'Weltma', 'back' => 'Germana'],
            ['front' => 'Jeddi', 'back' => 'Avi'],
            ['front' => 'Ḥenna', 'back' => 'Àvia']
        ]
    ],
    19 => [
        'titulo' => 'Dies de la Setmana',
        'vocab' => [
            ['front' => 'Letnin', 'back' => 'Dilluns'],
            ['front' => 'Tlata', 'back' => 'Dimarts'],
            ['front' => 'Larbɛa', 'back' => 'Dimecres'],
            ['front' => 'Lexmis', 'back' => 'Dijous'],
            ['front' => 'Lǧemɛa', 'back' => 'Divendres'],
            ['front' => 'Nhar s-sabt', 'back' => 'Dissabte'],
            ['front' => 'Nhar l-ḥedd', 'back' => 'Diumenge']
        ]
    ],
    32 => [
        'titulo' => 'Colors',
        'vocab' => [
            ['front' => 'Azegzaw', 'back' => 'Verd'],
            ['front' => 'Azeggaɣ', 'back' => 'Vermell'],
            ['front' => 'Anili', 'back' => 'Blau'],
            ['front' => 'Awraɣ', 'back' => 'Groc'],
            ['front' => 'Aberkan', 'back' => 'Negre'],
            ['front' => 'Amellal', 'back' => 'Blanc'],
            ['front' => 'Aqaḥwi', 'back' => 'Marró'],
            ['front' => 'Aẓerwal', 'back' => 'Gris']
        ]
    ]
];

// Paso 4: Obtener ID de librería H5P.MultiChoice
$library_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name = 'H5P.MultiChoice' LIMIT 1");
if (!$library_id) {
    die("ERROR: No se encontró la librería H5P.MultiChoice\n");
}
echo "3. Usando librería H5P.MultiChoice (ID: $library_id)\n\n";

// Paso 5: Crear quizzes para cada lección
echo "4. Creando quizzes H5P...\n";
$created = 0;

foreach ($vocabulary_map as $lesson_id => $lesson_data) {
    $lesson_exists = $wpdb->get_var("SELECT ID FROM wp_posts WHERE ID = $lesson_id");
    if (!$lesson_exists) {
        echo "   ⚠️  Lección $lesson_id no existe, saltando...\n";
        continue;
    }
    
    echo "   Procesando: {$lesson_data['titulo']} (Post ID: $lesson_id)\n";
    
    $params = new stdClass();
    $params->UI = new stdClass();
    $params->UI->scoreBarLabel = 'Puntuació: @score de @total';
    $params->UI->checkAnswerButton = 'Comprovar';
    $params->UI->retryButton = 'Reintentar';
    $params->UI->showSolutionButton = 'Veure solució';
    $params->UI->noAnswerMessage = 'No has seleccionat cap resposta';
    $params->UI->confirmRetry = 'Segur que vols reintentar?';
    
    $params->choices = [];
    
    foreach ($lesson_data['vocab'] as $item) {
        $choice = new stdClass();
        $choice->text = $item['back'];
        $choice->correct = true;
        $choice->tips = $item['front'];
        $params->choices[] = $choice;
    }
    
    $wpdb->insert(
        $wpdb->prefix . 'h5p_contents',
        [
            'library_id' => $library_id,
            'slug' => sanitize_title($lesson_data['titulo'] . '-quiz'),
            'title' => $lesson_data['titulo'] . ' - Quiz Tarifit',
            'parameters' => json_encode($params),
            'filtered' => json_encode($params),
            'user_id' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]
    );
    
    if ($wpdb->last_error) {
        echo "      ✗ Error: " . $wpdb->last_error . "\n";
        continue;
    }
    
    $h5p_id = $wpdb->insert_id;
    echo "      ✓ Creado H5P Quiz (ID: $h5p_id)\n";
    
    $shortcode = "[h5p id=\"$h5p_id\"]";
    $result = wp_update_post([
        'ID' => $lesson_id,
        'post_content' => $shortcode
    ]);
    
    if ($result) {
        echo "      ✓ Post actualizado con shortcode: $shortcode\n";
        $created++;
    } else {
        echo "      ✗ Error actualizando post\n";
    }
}

// Paso 6: Resumen final
echo "\n=== RESUMEN FINAL ===\n";
echo "✅ Quizzes H5P creados: $created\n";
echo "✅ Total de contenidos H5P en BD: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}h5p_contents") . "\n\n";

echo "Contenidos H5P:\n";
$contents = $wpdb->get_results("
    SELECT c.id, c.title, p.ID as post_id, p.post_title
    FROM {$wpdb->prefix}h5p_contents c
    LEFT JOIN wp_posts p ON p.post_content LIKE CONCAT('%[h5p id=\"', c.id, '\"%')
    ORDER BY p.ID
");
foreach ($contents as $c) {
    echo "  Post {$c->post_id} ({$c->post_title}): H5P ID {$c->id} - {$c->title}\n";
}

echo "\n✅ Script completado\n";
