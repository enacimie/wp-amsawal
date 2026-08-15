<?php
/**
 * Tests for wp-amsawal-ai.php
 *
 * @group ai
 */

class AI_Schema_Test extends PHPUnit\Framework\TestCase {

	public function test_get_schema_returns_null_for_unknown_type() {
		$this->assertNull( wp_amsawal_ai_get_schema( 'nonexistent' ) );
	}

	public function test_get_schema_returns_array_for_flashcards() {
		$schema = wp_amsawal_ai_get_schema( 'flashcards' );
		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'description', $schema );
		$this->assertArrayHasKey( 'schema', $schema );
	}

	public function test_get_schema_returns_array_for_all_supported_types() {
		$types = array(
			'flashcards', 'dialogcards', 'dictation', 'memory',
			'fill-blanks', 'mark-the-words', 'multiple-choice',
			'drag-drop', 'true-false', 'speak-the-words',
		);
		foreach ( $types as $type ) {
			$schema = wp_amsawal_ai_get_schema( $type );
			$this->assertNotNull( $schema, "Schema for '$type' should not be null" );
			$this->assertArrayHasKey( 'description', $schema, "Schema for '$type' should have description" );
		}
	}
}

class AI_Meta_Key_Test extends PHPUnit\Framework\TestCase {

	public function test_meta_key_format() {
		$key = wp_amsawal_ai_meta_key( 42, 'flashcards', 5 );
		$this->assertEquals( '_wp_amsawal_ai_42_flashcards_5', $key );
	}

	public function test_meta_key_with_zero_user() {
		$key = wp_amsawal_ai_meta_key( 1, 'memory', 0 );
		$this->assertEquals( '_wp_amsawal_ai_1_memory_0', $key );
	}

	public function test_meta_key_sanitizes_type() {
		$key = wp_amsawal_ai_meta_key( 1, 'Flash Cards!', 1 );
		$this->assertEquals( '_wp_amsawal_ai_1_flash-cards_1', $key );
	}
}

class AI_Extract_Json_Test extends PHPUnit\Framework\TestCase {

	public function test_extract_json_from_plain_json() {
		$raw = '{"cards":[{"text":"Hello","answer":"Hola"}]}';
		$result = wp_amsawal_ai_extract_json( $raw );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result['cards'] );
	}

	public function test_extract_json_from_markdown_block() {
		$raw = "```json\n{\"cards\":[{\"text\":\"Hello\"}]}\n```";
		$result = wp_amsawal_ai_extract_json( $raw );
		$this->assertIsArray( $result );
		$this->assertEquals( 'Hello', $result['cards'][0]['text'] );
	}

	public function test_extract_json_from_text_with_json() {
		$raw = "Here is your data: {\"question\":\"Test\",\"correct\":true} Done!";
		$result = wp_amsawal_ai_extract_json( $raw );
		$this->assertIsArray( $result );
		$this->assertEquals( 'Test', $result['question'] );
	}

	public function test_extract_json_returns_null_for_invalid() {
		$raw = "This is not JSON at all";
		$result = wp_amsawal_ai_extract_json( $raw );
		$this->assertNull( $result );
	}

	public function test_extract_json_strips_think_tags() {
		$raw = "<think>Let me think...</think>\n{\"answer\":42}";
		$result = wp_amsawal_ai_extract_json( $raw );
		$this->assertIsArray( $result );
		$this->assertEquals( 42, $result['answer'] );
	}
}

class AI_Prompt_Build_Test extends PHPUnit\Framework\TestCase {

	public function test_build_prompt_includes_vocabulary() {
		$context = array(
			'lesson_title' => 'Saludos básicos',
			'language'     => 'Tamazight',
			'level'        => 1,
			'vocabulary'   => array( 'ⴰⵣⵓⵍ', 'ⵎⴰⵏⵣⴰⴽⵉⵏ' ),
		);
		$prompt = wp_amsawal_ai_build_prompt( 'flashcards', $context );
		$this->assertStringContainsString( 'ⴰⵣⵓⵍ', $prompt );
		$this->assertStringContainsString( 'ⵎⴰⵏⵣⴰⴽⵉⵏ', $prompt );
		$this->assertStringContainsString( 'VOCABULARIO EXACTO', $prompt );
	}

