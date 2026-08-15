<?php
/**
 * PHPUnit Bootstrap for WP Amsawal
 *
 * Usage: vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/
 *
 * Requires: composer require --dev phpunit/phpunit
 *
 * For WordPress-specific tests, also install:
 *   composer require --dev 10up/wp_mock
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Plugin directory
define( 'WP_AMSAWAL_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WP_AMSAWAL_PLUGIN_URL', 'http://example.org/wp-content/plugins/wp-amsawal/' );

// Mock WordPress functions needed by the plugin
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		return WP_AMSAWAL_PLUGIN_URL . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', $key ) );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_array( $args ) ) {
			return array_merge( $defaults, $args );
		}
		return $defaults;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return wp_kses( $data, array(
			'a'      => array( 'href' => array(), 'title' => array() ),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'img'    => array( 'src' => array(), 'alt' => array(), 'class' => array() ),
		) );
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $string, $allowed_html ) {
		return strip_tags( $string, array_map( function( $tag ) use ( $allowed_html ) {
			return "<$tag>";
		}, array_keys( $allowed_html ) ) );
	}
}

if ( ! function_exists( 'wp_uniqid' ) ) {
	function wp_uniqid( $prefix = '' ) {
		return $prefix . uniqid();
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'mock_nonce_' . md5( $action );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return true; // Always pass in tests
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		if ( isset( $GLOBALS['studio_override_can'] ) && $GLOBALS['studio_override_can'] === false ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return true;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return (object) array(
			'ID'            => 1,
			'user_login'    => 'testuser',
			'user_nicename' => 'testuser',
			'display_name'  => 'Test User',
		);
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( $post_id, $meta_key, $meta_value = '', $delete_all = false ) {
		return true;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
		return null;
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		return 'Test Post';
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = null ) {
		return array();
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		return true;
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '', $scheme = null ) {
		return 'http://example.org/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'site_url' ) ) {
	function site_url( $path = '', $scheme = null ) {
		return 'http://example.org/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '', $scheme = null ) {
		return 'http://example.org/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		return new WP_Error( 'http_unavailable', 'HTTP requests disabled in tests' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return '';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'do_shortcode' ) ) {
	function do_shortcode( $content ) {
		return $content;
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return false;
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $post_types = '' ) {
		return false;
	}
}

if ( ! function_exists( 'is_front_page' ) ) {
	function is_front_page() {
		return false;
	}
}

if ( ! function_exists( 'is_page' ) ) {
	function is_page( $page = '' ) {
		return false;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();
		public $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( empty( $code ) ) return;
			$this->errors[ $code ][] = $message;
			if ( ! empty( $data ) ) $this->error_data[ $code ] = $data;
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) $code = $this->get_error_code();
			return isset( $this->errors[ $code ] ) ? $this->errors[ $code ][0] : '';
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return isset( $codes[0] ) ? $codes[0] : '';
		}
	}
}

// Load the plugin's AI functions (the most testable module)
require_once WP_AMSAWAL_PLUGIN_DIR . 'wp-amsawal-ai.php';

// Load Studio (needs the AI layer loaded first)
require_once WP_AMSAWAL_PLUGIN_DIR . 'wp-amsawal-studio.php';

// ── Additional mocks needed by Studio AJAX endpoints ──

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $postarr = array() ) {
		static $next_id = 1000;
		return $next_id++;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return ( $number == 1 ) ? $single : $plural;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) {
		return addslashes( $text );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( $text );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}

/**
 * Exception class for simulating wp_send_json in tests.
 * wp_send_json_success / wp_send_json_error throw this;
 * studio_safe_ajax() catches it and returns the response.
 */
class Studio_AJAX_Exception extends Exception {
	public $response;
	public function __construct( $response ) {
		$this->response = $response;
		parent::__construct( 'wp_send_json' );
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null ) {
		throw new Studio_AJAX_Exception( array( 'success' => true, 'data' => $data ) );
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null, $status_code = 200 ) {
		throw new Studio_AJAX_Exception( array( 'success' => false, 'data' => $data ) );
	}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( $action = -1, $query_arg = false, $stop = true ) {
		if ( ! empty( $GLOBALS['studio_override_nonce'] ) && $GLOBALS['studio_override_nonce'] === false ) {
			throw new Studio_AJAX_Exception( array( 'success' => false, 'data' => 'Nonce verification failed' ) );
		}
		return 1;
	}
}

// current_user_can already defined above with $GLOBALS override support.

if ( ! function_exists( 'wp_amsawal_rate_limit_or_die' ) ) {
	function wp_amsawal_rate_limit_or_die( $key, $max = 5, $window = 60 ) {
		if ( ! empty( $GLOBALS['studio_override_limit'] ) && $GLOBALS['studio_override_limit'] === false ) {
			throw new Studio_AJAX_Exception( array( 'success' => false, 'data' => array( 'message' => 'Rate limited' ) ) );
		}
		return true;
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = array() ) {
		throw new Exception( is_string( $message ) ? $message : wp_json_encode( $message ) );
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key = '', $single = false ) {
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' ) {
		return true;
	}
}

if ( ! function_exists( 'delete_user_meta' ) ) {
	function delete_user_meta( $user_id, $meta_key, $meta_value = '' ) {
		return true;
	}
}

if ( ! function_exists( 'wp_set_current_user' ) ) {
	function wp_set_current_user( $id, $name = '' ) {
		return (object) array( 'ID' => $id );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'mysql', $gmt = 0 ) {
		return $type === 'timestamp' ? time() : date( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'mb_strlen' ) ) {
	function mb_strlen( $str, $encoding = 'UTF-8' ) {
		return strlen( $str );
	}
}

if ( ! function_exists( 'mb_strtolower' ) ) {
	function mb_strtolower( $str, $encoding = 'UTF-8' ) {
		return strtolower( $str );
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num_words = 55, $more = '...' ) {
		$words = explode( ' ', $text );
		if ( count( $words ) > $num_words ) {
			return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
		}
		return $text;
	}
}
