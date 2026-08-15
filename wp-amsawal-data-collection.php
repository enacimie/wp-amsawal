<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * wp-amsawal-data-collection.php - Sistema de recolección de datos
 *
 * Este archivo implementa la recolección de datos de diferentes fuentes:
 * - H5P (resultados de actividades interactivas)
 * - Gamificación (sistema de puntos, insignias, rangos)
 * - H5P xAPI event recording (internal, no external LRS integration)
 * - Interacciones con IA
 * 
 * @package Amsawal
 * @subpackage Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal para la recolección de datos
 */
class WP_Amsawal_Data_Collection {

	private static $instance = null;
	private $analytics;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->analytics = WP_Amsawal_Analytics::get_instance();
		
		// Hooks para recolectar datos de diferentes fuentes
		$this->init_hooks();
	}

	/**
	 * Inicializar hooks para recolección de datos
	 */
	private function init_hooks() {
		// Gamification data collection (GamiPress integration)
		add_action( 'gamipress_award_achievement', array( $this, 'collect_gamification_achievement_data' ), 10, 5 );
		add_action( 'gamipress_update_user_points', array( $this, 'collect_gamification_points_data' ), 10, 8 );
		add_action( 'gamipress_update_user_rank', array( $this, 'collect_gamification_rank_data' ), 10, 5 );
		
		// Social data collection (BuddyPress integration)
		if ( class_exists( 'BuddyPress' ) ) {
			add_action( 'bp_activity_posted_update', array( $this, 'collect_social_activity_data' ), 10, 3 );
			add_action( 'groups_join_group', array( $this, 'collect_social_group_join_data' ), 10, 2 );
		}
		
		// AI interaction data
		add_action( 'wp_amsawal_ai_query_complete', array( $this, 'collect_ai_interaction_data' ), 10, 4 );
		add_action( 'wp_amsawal_essay_evaluated', array( $this, 'collect_ai_essay_data' ), 10, 3 );
	}

	/**
	 * Recolectar datos de logro de gamificación
	 */
	public function collect_gamification_achievement_data( $user_id, $achievement_id, $trigger, $site_id, $args ) {
		$achievement = get_post( $achievement_id );

		if ( ! $achievement ) {
			return;
		}
		$achievement_type = $achievement->post_type;

		$this->analytics->track_interaction(
			'gamification',
			'achievement_earned',
			array(
				'achievement_id' => $achievement_id,
				'achievement_title' => $achievement->post_title,
				'achievement_type' => $achievement_type,
				'points_rewarded' => gamipress_get_achievement_points( $achievement_id ),
				'context' => 'user_progression'
			),
			$achievement_type,
			$achievement_id,
			'Achievement'
		);
	}

	/**
	 * Recolectar datos de puntos de gamificación
	 */
	public function collect_gamification_points_data( $user_id, $new_points, $total_points, $admin_id, $achievement_id, $points_type, $reason, $log_type ) {
		$this->analytics->track_xp_event(
			$user_id,
			$new_points,
			$reason ?: "points_awarded ({$points_type})"
		);
	}

	/**
	 * Recolectar datos de ascenso de rango
	 */
	public function collect_gamification_rank_data( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) {
		if ( ! $new_rank ) {
			return;
		}

		$rank_id   = $new_rank->ID;
		$rank      = $new_rank;
		$rank_type = $new_rank->post_type;

		$this->analytics->track_interaction(
			'gamification',
			'rank_upgraded',
			array(
				'rank_id' => $rank_id,
				'rank_title' => $rank->post_title,
				'rank_type' => $rank_type,
				'admin_id' => $admin_id,
				'context' => 'user_progression'
			),
			$rank_type,
			$rank_id,
			'Rank'
		);
	}

	/**
	 * Recolectar datos de actividad social (BuddyPress)
	 */
	public function collect_social_activity_data( $content, $user_id, $activity_id ) {
		if ( ! class_exists( 'BuddyPress' ) ) {
			return;
		}

		$activity = new BP_Activity_Activity( $activity_id );
		
		$this->analytics->track_interaction(
			'social',
			'activity_posted',
			array(
				'activity_id' => $activity_id,
				'activity_type' => $activity->type,
				'content_length' => strlen( $content ),
				'component' => $activity->component,
				'context' => 'community_engagement'
			),
			$activity->type,
			$activity_id,
			'BPActivity'
		);
	}

	/**
	 * Recolectar datos de unión a grupo (BuddyPress)
	 */
	public function collect_social_group_join_data( $group_id, $user_id ) {
		if ( ! class_exists( 'BuddyPress' ) ) {
			return;
		}

		$group = groups_get_group( $group_id );
		
		$this->analytics->track_interaction(
			'social',
			'group_joined',
			array(
				'group_id' => $group_id,
				'group_name' => $group->name,
				'group_slug' => $group->slug,
				'member_count' => $group->total_member_count,
				'context' => 'community_engagement'
			),
			'group_membership',
			$group_id,
			'BuddyPressGroup'
		);
	}

	/**
	 * Recolectar datos de interacción con IA
	 */
	public function collect_ai_interaction_data( $prompt, $response, $user_id, $context ) {
		$this->analytics->track_interaction(
			'ai_interaction',
			'query_executed',
			array(
				'prompt_length' => strlen( $prompt ),
				'response_length' => strlen( $response ),
				'context' => $context,
				'tokens_used' => $this->estimate_token_count( $prompt . $response ),
				'response_time' => null
			),
			$prompt,
			null,
			'AIInteraction'
		);
	}

	/**
	 * Recolectar datos de evaluación de ensayo por IA
	 */
	public function collect_ai_essay_data( $user_id, $text, $evaluation_result ) {
		$this->analytics->track_interaction(
			'ai_interaction',
			'essay_evaluated',
			array(
				'text_length' => strlen( $text ),
				'score' => $evaluation_result['score'] ?? null,
				'feedback_length' => strlen( $evaluation_result['feedback'] ?? '' ),
				'correction_length' => strlen( $evaluation_result['corrected_text'] ?? '' ),
				'context' => 'writing_assessment',
				'tokens_used' => $this->estimate_token_count( $text )
			),
			'essay_evaluation',
			null,
			'AIEssay'
		);
	}

	/**
	 * Estimar conteo de tokens (método aproximado)
	 */
	private function estimate_token_count( $text ) {
		// Aproximación: 1 token ~ 4 caracteres o 0.75 palabras
		$words = str_word_count( strip_tags( $text ) );
		return (int) ceil( $words * 1.3 ); // Aproximadamente 1.3 tokens por palabra en español
	}

	/**
	 * Recolectar datos de progreso de lección
	 */
	public function collect_lesson_progress_data( $user_id, $lesson_id, $progress_percent, $action = 'progress_update' ) {
		$lesson = get_post( $lesson_id );
		
		if ( ! $lesson ) {
			return;
		}

		$this->analytics->track_interaction(
			'lesson',
			$action,
			array(
				'lesson_id' => $lesson_id,
				'lesson_title' => $lesson->post_title,
				'progress_percent' => $progress_percent,
				'course_id' => get_post_meta( $lesson_id, 'wp_amsawal_mb_course', true ),
				'context' => 'learning_path'
			),
			'progress',
			$lesson_id,
			'Lesson'
		);
	}

	/**
	 * Recolectar datos de completitud de unidad
	 */
	public function collect_unit_completion_data( $user_id, $unit_id, $time_taken ) {
		$unit = get_post( $unit_id );
		
		if ( ! $unit ) {
			return;
		}

		$this->analytics->track_interaction(
			'lesson',
			'unit_completed',
			array(
				'unit_id' => $unit_id,
				'unit_title' => $unit->post_title,
				'time_taken' => $time_taken,
				'course_id' => get_post_meta( $unit_id, 'wp_amsawal_mb_course', true ),
				'context' => 'learning_path'
			),
			'completion',
			$unit_id,
			'LearningUnit'
		);
	}

	/**
	 * Recolectar datos de comportamiento de usuario
	 */
	public function collect_user_behavior_data( $user_id, $behavior_type, $details = array() ) {
		$this->analytics->track_interaction(
			'user_behavior',
			$behavior_type,
			array_merge( array(
				'context' => 'learning_behavior',
				'timestamp' => current_time( 'mysql' )
			), $details ),
			$behavior_type,
			null,
			'UserBehavior'
		);
	}

	/**
	 * Recolectar datos de desempeño en actividades
	 */
	public function collect_performance_data( $user_id, $activity_type, $score, $max_score, $details = array() ) {
		$this->analytics->track_interaction(
			'performance',
			'activity_completed',
			array_merge( array(
				'activity_type' => $activity_type,
				'score' => $score,
				'max_score' => $max_score,
				'percentage' => $max_score > 0 ? round( ( $score / $max_score ) * 100, 2 ) : 0,
				'context' => 'assessment'
			), $details ),
			$activity_type,
			null,
			'Performance'
		);
	}
}

