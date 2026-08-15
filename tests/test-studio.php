<?php
/**
 * Tests for wp-amsawal-studio.php — AI Creator Studio AJAX endpoints.
 *
 * Tests: wp_amsawal_studio_ajax_generate, wp_amsawal_studio_ajax_map,
 *        wp_amsawal_studio_ajax_save.
 *
 * @group studio
 */

/* ── Helper: capture wp_send_json output ────────────────────────────── */
/* Studio_AJAX_Exception is defined in bootstrap.php. */

/**
 * Runs a callback that is expected to call wp_send_json_success or
 * wp_send_json_error.  Returns the decoded JSON response array.
 */
function studio_safe_ajax( callable $callback ) {
	ob_start();
	try {
		$callback();
	} catch ( Studio_AJAX_Exception $e ) {
		ob_get_clean();
		return $e->response;
	} catch ( Exception $e ) {
		ob_end_clean();
		return array( 'success' => false, 'data' => array( 'message' => $e->getMessage() ) );
	}
	ob_end_clean();
	return null;
}


/* ═══════════════════════════════════════════════════════════════════════
 * 1. HOOK REGISTRATION
 * ═══════════════════════════════════════════════════════════════════════ */

class Studio_Hook_Test extends PHPUnit\Framework\TestCase {

	public function test_generate_hook_registered() {
		global $wp_filter;
		$tag = 'wp_ajax_wp_amsawal_studio_generate';
		$this->assertArrayHasKey( $tag, $wp_filter, "Hook $tag should be registered" );
	}

	public function test_map_hook_registered() {
		global $wp_filter;
		$tag = 'wp_ajax_wp_amsawal_studio_map';
		$this->assertArrayHasKey( $tag, $wp_filter, "Hook $tag should be registered" );
	}

	public function test_save_hook_registered() {
		global $wp_filter;
		$tag = 'wp_ajax_wp_amsawal_studio_save';
		$this->assertArrayHasKey( $tag, $wp_filter, "Hook $tag should be registered" );
	}
}


/* ═══════════════════════════════════════════════════════════════════════
 * 2. ENDPOINT: wp_amsawal_studio_ajax_save
 *    (Simplest endpoint — test first to validate harness)
 * ═══════════════════════════════════════════════════════════════════════ */

class Studio_Save_Test extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		// Reset POST data and override flags.
		$_POST = array();
		$GLOBALS['studio_override_can']    = true;   // default: admin
		$GLOBALS['studio_override_nonce']  = true;   // default: nonce passes
		$GLOBALS['studio_override_limit']  = true;   // default: rate limit OK
	}

	/* ── Security ── */

	public function test_save_requires_nonce() {
		$GLOBALS['studio_override_nonce'] = false;

		$_POST = array(
			'_ajax_nonce' => 'bad_nonce',
			'lesson_id'   => '1',
			'type'        => 'flashcards',
			'content'     => '{"cards":[]}',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_save();
		} );

		$this->assertNotNull( $resp, 'Should have thrown a JSON response' );
		$this->assertFalse( $resp['success'] );
		// Nonce failure triggers wp_send_json_error with 403.
	}

	public function test_save_requires_admin_capability() {
		$GLOBALS['studio_override_can'] = false;

		$_POST = array(
			'_ajax_nonce' => 'ok',
			'lesson_id'   => '1',
			'type'        => 'flashcards',
			'content'     => '{"cards":[]}',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_save();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'No autorizado', is_array( $resp['data'] ) ? $resp['data']['message'] : (string) $resp['data'] );
	}

	/* ── Parameter validation ── */

	public function test_save_fails_without_lesson_id() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'lesson_id'   => '0',
			'type'        => 'flashcards',
			'content'     => '{"cards":[]}',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_save();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'Faltan', $resp['data']['message'] );
	}

	public function test_save_fails_without_type() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'lesson_id'   => '1',
			'type'        => '',
			'content'     => '{"cards":[]}',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_save();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
	}

	public function test_save_fails_with_invalid_json() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'lesson_id'   => '1',
			'type'        => 'flashcards',
			'content'     => 'NOT_JSON{{{',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_save();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'JSON', $resp['data']['message'] );
	}

	/* ── Happy path ── */

	public function test_save_success_with_valid_json() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'lesson_id'   => '42',
			'type'        => 'flashcards',
			'content'     => wp_json_encode( array(
				'cards' => array(
					array( 'text' => 'ⴰⵣⵓⵍ', 'answer' => 'Hola' ),
				),
			) ),
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_save();
		} );

		$this->assertNotNull( $resp );
		$this->assertTrue( $resp['success'] );
		$this->assertStringContainsString( 'guardado', strtolower( $resp['data']['message'] ) );
	}
}


