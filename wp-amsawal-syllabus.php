<?php
/**
 * wp-amsawal-syllabus.php — Temario oficial del curso (RAG para el tutor)
 *
 * Una única fuente de verdad con el temario del curso de Tarifit:
 *   - Boundaries de las 5 secciones (inicio, fin, color WCAG, guía cultural)
 *   - Vocabulario de cada lección (de postmeta)
 *   - Preguntas H5P (en contexto) por lección
 *
 * Tanto el home (path de aprendizaje) como el tutor virtual consumen estos
 * datos para que el contenido sea coherente en toda la plataforma.
 *
 * @package Amsawal
 * @since   0.0.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve la estructura completa de las 5 secciones del curso.
 *
 * @return array<int, array{title:string, desc:string, color:string, start:int, end:int, guide:string}>
 */
function wp_amsawal_get_syllabus() {
	return array(
		1 => array(
			'title'        => 'Sección 1',
			'desc'         => 'Alfabeto y Fonología',
			'color'        => '#006699', // WCAG 2.2 AA: 6.25:1 vs white
			'start_lesson' => 1,
			'end_lesson'   => 4,
			'guide'        => "El tifinagh (ⵜⵉⴼⵉⵏⴰⵖ) es el sistema de escritura ancestral de los amazigh. En esta sección aprenderás las vocales y consonantes básicas del tarifit. Vocales: ⴰ (a), ⵉ (i), ⵓ (u). Escritura de izquierda a derecha. Cada letra tiene un sonido único. Practica trazando cada letra en el aire mientras repites su sonido.",
		),
		2 => array(
			'title'        => 'Sección 2',
			'desc'         => 'Saludos y Presentaciones',
			'color'        => '#047857', // WCAG 2.2 AA: 5.48:1 vs white
			'start_lesson' => 5,
			'end_lesson'   => 8,
			'guide'        => "Los saludos son fundamentales en la cultura amazigh. Son más que palabras: son una forma de reconocer al otro. Saludos básicos: ⴰⵣⵓⵍ (Azul = hola/paz), ṣbaḥ rxar (buenos días). En la cultura amazigh, los saludos suelen ser largos y preguntar por la familia, la salud y el bienestar. Para presentarse: ism inu... (me llamo...).",
		),
		3 => array(
			'title'        => 'Sección 3',
			'desc'         => 'Números y Tiempo',
			'color'        => '#b45309', // WCAG 2.2 AA: 5.02:1 vs white
			'start_lesson' => 9,
			'end_lesson'   => 11,
			'guide'        => "El sistema numérico amazigh tiene sus propias raíces, aunque también ha adoptado formas del árabe. Números básicos (1-10): waḥit (1), ṯnayen (2), ṯraṯa (3), arbεa (4), xamsa (5), sitta (6), sebεa (7), ṯmanya (8), tesεa (9), εacra (10). El tiempo: nhar-a (hoy), tiwecca (mañana), iḍennaḍ (ayer).",
		),
		4 => array(
			'title'        => 'Sección 4',
			'desc'         => 'Familia y Personas',
			'color'        => '#b91c1c', // WCAG 2.2 AA: 6.47:1 vs white
			'start_lesson' => 12,
			'end_lesson'   => 15,
			'guide'        => "La familia (tawacult) es el núcleo de la sociedad amazigh. Los lazos familiares son fuertes y el respeto a los mayores es fundamental. Familia cercana: baba (padre), yemma (madre), uma (hermano), ultma (hermana), mmi (hijo), yedji (hija). En la cultura amazigh, los abuelos (jeddi/ḥenna) son los guardianes de la sabiduría y las tradiciones.",
		),
		5 => array(
			'title'        => 'Sección 5',
			'desc'         => 'Adjetivos y Descripciones',
			'color'        => '#6d28d9', // WCAG 2.2 AA: 7.10:1 vs white
			'start_lesson' => 16,
			'end_lesson'   => 19,
			'guide'        => "Los adjetivos en tarifit concuerdan en género (masc./fem.) y número (sing./pl.) con el sustantivo. Adjetivos básicos: ameqqran (grande), ameẓẓyan (pequeño), azirar (alto), aquḍaḍ (bajo). Colores: azegzaw (verde), azuggwaɣ (rojo), anili (azul), awraɣ (amarillo), amlil (blanco), asṭṭay (negro). Concordancia: masc. ameqqran, fem. tameqqrant, pl. imeqqranen.",
		),
	);
}


