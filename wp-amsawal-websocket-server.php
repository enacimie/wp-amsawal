#!/usr/bin/env php
<?php
/**
 * Amsawal WebSocket Server — Standalone process for real-time leaderboard.
 *
 * Implements RFC 6455: HTTP Upgrade handshake, client frame unmasking,
 * proper main loop with select() over all FDs simultaneously.
 *
 * Run: php wp-amsawal-websocket-server.php
 * Or via systemd/supervisor for production.
 *
 * @package Amsawal
 */

// Bootstrap WordPress.
$wp_load = dirname( __FILE__ ) . '/../../../../wp-load.php';
if ( file_exists( $wp_load ) ) {
	define( 'ABSPATH', dirname( $wp_load ) . '/' );
	require_once $wp_load;
} else {
	die( "wp-load.php not found. Place in wp-content/plugins/amsawal/ and run from there.\n" );
}

if ( ! defined( 'WP_AMSAWAL_WS_PORT' ) ) {
	define( 'WP_AMSAWAL_WS_PORT', 9501 );
}
if ( ! defined( 'WP_AMSAWAL_WS_SECRET' ) ) {
	define( 'WP_AMSAWAL_WS_SECRET', wp_hash( 'amsawal_ws_' . NONCE_SALT ) );
}
if ( ! defined( 'WP_AMSAWAL_WS_SOCK' ) ) {
	define( 'WP_AMSAWAL_WS_SOCK', WP_CONTENT_DIR . '/amsawal_ws.sock' );
}

/*───────────────────────────────────────────────────────────────────────
 * WebSocket Server (RFC 6455 compliant, pure PHP)
 *───────────────────────────────────────────────────────────────────────*/

class AmsawalWSServer {
	private $server;
	private $internal;
	private $clients = array();   // fd(int) => [userId, subscriptions, handshake, buffer, httpBuffer]
	private $all_fds  = array(); // fd(int) => resource
	private $client_count = 0;   // Track active connections

	// Security limits
	const MAX_CLIENTS     = 100;     // Maximum concurrent connections
	const MAX_BUFFER      = 65536;   // 64KB per client buffer
	const MAX_FRAME       = 1048576; // 1MB maximum frame payload
	const AUTH_TIMEOUT    = 30;      // Seconds to complete auth handshake
	const HTTP_MAX_HEADERS = 8192;   // 8KB max HTTP header size

	public function __construct( $port ) {
		$this->server = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		if ( ! $this->server ) {
			die( "[WS] Failed to create socket: " . socket_strerror( socket_last_error() ) . "\n" );
		}
		socket_set_option( $this->server, SOL_SOCKET, SO_REUSEADDR, 1 );
		socket_bind( $this->server, '0.0.0.0', $port );
		socket_listen( $this->server );

		// Unix socket for internal push from PHP web process.
		@unlink( WP_AMSAWAL_WS_SOCK );
		$this->internal = socket_create( AF_UNIX, SOCK_STREAM, 0 );
		socket_bind( $this->internal, WP_AMSAWAL_WS_SOCK );
		socket_listen( $this->internal );
		@chmod( WP_AMSAWAL_WS_SOCK, 0660 );

		$this->all_fds[ (int) $this->server ]   = $this->server;
		$this->all_fds[ (int) $this->internal ] = $this->internal;

		echo "[WS] Listening on tcp://" . '0.0.0.0' . ":{$port}\n";
		echo "[WS] Internal socket: " . WP_AMSAWAL_WS_SOCK . "\n";
		echo "[WS] PID: " . getmypid() . "\n\n";
	}