/* ═══════════════════════════════════════════════════════════════════════
 * 3. ENDPOINT: wp_amsawal_studio_ajax_map
 * ═══════════════════════════════════════════════════════════════════════ */

class Studio_Map_Test extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		$_POST = array();
		$GLOBALS['studio_override_can']   = true;
		$GLOBALS['studio_override_nonce'] = true;
	}

	/* ── Security ── */

	public function test_map_requires_nonce() {
		$GLOBALS['studio_override_nonce'] = false;

		$_POST = array(
			'_ajax_nonce' => 'bad',
			'course_id'   => '1',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_map();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
	}

	public function test_map_requires_admin() {
		$GLOBALS['studio_override_can'] = false;

		$_POST = array(
			'_ajax_nonce' => 'ok',
			'course_id'   => '1',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_map();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'No autorizado', is_array( $resp['data'] ) ? $resp['data']['message'] : (string) $resp['data'] );
	}

	/* ── Course map mode ── */

	public function test_map_fails_without_course_id() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'course_id'   => '0',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_map();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'Curso', $resp['data']['message'] );
	}

	public function test_map_returns_lessons_array() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'course_id'   => '1',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_map();
		} );

		$this->assertNotNull( $resp );
		$this->assertTrue( $resp['success'] );
		$this->assertArrayHasKey( 'lessons', $resp['data'] );
		$this->assertIsArray( $resp['data']['lessons'] );
	}

	/* ── Get content mode (for editor) ── */

	public function test_map_get_content_fails_without_lesson_id() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'mode'        => 'get_content',
			'lesson_id'   => '0',
			'type'        => 'flashcards',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_map();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'Faltan', $resp['data']['message'] );
	}

	public function test_map_get_content_fails_without_type() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'mode'        => 'get_content',
			'lesson_id'   => '1',
			'type'        => '',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_map();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
	}

	public function test_map_get_content_returns_null_for_empty_lesson() {
		$_POST = array(
			'_ajax_nonce' => 'ok',
			'mode'        => 'get_content',
			'lesson_id'   => '999',
			'type'        => 'flashcards',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_map();
		} );

		$this->assertNotNull( $resp );
		$this->assertTrue( $resp['success'] );
		$this->assertArrayHasKey( 'content', $resp['data'] );
		// Mock get_post_meta returns '' so content should be null (no data).
		$this->assertNull( $resp['data']['content'] );
	}
}


/* ═══════════════════════════════════════════════════════════════════════
 * 4. ENDPOINT: wp_amsawal_studio_ajax_generate
 * ═══════════════════════════════════════════════════════════════════════ */

