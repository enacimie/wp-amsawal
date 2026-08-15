<?php
/**
 * Repara la tabla wp_h5p_libraries_libraries y el campo preloaded_libraries
 * a partir de los library.json existentes en uploads/h5p/libraries.
 *
 * Uso: docker compose exec -T wordpress php /var/www/html/wp-content/plugins/wp-amsawal/scripts/dev/repair-h5p-library-dependencies.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/var/www/html/' );
}

require_once ABSPATH . 'wp-load.php';

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! is_user_logged_in() ) {
	wp_set_current_user( 1 ); // admin
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Se requieren permisos de administrador.' );
}

if ( ! class_exists( 'H5P_Plugin' ) ) {
	wp_die( 'El plugin H5P no está activo.' );
}

echo "=== Reparando dependencias H5P desde library.json ===\n\n";

$plugin = H5P_Plugin::get_instance();
$core   = $plugin->get_h5p_instance( 'core' );
$h5p_wp = $plugin->get_h5p_instance( 'interface' );
if ( ! $core || ! $h5p_wp ) {
	wp_die( 'No se pudo obtener instancias H5P.' );
}

$libraries_base = trailingslashit( wp_upload_dir()['basedir'] ) . 'h5p/libraries';
if ( ! is_dir( $libraries_base ) ) {
	wp_die( "No existe el directorio de librerías: {$libraries_base}" );
}

$libraries = $wpdb->get_results( "SELECT id, name, major_version, minor_version FROM {$wpdb->prefix}h5p_libraries" );

$fixed  = 0;
$failed = 0;

foreach ( $libraries as $library ) {
	$folder_name = "{$library->name}-{$library->major_version}.{$library->minor_version}";
	$library_json = trailingslashit( $libraries_base ) . $folder_name . '/library.json';

	if ( ! file_exists( $library_json ) ) {
		echo "⚠️  No se encontró library.json para {$folder_name}\n";
		$failed++;
		continue;
	}

	$json = json_decode( file_get_contents( $library_json ), true );
	if ( ! is_array( $json ) ) {
		echo "⚠️  JSON inválido para {$folder_name}\n";
		$failed++;
		continue;
	}

	$preloaded = isset( $json['preloadedDependencies'] ) ? $json['preloadedDependencies'] : array();

	// Borrar dependencias preexistentes para esta librería.
	$wpdb->delete(
		"{$wpdb->prefix}h5p_libraries_libraries",
		array( 'library_id' => $library->id ),
		array( '%d' )
	);

	$required_ids = array();
	foreach ( $preloaded as $dep ) {
		$required_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE name = %s AND major_version = %d AND minor_version = %d",
			$dep['machineName'], $dep['majorVersion'], $dep['minorVersion']
		) );

		if ( ! $required_id ) {
			echo "⚠️  Dependencia no instalada: {$dep['machineName']} {$dep['majorVersion']}.{$dep['minorVersion']} (requerida por {$folder_name})\n";
			continue;
		}

		$wpdb->insert(
			"{$wpdb->prefix}h5p_libraries_libraries",
			array(
				'library_id'          => $library->id,
				'required_library_id' => $required_id,
				'dependency_type'     => 'preloaded',
			),
			array( '%d', '%d', '%s' )
		);

		$required_ids[] = array(
			'machineName'  => $dep['machineName'],
			'majorVersion' => $dep['majorVersion'],
			'minorVersion' => $dep['minorVersion'],
		);
	}

	// Actualizar campo preloaded_libraries.
	$wpdb->update(
		"{$wpdb->prefix}h5p_libraries",
		array( 'preloaded_libraries' => wp_json_encode( $required_ids ) ),
		array( 'id' => $library->id ),
		array( '%s' ),
		array( '%d' )
	);

	echo "✅ {$folder_name}: " . count( $required_ids ) . " dependencias precargadas\n";
	$fixed++;
}

echo "\n{$fixed} librerías reparadas, {$failed} fallos.\n";

// Limpiar cachedassets para forzar regeneración.
$cache_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'h5p/cachedassets';
if ( is_dir( $cache_dir ) ) {
	$count = 0;
	foreach ( glob( $cache_dir . '/*' ) as $file ) {
		if ( is_file( $file ) ) {
			@unlink( $file );
			$count++;
		}
	}
	echo "🗑️  {$count} archivos cachedassets eliminados.\n";
}

// Refiltrar todos los contenidos existentes para regenerar dependencias y filtered.
$contents = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}h5p_contents" );
echo "\nRefiltrando " . count( $contents ) . " contenidos...\n";

$refiltered = 0;
foreach ( $contents as $content ) {
	$loaded = $core->loadContent( $content->id );
	if ( ! $loaded ) {
		echo "⚠️  No se pudo cargar contenido {$content->id}\n";
		continue;
	}
	$result = $core->filterParameters( $loaded );
	if ( $result !== null ) {
		$refiltered++;
	}
}

echo "✅ {$refiltered} contenidos refiltrados.\n";
echo "\n=== Reparación completada ===\n";