	/**
	 * Main event loop: select() over ALL file descriptors simultaneously.
	 * Handles TCP accept, Unix socket accept, and per-client frame processing.
	 */
	public function run() {
		while ( true ) {
			// Dispatch signals (SIGTERM/SIGINT) for graceful shutdown
			if ( function_exists( 'pcntl_signal_dispatch' ) ) {
				pcntl_signal_dispatch();
			}

			$read   = array_values( $this->all_fds );
			$write  = null;
			$except = null;

			// select() blocks up to timeout (here: 1s).
			$changed = @socket_select( $read, $write, $except, 1 );
			if ( false === $changed ) continue;

			// Check for authentication timeouts
			$now = time();
			foreach ( $this->clients as $fd => $client ) {
				// Disconnect clients that haven't authenticated within timeout
				if ( ! $client['authenticated'] && ( $now - $client['connected_at'] ) > self::AUTH_TIMEOUT ) {
					echo "[WS] Auth timeout for fd={$fd}, disconnecting\n";
					$this->disconnect( $fd );
				}
			}

			if ( 0 === $changed ) continue;

			foreach ( $read as $sock ) {
				$fd = (int) $sock;

				// New TCP connection from a browser.
				if ( $sock === $this->server ) {
					// Check max connections limit
					if ( $this->client_count >= self::MAX_CLIENTS ) {
						// Silently reject - don't accept the connection
						continue;
					}

					$client = @socket_accept( $sock );
					if ( $client ) {
						$cfd = (int) $client;
						$this->clients[ $cfd ] = array(
							'userId'        => 0,
							'subscriptions' => array(),
							'handshake'     => false,
							'authenticated' => false,  // Track auth state separately
							'buffer'        => '',
							'httpBuffer'    => '',
							'connected_at'  => time(), // For timeout tracking
						);
						$this->all_fds[ $cfd ] = $client;
						$this->client_count++;
					}
					continue;
				}

				// Push from internal Unix socket.
				if ( $sock === $this->internal ) {
					$this->acceptInternal();
					continue;
				}

				// Data from an existing client.
				$data = @socket_read( $sock, 65536, PHP_BINARY_READ );
				if ( false === $data || '' === $data ) {
					$this->disconnect( $fd );
					continue;
				}

				$client = &$this->clients[ $fd ];

				// ── Pre-handshake: accumulate HTTP upgrade request ──
				if ( ! $client['handshake'] && empty( $client['buffer'] ) ) {
					$client['httpBuffer'] .= $data;

					// Check max header size limit
					if ( strlen( $client['httpBuffer'] ) > self::HTTP_MAX_HEADERS ) {
						echo "[WS] Header too large ({$client['httpBuffer']} bytes), disconnecting fd={$fd}\n";
						$this->disconnect( $fd );
						continue;
					}

					// Check if we have the end-of-headers marker.
					$pos = strpos( $client['httpBuffer'], "\r\n\r\n" );
					if ( false === $pos ) continue; // incomplete, wait for more

					$headers_raw  = substr( $client['httpBuffer'], 0, $pos );
					$remaining    = substr( $client['httpBuffer'], $pos + 4 );
					$client['buffer'] = $remaining;

					if ( $this->performUpgrade( $fd, $headers_raw ) ) {
						// Upgrade sent — client is now in WS mode.
						continue;
					}
					// Upgrade failed — disconnect.
					$this->disconnect( $fd );
					continue;
				}

				// ── Post-handshake: WebSocket frame protocol ──
				$client['buffer'] .= $data;

				// Check buffer size limit to prevent memory exhaustion
				if ( strlen( $client['buffer'] ) > self::MAX_BUFFER ) {
					echo "[WS] Buffer overflow ({$client['buffer']} bytes), disconnecting fd={$fd}\n";
					$this->disconnect( $fd );
					continue;
				}

				while ( $this->processFrames( $fd ) ) {
					// Keep extracting frames from buffer.
				}
			}
		}
	}

	/*───────────────────────────────────────────────────────────────
	 * HTTP Upgrade Handshake (RFC 6455 §4.2)
	 *───────────────────────────────────────────────────────────────*/

	private function performUpgrade( $fd, $headers_raw ) {
		$headers = array();
		$lines   = explode( "\r\n", $headers_raw );
		$request_line = array_shift( $lines );
		foreach ( $lines as $line ) {
			$parts = explode( ': ', $line, 2 );
			if ( 2 === count( $parts ) ) {
				$headers[ strtolower( trim( $parts[0] ) ) ] = trim( $parts[1] );
			}
		}

		// Validate upgrade request.
		if ( empty( $headers['upgrade'] ) || strtolower( $headers['upgrade'] ) !== 'websocket' ) {
			return false;
		}
		if ( empty( $headers['sec-websocket-key'] ) ) {
			return false;
		}

		$key  = $headers['sec-websocket-key'];
		$hash = base64_encode( sha1( $key . '258EAFA5-E914-47DA-95CA-5AB5DC11BE71', true ) );

		$response  = "HTTP/1.1 101 Switching Protocols\r\n";
		$response .= "Upgrade: websocket\r\n";
		$response .= "Connection: Upgrade\r\n";
		$response .= "Sec-WebSocket-Accept: {$hash}\r\n";
		$response .= "\r\n";

		@socket_write( $this->all_fds[ $fd ], $response );

		$this->clients[ $fd ]['handshake'] = true;
		echo "[WS] New connection (fd=#{$fd}), awaiting auth frame...\n";
		return true;
	}

	/*───────────────────────────────────────────────────────────────
	 * Frame Parsing with Client Unmasking (RFC 6455 §5.3)
	 *───────────────────────────────────────────────────────────────*/