// Initialize the data collection system
function wp_amsawal_init_data_collection() {
	WP_Amsawal_Data_Collection::get_instance();
}
add_action( 'init', 'wp_amsawal_init_data_collection' );

/**
 * Función auxiliar para recolectar datos de progreso de lección
 */
function wp_amsawal_collect_lesson_progress( $lesson_id, $progress_percent, $action = 'progress_update' ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$collection = WP_Amsawal_Data_Collection::get_instance();
	$collection->collect_lesson_progress_data( $user_id, $lesson_id, $progress_percent, $action );
}

/**
 * Función auxiliar para recolectar datos de completitud de unidad
 */
function wp_amsawal_collect_unit_completion( $unit_id, $time_taken ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$collection = WP_Amsawal_Data_Collection::get_instance();
	$collection->collect_unit_completion_data( $user_id, $unit_id, $time_taken );
}

/**
 * Función auxiliar para recolectar datos de comportamiento de usuario
 */
function wp_amsawal_collect_user_behavior( $behavior_type, $details = array() ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$collection = WP_Amsawal_Data_Collection::get_instance();
	$collection->collect_user_behavior_data( $user_id, $behavior_type, $details );
}

/**
 * Función auxiliar para recolectar datos de desempeño
 */
function wp_amsawal_collect_performance_data( $activity_type, $score, $max_score, $details = array() ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$collection = WP_Amsawal_Data_Collection::get_instance();
	$collection->collect_performance_data( $user_id, $activity_type, $score, $max_score, $details );
}
// F14-2: Enhanced user behavior tracking
function amsawal_track_behavior($event_type, $user_id, $metadata = []) {
    global $wpdb;

    $table = $wpdb->prefix . 'amsawal_user_interactions';

    $data = [
        'user_id'          => $user_id,
        'interaction_type' => $event_type,
        'action'           => $event_type,
        'result_data'      => wp_json_encode($metadata),
        'timestamp'        => current_time('mysql')
    ];
    $format = ['%d', '%s', '%s', '%s', '%s'];

    if (isset($metadata['lesson_id'])) {
        $data['content_id']   = $metadata['lesson_id'];
        $data['content_type'] = 'lesson';
        $format[] = '%d';
        $format[] = '%s';
    } elseif (isset($metadata['h5p_id'])) {
        $data['content_id']   = $metadata['h5p_id'];
        $data['content_type'] = 'h5p';
        $format[] = '%d';
        $format[] = '%s';
    }

    $wpdb->insert($table, $data, $format);

    do_action('amsawal_behavior_tracked', $event_type, $user_id, $metadata);

    return $wpdb->insert_id;
}

