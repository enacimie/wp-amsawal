<?php
/**
 * bin/patch-h5p-embedtype.php
 * 
 * Aplica el parche embedType al plugin H5P.
 * La Reflection fix en wp-amsawal-ai.php es el fix primario;
 * este script es defensa en profundidad — modifica get_content_settings()
 * para que incluya embedType desde el servidor.
 *
 * Invocado por: setup-wp-test.sh
 * Uso directo:  docker compose exec wordpress php /var/www/html/wp-content/plugins/wp-amsawal/bin/patch-h5p-embedtype.php
 */

$file = '/var/www/html/wp-content/plugins/h5p/public/class-h5p-plugin.php';

if (!file_exists($file)) {
	fwrite(STDERR, "⚠️️  H5P no instalado\n");
	exit(0);
}

$code = file_get_contents($file);

if (strpos($code, '"embedType" =>') !== false) {
	echo "   ℹ️  Ya parcheado\n";
	exit(0);
}

// Insertar 'embedType' => ... justo después de 'library' => H5PCore::libraryToString(...)
$search  = "'library' => H5PCore::libraryToString(\$content['library']),";
$replace = $search . "\n      'embedType' => !empty(\$content['embedType']) ? \$content['embedType'] : 'div',";

$code = str_replace($search, $replace, $code, $count);

if ($count > 0) {
	file_put_contents($file, $code);
	echo "   <span class='dashicons dashicons-yes' aria-hidden='true'></span> Parche H5P embedType aplicado\n";
} else {
	fwrite(STDERR, "   ⚠️️  No se encontró la línea a parchear (¿versión H5P incompatible?)\n");
}