	private function processFrames( $fd ) {
		$client = &$this->clients[ $fd ];
		$buffer = &$client['buffer'];

		if ( strlen( $buffer ) < 2 ) return false;

		$byte1  = ord( $buffer[0] );
		$byte2  = ord( $buffer[1] );
		$opcode = $byte1 & 0x0F;
		$masked = (bool) ( $byte2 & 0x80 );
		$len    = $byte2 & 0x7F;
		$offset = 2;

		// Extended payload length.
		if ( 126 === $len ) {
			if ( strlen( $buffer ) < 4 ) return false;
			$len    = unpack( 'n', substr( $buffer, 2, 2 ) )[1];
			$offset = 4;
		} elseif ( 127 === $len ) {
			if ( strlen( $buffer ) < 10 ) return false;
			// 8-byte extended length (only use lower 4 bytes for PHP int limits).
			$len    = unpack( 'N2', substr( $buffer, 2, 8 ) )[2];
			$offset = 10;
		}

		// Reject oversized frames
		if ( $len > self::MAX_FRAME ) {
			echo "[WS] Frame too large ({$len} bytes) from fd={$fd}, disconnecting\n";
			$this->disconnect( $fd );
			return false;
		}

		// Masking key (4 bytes, present when masked=true).
		$mask_key = null;
		if ( $masked ) {
			if ( strlen( $buffer ) < $offset + 4 ) return false;
			$mask_key = substr( $buffer, $offset, 4 );
		}

		$total = $offset + ( $masked ? 4 : 0 ) + $len;
		if ( strlen( $buffer ) < $total ) return false;

		// Extract and unmask payload.
		$payload = substr( $buffer, $offset + ( $masked ? 4 : 0 ), $len );
		if ( $masked && $mask_key ) {
			$payload = $this->unmask( $payload, $mask_key );
		}

		// Consume processed bytes from buffer.
		$buffer = substr( $buffer, $total );

		$this->handleFrame( $fd, $opcode, $payload );
		return strlen( $buffer ) >= 2; // More frames pending?
	}

	/**
	 * Unmask a client-to-server payload per RFC 6455 §5.3.
	 */
	private function unmask( $payload, $mask_key ) {
		$unmasked = '';
		$len      = strlen( $payload );
		for ( $i = 0; $i < $len; $i++ ) {
			$unmasked .= $payload[ $i ] ^ $mask_key[ $i % 4 ];
		}
		return $unmasked;
	}

	/*───────────────────────────────────────────────────────────────
	 * Frame Handling (application logic)
	 *───────────────────────────────────────────────────────────────*/

	private function handleFrame( $fd, $opcode, $payload ) {
		// Close frame (opcode 8).
		if ( 8 === $opcode ) {
			$this->disconnect( $fd );
			return;
		}

		// Ping → Pong (opcode 9 → 10).
		if ( 9 === $opcode ) {
			$this->sendFrame( $fd, 10, $payload );
			return;
		}

		// Only handle text frames (opcode 1).
		if ( 1 !== $opcode ) return;

		$msg = json_decode( $payload, true );
		if ( ! is_array( $msg ) ) return;

		$client = &$this->clients[ $fd ];

		if ( ! $client['handshake'] ) {
			$this->authenticate( $fd, $msg );
			return;
		}

		$event = isset( $msg['event'] ) ? $msg['event'] : '';

		// Subscribe to leaderboard type.
		if ( 'subscribe' === $event ) {
			$type = sanitize_text_field( $msg['type'] ?? '' );
			if ( $type ) {
				$client['subscriptions'][ $type ] = true;
				$this->sendFrame( $fd, 1, json_encode( array(
					'event' => 'subscribed',
					'type'  => $type,
				) ) );
			}
			return;
		}

		if ( 'unsubscribe' === $event ) {
			$type = sanitize_text_field( $msg['type'] ?? '' );
			unset( $client['subscriptions'][ $type ] );
		}
	}

	/**
	 * Authenticate the client after handshake.
	 * Client sends: {"event":"auth", "token":"...", "userId":N}
	 */
	private function authenticate( $fd, $msg ) {
		if ( 'auth' !== ( $msg['event'] ?? '' ) ) {
			$this->sendFrame( $fd, 1, json_encode( array(
				'event'   => 'error',
				'message' => 'Expected auth event',
			) ) );
			$this->disconnect( $fd );
			return;
		}

		$token  = sanitize_text_field( $msg['token'] ?? '' );
		$userId = (int) ( $msg['userId'] ?? 0 );

		// Validate token against stored transient.
		$stored = get_transient( 'wp_amsawal_ws_token_' . $userId );
		if ( $stored && hash_equals( $stored, $token ) ) {
			$this->clients[ $fd ]['userId']        = $userId;
			$this->clients[ $fd ]['handshake']     = true;
			$this->clients[ $fd ]['authenticated'] = true; // Auth completed.
			delete_transient( 'wp_amsawal_ws_token_' . $userId );

			$this->sendFrame( $fd, 1, json_encode( array(
				'event'  => 'connected',
				'userId' => $userId,
			) ) );

			echo "[WS] User {$userId} authenticated (fd=#{$fd})\n";
		} else {
			$this->sendFrame( $fd, 1, json_encode( array(
				'event'   => 'error',
				'message' => 'Invalid token',
			) ) );
			$this->disconnect( $fd );
		}
	}

