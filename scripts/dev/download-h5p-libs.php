<?php
/**
 * Descargar librerías H5P desde GitHub
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

$librerias = [
    'H5P.DialogCards' => [
        'url' => 'https://github.com/h5p/h5p-dialog-cards/releases/download/v1.9.4/H5P.DialogCards-1.9.4.h5p',
        'alternativa' => 'https://raw.githubusercontent.com/h5p/h5p-dialog-cards/master/library.json'
    ],
    'H5P.MultiChoice' => [
        'url' => 'https://github.com/h5p/h5p-multi-choice/releases/download/v1.16.5/H5P.MultiChoice-1.16.5.h5p',
        'alternativa' => 'https://raw.githubusercontent.com/h5p/h5p-multi-choice/master/library.json'
    ],
    'H5P.Blanks' => [
        'url' => 'https://github.com/h5p/h5p-blanks/releases/download/v1.14.13/H5P.Blanks-1.14.13.h5p',
        'alternativa' => 'https://raw.githubusercontent.com/h5p/h5p-blanks/master/library.json'
    ]
];

$upload_dir = wp_upload_dir();
$h5p_dir = $upload_dir['basedir'] . '/h5p';

if (!file_exists($h5p_dir)) {
    mkdir($h5p_dir, 0755, true);
}

echo "Probando URLs alternativas para verificar si las librerías existen:\n\n";

foreach ($librerias as $nombre => $datos) {
    echo "$nombre:\n";
    
    // Probar URL alternativa (library.json)
    $response = @file_get_contents($datos['alternativa']);
    if ($response !== false) {
        $json = json_decode($response, true);
        echo "  ✅ library.json encontrado\n";
        echo "  Versión: {$json['majorVersion']}.{$json['minorVersion']}.{$json['patchVersion']}\n";
    } else {
        echo "  ❌ No se pudo acceder a library.json\n";
    }
    echo "\n";
}

// Intentar descargar desde URLs de ejemplo del H5P Hub
echo "Descargando ejemplos desde H5P Hub:\n\n";

$ejemplos_hub = [
    'H5P.DialogCards' => 'https://h5p.org/h5p/embed/712',
    'H5P.MultiChoice' => 'https://h5p.org/h5p/embed/694',
    'H5P.Blanks' => 'https://h5p.org/h5p/embed/719'
];

foreach ($ejemplos_hub as $nombre => $url) {
    echo "$nombre: $url\n";
}

echo "\n";
echo "MÉTODO RECOMENDADO:\n";
echo "1. Ve a http://localhost:8080/wp-admin/\n";
echo "2. Navega a H5P → Create Content\n";
echo "3. Haz clic en 'Get' para descargar las librerías desde el Hub\n";
echo "4. Selecciona: Dialog Cards, Multiple Choice, Fill in the Blanks\n";
echo "5. Instala cada una\n";
