<?php
/**
 * Script para crear quizzes H5P para todas las lecciones restantes
 * Uso: wp eval-file create-missing-quizzes.php --allow-root
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

require_once('/var/www/html/wp-load.php');

global $wpdb;

echo "=== CREANDO QUIZZES H5P PARA LECCIONES RESTANTES ===\n";

// Mapeo correcto de lecciones (IDs 11-33)
$quizzes = [
    // Lección 2: Consonantes básicas (ID 12)
    12 => [
        'title' => 'Lección 2: Consonantes básicas - Quiz',
        'choices' => [
            ['text' => 'ⴱ (b)', 'correct' => true],
            ['text' => 'ⴷ (d)', 'correct' => true],
            ['text' => 'ⴳ (g)', 'correct' => true],
            ['text' => 'ⴽ (k)', 'correct' => true],
        ]
    ],
    // Lección 3: Consonantes enfáticas (ID 13)
    13 => [
        'title' => 'Lección 3: Consonantes enfáticas - Quiz',
        'choices' => [
            ['text' => 'ⵟ (ṭ)', 'correct' => true],
            ['text' => 'ⴹ (ḍ)', 'correct' => true],
            ['text' => 'ⵚ (ṣ)', 'correct' => true],
            ['text' => 'ⵣ (ẓ)', 'correct' => true],
            ['text' => 'ⵕ (ṛ)', 'correct' => true],
        ]
    ],
    // Lección 4: Consonantes especiales (ID 14)
    14 => [
        'title' => 'Lección 4: Consonantes especiales - Quiz',
        'choices' => [
            ['text' => 'ⵖ (ɣ)', 'correct' => true],
            ['text' => 'ⵅ (x)', 'correct' => true],
            ['text' => 'ⵇ (q)', 'correct' => true],
            ['text' => 'ⵃ (ḥ)', 'correct' => true],
            ['text' => 'ⵄ (ɛ)', 'correct' => true],
        ]
    ],
    // Lección 6: Presentarse (ID 16)
    16 => [
        'title' => 'Lección 6: Presentarse - Quiz',
        'choices' => [
            ['text' => 'Ism inu - Mi nombre es', 'correct' => true],
            ['text' => 'Man tsemmaḍ? - ¿Cómo te llamas?', 'correct' => true],
            ['text' => 'Nekk - Yo', 'correct' => true],
            ['text' => 'Cek - Tú', 'correct' => true],
            ['text' => 'Netta - Él', 'correct' => true],
            ['text' => 'Nettat - Ella', 'correct' => true],
        ]
    ],
    // Lección 7: Pronombres Demostrativos (ID 17)
    17 => [
        'title' => 'Lección 7: Pronombres Demostrativos - Quiz',
        'choices' => [
            ['text' => 'Wa - Este (masculino)', 'correct' => true],
            ['text' => 'Ta - Esta (femenino)', 'correct' => true],
            ['text' => 'Win - Ese (masculino)', 'correct' => true],
            ['text' => 'Tin - Esa (femenino)', 'correct' => true],
            ['text' => 'Wenni - Aquel (masculino)', 'correct' => true],
            ['text' => 'Tenni - Aquella (femenino)', 'correct' => true],
        ]
    ],
    // Lección 10: Números 11-20 (ID 20)
    20 => [
        'title' => 'Lección 10: Números 11-20 - Quiz',
        'choices' => [
            ['text' => 'mraw d iwen - once (11)', 'correct' => true],
            ['text' => 'mraw d sin - doce (12)', 'correct' => true],
            ['text' => 'mraw d kraḍ - trece (13)', 'correct' => true],
            ['text' => 'mraw d ukuẓ - catorce (14)', 'correct' => true],
            ['text' => 'mraw d semmus - quince (15)', 'correct' => true],
            ['text' => 'sin d mraw - veinte (20)', 'correct' => true],
        ]
    ],
    // Lección 11: Adverbios de Tiempo (ID 21)
    21 => [
        'title' => 'Lección 11: Adverbios de Tiempo - Quiz',
        'choices' => [
            ['text' => 'iḍennaḍ - ayer', 'correct' => true],
            ['text' => 'ass-a - hoy', 'correct' => true],
            ['text' => 'tiwecca - mañana', 'correct' => true],
            ['text' => 'imiren - ahora', 'correct' => true],
            ['text' => 'qbel - antes', 'correct' => true],
            ['text' => 'bɛd - después', 'correct' => true],
            ['text' => 'yallah - todavía', 'correct' => true],
        ]
    ],
    // Lección 13: Hermanos y Hermanas (ID 26)
    26 => [
        'title' => 'Lección 13: Hermanos y Hermanas - Quiz',
        'choices' => [
            ['text' => 'Uma - Mi hermano', 'correct' => true],
            ['text' => 'Weltma - Mi hermana', 'correct' => true],
            ['text' => 'Aytma - Mis hermanos', 'correct' => true],
            ['text' => 'Istma - Mis hermanas', 'correct' => true],
        ]
    ],
    // Lección 14: Tíos y Tías (ID 27)
    27 => [
        'title' => 'Lección 14: Tíos y Tías - Quiz',
        'choices' => [
            ['text' => 'Xali - Mi tío materno', 'correct' => true],
            ['text' => 'Xalti - Mi tía materna', 'correct' => true],
            ['text' => 'Ammi - Mi tío paterno', 'correct' => true],
            ['text' => 'Halti - Mi tía paterna', 'correct' => true],
        ]
    ],
    // Lección 15: Pronombres Personales (ID 28)
    28 => [
        'title' => 'Lección 15: Pronombres Personales - Quiz',
        'choices' => [
            ['text' => 'Nekk - Yo', 'correct' => true],
            ['text' => 'Cek - Tú (masculino)', 'correct' => true],
            ['text' => 'Kem - Tú (femenino)', 'correct' => true],
            ['text' => 'Netta - Él', 'correct' => true],
            ['text' => 'Nettat - Ella', 'correct' => true],
            ['text' => 'Neccnin - Nosotros', 'correct' => true],
            ['text' => 'Kenniw - Vosotros', 'correct' => true],
            ['text' => 'Nitni - Ellos', 'correct' => true],
            ['text' => 'Nitenti - Ellas', 'correct' => true],
        ]
    ],
    // Lección 16: Adjetivos Básicos (ID 30)
    30 => [
        'title' => 'Lección 16: Adjetivos Básicos - Quiz',
        'choices' => [
            ['text' => 'ameqqran - grande (masculino)', 'correct' => true],
            ['text' => 'tameqqʷrant - grande (femenino)', 'correct' => true],
            ['text' => 'ameẓẓyan - pequeño (masculino)', 'correct' => true],
            ['text' => 'tameẓẓyunt - pequeña (femenino)', 'correct' => true],
        ]
    ],
    // Lección 17: Adjetivos de Tamaño (ID 31)
    31 => [
        'title' => 'Lección 17: Adjetivos de Tamaño - Quiz',
        'choices' => [
            ['text' => 'azirar - alto (masculino)', 'correct' => true],
            ['text' => 'tazirart - alta (femenino)', 'correct' => true],
            ['text' => 'aquḍad - bajo (masculino)', 'correct' => true],
            ['text' => 'taquḍart - baja (femenino)', 'correct' => true],
        ]
    ],
    // Lección 19: Cualidades Físicas (ID 33)
    33 => [
        'title' => 'Lección 19: Cualidades Físicas - Quiz',
        'choices' => [
            ['text' => 'aḥlawan - dulce', 'correct' => true],
            ['text' => 'amerzag - amargo', 'correct' => true],
            ['text' => 'iɣzif - fuerte', 'correct' => true],
            ['text' => 'aẓidan - sabroso', 'correct' => true],
        ]
    ],
];

$total_created = 0;
$total_errors = 0;

foreach ($quizzes as $post_id => $quiz_data) {
    echo "Procesando: {$quiz_data['title']} (Post ID: $post_id)\n";

    // Verificar que el post existe
    $post = get_post($post_id);
    if (!$post) {
        echo "  ❌ Post no existe, saltando...\n";
        $total_errors++;
        continue;
    }

    // Crear parámetros del quiz (formato H5P MultiChoice JSON)
    $params = json_encode([
        'UI' => [
            'scoreBarLabel' => 'Puntuación: @score de @total',
            'checkAnswerButton' => 'Comprobar',
            'retryButton' => 'Reintentar',
            'showSolutionButton' => 'Mostrar solución',
            'noAnswerMessage' => 'No has seleccionado ninguna respuesta',
            'confirmRetry' => '¿Seguro que quieres volver a intentarlo?'
        ],
        'choices' => $quiz_data['choices']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Insertar en wp_h5p_contents con estructura correcta
    $h5p_data = [
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
        'user_id' => 1,
        'title' => $quiz_data['title'],
        'library_id' => 2, // H5P.MultiChoice
        'parameters' => $params,
        'filtered' => '',
        'slug' => sanitize_title($quiz_data['title']),
        'embed_type' => 'div',
        'disable' => 0,
        'content_type' => 'Multi Choice'
    ];

    $result = $wpdb->insert($wpdb->prefix . 'h5p_contents', $h5p_data);

    if ($result === false) {
        echo "  ❌ Error creando quiz: " . $wpdb->last_error . "\n";
        $total_errors++;
        continue;
    }

    $h5p_id = $wpdb->insert_id;
    echo "  ✅ Quiz creado (H5P ID: $h5p_id)\n";

    // Actualizar el post con el shortcode
    $shortcode = "[h5p id=\"$h5p_id\"]";
    $update_result = wp_update_post([
        'ID' => $post_id,
        'post_content' => $shortcode
    ]);

    if ($update_result) {
        echo "  ✅ Post actualizado con shortcode: $shortcode\n";
        $total_created++;
    } else {
        echo "  ⚠️ Post actualizado pero retornó 0\n";
        $total_created++;
    }

    echo "\n";
}

echo "====================\n";
echo "✅ RESUMEN\n";
echo "====================\n";
echo "Quizzes creados: $total_created\n";
echo "Errores: $total_errors\n";
echo "Total procesados: " . count($quizzes) . "\n";
echo "====================\n";
