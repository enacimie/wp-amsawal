<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * wp-amsawal-ai-schemas.php — Schemas de contenido IA y constructores de prompts
 *
 * Extraído de wp-amsawal-ai.php para mantener el archivo principal enfocado
 * en la lógica de negocio (storage, connectors, rendering).
 *
 * @package Amsawal
 * @since   0.0.4
 *
 * NOTA: wp_amsawal_tutor_build_prompt() vive en wp-amsawal-ai-tutor.php
 * porque está acoplada al historial de conversación del tutor (no usa schemas).
 */

if (!defined('WPINC')) { die; }

/*───────────────────────────────────────────────────────────────────────
 * 1. SCHEMAS — Plantillas content.json para cada tipo H5P
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_ai_get_schema($type) {
	$schemas = array(

		'flashcards' => array(
			'description' => 'Tarjetas de vocabulario con imagen opcional',
			'schema'      => array(
				'cards' => array(
					'type'  => 'array',
					'items' => array(
						'text'  => 'string',   // Frontal (ej: ⴰⵣⵓⵍ)
						'answer'=> 'string',   // Reverso (ej: Hola)
						'image' => 'string|null', // URL opcional
						'tip'   => 'string|null', // Pista opcional
					),
				),
			),
		),

		'dialogcards' => array(
			'description' => 'Tarjetas de diálogo con pistas',
			'schema'      => array(
				'cards' => array(
					'type'  => 'array',
					'items' => array(
						'text'  => 'string',
						'answer'=> 'string',
						'tip'   => 'string|null',
					),
				),
			),
		),

		'dictation' => array(
			'description' => 'Dictados con audio y transcripción',
			'schema'      => array(
				'texts' => array(
					'type'  => 'array',
					'items' => array(
						'text'  => 'string',  // Texto a transcribir
						'audio' => 'string|null', // URL audio (opcional, IA puede generar TTS)
						'hint'  => 'string|null', // Pista
					),
				),
			),
		),

		'memory' => array(
			'description' => 'Juego de memoria por parejas',
			'schema'      => array(
				'cards' => array(
					'type'  => 'array',
					'items' => array(
						'pair_id' => 'int',
						'text'    => 'string',
						'image'   => 'string|null',
						'side'    => 'string', // 'a' o 'b' (cada pareja tiene 2 cartas)
					),
				),
			),
		),

		'fill-blanks' => array(
			'description' => 'Rellenar huecos en frases',
			'schema'      => array(
				'text'   => 'string', // "El *sol* sale por el *este*" → *palabra* = hueco
				'hints'  => 'array|null',
			),
		),

		'mark-the-words' => array(
			'description' => 'Marcar palabras correctas en un texto',
			'schema'      => array(
				'text'         => 'string',
				'wordsToFind'  => 'array',
			),
		),

		'multiple-choice' => array(
			'description' => 'Pregunta de opción múltiple con una única respuesta correcta',
			'schema'      => array(
				'question' => 'string',
				'options'  => array(
					'type'  => 'array',
					'items' => array(
						'text'    => 'string',   // Texto de la opción de respuesta
						'correct' => 'boolean',  // true solo en la opción correcta
					),
				),
			),
		),

		'drag-drop' => array(
			'description' => 'Arrastrar palabras/imágenes a zonas',
			'schema'      => array(
				'pairs' => array(
					'type'  => 'array',
					'items' => array(
						'label'  => 'string',
						'target' => 'string',
					),
				),
			),
		),

		'true-false' => array(
			'description' => 'Pregunta verdadero o falso',
			'schema'      => array(
				'question' => 'string',
				'correct'  => 'bool',
			),
		),

		'speak-the-words' => array(
			'description' => 'Pronunciar palabras',
			'schema'      => array(
				'words' => array(
					'type'  => 'array',
					'items' => array(
						'text'  => 'string',
						'hint'  => 'string|null',
					),
				),
			),
		),

		'essay' => array(
			'description' => 'Ejercicio de escritura abierta (ensayo/párrafo)',
			'schema'      => array(
				'prompt'       => 'string',  // Enunciado / pregunta
				'max_chars'    => 'int',     // Límite de caracteres (default 1000)
				'rubric'       => 'string|null', // Rúbrica de evaluación (qué se espera)
				'example'      => 'string|null', // Ejemplo de respuesta
			),
		),

		'adaptest' => array(
			'description' => 'Test adaptativo: preguntas que ajustan dificultad según rendimiento',
			'schema'      => array(
				'questions' => array(
					'type'  => 'array',
					'items' => array(
						'question'    => 'string',  // Enunciado
						'options'     => 'array',   // 4 opciones
						'correct'     => 'int',     // Índice correcto (0-3)
						'difficulty'  => 'int',     // 1-5
						'explanation' => 'string|null', // Explicación tras responder
					),
				),
			),
		),
	);

	return isset($schemas[$type]) ? $schemas[$type] : null;
}


/*───────────────────────────────────────────────────────────────────────
 * 2. AI GENERATION PROMPTS — Plantillas de prompt por tipo H5P
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Construye el prompt para que una IA genere contenido de un tipo H5P.
 *
 * @param string $type     Tipo H5P
 * @param array  $context  Datos de contexto: lesson_title, course, level, language, vocabulary[], extra_instructions
 * @return string          Prompt listo para enviar al LLM
 */
function wp_amsawal_ai_build_prompt($type, $context) {
	$schema = wp_amsawal_ai_get_schema($type);
	if (!$schema) return '';

	$schema_json = wp_json_encode($schema['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	$desc        = $schema['description'];
	$title       = isset($context['lesson_title']) ? $context['lesson_title'] : 'Lección';
	$lang        = isset($context['language']) ? $context['language'] : 'Tamazight (Tarifit)';
	$level       = isset($context['level']) ? $context['level'] : 1;
	$extra       = isset($context['extra_instructions']) ? $context['extra_instructions'] : '';
	$history     = isset($context['user_history']) ? $context['user_history'] : '';

	// Estrategia de inyección de vocabulario:
	// Los modelos pequeños NO tokenizan bien Tifinagh desde cero.
	// Solución: el profesor proporciona el vocabulario EXACTO,
	// la IA solo lo estructura en el JSON.
	// Soporta dos formatos:
	//   - Array indexado: ["ⴰⵣⵓⵍ = Hola (azul)", ...]
	//   - Array asociativo: ["ⵣⵓⵍ (zul)" => "Hola", ...]
	$vocab_block = '';
	if (!empty($context['vocabulary'])) {
		$vocab_block = "VOCABULARIO EXACTO A USAR (copia estas palabras tal cual, sin modificarlas):\n";
		foreach ($context['vocabulary'] as $key => $value) {
			if (is_int($key)) {
				// Array indexado — value es el string completo
				$vocab_block .= "- $value\n";
			} else {
				// Array asociativo — key es la palabra, value es la traducción
				$vocab_block .= "- $key = $value\n";
			}
		}
		$vocab_block .= "\n";
	}

	$prompt = <<<PROMPT
Eres un profesor de $lang. Crea contenido para una actividad "$type" ($desc).
Lección: "$title" (nivel $level).

$vocab_block$extra

$history

Devuelve SOLO el JSON con esta estructura exacta (sin markdown, sin explicaciones):
$schema_json

Reglas:
1. JSON válido (sin comas finales, comillas escapadas).
2. Copia el vocabulario EXACTAMENTE como aparece arriba si se proporcionó.
3. Dificultad adaptada al nivel $level.
4. Genera entre 5 y 10 items.
5. SOLO el JSON, nada más.
PROMPT;

	return $prompt;
}
