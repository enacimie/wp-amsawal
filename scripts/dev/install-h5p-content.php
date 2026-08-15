<?php
/**
 * Script para instalar librerías H5P y crear actividades con vocabulario Tarifit
 * Uso: wp eval-file install-h5p-content.php --allow-root
 */

// Cargar H5P manualmente


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

require_once WP_PLUGIN_DIR . '/h5p/autoloader.php';
require_once WP_PLUGIN_DIR . '/h5p/public/class-h5p-plugin.php';

echo "🎯 INSTALAR H5P Y CREAR ACTIVIDADES TARIFIT\n";
echo str_repeat("=", 70) . "\n\n";

// Paso 1: Verificar que H5P esté cargado
if (!class_exists('H5P_Plugin')) {
    echo "❌ H5P no está cargado\n";
    exit(1);
}

$h5p_plugin = H5P_Plugin::get_instance();
$interface = $h5p_plugin->get_h5p_instance('interface');
$validator = $h5p_plugin->get_h5p_instance('validator');
$storage = $h5p_plugin->get_h5p_instance('storage');

echo "✅ H5P está cargado y listo\n\n";

// Paso 2: Verificar librerías disponibles
global $wpdb;

$lib_dialog_cards = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name='H5P.DialogCards' LIMIT 1");
$lib_multichoice = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name='H5P.MultiChoice' LIMIT 1");
$lib_blanks = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name='H5P.Blanks' LIMIT 1");

echo "📦 Librerías disponibles:\n";
echo "  Dialog Cards: " . ($lib_dialog_cards ?: "❌ NO INSTALADA") . "\n";
echo "  MultiChoice: " . ($lib_multichoice ?: "❌ NO INSTALADA") . "\n";
echo "  Blanks: " . ($lib_blanks ?: "❌ NO INSTALADA") . "\n\n";

// Si faltan librerías, intentar descargarlas e instalarlas
if (!$lib_dialog_cards || !$lib_multichoice || !$lib_blanks) {
    echo "⚠️  Faltan librerías H5P. Descargando e instalando...\n\n";
    
    $librerias_descargar = [
        'H5P.DialogCards' => 'https://h5p.org/sites/default/files/h5p/content/3275/files/H5P.DialogCards-1.9.4.h5p',
        'H5P.MultiChoice' => 'https://h5p.org/sites/default/files/h5p/content/3276/files/H5P.MultiChoice-1.16.5.h5p',
        'H5P.Blanks' => 'https://h5p.org/sites/default/files/h5p/content/3277/files/H5P.Blanks-1.14.13.h5p',
    ];
    
    $upload_dir = wp_upload_dir();
    $h5p_dir = $upload_dir['basedir'] . '/h5p';
    
    if (!file_exists($h5p_dir)) {
        mkdir($h5p_dir, 0755, true);
    }
    
    foreach ($librerias_descargar as $nombre => $url) {
        $filename = basename($url);
        $temp_file = $h5p_dir . '/' . $filename;
        
        echo "  Descargando $nombre... ";
        $content = @file_get_contents($url);
        
        if ($content === false) {
            echo "❌ Error de descarga\n";
            continue;
        }
        
        file_put_contents($temp_file, $content);
        echo "✅\n";
        
        echo "  Instalando $nombre... ";
        try {
            if ($storage->savePackage($temp_file)) {
                echo "✅\n";
            } else {
                echo "❌ Error al instalar\n";
            }
        } catch (Exception $e) {
            echo "❌ " . $e->getMessage() . "\n";
        }
        
        // Limpiar archivo temporal
        @unlink($temp_file);
        echo "\n";
    }
    
    // Re-verificar librerías
    $lib_dialog_cards = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name='H5P.DialogCards' LIMIT 1");
    $lib_multichoice = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name='H5P.MultiChoice' LIMIT 1");
    $lib_blanks = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name='H5P.Blanks' LIMIT 1");
    
    if (!$lib_dialog_cards || !$lib_multichoice || !$lib_blanks) {
        echo "❌ NO SE PUDIERON INSTALAR LAS LIBRERÍAS\n";
        echo "Instálalas manualmente desde: http://localhost:8080/wp-admin/admin.php?page=h5p_libraries\n\n";
        exit(1);
    }
}