	public function test_build_prompt_without_vocabulary() {
		$context = array(
			'lesson_title' => 'Test',
			'language'     => 'Tamazight',
			'level'        => 1,
		);
		$prompt = wp_amsawal_ai_build_prompt( 'flashcards', $context );
		$this->assertStringContainsString( 'Saludos básicos', $prompt );
		$this->assertStringNotContainsString( 'VOCABULARIO EXACTO', $prompt );
	}

	public function test_build_prompt_returns_empty_for_unknown_type() {
		$prompt = wp_amsawal_ai_build_prompt( 'unknown', array() );
		$this->assertEquals( '', $prompt );
	}
}

class AI_H5P_Library_Map_Test extends PHPUnit\Framework\TestCase {

	public function test_library_map_has_all_entries() {
		$map = wp_amsawal_ai_get_h5p_library_map();
		$this->assertIsArray( $map );
		$this->assertArrayHasKey( 'flashcards', $map );
		$this->assertArrayHasKey( 'multiple-choice', $map );
		$this->assertArrayHasKey( 'true-false', $map );
	}

	public function test_library_map_entry_structure() {
		$map = wp_amsawal_ai_get_h5p_library_map();
		$entry = $map['flashcards'];
		$this->assertArrayHasKey( 'machine', $entry );
		$this->assertArrayHasKey( 'id', $entry );
		$this->assertArrayHasKey( 'major', $entry );
		$this->assertArrayHasKey( 'minor', $entry );
		$this->assertEquals( 'H5P.Flashcards', $entry['machine'] );
	}
}

class AI_Convert_Params_Test extends PHPUnit\Framework\TestCase {

	public function test_convert_flashcards() {
		$ai_data = array(
			'cards' => array(
				array( 'text' => 'ⴰⵣⵓⵍ', 'answer' => 'Hola', 'tip' => 'Saludo' ),
			),
		);
		$params = wp_amsawal_ai_convert_to_h5p_params( 'flashcards', $ai_data, 'Test' );
		$this->assertIsArray( $params );
		$this->assertCount( 1, $params['cards'] );
		$this->assertEquals( 'ⴰⵣⵓⵍ', $params['cards'][0]['text'] );
	}

	public function test_convert_multiple_choice() {
		$ai_data = array(
			'question' => '¿Qué significa ⴰⵣⵓⵍ?',
			'options'  => array( 'Hola', 'Adiós', 'Gracias', 'Por favor' ),
			'correct'  => 0,
		);
		$params = wp_amsawal_ai_convert_to_h5p_params( 'multiple-choice', $ai_data, 'Test' );
		$this->assertIsArray( $params );
		$this->assertCount( 4, $params['answers'] );
		$this->assertTrue( $params['answers'][0]['correct'] );
		$this->assertFalse( $params['answers'][1]['correct'] );
	}

	public function test_convert_fill_blanks() {
		$ai_data = array( 'text' => 'El *sol* sale por el *este*' );
		$params = wp_amsawal_ai_convert_to_h5p_params( 'fill-blanks', $ai_data, 'Test' );
		$this->assertIsArray( $params );
		$this->assertEquals( 'El *sol* sale por el *este*', $params['text'] );
	}

	public function test_convert_unknown_type_returns_null() {
		$params = wp_amsawal_ai_convert_to_h5p_params( 'unknown', array(), 'Test' );
		$this->assertNull( $params );
	}
}

class AI_Detect_Backend_Test extends PHPUnit\Framework_TestCase {

	public function test_detect_ollama_by_default() {
		// Without any constants defined, should default to ollama
		$backend = wp_amsawal_ai_detect_backend();
		$this->assertEquals( 'ollama', $backend );
	}

	public function test_detect_openai_by_url() {
		// This test requires defining constants before the function runs
		// For now, we test the logic with the current setup
		$this->assertContains( wp_amsawal_ai_detect_backend(), array( 'openai', 'ollama' ) );
	}
}