// Track specific behaviors
add_action('amsawal_lesson_start', function($user_id, $lesson_id) {
    amsawal_track_behavior('lesson_start', $user_id, ['lesson_id' => $lesson_id]);
}, 10, 2);

add_action('amsawal_lesson_complete', function($user_id, $is_repeat) {
    amsawal_track_behavior('lesson_complete', $user_id, ['is_repeat' => $is_repeat]);
}, 10, 2);

add_action('amsawal_quiz_start', function($user_id, $h5p_id) {
    amsawal_track_behavior('quiz_start', $user_id, ['h5p_id' => $h5p_id]);
}, 10, 2);

add_action('amsawal_quiz_complete', function($user_id, $h5p_id, $score) {
    amsawal_track_behavior('quiz_complete', $user_id, [
        'h5p_id' => $h5p_id,
        'score' => $score
    ]);
}, 10, 3);

add_action('amsawal_streak_updated', function($user_id, $streak_count) {
    amsawal_track_behavior('streak_update', $user_id, ['streak' => $streak_count]);
}, 10, 2);

add_action('amsawal_level_up', function($user_id, $new_level) {
    amsawal_track_behavior('level_up', $user_id, ['level' => $new_level]);
}, 10, 2);

add_action('amsawal_achievement_earned', function($user_id, $achievement_id) {
    amsawal_track_behavior('achievement_unlocked', $user_id, ['achievement_id' => $achievement_id]);
}, 10, 2);

// F15-5: SQL injection prevention
// Todas las queries deben usar $wpdb->prepare()
// Ejemplo correcto:
// $wpdb->get_var($wpdb->prepare("SELECT * FROM table WHERE id = %d", $id));
// $wpdb->get_results($wpdb->prepare("SELECT * FROM table WHERE name = %s", $name));
// Nunca usar variables directamente en queries SQL
