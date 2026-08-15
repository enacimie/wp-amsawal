<?php
/**
 * Regenera los quizzes H5P de las 19 lecciones con datos reales.
 *
 * Uso: docker compose exec -T wordpress php /var/www/html/wp-content/plugins/wp-amsawal/scripts/dev/regenerate-real-h5p-quizzes.php
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

if ( ! function_exists( 'wp_amsawal_ai_create_h5p_content' ) ) {
	wp_die( 'No está disponible wp_amsawal_ai_create_h5p_content(). ¿Está activo el plugin Amsawal?' );
}

/**
 * Borra un contenido H5P existente usando la API del plugin H5P.
 *
 * @param int $h5p_id ID del contenido H5P.
 * @return bool True si se borró o no existía.
 */
function amsawal_dev_delete_h5p_content( $h5p_id ) {
	$h5p_id = intval( $h5p_id );
	if ( $h5p_id <= 0 ) {
		return true;
	}

	if ( ! class_exists( 'H5P_Plugin' ) ) {
		return false;
	}

	$plugin = H5P_Plugin::get_instance();
	$h5p_wp = $plugin->get_h5p_instance( 'interface' );
	if ( ! $h5p_wp ) {
		return false;
	}

	$h5p_wp->deleteContentData( $h5p_id );
	return true;
}

/**
 * Mapa de lecciones con quiz de opción múltiple real.
 * Cada entrada: pregunta, opciones ({text, correct}) y correspondencia lección ID.
 */
$lesson_quizzes = array(
	// Módulo 1: Alfabeto y Fonología
	11 => array(
		'question' => '¿Cuáles son las vocales básicas en Tifinagh (Tarifit)?',
		'options'  => array(
			array( 'text' => 'A, I, U', 'correct' => true ),
			array( 'text' => 'A, E, I, O, U', 'correct' => false ),
			array( 'text' => 'B, D, G', 'correct' => false ),
			array( 'text' => 'F, S, Z', 'correct' => false ),
		),
	),
	12 => array(
		'question' => '¿Cuál de estas letras es una consonante oclusiva en Tifinagh?',
		'options'  => array(
			array( 'text' => 'B', 'correct' => true ),
			array( 'text' => 'S', 'correct' => false ),
			array( 'text' => 'F', 'correct' => false ),
			array( 'text' => 'R', 'correct' => false ),
		),
	),
	13 => array(
		'question' => '¿Cuál de estas letras es una consonante fricativa en Tifinagh?',
		'options'  => array(
			array( 'text' => 'S', 'correct' => true ),
			array( 'text' => 'B', 'correct' => false ),
			array( 'text' => 'K', 'correct' => false ),
			array( 'text' => 'M', 'correct' => false ),
		),
	),
	14 => array(
		'question' => '¿Cuál de estas letras es una consonante especial en Tifinagh?',
		'options'  => array(
			array( 'text' => 'Y', 'correct' => true ),
			array( 'text' => 'T', 'correct' => false ),
			array( 'text' => 'D', 'correct' => false ),
			array( 'text' => 'H', 'correct' => false ),
		),
	),
	// Módulo 2: Saludos y Presentaciones
	16 => array(
		'question' => '¿Cómo se dice "hola" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Azul', 'correct' => true ),
			array( 'text' => 'Tanemmirt', 'correct' => false ),
			array( 'text' => 'Imma', 'correct' => false ),
			array( 'text' => 'Aman', 'correct' => false ),
		),
	),
	17 => array(
		'question' => '¿Qué significa "Ism inu" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Mi nombre es', 'correct' => true ),
			array( 'text' => 'Muchas gracias', 'correct' => false ),
			array( 'text' => 'Buenos días', 'correct' => false ),
			array( 'text' => 'Adiós', 'correct' => false ),
		),
	),
	18 => array(
		'question' => '¿Cuál de estos es un pronombre demostrativo en Tarifit?',
		'options'  => array(
			array( 'text' => 'Wa', 'correct' => true ),
			array( 'text' => 'Ism', 'correct' => false ),
			array( 'text' => 'Azul', 'correct' => false ),
			array( 'text' => 'Aman', 'correct' => false ),
		),
	),
	19 => array(
		'question' => '¿Cómo se dice "lunes" en Tarifit rifeño?',
		'options'  => array(
			array( 'text' => 'Aynas', 'correct' => true ),
			array( 'text' => 'Asinas', 'correct' => false ),
			array( 'text' => 'Akwas', 'correct' => false ),
			array( 'text' => 'Asamas', 'correct' => false ),
		),
	),
	// Módulo 3: Números y Tiempo
	21 => array(
		'question' => '¿Cómo se dice "uno" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Yijj', 'correct' => true ),
			array( 'text' => 'Sin', 'correct' => false ),
			array( 'text' => 'Kraḍ', 'correct' => false ),
			array( 'text' => 'Mraw', 'correct' => false ),
		),
	),
	22 => array(
		'question' => '¿Cómo se dice "diez" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Mraw', 'correct' => true ),
			array( 'text' => 'Sin', 'correct' => false ),
			array( 'text' => 'Smmus', 'correct' => false ),
			array( 'text' => 'Tam', 'correct' => false ),
		),
	),
	23 => array(
		'question' => '¿Cuál de estas palabras es un adverbio de tiempo en Tarifit?',
		'options'  => array(
			array( 'text' => 'Tura', 'correct' => true ),
			array( 'text' => 'Amellal', 'correct' => false ),
			array( 'text' => 'Aɣrum', 'correct' => false ),
			array( 'text' => 'Ultma', 'correct' => false ),
		),
	),
	// Módulo 4: Familia y Personas
	25 => array(
		'question' => '¿Cómo se dice "madre" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Imma', 'correct' => true ),
			array( 'text' => 'Baba', 'correct' => false ),
			array( 'text' => 'Mmi', 'correct' => false ),
			array( 'text' => 'Weltma', 'correct' => false ),
		),
	),
	26 => array(
		'question' => '¿Cómo se dice "hermana" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Ultma', 'correct' => true ),
			array( 'text' => 'Agma', 'correct' => false ),
			array( 'text' => 'Imma', 'correct' => false ),
			array( 'text' => 'Jddi', 'correct' => false ),
		),
	),
	27 => array(
		'question' => '¿Cómo se dice "tío" en Tarifit rifeño?',
		'options'  => array(
			array( 'text' => 'Massi', 'correct' => true ),
			array( 'text' => 'Baba', 'correct' => false ),
			array( 'text' => 'Yelli', 'correct' => false ),
			array( 'text' => 'Tanemmirt', 'correct' => false ),
		),
	),
	28 => array(
		'question' => '¿Cuál de estos es un pronombre personal en Tarifit?',
		'options'  => array(
			array( 'text' => 'Nekk', 'correct' => true ),
			array( 'text' => 'Wa', 'correct' => false ),
			array( 'text' => 'Aynas', 'correct' => false ),
			array( 'text' => 'Azul', 'correct' => false ),
		),
	),
	// Módulo 5: Adjetivos y Descripciones
	30 => array(
		'question' => '¿Cómo se dice "grande" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Meqqren', 'correct' => true ),
			array( 'text' => 'Wezzilen', 'correct' => false ),
			array( 'text' => 'Amellal', 'correct' => false ),
			array( 'text' => 'Aqehwi', 'correct' => false ),
		),
	),
	31 => array(
		'question' => '¿Cómo se dice "alto" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Twelḥaḍ', 'correct' => true ),
			array( 'text' => 'Wezzilen', 'correct' => false ),
			array( 'text' => 'Meqqren', 'correct' => false ),
			array( 'text' => 'Ixṣer', 'correct' => false ),
		),
	),
	32 => array(
		'question' => '¿Cómo se dice "blanco" en Tarifit?',
		'options'  => array(
			array( 'text' => 'Amellal', 'correct' => true ),
			array( 'text' => 'Aberkan', 'correct' => false ),
			array( 'text' => 'Anili', 'correct' => false ),
			array( 'text' => 'Awraɣ', 'correct' => false ),
		),
	),
	33 => array(
		'question' => '¿Cuál de estas palabras describe una cualidad física en Tarifit?',
		'options'  => array(
			array( 'text' => 'Aẓuran', 'correct' => true ),
			array( 'text' => 'Amellal', 'correct' => false ),
			array( 'text' => 'Meqqren', 'correct' => false ),
			array( 'text' => 'Aynas', 'correct' => false ),
		),
	),
);

