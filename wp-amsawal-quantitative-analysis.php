<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * wp-amsawal-quantitative-analysis.php - Herramientas de análisis cuantitativo
 *
 * Este archivo implementa herramientas de análisis estadístico de los datos
 * recopilados del sistema de aprendizaje Amsawal, incluyendo métricas de
 * rendimiento, progreso, compromiso y patrones de aprendizaje.
 *
 * @package Amsawal
 * @subpackage Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal para análisis cuantitativo
 */
class WP_Amsawal_Quantitative_Analysis {

	private static $instance = null;
	private $table;
	private $db;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $wpdb->prefix . 'amsawal_user_interactions';
	}

	/**
	 * Obtener métricas generales de usuarios
	 */
	public function get_user_engagement_metrics( $start_date = null, $end_date = null, $user_ids = array() ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-1 month' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( ! empty( $user_ids ) ) {
			$user_placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
			$where_clause .= " AND user_id IN ($user_placeholders)";
			$params = array_merge( $params, $user_ids );
		}

		// Consulta para obtener métricas de compromiso
		$query = "SELECT 
					user_id,
					COUNT(*) as total_interactions,
					COUNT(DISTINCT DATE(timestamp)) as active_days,
					AVG(CASE WHEN JSON_EXTRACT(result_data, '$.score') IS NOT NULL AND JSON_EXTRACT(result_data, '$.max_score') > 0 THEN (JSON_EXTRACT(result_data, '$.score') / JSON_EXTRACT(result_data, '$.max_score')) * 100 ELSE NULL END) as avg_accuracy,
					SUM(COALESCE(JSON_EXTRACT(result_data, '$.duration'), 0)) as total_duration_seconds,
					MAX(timestamp) as last_interaction
				  FROM {$this->table}
				  {$where_clause}
				  GROUP BY user_id";

		$results = $this->db->get_results( $this->db->prepare( $query, $params ) );

		$processed_results = array();
		foreach ( $results as $result ) {
			$processed_results[ $result->user_id ] = array(
				'total_interactions' => (int) $result->total_interactions,
				'active_days' => (int) $result->active_days,
				'avg_accuracy' => round( (float) $result->avg_accuracy, 2 ),
				'total_duration_minutes' => round( (int) $result->total_duration_seconds / 60, 2 ),
				'last_interaction' => $result->last_interaction,
				'engagement_score' => $this->calculate_engagement_score( $result )
			);
		}

		return $processed_results;
	}

	/**
	 * Calcular puntuación de compromiso
	 */
	private function calculate_engagement_score( $user_metrics ) {
		// Fórmula de compromiso basada en interacciones, días activos y precisión
		$interaction_factor = min( 100, $user_metrics->total_interactions / 10 ); // Normalizar interacciones
		$consistency_factor = min( 100, ( $user_metrics->active_days / 30 ) * 100 ); // Suponiendo periodo de 30 días
		$accuracy_factor = min( 100, $user_metrics->avg_accuracy ? $user_metrics->avg_accuracy : 0 );

		// Ponderaciones: consistencia (40%), interacciones (30%), precisión (30%)
		$score = ( $consistency_factor * 0.4 ) + ( $interaction_factor * 0.3 ) + ( $accuracy_factor * 0.3 );

		return round( $score, 2 );
	}

	/**
	 * Obtener métricas de progreso de aprendizaje
	 */
	public function get_learning_progress_metrics( $start_date = null, $end_date = null, $user_ids = array() ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-1 month' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$where_clause = "WHERE timestamp BETWEEN %s AND %s AND interaction_type IN ('lesson', 'quiz', 'h5p')";
		$params = array( $start_date, $end_date );

		if ( ! empty( $user_ids ) ) {
			$user_placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
			$where_clause .= " AND user_id IN ($user_placeholders)";
			$params = array_merge( $params, $user_ids );
		}

		$query = "SELECT 
					user_id,
					COUNT(*) as lessons_interacted,
					COUNT(CASE WHEN action = 'complete' THEN 1 END) as lessons_completed,
					AVG(CASE WHEN JSON_EXTRACT(result_data, '$.score') IS NOT NULL AND JSON_EXTRACT(result_data, '$.max_score') > 0 THEN (JSON_EXTRACT(result_data, '$.score') / JSON_EXTRACT(result_data, '$.max_score')) * 100 ELSE NULL END) as avg_lesson_score,
					COUNT(DISTINCT JSON_EXTRACT(result_data, '$.content_id')) as unique_lessons_accessed
				  FROM {$this->table}
				  {$where_clause}
				  GROUP BY user_id";

		$results = $this->db->get_results( $this->db->prepare( $query, $params ) );

		$processed_results = array();
		foreach ( $results as $result ) {
			$processed_results[ $result->user_id ] = array(
				'lessons_interacted' => (int) $result->lessons_interacted,
				'lessons_completed' => (int) $result->lessons_completed,
				'completion_rate' => $result->lessons_interacted > 0 ? round( ( $result->lessons_completed / $result->lessons_interacted ) * 100, 2 ) : 0,
				'avg_lesson_score' => round( (float) $result->avg_lesson_score, 2 ),
				'unique_lessons_accessed' => (int) $result->unique_lessons_accessed,
				'progress_score' => $this->calculate_progress_score( $result )
			);
		}

		return $processed_results;
	}

	/**
	 * Calcular puntuación de progreso
	 */
	private function calculate_progress_score( $progress_metrics ) {
		// Puntuación basada en tasa de completitud, puntuación promedio y variedad de contenido
		$completion_factor = min( 100, $progress_metrics->completion_rate );
		$accuracy_factor = min( 100, $progress_metrics->avg_lesson_score ? $progress_metrics->avg_lesson_score : 0 );
		$variety_factor = min( 100, ( $progress_metrics->unique_lessons_accessed / 20 ) * 100 ); // Suponiendo 20 como máximo razonable

		// Ponderaciones: completitud (50%), precisión (30%), variedad (20%)
		$score = ( $completion_factor * 0.5 ) + ( $accuracy_factor * 0.3 ) + ( $variety_factor * 0.2 );

		return round( $score, 2 );
	}

	/**
	 * Obtener métricas de rendimiento por tipo de contenido
	 */
	public function get_content_performance_metrics( $start_date = null, $end_date = null, $content_types = array() ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-1 month' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

        $where_clause = "WHERE timestamp BETWEEN %s AND %s AND JSON_EXTRACT(result_data, '$.score') IS NOT NULL AND JSON_EXTRACT(result_data, '$.max_score') > 0";
        $params = array( $start_date, $end_date );

        if ( ! empty( $content_types ) ) {
            $type_placeholders = implode( ',', array_fill( 0, count( $content_types ), '%s' ) );
            $where_clause .= " AND interaction_type IN ($type_placeholders)";
            $params = array_merge( $params, $content_types );
        }

        $query = "SELECT 
                    interaction_type as content_type,
                    JSON_EXTRACT(result_data, '$.content_id') as content_id,
                    COUNT(*) as total_attempts,
                    COUNT(CASE WHEN JSON_EXTRACT(result_data, '$.score') >= (JSON_EXTRACT(result_data, '$.max_score') * 0.8) THEN 1 END) as high_score_attempts,
                    AVG((JSON_EXTRACT(result_data, '$.score') / JSON_EXTRACT(result_data, '$.max_score')) * 100) as avg_success_rate,
                    STDDEV((JSON_EXTRACT(result_data, '$.score') / JSON_EXTRACT(result_data, '$.max_score')) * 100) as score_std_deviation
                  FROM {$this->table}
                  {$where_clause}
                  GROUP BY interaction_type, JSON_EXTRACT(result_data, '$.content_id')
                  ORDER BY avg_success_rate DESC";

		$results = $this->db->get_results( $this->db->prepare( $query, $params ) );

		$processed_results = array();
		foreach ( $results as $result ) {
			$processed_results[] = array(
				'content_type' => $result->content_type,
				'content_id' => (int) $result->content_id,
				'total_attempts' => (int) $result->total_attempts,
				'high_score_attempts' => (int) $result->high_score_attempts,
				'high_score_rate' => $result->total_attempts > 0 ? round( ( $result->high_score_attempts / $result->total_attempts ) * 100, 2 ) : 0,
				'avg_success_rate' => round( (float) $result->avg_success_rate, 2 ),
				'score_std_deviation' => $result->score_std_deviation ? round( (float) $result->score_std_deviation, 2 ) : 0,
				'difficulty_level' => $this->determine_difficulty_level( $result )
			);
		}

		return $processed_results;
	}

	/**
	 * Determinar nivel de dificultad basado en tasas de éxito
	 */
	private function determine_difficulty_level( $content_metrics ) {
		$success_rate = $content_metrics->avg_success_rate;

		if ( $success_rate >= 85 ) {
			return 'easy';
		} elseif ( $success_rate >= 65 ) {
			return 'medium';
		} elseif ( $success_rate >= 45 ) {
			return 'hard';
		} else {
			return 'very_hard';
		}
	}

	/**
	 * Obtener métricas de retención y abandono
	 */
	public function get_retention_metrics( $start_date = null, $end_date = null, $period = 'week' ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-3 months' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		// Calcular retención por cohortes
		$cohorts = $this->calculate_cohort_retention( $start_date, $end_date, $period );

		// Calcular métricas generales de retención
		$total_users = $this->db->get_var( $this->db->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$this->table} WHERE timestamp BETWEEN %s AND %s",
			$start_date, $end_date
		) );

		$active_users = $this->db->get_var( $this->db->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$this->table} WHERE timestamp >= %s",
			date( 'Y-m-d', strtotime( '-7 days' ) )
		) );

		return array(
			'total_users' => (int) $total_users,
			'active_users' => (int) $active_users,
			'retention_rate' => $total_users > 0 ? round( ( $active_users / $total_users ) * 100, 2 ) : 0,
			'cohort_data' => $cohorts
		);
	}

	/**
	 * Calcular retención por cohortes
	 */
	private function calculate_cohort_retention( $start_date, $end_date, $period ) {
		// Esta es una implementación básica - en producción se necesitaría una lógica más compleja
		$query = $this->db->prepare(
			"SELECT 
				DATE(DATE_SUB(timestamp, INTERVAL DAYOFWEEK(timestamp) DAY)) as cohort_week,
				COUNT(DISTINCT user_id) as initial_users
			 FROM {$this->table}
			 WHERE timestamp BETWEEN %s AND %s
			 GROUP BY DATE(DATE_SUB(timestamp, INTERVAL DAYOFWEEK(timestamp) DAY))
			 ORDER BY cohort_week",
			$start_date, $end_date
		);

		$cohorts = $this->db->get_results( $query );

		$retention_data = array();
		foreach ( $cohorts as $cohort ) {
			$week_start = $cohort->cohort_week;
			$initial_users = $cohort->initial_users;

			// Usuarios activos 1 semana después
			$active_after_1w = $this->db->get_var( $this->db->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$this->table}
				 WHERE user_id IN (
					SELECT DISTINCT user_id FROM {$this->table}
					WHERE DATE(timestamp) >= %s AND DATE(timestamp) < DATE_ADD(%s, INTERVAL 1 WEEK)
				 )
				 AND DATE(timestamp) BETWEEN DATE_ADD(%s, INTERVAL 1 WEEK) AND DATE_ADD(%s, INTERVAL 2 WEEK)",
				$week_start, $week_start, $week_start, $week_start
			) );

			$retention_data[] = array(
				'cohort_week' => $week_start,
				'initial_users' => (int) $initial_users,
				'active_after_1w' => (int) $active_after_1w,
				'retention_1w' => $initial_users > 0 ? round( ( $active_after_1w / $initial_users ) * 100, 2 ) : 0
			);
		}

		return $retention_data;
	}

	/**
	 * Obtener tendencias de uso
	 */
	public function get_usage_trends( $start_date = null, $end_date = null, $granularity = 'day' ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-1 month' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$date_format = $this->get_date_format_for_granularity( $granularity );

        $query = $this->db->prepare(
            "SELECT 
                DATE_FORMAT(timestamp, %s) as period,
                COUNT(*) as total_interactions,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT JSON_EXTRACT(result_data, '$.content_id')) as unique_content,
                AVG(COALESCE(JSON_EXTRACT(result_data, '$.duration'), 0)) as avg_duration
             FROM {$this->table}
             WHERE timestamp BETWEEN %s AND %s
             GROUP BY DATE_FORMAT(timestamp, %s)
             ORDER BY period",
            $date_format, $start_date, $end_date, $date_format
        );

		$results = $this->db->get_results( $query );

		return array_map( function( $row ) {
			return array(
				'period' => $row->period,
				'total_interactions' => (int) $row->total_interactions,
				'unique_users' => (int) $row->unique_users,
				'unique_content' => (int) $row->unique_content,
				'avg_duration_seconds' => round( (float) $row->avg_duration, 2 ),
				'avg_interactions_per_user' => $row->unique_users > 0 ? round( $row->total_interactions / $row->unique_users, 2 ) : 0
			);
		}, $results );
	}

	/**
	 * Obtener formato de fecha según granularidad
	 */
	private function get_date_format_for_granularity( $granularity ) {
		switch ( $granularity ) {
			case 'hour':
				return '%Y-%m-%d %H:00:00';
			case 'day':
				return '%Y-%m-%d';
			case 'week':
				return '%Y-W%u';
			case 'month':
				return '%Y-%m';
			default:
				return '%Y-%m-%d';
		}
	}

	/**
	 * Obtener métricas de gamificación
	 */
	public function get_gamification_metrics( $start_date = null, $end_date = null, $user_ids = array() ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-1 month' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$where_clause = "WHERE timestamp BETWEEN %s AND %s AND interaction_type = 'gamification'";
		$params = array( $start_date, $end_date );

		if ( ! empty( $user_ids ) ) {
			$user_placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
			$where_clause .= " AND user_id IN ($user_placeholders)";
			$params = array_merge( $params, $user_ids );
		}

		$query = "SELECT 
					user_id,
					COUNT(CASE WHEN action = 'achievement_earned' THEN 1 END) as achievements_earned,
					COUNT(CASE WHEN action = 'points_awarded' THEN 1 END) as points_events,
					SUM(CASE WHEN action = 'points_awarded' AND JSON_EXTRACT(result_data, '$.points') IS NOT NULL THEN JSON_EXTRACT(result_data, '$.points') ELSE 0 END) as total_points_gained,
					COUNT(CASE WHEN action = 'rank_upgraded' THEN 1 END) as ranks_gained
				  FROM {$this->table}
				  {$where_clause}
				  GROUP BY user_id";

		$results = $this->db->get_results( $this->db->prepare( $query, $params ) );

		$processed_results = array();
		foreach ( $results as $result ) {
			$processed_results[ $result->user_id ] = array(
				'achievements_earned' => (int) $result->achievements_earned,
				'points_events' => (int) $result->points_events,
				'total_points_gained' => (int) $result->total_points_gained,
				'ranks_gained' => (int) $result->ranks_gained,
				'gamification_score' => $this->calculate_gamification_score( $result )
			);
		}

		return $processed_results;
	}

	/**
	 * Calcular puntuación de gamificación
	 */
	private function calculate_gamification_score( $gamification_metrics ) {
		// Puntuación basada en logros, puntos y ascensos
		$achievement_factor = min( 100, $gamification_metrics->achievements_earned * 5 );
		$points_factor = min( 100, ( $gamification_metrics->total_points_gained / 100 ) * 100 ); // Suponiendo 100 puntos como referencia
		$rank_factor = min( 100, $gamification_metrics->ranks_gained * 20 );

		// Ponderaciones: logros (40%), puntos (40%), rangos (20%)
		$score = ( $achievement_factor * 0.4 ) + ( $points_factor * 0.4 ) + ( $rank_factor * 0.2 );

		return round( $score, 2 );
	}

	/**
	 * Obtener análisis de patrones de aprendizaje
	 */
	public function get_learning_patterns( $start_date = null, $end_date = null, $user_id = null ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-3 months' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = $this->db->prepare(
			"SELECT 
				user_id,
				interaction_type,
				COUNT(*) as interaction_count,
				AVG(CASE WHEN JSON_EXTRACT(result_data, '$.score') IS NOT NULL AND JSON_EXTRACT(result_data, '$.max_score') > 0 THEN (JSON_EXTRACT(result_data, '$.score') / JSON_EXTRACT(result_data, '$.max_score')) * 100 ELSE NULL END) as avg_performance,
				AVG(COALESCE(JSON_EXTRACT(result_data, '$.duration'), 0)) as avg_duration,
				HOUR(timestamp) as peak_hour,
				DAYOFWEEK(timestamp) as peak_day
			 FROM {$this->table}
			 {$where_clause}
			 GROUP BY user_id, interaction_type, HOUR(timestamp), DAYOFWEEK(timestamp)
			 ORDER BY interaction_count DESC",
			$params
		);

		$results = $this->db->get_results( $query );

		$patterns = array();
		foreach ( $results as $result ) {
			if ( ! isset( $patterns[ $result->user_id ] ) ) {
				$patterns[ $result->user_id ] = array(
					'user_id' => $result->user_id,
					'patterns' => array()
				);
			}

			$patterns[ $result->user_id ]['patterns'][] = array(
				'interaction_type' => $result->interaction_type,
				'interaction_count' => (int) $result->interaction_count,
				'avg_performance' => round( (float) $result->avg_performance, 2 ),
				'avg_duration' => round( (int) $result->avg_duration, 2 ),
				'peak_hour' => (int) $result->peak_hour,
				'peak_day' => $this->get_day_name( $result->peak_day )
			);
		}

		return array_values( $patterns );
	}

	/**
	 * Obtener nombre del día
	 */
	private function get_day_name( $day_of_week ) {
		$days = array(
			1 => 'Domingo',
			2 => 'Lunes', 
			3 => 'Martes',
			4 => 'Miércoles',
			5 => 'Jueves',
			6 => 'Viernes',
			7 => 'Sábado'
		);

		return isset( $days[ $day_of_week ] ) ? $days[ $day_of_week ] : 'Unknown';
	}

	/**
	 * Obtener métricas resumidas para un período
	 */
	public function get_summary_metrics( $start_date = null, $end_date = null ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01', strtotime( '-1 month' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$stats = array();

		// Total de interacciones
		$stats['total_interactions'] = (int) $this->db->get_var( $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table} WHERE timestamp BETWEEN %s AND %s",
			$start_date, $end_date
		) );

		// Usuarios únicos
		$stats['unique_users'] = (int) $this->db->get_var( $this->db->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$this->table} WHERE timestamp BETWEEN %s AND %s",
			$start_date, $end_date
		) );

                	// Contenido único interactuado
		$stats['unique_content'] = (int) $this->db->get_var( $this->db->prepare(
			"SELECT COUNT(DISTINCT JSON_EXTRACT(result_data, '$.content_id')) FROM {$this->table} WHERE timestamp BETWEEN %s AND %s AND JSON_EXTRACT(result_data, '$.content_id') IS NOT NULL",
			$start_date, $end_date
		) );

		// Puntuación promedio general
		$avg_score_result = $this->db->get_var( $this->db->prepare(
			"SELECT AVG((JSON_EXTRACT(result_data, '$.score') / JSON_EXTRACT(result_data, '$.max_score')) * 100) FROM {$this->table} 
			 WHERE timestamp BETWEEN %s AND %s AND JSON_EXTRACT(result_data, '$.score') IS NOT NULL AND JSON_EXTRACT(result_data, '$.max_score') > 0",
			$start_date, $end_date
		) );
		$stats['average_score'] = $avg_score_result ? round( (float) $avg_score_result, 2 ) : 0;

		// Duración promedio de sesión
		$avg_duration_result = $this->db->get_var( $this->db->prepare(
			"SELECT AVG(COALESCE(JSON_EXTRACT(result_data, '$.duration'), 0)) FROM {$this->table} 
			 WHERE timestamp BETWEEN %s AND %s",
			$start_date, $end_date
		) );
		$stats['average_duration'] = $avg_duration_result ? round( (float) $avg_duration_result / 60, 2 ) : 0; // En minutos

		// Tasa de completitud de lecciones
		$lesson_completions = (int) $this->db->get_var( $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table} 
			 WHERE timestamp BETWEEN %s AND %s AND interaction_type = 'lesson' AND action = 'complete'",
			$start_date, $end_date
		) );
		$lesson_starts = (int) $this->db->get_var( $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table} 
			 WHERE timestamp BETWEEN %s AND %s AND interaction_type = 'lesson' AND action = 'start'",
			$start_date, $end_date
		) );
		$stats['lesson_completion_rate'] = $lesson_starts > 0 ? round( ( $lesson_completions / $lesson_starts ) * 100, 2 ) : 0;

		return $stats;
	}

	/**
	 * Exportar datos de análisis
	 */
	public function export_analysis_data( $start_date, $end_date, $format = 'csv', $filters = array() ) {
		$data = $this->get_comprehensive_analysis_data( $start_date, $end_date, $filters );

		switch ( $format ) {
			case 'json':
				header( 'Content-Type: application/json' );
				header( 'Content-Disposition: attachment; filename="amsawal-analytics-' . date( 'Y-m-d' ) . '.json"' );
				echo wp_json_encode( $data, JSON_PRETTY_PRINT );
				break;
			
			case 'csv':
			default:
				header( 'Content-Type: text/csv' );
				header( 'Content-Disposition: attachment; filename="amsawal-analytics-' . date( 'Y-m-d' ) . '.csv"' );
				
				$output = fopen( 'php://output', 'w' );
				
				// Escribir encabezados
				if ( ! empty( $data ) ) {
					$headers = array_keys( reset( $data ) );
					fputcsv( $output, $headers );
					
					// Escribir filas
					foreach ( $data as $row ) {
						fputcsv( $output, array_values( $row ) );
					}
				}
				
				fclose( $output );
				break;
		}
		
		exit;
	}

	/**
	 * Obtener datos de análisis comprehensivos
	 */
	private function get_comprehensive_analysis_data( $start_date, $end_date, $filters ) {
		// Esta función retornaría datos combinados de todas las métricas
		// Por simplicidad, voy a retornar un conjunto básico de datos
		$data = array();
		
		// Datos de ejemplo para demostración
		$engagement_metrics = $this->get_user_engagement_metrics( $start_date, $end_date );
		
		foreach ( $engagement_metrics as $user_id => $metrics ) {
			$user = get_user_by( 'id', $user_id );
			
			$data[] = array(
				'user_id' => $user_id,
				'username' => $user ? $user->user_login : 'unknown',
				'display_name' => $user ? $user->display_name : 'unknown',
				'total_interactions' => $metrics['total_interactions'],
				'active_days' => $metrics['active_days'],
				'avg_accuracy' => $metrics['avg_accuracy'],
				'total_duration_minutes' => $metrics['total_duration_minutes'],
				'engagement_score' => $metrics['engagement_score'],
				'data_export_date' => current_time( 'mysql' )
			);
		}
		
		return $data;
	}
}

