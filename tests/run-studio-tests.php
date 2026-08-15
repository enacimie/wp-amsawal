<?php
/**
 * Inline test runner for Studio AJAX endpoints.
 *
 * Runs inside Docker via: wp eval-file tests/run-studio-tests.php --allow-root
 *
 * Uses the same AJAX simulation pattern as smoke-test.php:
 * - DOING_AJAX forces wp_send_json to use wp_die() instead of die()
 * - wp_die_handler filters throw an exception
 * - studio_ajax() captures buffered output OR exception data
 */

define( 'DOING_AJAX', true );

$GLOBALS['st_pass'] = 0;
$GLOBALS['st_fail'] = 0;
$GLOBALS['st_errors'] = array();

function st_assert( $val, $msg ) {
	if ( $val ) { $GLOBALS['st_pass']++; echo "  ✅ $msg\n"; }
	else { $GLOBALS['st_fail']++; $GLOBALS['st_errors'][] = $msg; echo "  ❌ $msg\n"; }
}
function st_assert_contains( $needle, $haystack, $msg ) {
	st_assert( is_string($haystack) && strpos($haystack, $needle) !== false, "$msg ('$needle' not found)" );
}

// ── Die handler exception class ──

class Studio_Test_Die extends Exception {
	public $response;
	public function __construct( $response ) {
		$this->response = $response;
		parent::__construct( 'wp_die' );
	}
}

// ── Die handler filters (throw instead of terminating) ──

add_filter( 'wp_die_handler', function() {
	return function( $message, $title = '', $args = array() ) {
		throw new Studio_Test_Die( is_array( $message ) ? $message : (string) $message );
	};
}, 9999 );
add_filter( 'wp_die_ajax_handler', function() {
	return function( $message, $title = '', $args = array() ) {
		throw new Studio_Test_Die( is_array( $message ) ? $message : (string) $message );
	};
}, 9999 );
add_filter( 'wp_die_json_handler', function() {
	return function( $message, $title = '', $args = array() ) {
		throw new Studio_Test_Die( is_array( $message ) ? $message : (string) $message );
	};
}, 9999 );

/**
 * Run a callback expected to call wp_send_json_success/error.
 *
 * Strategy: ob_start → run callback. Two scenarios:
 *   A) wp_send_json echoes JSON then calls wp_die → handler throws →
 *      catch reads buffer (has JSON) and optionally falls through to response arg.
 *   B) wp_die is called with a message array (e.g. wp_die(-1, 403)) without
 *      prior echo → buffer is empty, but exception->response has the data.
 *
 * If the callback completes WITHOUT throwing (unlikely in real WP but possible
 * if the die handler was cached before our filter), we still check the buffer.
 */
function studio_ajax( callable $callback ) {
	ob_start();
	try {
		$callback();
		// No exception — check if output was echoed anyway.
		$out = ob_get_clean();
		if ( ! empty( $out ) ) {
			$decoded = json_decode( trim( $out ), true );
			if ( is_array( $decoded ) ) return $decoded;
		}
		return null;
	} catch ( Studio_Test_Die $e ) {
		$out = ob_get_clean();
		// Try buffer first (has the JSON echoed by wp_send_json).
		if ( ! empty( $out ) ) {
			$decoded = json_decode( trim( $out ), true );
			if ( is_array( $decoded ) ) return $decoded;
		}
		// Buffer empty — the message was passed as the wp_die argument.
		$msg = $e->response;
		if ( is_array( $msg ) ) return $msg;
		return array( 'success' => false, 'data' => array( 'message' => (string) $msg ) );
	} catch ( \Throwable $e ) {
		ob_end_clean();
		return array( 'success' => false, 'data' => array( 'message' => $e->getMessage() ) );
	}
}

/**
 * Extract message string from a response.
 */
function studio_msg( $resp ) {
	if ( ! is_array( $resp ) ) return '';
	$d = isset( $resp['data'] ) ? $resp['data'] : '';
	if ( is_array( $d ) && isset( $d['message'] ) ) return (string) $d['message'];
	if ( is_string( $d ) ) return $d;
	return wp_json_encode( $d );
}

wp_set_current_user( 1 );
delete_transient( 'amsawal_rl_studio_generate' );

// IMPORTANT: In CLI context, $_REQUEST is not auto-populated from $_POST.
// check_ajax_referer() reads $_REQUEST, so we must sync before each test.
// (smoke-test.php does the same thing at line ~32)

// Short-circuit all HTTP requests to prevent AI calls from hanging.
add_filter( 'pre_http_request', function( $pre, $r, $url ) {
	return new WP_Error( 'test_skip', 'HTTP disabled in tests' );
}, 10, 3 );

echo "═══════════════════════════════════════════════════\n";
echo " STUDIO AJAX ENDPOINT TESTS\n";
echo "═══════════════════════════════════════════════════\n\n";