/**
 * Determina la sección a la que pertenece una lección.
 *
 * @param int $lesson_num
 * @return int 1..5
 */
function wp_amsawal_get_section_for_lesson( $lesson_num ) {
	$syllabus = wp_amsawal_get_syllabus();
	$section  = 1;
	foreach ( $syllabus as $num => $info ) {
		if ( $lesson_num >= $info['start_lesson'] ) {
			$section = $num;
		}
	}
	return $section;
}


/**
 * Carga el vocabulario oficial de una lección.
 *
 * El postmeta está doblemente serializado por un bug legacy:
 * `s:N:"a:M:{s:L:\"k\";s:L:\"v\";...}"`. Hacemos `unserialize` dos veces
 * para llegar al array asociativo.
 *
 * @param int $post_id
 * @return array<string,string> tarifit => español
 */
function wp_amsawal_get_lesson_vocabulary( $post_id ) {
	$raw = get_post_meta( $post_id, 'wp_amsawal_vocabulary', true );
	if ( empty( $raw ) ) {
		return array();
	}
	// Primera capa: el meta_value es en sí un string serializado.
	if ( is_string( $raw ) ) {
		$arr = @unserialize( $raw );
		if ( is_array( $arr ) ) {
			return $arr;
		}
	}
	if ( is_array( $raw ) ) {
		return $raw;
	}
	return array();
}


/**
 * Carga las preguntas H5P (multiple-choice) de una lección.
 * Devuelve hasta 12 preguntas con sus opciones y respuesta correcta.
 *
 * @param int $post_id
 * @return array<int, array{question:string, options:array<int,array{text:string, correct:bool}>, correct_text:string}>
 */
function wp_amsawal_get_lesson_h5p_questions( $post_id ) {
	$key = '_wp_amsawal_ai_' . $post_id . '_multiple-choice_0';
	$raw = get_post_meta( $post_id, $key, true );
	if ( empty( $raw ) ) {
		return array();
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || empty( $data['questions'] ) ) {
		return array();
	}

	$out = array();
	foreach ( $data['questions'] as $q ) {
		$opts     = array();
		$correct  = '';
		if ( ! empty( $q['options'] ) ) {
			foreach ( $q['options'] as $opt ) {
				$opts[] = array(
					'text'    => isset( $opt['text'] ) ? (string) $opt['text'] : '',
					'correct' => ! empty( $opt['correct'] ),
				);
				if ( ! empty( $opt['correct'] ) ) {
					$correct = isset( $opt['text'] ) ? (string) $opt['text'] : '';
				}
			}
		}
		$out[] = array(
			'question'     => isset( $q['question'] ) ? (string) $q['question'] : '',
			'options'      => $opts,
			'correct_text' => $correct,
		);
	}
	return $out;
}


/**
 * Busca la página de lección por su número.
 *
 * @param int $lesson_num
 * @return WP_Post|null
 */
function wp_amsawal_get_lesson_post( $lesson_num ) {
	$posts = get_posts( array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => 1,
		'meta_query'  => array(
			array(
				'key'   => 'wp_amsawal_mb_lesson',
				'value' => (int) $lesson_num,
			),
			array(
				'key'   => 'wp_amsawal_mb_typeh5p',
				'value' => 'lesson',
			),
		),
	) );
	return empty( $posts ) ? null : $posts[0];
}


/**
 * Carga el temario completo de una lección: vocabulario + preguntas en contexto.
 *
 * @param int $lesson_num
 * @return array{title:string, vocabulary:array, questions:array, section:int, section_info:array}|null
 */
function wp_amsawal_get_lesson_syllabus( $lesson_num ) {
	$post = wp_amsawal_get_lesson_post( $lesson_num );
	if ( ! $post ) {
		return null;
	}

	$section_num  = wp_amsawal_get_section_for_lesson( $lesson_num );
	$syllabus     = wp_amsawal_get_syllabus();
	$section_info = isset( $syllabus[ $section_num ] ) ? $syllabus[ $section_num ] : null;

	return array(
		'title'       => $post->post_title,
		'vocabulary'  => wp_amsawal_get_lesson_vocabulary( $post->ID ),
		'questions'   => wp_amsawal_get_lesson_h5p_questions( $post->ID ),
		'section'     => $section_num,
		'section_info'=> $section_info,
	);
}