// Initialize quantitative analysis
function wp_amsawal_init_quantitative_analysis() {
	WP_Amsawal_Quantitative_Analysis::get_instance();
}
add_action( 'init', 'wp_amsawal_init_quantitative_analysis' );

/**
 * Función auxiliar para obtener métricas de compromiso
 */
function wp_amsawal_get_user_engagement_metrics( $start_date = null, $end_date = null, $user_ids = array() ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_user_engagement_metrics( $start_date, $end_date, $user_ids );
}

/**
 * Función auxiliar para obtener métricas de progreso
 */
function wp_amsawal_get_learning_progress_metrics( $start_date = null, $end_date = null, $user_ids = array() ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_learning_progress_metrics( $start_date, $end_date, $user_ids );
}

/**
 * Función auxiliar para obtener métricas de rendimiento de contenido
 */
function wp_amsawal_get_content_performance_metrics( $start_date = null, $end_date = null, $content_types = array() ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_content_performance_metrics( $start_date, $end_date, $content_types );
}

/**
 * Función auxiliar para obtener métricas de retención
 */
function wp_amsawal_get_retention_metrics( $start_date = null, $end_date = null, $period = 'week' ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_retention_metrics( $start_date, $end_date, $period );
}

/**
 * Función auxiliar para obtener tendencias de uso
 */
function wp_amsawal_get_usage_trends( $start_date = null, $end_date = null, $granularity = 'day' ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_usage_trends( $start_date, $end_date, $granularity );
}

/**
 * Función auxiliar para obtener métricas de gamificación
 */
function wp_amsawal_get_gamification_metrics( $start_date = null, $end_date = null, $user_ids = array() ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_gamification_metrics( $start_date, $end_date, $user_ids );
}

/**
 * Función auxiliar para obtener patrones de aprendizaje
 */
function wp_amsawal_get_learning_patterns( $start_date = null, $end_date = null, $user_id = null ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_learning_patterns( $start_date, $end_date, $user_id );
}

/**
 * Función auxiliar para obtener métricas resumidas
 */
function wp_amsawal_get_summary_metrics( $start_date = null, $end_date = null ) {
	$analysis = WP_Amsawal_Quantitative_Analysis::get_instance();
	return $analysis->get_summary_metrics( $start_date, $end_date );
}