// ── 1. Hook registration ──
echo "[1] Hook Registration\n";
global $wp_filter;
st_assert( isset( $wp_filter['wp_ajax_wp_amsawal_studio_generate'] ), 'generate hook registered' );
st_assert( isset( $wp_filter['wp_ajax_wp_amsawal_studio_map'] ), 'map hook registered' );
st_assert( isset( $wp_filter['wp_ajax_wp_amsawal_studio_save'] ), 'save hook registered' );


// ── 2. SAVE ENDPOINT ──
echo "\n[2] Endpoint: wp_amsawal_studio_ajax_save\n";

// 2a. Missing lesson_id (lesson_id=0 is falsy → triggers "Faltan parámetros")
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_save' ),
	'lesson_id'   => '0',
	'type'        => 'flashcards',
	'content'     => '{"cards":[]}',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_save(); } );
st_assert( is_array($resp), 'save: missing lesson_id returns array' );
st_assert( is_array($resp) && ! $resp['success'], 'save: missing lesson_id → success=false' );
st_assert_contains( 'Faltan', studio_msg($resp), 'save: missing params → "Faltan"' );

// 2b. Missing type (type=sanitize_key("") is empty → falsy)
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_save' ),
	'lesson_id'   => '1',
	'type'        => '',
	'content'     => '{"cards":[]}',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_save(); } );
st_assert( is_array($resp) && ! $resp['success'], 'save: missing type → success=false' );

// 2c. Invalid JSON
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_save' ),
	'lesson_id'   => '1',
	'type'        => 'flashcards',
	'content'     => 'NOT_JSON{{{',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_save(); } );
st_assert( is_array($resp) && ! $resp['success'], 'save: invalid JSON → success=false' );
st_assert_contains( 'JSON', studio_msg($resp), 'save: invalid JSON → mentions JSON' );

// 2d. Empty content
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_save' ),
	'lesson_id'   => '1',
	'type'        => 'flashcards',
	'content'     => '',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_save(); } );
st_assert( is_array($resp) && ! $resp['success'], 'save: empty content → success=false' );

// 2e. Happy path — save valid JSON for a real lesson
$test_course = 'studio_test_' . uniqid();
$test_lesson_id = wp_amsawal_studio_find_or_create_lesson( $test_course, 'Save Test ' . uniqid(), 1 );
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_save' ),
	'lesson_id'   => (string) $test_lesson_id,
	'type'        => 'flashcards',
	'content'     => wp_json_encode( array( 'cards' => array( array( 'text' => 'ⴰⵣⵓⵍ', 'answer' => 'Hola' ) ) ) ),
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_save(); } );
st_assert( is_array($resp), 'save: happy path returns array' );
st_assert( is_array($resp) && $resp['success'], 'save: happy path → success=true' );
st_assert_contains( 'guardado', strtolower( studio_msg($resp) ), 'save: happy path → "guardado"' );


// ── 3. MAP ENDPOINT ──
echo "\n[3] Endpoint: wp_amsawal_studio_ajax_map\n";

// 3a. Missing course_id
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_map' ),
	'course_id'   => '0',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_map(); } );
st_assert( is_array($resp) && ! $resp['success'], 'map: missing course_id → success=false' );
st_assert_contains( 'Curso', studio_msg($resp), 'map: missing course → "Curso"' );

// 3b. Course map — valid course
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_map' ),
	'course_id'   => (string) $test_lesson_id,
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_map(); } );
st_assert( is_array($resp) && $resp['success'], 'map: course_map → success=true' );
st_assert( is_array($resp) && isset( $resp['data']['lessons'] ), 'map: course_map → has lessons key' );
st_assert( is_array($resp) && is_array( $resp['data']['lessons'] ), 'map: course_map → lessons is array' );

// 3c. get_content mode: missing lesson_id
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_map' ),
	'mode'        => 'get_content',
	'lesson_id'   => '0',
	'type'        => 'flashcards',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_map(); } );
st_assert( is_array($resp) && ! $resp['success'], 'map: get_content missing lesson_id → success=false' );
st_assert_contains( 'Faltan', studio_msg($resp), 'map: get_content → "Faltan"' );

// 3d. get_content mode: missing type
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_map' ),
	'mode'        => 'get_content',
	'lesson_id'   => (string) $test_lesson_id,
	'type'        => '',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_map(); } );
st_assert( is_array($resp) && ! $resp['success'], 'map: get_content missing type → success=false' );

// 3e. get_content mode: valid request
$_POST = array(
	'_ajax_nonce' => wp_create_nonce( 'wp_amsawal_studio_map' ),
	'mode'        => 'get_content',
	'lesson_id'   => (string) $test_lesson_id,
	'type'        => 'flashcards',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_map(); } );
st_assert( is_array($resp) && $resp['success'], 'map: get_content valid → success=true' );
st_assert( is_array($resp) && isset( $resp['data']['content'] ), 'map: get_content → has content key' );
st_assert( is_array($resp) && isset( $resp['data']['lesson_title'] ), 'map: get_content → has lesson_title' );
st_assert( is_array($resp) && isset( $resp['data']['lesson_num'] ), 'map: get_content → has lesson_num' );