echo "✅ Todas las librerías están disponibles\n\n";

// Paso 3: Vocabulario real de Tarifit
$leccion_vocabulario = [
    11 => [
        'titulo' => 'Saludos básicos en Tarifit',
        'vocabulario' => [
            ['front' => 'Azul', 'back' => 'Hola (saludo formal)'],
            ['front' => 'Masa iɣef?', 'back' => '¿Cómo estás?'],
            ['front' => 'Masa iɣef-ik?', 'back' => '¿Cómo estás? (a hombre)'],
            ['front' => 'Masa iɣef-im?', 'back' => '¿Cómo estás? (a mujer)'],
            ['front' => 'Labas', 'back' => 'Bien / Estoy bien'],
            ['front' => 'Tanemmirt', 'back' => 'Gracias'],
            ['front' => 'Ar tufat', 'back' => 'Hasta luego'],
            ['front' => 'Sslam ɣef', 'back' => 'La paz sea contigo'],
        ]
    ],
    12 => [
        'titulo' => 'Pronombres personales',
        'vocabulario' => [
            ['front' => 'Nekk', 'back' => 'Yo'],
            ['front' => 'Cek / Kem', 'back' => 'Tú (masculino/femenino)'],
            ['front' => 'Netta', 'back' => 'Él'],
            ['front' => 'Nettat', 'back' => 'Ella'],
            ['front' => 'Nekni', 'back' => 'Nosotros'],
            ['front' => 'Kunwi', 'back' => 'Vosotros (masculino)'],
            ['front' => 'Kunemti', 'back' => 'Vosotras (femenino)'],
            ['front' => 'Nitni', 'back' => 'Ellos'],
            ['front' => 'Nitenti', 'back' => 'Ellas'],
        ]
    ],
    13 => [
        'titulo' => 'Números del 1 al 10',
        'vocabulario' => [
            ['front' => '1', 'back' => 'iwen'],
            ['front' => '2', 'back' => 'sin'],
            ['front' => '3', 'back' => 'kraḍ'],
            ['front' => '4', 'back' => 'ukuẓ'],
            ['front' => '5', 'back' => 'semmus'],
            ['front' => '6', 'back' => 'sḍis'],
            ['front' => '7', 'back' => 'sa'],
            ['front' => '8', 'back' => 'tam'],
            ['front' => '9', 'back' => 'tẓa'],
            ['front' => '10', 'back' => 'mraw'],
        ]
    ],
    14 => [
        'titulo' => 'La familia',
        'vocabulario' => [
            ['front' => 'baba / yemma', 'back' => 'padre / madre'],
            ['front' => 'mmi / yelli', 'back' => 'hijo / hija'],
            ['front' => 'uma / weltma', 'back' => 'hermano / hermana'],
            ['front' => 'jeddi / ḥenna', 'back' => 'abuelo / abuela'],
            ['front' => 'xali / xalti', 'back' => 'tío / tía'],
            ['front' => 'argaz', 'back' => 'hombre / esposo'],
            ['front' => 'tameṭṭuṭ', 'back' => 'mujer / esposa'],
            ['front' => 'aḥenjir', 'back' => 'niño'],
        ]
    ],
    15 => [
        'titulo' => 'Días de la semana',
        'vocabulario' => [
            ['front' => 'Letnin', 'back' => 'Lunes'],
            ['front' => 'Tleta', 'back' => 'Martes'],
            ['front' => 'Larebɛa', 'back' => 'Miércoles'],
            ['front' => 'Lexmis', 'back' => 'Jueves'],
            ['front' => 'Ljemɛa', 'back' => 'Viernes'],
            ['front' => 'Sebt', 'back' => 'Sábado'],
            ['front' => 'Lḥed', 'back' => 'Domingo'],
        ]
    ],
    21 => [
        'titulo' => 'Los colores',
        'vocabulario' => [
            ['front' => 'azegzaw', 'back' => 'verde'],
            ['front' => 'azeggwaɣ', 'back' => 'rojo'],
            ['front' => 'anili', 'back' => 'azul'],
            ['front' => 'awraɣ', 'back' => 'amarillo'],
            ['front' => 'aberkan', 'back' => 'negro'],
            ['front' => 'amellal', 'back' => 'blanco'],
            ['front' => 'aqaḥwi', 'back' => 'marrón'],
            ['front' => 'amumiy', 'back' => 'gris'],
        ]
    ],
    22 => [
        'titulo' => 'Alimentos y bebidas',
        'vocabulario' => [
            ['front' => 'aɣrum', 'back' => 'pan'],
            ['front' => 'ayefki', 'back' => 'leche'],
            ['front' => 'aman', 'back' => 'agua'],
            ['front' => 'atay', 'back' => 'té'],
            ['front' => 'aksum', 'back' => 'carne'],
            ['front' => 'aslem', 'back' => 'pescado'],
            ['front' => 'tazart', 'back' => 'higo'],
            ['front' => 'tazenzt', 'back' => 'aceituna'],
        ]
    ],
    23 => [
        'titulo' => 'Verbos de uso diario',
        'vocabulario' => [
            ['front' => 'čča', 'back' => 'comer'],
            ['front' => 'swa', 'back' => 'beber'],
            ['front' => 'ddu', 'back' => 'ir'],
            ['front' => 'as', 'back' => 'venir'],
            ['front' => 'ini', 'back' => 'decir'],
            ['front' => 'ẓer', 'back' => 'ver'],
            ['front' => 'sflid', 'back' => 'escuchar'],
            ['front' => 'issn', 'back' => 'saber / conocer'],
        ]
    ],
];