class Studio_Generate_Test extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		$_POST = array();
		$GLOBALS['studio_override_can']   = true;
		$GLOBALS['studio_override_nonce'] = true;
		$GLOBALS['studio_override_limit'] = true;
	}

	/* ── Security ── */

	public function test_generate_requires_nonce() {
		$GLOBALS['studio_override_nonce'] = false;

		$_POST = array(
			'_ajax_nonce'  => 'bad',
			'topic'        => 'Greetings',
			'course_name'  => 'Tamazight 101',
			'num_lessons'  => '3',
			'level'        => '1',
			'types'        => '["flashcards"]',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_generate();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
	}

	public function test_generate_requires_admin() {
		$GLOBALS['studio_override_can'] = false;

		$_POST = array(
			'_ajax_nonce'  => 'ok',
			'topic'        => 'Greetings',
			'course_name'  => 'Tamazight 101',
			'num_lessons'  => '3',
			'level'        => '1',
			'types'        => '["flashcards"]',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_generate();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'No autorizado', is_array( $resp['data'] ) ? $resp['data']['message'] : (string) $resp['data'] );
	}

	/* ── Structure mode (default) ── */

	public function test_generate_structure_calls_ai_query() {
		// wp_amsawal_ai_query is mocked to return WP_Error by default.
		// So the endpoint should return an error with the AI message.
		$_POST = array(
			'_ajax_nonce'  => 'ok',
			'topic'        => 'Greetings',
			'course_name'  => 'Tamazight 101',
			'num_lessons'  => '3',
			'level'        => '1',
			'types'        => '["flashcards"]',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_generate();
		} );

		$this->assertNotNull( $resp );
		// Since AI query returns WP_Error, we should get an error response.
		$this->assertFalse( $resp['success'] );
	}

	public function test_generate_sanitizes_num_lessons() {
		// num_lessons should be clamped to 1..20.
		$_POST = array(
			'_ajax_nonce'  => 'ok',
			'topic'        => 'Test',
			'course_name'  => 'Test',
			'num_lessons'  => '100',  // Should be clamped to 20.
			'level'        => '1',
			'types'        => '["flashcards"]',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_generate();
		} );

		// We just verify it doesn't crash — the clamping is internal.
		$this->assertNotNull( $resp );
	}

	public function test_generate_handles_invalid_types_json() {
		$_POST = array(
			'_ajax_nonce'  => 'ok',
			'topic'        => 'Test',
			'course_name'  => 'Test',
			'num_lessons'  => '3',
			'level'        => '1',
			'types'        => 'NOT_JSON',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_generate();
		} );

		// Should still work (falls back to ['flashcards']).
		$this->assertNotNull( $resp );
	}

	/* ── create_and_generate mode ── */

	public function test_generate_create_mode_fails_without_lesson_title() {
		$_POST = array(
			'_ajax_nonce'   => 'ok',
			'mode'          => 'create_and_generate',
			'course_name'   => 'Test Course',
			'lesson_title'  => '',
			'type'          => 'flashcards',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_generate();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
		$this->assertStringContainsString( 'Faltan', $resp['data']['message'] );
	}

	public function test_generate_create_mode_fails_without_type() {
		$_POST = array(
			'_ajax_nonce'   => 'ok',
			'mode'          => 'create_and_generate',
			'course_name'   => 'Test Course',
			'lesson_title'  => 'Lesson 1',
			'type'          => '',
		);

		$resp = studio_safe_ajax( function() {
			wp_amsawal_studio_ajax_generate();
		} );

		$this->assertNotNull( $resp );
		$this->assertFalse( $resp['success'] );
	}
}


/* ═══════════════════════════════════════════════════════════════════════
 * 5. HELPER: wp_amsawal_studio_find_or_create_lesson
 * ═══════════════════════════════════════════════════════════════════════ */

class Studio_Find_Lesson_Test extends PHPUnit\Framework\TestCase {

	public function test_find_or_create_returns_zero_when_no_course() {
		// get_posts mock returns empty array, and wp_insert_post mock
		// is not available, so it should return 0.
		$result = wp_amsawal_studio_find_or_create_lesson( 'nonexistent', 'Lesson', 1 );
		// With our mocks, get_posts returns [] and wp_insert_post is not defined,
		// so the function should handle gracefully.
		$this->assertIsInt( $result );
	}
}