// ── 4. GENERATE ENDPOINT ──
echo "\n[4] Endpoint: wp_amsawal_studio_ajax_generate\n";

// 4a. Structure mode — AI returns WP_Error (wp_remote_post returns WP_Error in test)
$_POST = array(
	'_ajax_nonce'  => wp_create_nonce( 'wp_amsawal_studio_generate' ),
	'topic'        => 'Greetings',
	'course_name'  => 'Test',
	'num_lessons'  => '3',
	'level'        => '1',
	'types'        => '["flashcards"]',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_generate(); } );
st_assert( is_array($resp), 'generate: structure returns array' );
st_assert( is_array($resp) && ! $resp['success'], 'generate: AI WP_Error → success=false' );

// 4b. Invalid types JSON → still works (falls back to ['flashcards'])
$_POST = array(
	'_ajax_nonce'  => wp_create_nonce( 'wp_amsawal_studio_generate' ),
	'topic'        => 'Test',
	'course_name'  => 'Test',
	'num_lessons'  => '3',
	'level'        => '1',
	'types'        => 'BAD_JSON',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_generate(); } );
st_assert( is_array($resp), 'generate: invalid types returns array (no crash)' );

// 4c. create_and_generate: missing lesson_title
$_POST = array(
	'_ajax_nonce'   => wp_create_nonce( 'wp_amsawal_studio_generate' ),
	'mode'          => 'create_and_generate',
	'course_name'   => 'Test',
	'lesson_title'  => '',
	'type'          => 'flashcards',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_generate(); } );
st_assert( is_array($resp) && ! $resp['success'], 'generate: missing title → success=false' );
st_assert_contains( 'Faltan', studio_msg($resp), 'generate: missing title → "Faltan"' );

// 4d. create_and_generate: missing type
$_POST = array(
	'_ajax_nonce'   => wp_create_nonce( 'wp_amsawal_studio_generate' ),
	'mode'          => 'create_and_generate',
	'course_name'   => 'Test',
	'lesson_title'  => 'Lesson 1',
	'type'          => '',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_generate(); } );
st_assert( is_array($resp) && ! $resp['success'], 'generate: missing type → success=false' );

// 4e. num_lessons clamping (100 → 20) — doesn't crash
$_POST = array(
	'_ajax_nonce'  => wp_create_nonce( 'wp_amsawal_studio_generate' ),
	'topic'        => 'Test',
	'course_name'  => 'Test',
	'num_lessons'  => '100',
	'level'        => '1',
	'types'        => '["flashcards"]',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_generate(); } );
st_assert( is_array($resp), 'generate: num_lessons=100 doesn\'t crash' );


// ── 5. Helper function ──
echo "\n[5] Helper: wp_amsawal_studio_find_or_create_lesson\n";
$unique = 'studio_test_' . uniqid();
$result = wp_amsawal_studio_find_or_create_lesson( $unique, 'Helper Test ' . uniqid(), 1 );
st_assert( is_int( $result ), 'find_or_create: returns int' );
st_assert( $result > 0, 'find_or_create: returns positive ID' );


// ── 6. Security: bad nonce rejection ──
echo "\n[6] Security: nonce rejection\n";
$_POST = array(
	'_ajax_nonce' => 'definitely_bad_nonce_12345',
	'lesson_id'   => '1',
	'type'        => 'flashcards',
	'content'     => '{"cards":[]}',
);
$_REQUEST = $_POST;
$resp = studio_ajax( function() { wp_amsawal_studio_ajax_save(); } );
st_assert( is_array($resp), 'nonce: bad nonce returns array' );
st_assert( is_array($resp) && ! $resp['success'], 'nonce: bad nonce → success=false' );

// ── 7. Cleanup: delete test data ──
echo "\n[7] Cleanup\n";
if ( $test_lesson_id && $test_lesson_id > 0 ) {
	wp_delete_post( $test_lesson_id, true );
	// Also delete the course parent if we created one.
	$parent = wp_get_post_parent_id( $test_lesson_id );
	if ( $parent ) wp_delete_post( $parent, true );
}
$cleanup = get_posts( array(
	'post_type'   => 'page',
	'post_status' => 'publish',
	'numberposts' => -1,
	'meta_query'  => array( array( 'key' => 'wp_amsawal_mb_course', 'value' => 'studio_test_', 'compare' => 'LIKE' ) ),
) );
$cleaned = 0;
foreach ( $cleanup as $p ) {
	wp_delete_post( $p->ID, true );
	$cleaned++;
}
echo "  Cleaned up $cleaned test pages\n";

// ── Summary ──
$p = $GLOBALS['st_pass'];
$f = $GLOBALS['st_fail'];
echo "\n═══════════════════════════════════════════════════\n";
echo " RESULTS: $p passed, $f failed\n";
echo "═══════════════════════════════════════════════════\n";
if ( ! empty( $GLOBALS['st_errors'] ) ) {
	echo "\nFailures:\n";
	foreach ( $GLOBALS['st_errors'] as $e ) { echo "  • $e\n"; }
}
echo "\n";
