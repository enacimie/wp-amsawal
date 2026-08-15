<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * wp-amsawal-qualitative-analysis.php - Sistema de análisis cualitativo con IA
 *
 * Este archivo implementa el sistema de análisis cualitativo de los datos
 * recopilados del sistema, aprovechando la API de IA para interpretar
 * patrones, tendencias y comportamientos cualitativos de los usuarios.
 *
 * @package Amsawal
 * @subpackage Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal para análisis cualitativo con IA
 */
class WP_Amsawal_Qualitative_Analysis {

	private static $instance = null;
	private $analytics;
	private $db;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->analytics = WP_Amsawal_Analytics::get_instance();
		
		// Hooks para análisis cualitativo
		$this->init_hooks();
	}

	/**
	 * Inicializar hooks para análisis cualitativo
	 */
	private function init_hooks() {
		// AJAX para análisis cualitativo
		add_action( 'wp_ajax_wp_amsawal_run_qualitative_analysis', array( $this, 'ajax_run_qualitative_analysis' ) );
		add_action( 'wp_ajax_wp_amsawal_get_ai_insights', array( $this, 'ajax_get_ai_insights' ) );
	}

	/**
	 * Ejecutar análisis cualitativo con IA
	 */
	public function run_qualitative_analysis( $start_date, $end_date, $user_id = null, $interaction_type = null ) {
		// Recoger datos relevantes para análisis cualitativo
		$interaction_data = $this->get_interaction_data_for_analysis( $start_date, $end_date, $user_id, $interaction_type );
		$engagement_data = $this->get_engagement_summary( $start_date, $end_date, $user_id );
		$performance_data = $this->get_performance_summary( $start_date, $end_date, $user_id );
		$behavior_data = $this->get_behavior_patterns( $start_date, $end_date, $user_id );

		// Preparar prompt para la IA
		$prompt = $this->build_qualitative_analysis_prompt( 
			$interaction_data, 
			$engagement_data, 
			$performance_data, 
			$behavior_data 
		);

		// Llamar a la API de IA
		$ai_response = $this->call_ai_api( $prompt );

		if ( is_wp_error( $ai_response ) ) {
			return $ai_response;
		}

		// Procesar la respuesta de la IA
		$analysis_result = $this->parse_ai_response( $ai_response );

		// Guardar el análisis cualitativo
		if ( $analysis_result ) {
			$this->save_qualitative_analysis( $start_date, $end_date, $user_id, $analysis_result );
		}

		return $analysis_result;
	}

	/**
	 * Construir prompt para análisis cualitativo
	 */
	private function build_qualitative_analysis_prompt( $interactions, $engagement, $performance, $behavior ) {
		$prompt = "Eres un experto en análisis de datos de aprendizaje y educación. Has recibido datos sobre el comportamiento de un estudiante en una plataforma de aprendizaje de idiomas (específicamente Tamazight/Rif Berber).\n\n";
		
		$prompt .= "DATOS DE INTERACCIÓN:\n";
		$prompt .= "- Total de interacciones: " . count( $interactions ) . "\n";
		$prompt .= "- Tipos de interacción: " . implode( ', ', array_unique( array_column( $interactions, 'interaction_type' ) ) ) . "\n";
		$prompt .= "- Fechas: Desde " . min( array_column( $interactions, 'timestamp' ) ) . " hasta " . max( array_column( $interactions, 'timestamp' ) ) . "\n\n";

		$prompt .= "DATOS DE COMPROMISO:\n";
		if ( ! empty( $engagement ) ) {
			$prompt .= "- Días activos: " . ( $engagement['active_days'] ?? 0 ) . "\n";
			$prompt .= "- Promedio de interacciones por día: " . ( $engagement['avg_daily_interactions'] ?? 0 ) . "\n";
			$prompt .= "- Puntuación de compromiso: " . ( $engagement['engagement_score'] ?? 0 ) . "\n\n";
		}

		$prompt .= "DATOS DE RENDIMIENTO:\n";
		if ( ! empty( $performance ) ) {
			$prompt .= "- Precisión promedio: " . ( $performance['avg_accuracy'] ?? 0 ) . "%\n";
			$prompt .= "- Tasa de completitud: " . ( $performance['completion_rate'] ?? 0 ) . "%\n";
			$prompt .= "- Puntuación promedio: " . ( $performance['avg_score'] ?? 0 ) . "\n\n";
		}

		$prompt .= "PATRONES DE COMPORTAMIENTO:\n";
		if ( ! empty( $behavior ) ) {
			$prompt .= "- Horas pico de actividad: " . ( $behavior['peak_hours'] ?? 'No disponible' ) . "\n";
			$prompt .= "- Días de la semana más activos: " . ( $behavior['peak_days'] ?? 'No disponible' ) . "\n";
			$prompt .= "- Duración promedio de sesión: " . ( $behavior['avg_session_duration'] ?? 0 ) . " minutos\n\n";
		}

		$prompt .= "Por favor proporciona un análisis cualitativo detallado que incluya:\n";
		$prompt .= "1. Evaluación del nivel de compromiso del estudiante\n";
		$prompt .= "2. Patrones de aprendizaje identificados\n";
		$prompt .= "3. Fortalezas y debilidades observadas\n";
		$prompt .= "4. Recomendaciones pedagógicas específicas\n";
		$prompt .= "5. Posibles barreras o desafíos detectados\n\n";

		$prompt .= "Formatea tu respuesta como un JSON con esta estructura:\n";
		$prompt .= "{\n";
		$prompt .= "  \"engagement_assessment\": \"Evaluación del nivel de compromiso\",\n";
		$prompt .= "  \"learning_patterns\": \"Patrones de aprendizaje identificados\",\n";
		$prompt .= "  \"strengths\": \"Fortalezas observadas\",\n";
		$prompt .= "  \"weaknesses\": \"Debilidades observadas\",\n";
		$prompt .= "  \"pedagogical_recommendations\": \"Recomendaciones pedagógicas\",\n";
		$prompt .= "  \"challenges_identified\": \"Barreras o desafíos detectados\",\n";
		$prompt .= "  \"confidence_level\": 85 (número del 0-100 indicando confianza)\n";
		$prompt .= "}";

		return $prompt;
	}

	/**
	 * Llamar a la API de IA
	 */
	private function call_ai_api( $prompt ) {
		// Usar la función de IA existente del sistema
		if ( function_exists( 'wp_amsawal_ai_query' ) ) {
			$result = wp_amsawal_ai_query( $prompt, array(
				'model' => get_option( 'wp_amsawal_ai_model', 'enacimie/qwen3.5-4b' ),
				'temperature' => 0.3,
				'max_tokens' => 1000
			) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return $result;
		} else {
			return new WP_Error( 'ai_not_available', 'La funcionalidad de IA no está disponible' );
		}
	}

	/**
	 * Parsear respuesta de la IA
	 */
	private function parse_ai_response( $ai_response ) {
		// Primero intentar decodificar directamente como JSON
		$data = json_decode( $ai_response, true );
		
		if ( json_last_error() === JSON_ERROR_NONE && $data ) {
			return $data;
		}
		
		// Si no es JSON puro, intentar extraer JSON de markdown
		if ( preg_match( '/```(?:json)?\s*(\{.*\})\s*```/s', $ai_response, $matches ) ) {
			$json_part = $matches[1];
			$data = json_decode( $json_part, true );
			
			if ( json_last_error() === JSON_ERROR_NONE && $data ) {
				return $data;
			}
		}
		
		// Si no hay JSON, crear estructura con la respuesta completa
		return array(
			'engagement_assessment' => 'No disponible',
			'learning_patterns' => 'No disponible',
			'strengths' => 'No disponible',
			'weaknesses' => 'No disponible',
			'pedagogical_recommendations' => $ai_response, // Guardar toda la respuesta como recomendaciones
			'challenges_identified' => 'No disponible',
			'confidence_level' => 50
		);
	}

	/**
	 * Obtener datos de interacción para análisis
	 */
	private function get_interaction_data_for_analysis( $start_date, $end_date, $user_id = null, $interaction_type = null ) {
		global $wpdb;
		
		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		if ( $interaction_type ) {
			$where_clause .= " AND interaction_type = %s";
			$params[] = $interaction_type;
		}

		$query = "SELECT * FROM {$this->analytics->interaction_table_name} {$where_clause} ORDER BY timestamp ASC";

		return $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );
	}

	/**
	 * Obtener resumen de compromiso
	 */
	private function get_engagement_summary( $start_date, $end_date, $user_id = null ) {
		global $wpdb;
		
		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					COUNT(DISTINCT DATE(timestamp)) as active_days,
					COUNT(*) as total_interactions,
					COUNT(*) / COUNT(DISTINCT DATE(timestamp)) as avg_daily_interactions
				  FROM {$this->analytics->interaction_table_name} 
				  {$where_clause}";

		$result = $wpdb->get_row( $wpdb->prepare( $query, $params ), ARRAY_A );

		if ( $result ) {
			$engagement_score = $this->calculate_engagement_score( $result );
			$result['engagement_score'] = $engagement_score;
		}

		return $result ?: array();
	}

	/**
	 * Calcular puntuación de compromiso
	 */
	private function calculate_engagement_score( $engagement_data ) {
		$active_days = $engagement_data['active_days'] ?? 0;
		$total_interactions = $engagement_data['total_interactions'] ?? 0;
		$avg_daily_interactions = $engagement_data['avg_daily_interactions'] ?? 0;

		// Calcular puntuación de 0-100 basada en diferentes factores
		$day_score = min( 50, ( $active_days / 7 ) * 50 ); // Máximo 50 puntos por días activos
		$interaction_score = min( 30, ( $total_interactions / 50 ) * 30 ); // Máximo 30 puntos por interacciones
		$frequency_score = min( 20, ( $avg_daily_interactions / 5 ) * 20 ); // Máximo 20 puntos por frecuencia

		return round( $day_score + $interaction_score + $frequency_score, 2 );
	}

	/**
	 * Obtener resumen de rendimiento
	 */
	private function get_performance_summary( $start_date, $end_date, $user_id = null ) {
		global $wpdb;
		
		$where_clause = "WHERE timestamp BETWEEN %s AND %s AND score IS NOT NULL AND max_score > 0";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					AVG((score / max_score) * 100) as avg_accuracy,
					COUNT(CASE WHEN score >= max_score * 0.8 THEN 1 END) as completed_activities,
					COUNT(*) as total_activities,
					AVG(score) as avg_score
				  FROM {$this->analytics->interaction_table_name} 
				  {$where_clause}";

		$result = $wpdb->get_row( $wpdb->prepare( $query, $params ), ARRAY_A );

		if ( $result ) {
			$result['completion_rate'] = $result['total_activities'] > 0 
				? round( ( $result['completed_activities'] / $result['total_activities'] ) * 100, 2 ) 
				: 0;
		}

		return $result ?: array();
	}

	/**
	 * Obtener patrones de comportamiento
	 */
	private function get_behavior_patterns( $start_date, $end_date, $user_id = null ) {
		global $wpdb;
		
		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					HOUR(timestamp) as hour,
					DAYOFWEEK(timestamp) as day_of_week,
					AVG(duration) as avg_duration,
					COUNT(*) as activity_count
				  FROM {$this->analytics->interaction_table_name} 
				  {$where_clause} AND duration IS NOT NULL
				  GROUP BY HOUR(timestamp), DAYOFWEEK(timestamp)
				  ORDER BY activity_count DESC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );

		if ( empty( $results ) ) {
			return array();
		}

		// Agrupar por hora y día para encontrar patrones
		$hours = array();
		$days = array();
		$total_duration = 0;
		$count = 0;

		foreach ( $results as $result ) {
			$hours[] = $result['hour'];
			$days[] = $result['day_of_week'];
			$total_duration += $result['avg_duration'];
			$count++;
		}

		$peak_hours = ! empty( $hours ) ? implode( ', ', array_slice( array_unique( $hours ), 0, 3 ) ) : 'No disponible';
		$peak_days = ! empty( $days ) ? $this->convert_day_numbers_to_names( array_slice( array_unique( $days ), 0, 3 ) ) : 'No disponible';
		$avg_session_duration = $count > 0 ? round( ( $total_duration / $count ) / 60, 2 ) : 0; // En minutos

		return array(
			'peak_hours' => $peak_hours,
			'peak_days' => $peak_days,
			'avg_session_duration' => $avg_session_duration
		);
	}

	/**
	 * Convertir números de día a nombres
	 */
	private function convert_day_numbers_to_names( $day_numbers ) {
		$day_names = array(
			1 => 'Dom', 2 => 'Lun', 3 => 'Mar', 4 => 'Mié',
			5 => 'Jue', 6 => 'Vie', 7 => 'Sáb'
		);

		$names = array();
		foreach ( $day_numbers as $num ) {
			$names[] = $day_names[ $num ] ?? 'Desconocido';
		}

		return implode( ', ', $names );
	}

	/**
	 * Guardar análisis cualitativo
	 */
	private function save_qualitative_analysis( $start_date, $end_date, $user_id, $analysis_result ) {
		global $wpdb;

		// Guardar en la tabla de análisis cualitativo
		$table_name = $wpdb->prefix . 'amsawal_qualitative_analysis';

		$insert_data = array(
			'user_id' => $user_id ?: 0,
			'analysis_period_start' => $start_date,
			'analysis_period_end' => $end_date,
			'analysis_result' => wp_json_encode( $analysis_result ),
			'created_at' => current_time( 'mysql' )
		);

		$insert_format = array(
			'%d',  // user_id
			'%s',  // analysis_period_start
			'%s',  // analysis_period_end
			'%s',  // analysis_result
			'%s'   // created_at
		);

		return $wpdb->insert( $table_name, $insert_data, $insert_format );
	}

	/**
	 * Obtener insights de IA
	 */
	public function get_ai_insights( $start_date, $end_date, $user_id = null, $limit = 10 ) {
		global $wpdb;

		$where_clause = "WHERE analysis_period_start >= %s AND analysis_period_end <= %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT * FROM {$wpdb->prefix}amsawal_qualitative_analysis 
				  {$where_clause} 
				  ORDER BY created_at DESC 
				  LIMIT %d";

		$params[] = $limit;

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$insights = array();
		foreach ( $results as $result ) {
			$parsed_result = json_decode( $result->analysis_result, true );
			if ( $parsed_result ) {
				$insights[] = array(
					'id' => $result->id,
					'user_id' => $result->user_id,
					'created_at' => $result->created_at,
					'engagement_assessment' => $parsed_result['engagement_assessment'] ?? '',
					'learning_patterns' => $parsed_result['learning_patterns'] ?? '',
					'strengths' => $parsed_result['strengths'] ?? '',
					'weaknesses' => $parsed_result['weaknesses'] ?? '',
					'pedagogical_recommendations' => $parsed_result['pedagogical_recommendations'] ?? '',
					'challenges_identified' => $parsed_result['challenges_identified'] ?? '',
					'confidence_level' => $parsed_result['confidence_level'] ?? 50
				);
			}
		}

		return $insights;
	}

	/**
	 * AJAX: Ejecutar análisis cualitativo
	 */
	public function ajax_run_qualitative_analysis() {
		// Verificar permisos y nonce
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permiso para realizar esta acción' ) );
		}

		check_ajax_referer( 'wp_amsawal_run_qualitative_analysis', 'security' );

		$start_date = sanitize_text_field( $_POST['start_date'] );
		$end_date = sanitize_text_field( $_POST['end_date'] );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : null;
		$interaction_type = isset( $_POST['interaction_type'] ) ? sanitize_text_field( $_POST['interaction_type'] ) : null;

		// Validar fechas
		if ( ! strtotime( $start_date ) || ! strtotime( $end_date ) ) {
			wp_send_json_error( array( 'message' => 'Fechas inválidas' ) );
		}

		// Ejecutar análisis
		$result = $this->run_qualitative_analysis( $start_date, $end_date, $user_id, $interaction_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'analysis' => $this->format_analysis_output( $result ),
			'data' => $result
		) );
	}

	/**
	 * Formatear salida del análisis para mostrar
	 */
	private function format_analysis_output( $analysis_data ) {
		$output = '<div class="ai-analysis-output">';

		if ( isset( $analysis_data['engagement_assessment'] ) ) {
			$output .= '<h4>📊 Evaluación de Compromiso</h4>';
			$output .= '<p>' . esc_html( $analysis_data['engagement_assessment'] ) . '</p>';
		}

		if ( isset( $analysis_data['learning_patterns'] ) ) {
			$output .= '<h4>💡 Patrones de Aprendizaje</h4>';
			$output .= '<p>' . esc_html( $analysis_data['learning_patterns'] ) . '</p>';
		}

		if ( isset( $analysis_data['strengths'] ) ) {
			$output .= '<h4>👍 Fortalezas</h4>';
			$output .= '<p>' . esc_html( $analysis_data['strengths'] ) . '</p>';
		}

		if ( isset( $analysis_data['weaknesses'] ) ) {
			$output .= '<h4>⚠️️ Debilidades</h4>';
			$output .= '<p>' . esc_html( $analysis_data['weaknesses'] ) . '</p>';
		}

		if ( isset( $analysis_data['pedagogical_recommendations'] ) ) {
			$output .= '<h4>🎓 Recomendaciones Pedagógicas</h4>';
			$output .= '<p>' . esc_html( $analysis_data['pedagogical_recommendations'] ) . '</p>';
		}

		if ( isset( $analysis_data['challenges_identified'] ) ) {
			$output .= '<h4>⚠️ Desafíos Identificados</h4>';
			$output .= '<p>' . esc_html( $analysis_data['challenges_identified'] ) . '</p>';
		}

		$output .= '<div class="confidence-indicator">Confianza del análisis: ' . 
		          ( isset( $analysis_data['confidence_level'] ) ? esc_html( $analysis_data['confidence_level'] ) . '%' : 'No disponible' ) . 
		          '</div>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * AJAX: Obtener insights de IA
	 */
	public function ajax_get_ai_insights() {
		// Verificar permisos y nonce
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permiso para realizar esta acción' ) );
		}

		check_ajax_referer( 'wp_amsawal_get_ai_insights', 'security' );

		$start_date = sanitize_text_field( $_POST['start_date'] );
		$end_date = sanitize_text_field( $_POST['end_date'] );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : null;

		// Validar fechas
		if ( ! strtotime( $start_date ) || ! strtotime( $end_date ) ) {
			wp_send_json_error( array( 'message' => 'Fechas inválidas' ) );
		}

		$insights = $this->get_ai_insights( $start_date, $end_date, $user_id );

		wp_send_json_success( $insights );
	}

	/**
	 * Obtener análisis cualitativo histórico
	 */
	public function get_historical_qualitative_analysis( $user_id = null, $limit = 10, $offset = 0 ) {
		global $wpdb;

		$where_clause = "";
		$params = array();

		if ( $user_id ) {
			$where_clause = "WHERE user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT * FROM {$wpdb->prefix}amsawal_qualitative_analysis 
				  {$where_clause}
				  ORDER BY created_at DESC 
				  LIMIT %d OFFSET %d";

		$params[] = $limit;
		$params[] = $offset;

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$formatted_results = array();
		foreach ( $results as $result ) {
			$parsed_result = json_decode( $result->analysis_result, true );
			$formatted_results[] = array(
				'id' => $result->id,
				'user_id' => $result->user_id,
				'analysis_period' => array(
					'start' => $result->analysis_period_start,
					'end' => $result->analysis_period_end
				),
				'created_at' => $result->created_at,
				'analysis_data' => $parsed_result ?: array(),
				'confidence_level' => $parsed_result['confidence_level'] ?? 50
			);
		}

		return $formatted_results;
	}

	/**
	 * Obtener insights de IA en vivo (ejecuta análisis bajo demanda)
	 * Renombrada para evitar conflicto con get_ai_insights() de DB (línea ~401)
	 */
	public function run_ai_insights_now( $start_date, $end_date, $user_id = null ) {
		$interactions = $this->get_interaction_data_for_analysis( $start_date, $end_date, $user_id );
		$engagement = $this->get_engagement_summary( $start_date, $end_date, $user_id );
		$performance = $this->get_performance_summary( $start_date, $end_date, $user_id );
		$behavior = $this->get_behavior_patterns( $start_date, $end_date, $user_id );

		$prompt = $this->build_qualitative_analysis_prompt( $interactions, $engagement, $performance, $behavior );
		$ai_response = $this->call_ai_api( $prompt );

		if ( is_wp_error( $ai_response ) ) {
			return $ai_response;
		}

		return $this->parse_ai_response( $ai_response );
	}

	/**
	 * Generar informe cualitativo
	 */
	public function generate_qualitative_report( $start_date, $end_date, $user_id = null, $format = 'html' ) {
		$analysis = $this->run_qualitative_analysis( $start_date, $end_date, $user_id );

		if ( is_wp_error( $analysis ) ) {
			return $analysis;
		}

		switch ( $format ) {
			case 'json':
				return json_encode( $analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			case 'pdf':
				// Para generar PDF, necesitaríamos una librería como TCPDF o DomPDF
				// Por ahora, devolvemos HTML que podría ser convertido a PDF
				return $this->generate_pdf_report_html( $analysis, $start_date, $end_date, $user_id );
			default:
			case 'html':
				return $this->generate_html_report( $analysis, $start_date, $end_date, $user_id );
		}
	}

	/**
	 * Generar informe HTML
	 */
	private function generate_html_report( $analysis, $start_date, $end_date, $user_id ) {
		$user_info = $user_id ? get_userdata( $user_id ) : null;

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<title>Informe de Análisis Cualitativo - Amsawal</title>
			<style>
				body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
				.header { text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 20px; margin-bottom: 30px; }
				.section { margin-bottom: 25px; }
				h1 { color: #2c3e50; }
				h2 { color: #3498db; border-bottom: 1px solid #ecf0f1; padding-bottom: 5px; }
				.confidence { background: #f8f9fa; padding: 10px; border-left: 4px solid #3498db; margin: 15px 0; }
			</style>
		</head>
		<body>
			<div class="header">
				<h1>Informe de Análisis Cualitativo</h1>
				<p><strong>Periodo:</strong> <?php echo esc_html( $start_date ); ?> - <?php echo esc_html( $end_date ); ?></p>
				<?php if ( $user_info ): ?>
					<p><strong>Usuario:</strong> <?php echo esc_html( $user_info->display_name ); ?> (<?php echo esc_html( $user_info->user_email ); ?>)</p>
				<?php endif; ?>
				<p><strong>Generado:</strong> <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?></p>
			</div>

			<?php if ( isset( $analysis['engagement_assessment'] ) ): ?>
			<div class="section">
				<h2>📊 Evaluación de Compromiso</h2>
				<p><?php echo esc_html( $analysis['engagement_assessment'] ); ?></p>
			</div>
			<?php endif; ?>

			<?php if ( isset( $analysis['learning_patterns'] ) ): ?>
			<div class="section">
				<h2>💡 Patrones de Aprendizaje</h2>
				<p><?php echo esc_html( $analysis['learning_patterns'] ); ?></p>
			</div>
			<?php endif; ?>

			<?php if ( isset( $analysis['strengths'] ) ): ?>
			<div class="section">
				<h2>👍 Fortalezas Identificadas</h2>
				<p><?php echo esc_html( $analysis['strengths'] ); ?></p>
			</div>
			<?php endif; ?>

			<?php if ( isset( $analysis['weaknesses'] ) ): ?>
			<div class="section">
				<h2>⚠️️ Áreas de Mejora</h2>
				<p><?php echo esc_html( $analysis['weaknesses'] ); ?></p>
			</div>
			<?php endif; ?>

			<?php if ( isset( $analysis['pedagogical_recommendations'] ) ): ?>
			<div class="section">
				<h2>🎓 Recomendaciones Pedagógicas</h2>
				<p><?php echo esc_html( $analysis['pedagogical_recommendations'] ); ?></p>
			</div>
			<?php endif; ?>

			<?php if ( isset( $analysis['challenges_identified'] ) ): ?>
			<div class="section">
				<h2>⚠️ Desafíos Detectados</h2>
				<p><?php echo esc_html( $analysis['challenges_identified'] ); ?></p>
			</div>
			<?php endif; ?>

			<div class="confidence">
				<p><strong>Nivel de Confianza del Análisis:</strong> <?php echo isset( $analysis['confidence_level'] ) ? esc_html( $analysis['confidence_level'] ) . '%' : 'No disponible'; ?></p>
				<p>Este análisis fue generado por inteligencia artificial basado en los datos de interacción del usuario con la plataforma Amsawal.</p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generar HTML para reporte PDF
	 */
	private function generate_pdf_report_html( $analysis, $start_date, $end_date, $user_id ) {
		return $this->generate_html_report( $analysis, $start_date, $end_date, $user_id );
	}
}

// Inicializar el sistema de análisis cualitativo
function wp_amsawal_init_qualitative_analysis() {
	WP_Amsawal_Qualitative_Analysis::get_instance();
}
add_action( 'init', 'wp_amsawal_init_qualitative_analysis' );

/**
 * Función auxiliar para ejecutar análisis cualitativo
 */
function wp_amsawal_run_qualitative_analysis( $start_date, $end_date, $user_id = null, $interaction_type = null ) {
	$analysis = WP_Amsawal_Qualitative_Analysis::get_instance();
	return $analysis->run_qualitative_analysis( $start_date, $end_date, $user_id, $interaction_type );
}

/**
 * Función auxiliar para obtener insights de IA
 */
function wp_amsawal_get_ai_insights( $start_date, $end_date, $user_id = null ) {
	$analysis = WP_Amsawal_Qualitative_Analysis::get_instance();
	return $analysis->get_ai_insights( $start_date, $end_date, $user_id );
}

/**
 * Función auxiliar para obtener análisis cualitativo histórico
 */
function wp_amsawal_get_historical_qualitative_analysis( $user_id = null, $limit = 10, $offset = 0 ) {
	$analysis = WP_Amsawal_Qualitative_Analysis::get_instance();
	return $analysis->get_historical_qualitative_analysis( $user_id, $limit, $offset );
}

/**
 * Función auxiliar para generar informe cualitativo
 */
function wp_amsawal_generate_qualitative_report( $start_date, $end_date, $user_id = null, $format = 'html' ) {
	$analysis = WP_Amsawal_Qualitative_Analysis::get_instance();
	return $analysis->generate_qualitative_report( $start_date, $end_date, $user_id, $format );
}
// F15-1: Security - Nonce verification
// Añade esto al inicio de cada wp_ajax_ handler:
// check_ajax_referer('amsawal_nonce', 'nonce');
