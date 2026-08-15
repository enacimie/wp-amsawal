<?php
/**
 * WP Amsawal Analytics — Core analytics class (stub)
 *
 * This singleton provides a unified interface for analytics data collection.
 * Full implementation (retention metrics, engagement scores, statistical
 * analysis) is deferred to a future release. For now, it provides a safe
 * no-op fallback so dependent modules (data-collection, qualitative,
 * quantitative, visualizations, admin panel) can load without crashing.
 *
 * @package Amsawal
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Amsawal_Analytics {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Cached aggregated data.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * Return singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use get_instance().
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register hooks for data collection.
	 */
	private function init_hooks() {
		add_action( 'amsawal_xp_awarded', array( $this, 'track_xp_event' ), 10, 3 );
		add_action( 'amsawal_level_up', array( $this, 'track_level_up' ), 10, 2 );
	}

	// ── Public API ──────────────────────────────────────────

	/**
	 * Generic interaction tracker used by the data-collection layer.
	 *
	 * Accepts both the legacy 3-arg signature (category, action, data)
	 * and the extended 6-arg signature (category, action, data, object_type, object_id, label).
	 *
	 * @param string $category    Interaction category (gamification, xapi, etc.).
	 * @param string $action      Specific action (points_awarded, rank_upgraded, etc.).
	 * @param array  $data        Event metadata.
	 * @param string $object_type Optional object type.
	 * @param mixed  $object_id   Optional object ID.
	 * @param string $label       Optional human-readable label.
	 */
	public function track_interaction( $category, $action, $data = array(), $object_type = '', $object_id = null, $label = '' ) {
		$payload = is_array( $data ) ? $data : array();
		if ( $object_type ) {
			$payload['object_type'] = $object_type;
		}
		if ( $object_id !== null && $object_id !== '' ) {
			$payload['object_id'] = $object_id;
		}
		if ( $label ) {
			$payload['label'] = $label;
		}
		$payload['category'] = $category;
		$payload['action']   = $action;

		$this->store_event( 0, $category . ':' . $action, $payload );
	}

	/**
	 * Record an XP award event.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $amount  XP amount.
	 * @param string $reason  Reason description.
	 */
	public function track_xp_event( $user_id, $amount, $reason ) {
		$this->store_event( $user_id, 'xp_awarded', array(
			'amount' => $amount,
			'reason' => $reason,
		) );
	}

	/**
	 * Record a level-up event.
	 *
	 * @param int $user_id   User ID.
	 * @param int $new_level New level number.
	 */
	public function track_level_up( $user_id, $new_level ) {
		$this->store_event( $user_id, 'level_up', array(
			'new_level' => $new_level,
		) );
	}

	/**
	 * Get aggregated metrics for the admin dashboard.
	 *
	 * @return array
	 */
	public function get_metrics() {
		if ( ! empty( $this->cache['metrics'] ) ) {
			return $this->cache['metrics'];
		}

		global $wpdb;

		$metrics = array(
			'total_users'       => 0,
			'active_users_7d'   => 0,
			'total_lessons'     => 0,
			'avg_score'         => 0,
			'retention_rate'    => 0,
			'total_xp_awarded'  => 0,
		);

		// Total users
		$metrics['total_users'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

		// Active users (7 days)
		$metrics['active_users_7d'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value >= %s",
			'_wp_amsawal_last_activity_date',
			date( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) )
		) );

		// Total lessons completed
		$metrics['total_lessons'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}h5p_results WHERE score > 0"
		) );

		// Average score
		$avg_row = $wpdb->get_row( $wpdb->prepare(
			"SELECT AVG(score) as avg_score, MAX(max_score) as max_score FROM {$wpdb->prefix}h5p_results WHERE max_score > %d",
			0
		) );
		if ( $avg_row && $avg_row->max_score > 0 ) {
			$metrics['avg_score'] = (int) round( ( $avg_row->avg_score / $avg_row->max_score ) * 100 );
		}

		// Total XP
		$metrics['total_xp_awarded'] = (int) $wpdb->get_var(
			"SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->usermeta} WHERE meta_key = '_amsawal_xp'"
		);

		$this->cache['metrics'] = $metrics;
		return $metrics;
	}

	/**
	 * Get recent events for the dashboard feed.
	 *
	 * @param int $limit Max number of events.
	 * @return array
	 */
	public function get_recent_events( $limit = 20 ) {
		// Pull from H5P results as the primary event source.
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT r.user_id, r.content_id, r.score, r.max_score, r.finished,
			        c.title
			 FROM {$wpdb->prefix}h5p_results r
			 LEFT JOIN {$wpdb->prefix}h5p_contents c ON c.id = r.content_id
			 ORDER BY r.finished DESC
			 LIMIT %d",
			$limit
		) );
	}

	// ── Internal helpers ───────────────────────────────────

	/**
	 * Store an event in the database.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Event type.
	 * @param array  $data    Event metadata.
	 */
	private function store_event( $user_id, $type, $data = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'amsawal_user_interactions';

		// Silently skip if table doesn't exist (schema created separately)
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table
		) );

		if ( ! $table_exists ) {
			return;
		}

		// Map event type to interaction_type + action columns
		$interaction_type = $type;
		$action = isset( $data['action'] ) ? $data['action'] : $type;
		$interaction_subtype = isset( $data['category'] ) ? $data['category'] : null;

		$wpdb->insert(
			$table,
			array(
				'user_id'             => $user_id,
				'interaction_type'    => $interaction_type,
				'interaction_subtype' => $interaction_subtype,
				'action'              => $action,
				'result_data'         => wp_json_encode( $data ),
				'timestamp'           => current_time( 'mysql' ),
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}