echo "=== Regenerando quizzes H5P con contenido real ===\n\n";

foreach ( $lesson_quizzes as $lesson_id => $quiz ) {
	$post = get_post( $lesson_id );
	if ( ! $post || $post->post_type !== 'page' ) {
		echo "⚠️ Lección {$lesson_id} no encontrada. Saltando.\n";
		continue;
	}

	$title = get_the_title( $lesson_id );

	// Borrar contenido H5P previo para forzar la ruta completa de creación + filtrado.
	$old_h5p_id = wp_amsawal_ai_get_h5p_content_id( $lesson_id, 'multiple-choice' );
	if ( $old_h5p_id && ! amsawal_dev_delete_h5p_content( $old_h5p_id ) ) {
		echo "❌ Error borrando H5P anterior {$old_h5p_id} para lección {$lesson_id}\n";
		continue;
	}
	if ( $old_h5p_id ) {
		delete_post_meta( $lesson_id, wp_amsawal_ai_meta_key( $lesson_id, 'multiple-choice', 0 ) . '_h5pid' );
		echo "🗑️  Borrado H5P anterior {$old_h5p_id} de lección {$lesson_id}\n";
	}

	$h5p_id = wp_amsawal_ai_create_h5p_content( $lesson_id, 'multiple-choice', $quiz );

	if ( ! $h5p_id ) {
		echo "❌ Error creando H5P para lección {$lesson_id}: {$title}\n";
		continue;
	}

	// Actualizar contenido de la lección con el shortcode.
	wp_update_post(
		array(
			'ID'           => $lesson_id,
			'post_content' => '[h5p id="' . $h5p_id . '"]',
		)
	);

	echo "✅ Lección {$lesson_id}: {$title} → H5P {$h5p_id}\n";
}

echo "\n=== Proceso completado ===\n";
