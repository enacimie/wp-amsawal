<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Amsawal WebSocket — Real-time leaderboard push via WebSocket.
 *
 * Falls back to 30s polling if WebSocket is unavailable.
 * The WS server runs as a standalone process (php wp-amsawal-websocket-server.php).
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*───────────────────────────────────────────────────────────────────────
 * 1. WS CONFIG
 *───────────────────────────────────────────────────────────────────────*/

define( 'WP_AMSAWAL_WS_PORT', 9501 );
define( 'WP_AMSAWAL_WS_SECRET', hash( 'sha256', 'amsawal_ws_' . NONCE_SALT ) );

/*───────────────────────────────────────────────────────────────────────
 * 2. ENQUEUE WS CLIENT CONFIG
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_enqueue_scripts', 'wp_amsawal_ws_enqueue_config', 99 );

function wp_amsawal_ws_enqueue_config() {
	$ws_url = 'ws://' . wp_parse_url( get_site_url(), PHP_URL_HOST ) . ':' . WP_AMSAWAL_WS_PORT;

	$config = array(
		'wsUrl'      => $ws_url,
		'wsEnabled'  => (bool) get_option( 'wp_amsawal_ws_enabled', true ),
		'wsFallback' => 30, // seconds for polling fallback
		'wsReconnect'=> 3,  // seconds between reconnect attempts
		'wsMaxRetry' => 10, // max reconnect attempts before giving up
	);

	wp_localize_script( 'pure-js-script', 'wpAmsawalWS', $config );
}

/*───────────────────────────────────────────────────────────────────────
 * 3. WS ENDPOINT — REST API for WS auth token
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'rest_api_init', function() {
	register_rest_route( 'amsawal/v1', '/ws-token', array(
		'methods'  => 'GET',
		'callback' => 'wp_amsawal_ws_token',
		'permission_callback' => function() {
			return is_user_logged_in();
		},
	) );
} );

function wp_amsawal_ws_token( $request ) {
	$user_id = get_current_user_id();
	
	// Generate cryptographically secure random token to prevent duplicate tokens
	// within the same second for the same user.
	$token = bin2hex( random_bytes( 32 ) );
	$expires = time() + 3600;

	// Store token for validation.
	set_transient( 'wp_amsawal_ws_token_' . $user_id, $token, 3600 );

	return rest_ensure_response( array(
		'token'   => $token,
		'expires' => $expires,
		'userId'  => $user_id,
	) );
}

/*───────────────────────────────────────────────────────────────────────
 * 4. PUSH HELPER — Broadcast leaderboard changes
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Push leaderboard update to all connected WS clients.
 * Called after track_item or h5p_completion_check invalidates cache.
 *
 * @param string $type    Leaderboard type ('monedas' or course slug).
 * @param array  $data    Updated leaderboard data.
 */
function wp_amsawal_ws_broadcast( $type, $data = array() ) {
	$socket_file = WP_CONTENT_DIR . '/amsawal_ws.sock';

	if ( ! file_exists( $socket_file ) ) {
		return false; // WS server not running.
	}

	$message = json_encode( array(
		'event' => 'leaderboard_update',
		'type'  => $type,
		'data'  => $data,
		'time'  => time(),
	) );

	// Use Unix socket to talk to WS server.
	$sock = @stream_socket_client( 'unix://' . $socket_file, $errno, $errstr, 2 );
	if ( ! $sock ) {
		return false;
	}

	fwrite( $sock, $message . "\n" );
	fclose( $sock );

	return true;
}

/*───────────────────────────────────────────────────────────────────────
 * 5. HOOK INTO CACHE INVALIDATION
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_amsawal_leaderboard_invalidated', 'wp_amsawal_ws_on_invalidate', 10, 2 );

function wp_amsawal_ws_on_invalidate( $type, $data ) {
	wp_amsawal_ws_broadcast( $type, $data );
}