	/*───────────────────────────────────────────────────────────────
	 * Send a WebSocket frame (server → client, no masking needed)
	 *───────────────────────────────────────────────────────────────*/

	private function sendFrame( $fd, $opcode, $payload ) {
		if ( ! isset( $this->all_fds[ $fd ] ) ) return;

		$len   = strlen( $payload );
		$frame = chr( 0x80 | $opcode );

		if ( $len > 65535 ) {
			$frame .= chr( 127 ) . pack( 'J', $len ); // 8-byte big-endian.
		} elseif ( $len > 125 ) {
			$frame .= chr( 126 ) . pack( 'n', $len );
		} else {
			$frame .= chr( $len );
		}

		$frame .= $payload;
		@socket_write( $this->all_fds[ $fd ], $frame, strlen( $frame ) );
	}

	/*───────────────────────────────────────────────────────────────
	 * Broadcast to all subscribed clients
	 *───────────────────────────────────────────────────────────────*/

	public function broadcast( $type, $data ) {
		$message = json_encode( array(
			'event' => 'leaderboard_update',
			'type'  => $type,
			'data'  => $data,
			'time'  => time(),
		) );

		foreach ( $this->clients as $fd => &$client ) {
			if ( ! $client['handshake'] ) continue;
			if ( ! empty( $client['subscriptions'][ $type ] ) || ! empty( $client['subscriptions']['all'] ) ) {
				$this->sendFrame( $fd, 1, $message );
			}
		}
	}

	/*───────────────────────────────────────────────────────────────
	 * Accept push messages from the PHP web process via Unix socket
	 *───────────────────────────────────────────────────────────────*/

	private function acceptInternal() {
		$client = @socket_accept( $this->internal );
		if ( ! $client ) return;

		$data = '';
		$chunk = '';
		while ( ( $chunk = @socket_read( $client, 4096, PHP_BINARY_READ ) ) !== '' && $chunk !== false ) {
			$data .= $chunk;
		}
		@socket_close( $client );

		if ( '' === $data ) return;

		$msg = json_decode( trim( $data ), true );
		if ( $msg && 'leaderboard_update' === ( $msg['event'] ?? '' ) ) {
			$type = sanitize_text_field( $msg['type'] ?? 'monedas' );
			$this->broadcast( $type, $msg['data'] ?? array() );
		}
	}

	/*───────────────────────────────────────────────────────────────
	 * Disconnect and cleanup
	 *───────────────────────────────────────────────────────────────*/

	private function disconnect( $fd ) {
		if ( isset( $this->clients[ $fd ] ) ) {
			$userId = $this->clients[ $fd ]['userId'];
			if ( $userId ) {
				echo "[WS] User {$userId} disconnected (fd=#{$fd})\n";
			}
			unset( $this->clients[ $fd ] );
			$this->client_count--;
		}
		if ( isset( $this->all_fds[ $fd ] ) ) {
			@socket_close( $this->all_fds[ $fd ] );
			unset( $this->all_fds[ $fd ] );
		}
	}

	/**
	 * Graceful shutdown: close all sockets and remove Unix socket file.
	 */
	public function shutdown() {
		@unlink( WP_AMSAWAL_WS_SOCK );
		foreach ( $this->all_fds as $fd => $sock ) {
			@socket_close( $sock );
		}
		$this->all_fds = array();
		$this->clients = array();
		echo "[WS] Server shut down.\n";
	}
}

/*───────────────────────────────────────────────────────────────────────
 * MAIN
 *───────────────────────────────────────────────────────────────────────*/

echo "=== Amsawal WebSocket Server ===\n";

$server = new AmsawalWSServer( WP_AMSAWAL_WS_PORT );

// Graceful shutdown on SIGTERM / SIGINT.
if ( function_exists( 'pcntl_signal' ) ) {
	pcntl_signal( SIGTERM, function() use ( $server ) {
		$server->shutdown();
		exit( 0 );
	});
	pcntl_signal( SIGINT, function() use ( $server ) {
		$server->shutdown();
		exit( 0 );
	});
}

// Single, unified event loop — no delegation to run() / processInternal().
$server->run();