echo "📚 Vocabulario preparado para " . count($leccion_vocabulario) . " lecciones\n\n";

// Paso 4: Crear contenidos H5P
echo "🎮 CREANDO ACTIVIDADES H5P\n";
echo str_repeat("-", 70) . "\n\n";

$actividades_creadas = 0;
$shortcodes_inseridos = 0;

foreach ($leccion_vocabulario as $post_id => $data) {
    echo "📖 Lección $post_id: {$data['titulo']}\n";
    
    $post = get_post($post_id);
    if (!$post) {
        echo "   ⚠️  Página no existe, saltando...\n\n";
        continue;
    }
    
    $vocab = $data['vocabulario'];
    
    // ACTIVIDAD 1: Dialog Cards (Flashcards)
    if ($lib_dialog_cards) {
        echo "   📇 Creando Dialog Cards... ";
        
        $cards_array = [];
        foreach ($vocab as $item) {
            $cards_array[] = [
                'text' => '<p>' . $item['front'] . '</p>',
                'answer' => '<p>' . $item['back'] . '</p>',
            ];
        }
        
        $params = new stdClass();
        $params->dialogCards = new stdClass();
        $params->dialogCards->mode = 'normal';
        $params->dialogCards->cards = $cards_array;
        
        $content_data = [
            'title' => $data['titulo'] . ' - Flashcards',
            'library' => [
                'name' => 'H5P.DialogCards',
                'majorVersion' => 1,
                'minorVersion' => 9,
            ],
            'params' => $params,
            'disable' => 0,
        ];
        
        try {
            $content_id = $h5p_plugin->save_content($content_data);
            if ($content_id) {
                echo "✅ (ID: $content_id)\n";
                $actividades_creadas++;
                
                // Insertar shortcode
                $current_content = $post->post_content;
                if (strpos($current_content, '[h5p') === false) {
                    $new_content = "[h5p id=\"$content_id\"]\n\n" . $current_content;
                    wp_update_post([
                        'ID' => $post_id,
                        'post_content' => $new_content
                    ]);
                    $shortcodes_inseridos++;
                }
            }
        } catch (Exception $e) {
            echo "❌ " . $e->getMessage() . "\n";
        }
    }
    
    // ACTIVIDAD 2: MultiChoice (Quiz)
    if ($lib_multichoice) {
        echo "   ❓ Creando Quiz MultiChoice... ";
        
        $questions = [];
        foreach ($vocab as $item) {
            $opciones_incorrectas = [];
            $vocab_disponible = array_filter($vocab, function($v) use ($item) {
                return $v['back'] !== $item['back'];
            });
            
            shuffle($vocab_disponible);
            $opciones_incorrectas = array_slice($vocab_disponible, 0, 3);
            
            $opciones = [
                ['text' => $item['back'], 'correct' => true],
            ];
            
            foreach ($opciones_incorrectas as $incorrecta) {
                $opciones[] = ['text' => $incorrecta['back'], 'correct' => false];
            }
            
            shuffle($opciones);
            
            $questions[] = [
                'question' => "¿Qué significa '" . $item['front'] . "'?",
                'answers' => $opciones,
            ];
        }
        
        $params = new stdClass();
        $params->questions = $questions;
        $params->UI = new stdClass();
        $params->UI->scoreBarLabel = 'Obtuviste @score de @total';
        $params->UI->checkAnswerButton = 'Comprobar';
        $params->UI->tryAgainButton = 'Reintentar';
        $params->UI->showSolutionButton = 'Mostrar solución';
        
        $content_data = [
            'title' => $data['titulo'] . ' - Quiz',
            'library' => [
                'name' => 'H5P.MultiChoice',
                'majorVersion' => 1,
                'minorVersion' => 16,
            ],
            'params' => $params,
            'disable' => 0,
        ];
        
        try {
            $content_id = $h5p_plugin->save_content($content_data);
            if ($content_id) {
                echo "✅ (ID: $content_id)\n";
                $actividades_creadas++;
                
                $current_content = get_post($post_id)->post_content;
                $new_content = $current_content . "\n\n[h5p id=\"$content_id\"]";
                wp_update_post([
                    'ID' => $post_id,
                    'post_content' => $new_content
                ]);
                $shortcodes_inseridos++;
            }
        } catch (Exception $e) {
            echo "❌ " . $e->getMessage() . "\n";
        }
    }
    
    // ACTIVIDAD 3: Fill in the Blanks
    if ($lib_blanks) {
        echo "   ✏️  Creando Fill in the Blanks... ";
        
        $fields = [];
        foreach ($vocab as $item) {
            $fields[] = [
                'task' => $item['front'] . ' significa *' . $item['back'] . '* en español',
            ];
        }
        
        $params = new stdClass();
        $params->fields = $fields;
        
        $content_data = [
            'title' => $data['titulo'] . ' - Completar',
            'library' => [
                'name' => 'H5P.Blanks',
                'majorVersion' => 1,
                'minorVersion' => 14,
            ],
            'params' => $params,
            'disable' => 0,
        ];
        
        try {
            $content_id = $h5p_plugin->save_content($content_data);
            if ($content_id) {
                echo "✅ (ID: $content_id)\n";
                $actividades_creadas++;
                
                $current_content = get_post($post_id)->post_content;
                $new_content = $current_content . "\n\n[h5p id=\"$content_id\"]";
                wp_update_post([
                    'ID' => $post_id,
                    'post_content' => $new_content
                ]);
                $shortcodes_inseridos++;
            }
        } catch (Exception $e) {
            echo "❌ " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

// Resumen final
echo str_repeat("=", 70) . "\n";
echo "✅ PROCESO COMPLETADO\n";
echo str_repeat("=", 70) . "\n\n";

echo "📊 Resumen:\n";
echo "   • Vocabulario: " . count($leccion_vocabulario) . " lecciones\n";
echo "   • Actividades H5P creadas: $actividades_creadas\n";
echo "   • Shortcodes insertados: $shortcodes_inseridos\n\n";

echo "🎯 Próximos pasos:\n";
echo "   1. Visitar http://localhost:8080/wp-admin/admin.php?page=h5p\n";
echo "   2. Verificar que las actividades aparecen\n";
echo "   3. Probar cada lección en la vista de usuario\n";
echo "   4. Probar el Learning Path completo\n\n";
