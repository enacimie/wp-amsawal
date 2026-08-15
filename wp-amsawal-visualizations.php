<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * wp-amsawal-visualizations.php - Sistema de visualizaciones gráficas
 *
 * Este archivo implementa las visualizaciones gráficas para el panel de análisis
 * de datos del sistema Amsawal, incluyendo gráficos de rendimiento, 
 * compromiso y tendencias de aprendizaje.
 *
 * @package Amsawal
 * @subpackage Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal para visualizaciones gráficas
 */
class WP_Amsawal_Visualizations {

	private static $instance = null;
	private $analytics;
	private $quantitative;
	private $qualitative;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->analytics = WP_Amsawal_Analytics::get_instance();
		$this->quantitative = WP_Amsawal_Quantitative_Analysis::get_instance();
		$this->qualitative = WP_Amsawal_Qualitative_Analysis::get_instance();
		
		// Hooks para visualizaciones
		$this->init_hooks();
	}

	/**
	 * Inicializar hooks para visualizaciones
	 */
	private function init_hooks() {
		// Cargar scripts de visualización
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_visualization_scripts' ) );
		add_action( 'wp_ajax_wp_amsawal_get_chart_data', array( $this, 'ajax_get_chart_data' ) );
		add_action( 'wp_ajax_wp_amsawal_export_chart', array( $this, 'ajax_export_chart' ) );
	}

	/**
	 * Cargar scripts necesarios para visualizaciones
	 */
	public function enqueue_visualization_scripts( $hook ) {
		if ( strpos( $hook, 'wp-amsawal-analytics' ) === false ) {
			return;
		}

		// Cargar Google Charts
		wp_enqueue_script( 
			'google-charts', 
			'https://www.gstatic.com/charts/loader.js', 
			array(), 
			null, 
			true 
		);

		// Cargar Chart.js para alternativas
		wp_enqueue_script(
			'chartjs',
			plugins_url( 'js/chart.min.js', __FILE__ ),
			array(),
			'3.9.1',
			true
		);

		// Scripts de visualización personalizados
		wp_enqueue_script(
			'wp-amsawal-visualizations',
			plugins_url( 'js/visualizations.js', __FILE__ ),
			array( 'jquery', 'google-charts' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'js/visualizations.js' ),
			true
		);

		// Estilos de visualización
		wp_enqueue_style(
			'wp-amsawal-visualizations',
			plugins_url( 'css/visualizations.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'css/visualizations.css' )
		);

		// Localizar datos para JavaScript
		wp_localize_script( 'wp-amsawal-visualizations', 'wpAmsawalViz', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wp_amsawal_visualizations_nonce' ),
			'i18n' => array(
				'loading' => __( 'Cargando gráfico...', 'amsawal' ),
				'error' => __( 'Error al cargar el gráfico', 'amsawal' ),
				'noData' => __( 'No hay datos disponibles para mostrar', 'amsawal' ),
			)
		) );
	}

	/**
	 * Obtener datos para gráficos
	 */
	public function get_chart_data( $chart_type, $start_date, $end_date, $user_id = null, $options = array() ) {
		switch ( $chart_type ) {
			case 'activity_trend':
				return $this->get_activity_trend_data( $start_date, $end_date, $user_id );
			case 'engagement_over_time':
				return $this->get_engagement_over_time_data( $start_date, $end_date, $user_id );
			case 'performance_comparison':
				return $this->get_performance_comparison_data( $start_date, $end_date, $user_id );
			case 'content_popularity':
				return $this->get_content_popularity_data( $start_date, $end_date, $user_id );
			case 'learning_paths':
				return $this->get_learning_paths_data( $start_date, $end_date, $user_id );
			case 'user_retention':
				return $this->get_user_retention_data( $start_date, $end_date, $user_id );
			case 'ai_insights_sentiment':
				return $this->get_ai_insights_sentiment_data( $start_date, $end_date, $user_id );
			default:
				return new WP_Error( 'invalid_chart_type', 'Tipo de gráfico no válido' );
		}
	}

	/**
	 * Obtener datos de tendencia de actividad
	 */
	private function get_activity_trend_data( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					DATE(timestamp) as date,
					COUNT(*) as total_interactions,
					COUNT(DISTINCT user_id) as unique_users,
					COUNT(CASE WHEN score IS NOT NULL THEN 1 END) as scored_activities,
					AVG(CASE WHEN score IS NOT NULL AND max_score > 0 THEN (score / max_score) * 100 ELSE NULL END) as avg_accuracy
				  FROM {$this->analytics->interaction_table_name}
				  {$where_clause}
				  GROUP BY DATE(timestamp)
				  ORDER BY date ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$chart_data = array(
			'cols' => array(
				array( 'id' => 'date', 'label' => 'Fecha', 'type' => 'string' ),
				array( 'id' => 'interactions', 'label' => 'Interacciones', 'type' => 'number' ),
				array( 'id' => 'users', 'label' => 'Usuarios Únicos', 'type' => 'number' ),
				array( 'id' => 'accuracy', 'label' => 'Precisión (%)', 'type' => 'number' ),
			),
			'rows' => array()
		);

		foreach ( $results as $result ) {
			$chart_data['rows'][] = array(
				'c' => array(
					array( 'v' => $result->date ),
					array( 'v' => (int) $result->total_interactions ),
					array( 'v' => (int) $result->unique_users ),
					array( 'v' => $result->avg_accuracy ? round( (float) $result->avg_accuracy, 2 ) : 0 ),
				)
			);
		}

		return $chart_data;
	}

	/**
	 * Obtener datos de compromiso a lo largo del tiempo
	 */
	private function get_engagement_over_time_data( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					DATE(timestamp) as date,
					COUNT(DISTINCT user_id) as daily_active_users,
					COUNT(*) as total_interactions,
					COUNT(DISTINCT content_id) as unique_content_interacted,
					AVG(duration) as avg_session_duration
				  FROM {$this->analytics->interaction_table_name}
				  {$where_clause}
				  GROUP BY DATE(timestamp)
				  ORDER BY date ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$chart_data = array(
			'cols' => array(
				array( 'id' => 'date', 'label' => 'Fecha', 'type' => 'string' ),
				array( 'id' => 'active_users', 'label' => 'Usuarios Activos', 'type' => 'number' ),
				array( 'id' => 'interactions', 'label' => 'Interacciones', 'type' => 'number' ),
				array( 'id' => 'content_interacted', 'label' => 'Contenido Interactuado', 'type' => 'number' ),
				array( 'id' => 'session_duration', 'label' => 'Duración Promedio (min)', 'type' => 'number' ),
			),
			'rows' => array()
		);

		foreach ( $results as $result ) {
			$chart_data['rows'][] = array(
				'c' => array(
					array( 'v' => $result->date ),
					array( 'v' => (int) $result->daily_active_users ),
					array( 'v' => (int) $result->total_interactions ),
					array( 'v' => (int) $result->unique_content_interacted ),
					array( 'v' => $result->avg_session_duration ? round( (float) $result->avg_session_duration / 60, 2 ) : 0 ), // Convertir a minutos
				)
			);
		}

		return $chart_data;
	}

	/**
	 * Obtener datos de comparación de rendimiento
	 */
	private function get_performance_comparison_data( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		$where_clause = "WHERE timestamp BETWEEN %s AND %s AND score IS NOT NULL AND max_score > 0";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					interaction_type,
					COUNT(*) as attempts,
					AVG((score / max_score) * 100) as avg_score,
					MIN((score / max_score) * 100) as min_score,
					MAX((score / max_score) * 100) as max_score,
					STDDEV((score / max_score) * 100) as std_deviation
				  FROM {$this->analytics->interaction_table_name}
				  {$where_clause}
				  GROUP BY interaction_type
				  ORDER BY avg_score DESC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$chart_data = array(
			'cols' => array(
				array( 'id' => 'type', 'label' => 'Tipo de Actividad', 'type' => 'string' ),
				array( 'id' => 'avg_score', 'label' => 'Promedio', 'type' => 'number' ),
				array( 'id' => 'min_score', 'label' => 'Mínimo', 'type' => 'number' ),
				array( 'id' => 'max_score', 'label' => 'Máximo', 'type' => 'number' ),
			),
			'rows' => array()
		);

		foreach ( $results as $result ) {
			$chart_data['rows'][] = array(
				'c' => array(
					array( 'v' => $result->interaction_type ),
					array( 'v' => round( (float) $result->avg_score, 2 ) ),
					array( 'v' => round( (float) $result->min_score, 2 ) ),
					array( 'v' => round( (float) $result->max_score, 2 ) ),
				)
			);
		}

		return $chart_data;
	}

	/**
	 * Obtener datos de popularidad de contenido
	 */
	private function get_content_popularity_data( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					content_type,
					content_id,
					COUNT(*) as interaction_count,
					AVG(CASE WHEN score IS NOT NULL AND max_score > 0 THEN (score / max_score) * 100 ELSE NULL END) as avg_accuracy,
					COUNT(DISTINCT user_id) as unique_users
				  FROM {$this->analytics->interaction_table_name}
				  {$where_clause}
				  GROUP BY content_type, content_id
				  ORDER BY interaction_count DESC
				  LIMIT 20";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$chart_data = array(
			'cols' => array(
				array( 'id' => 'content', 'label' => 'Contenido', 'type' => 'string' ),
				array( 'id' => 'interactions', 'label' => 'Interacciones', 'type' => 'number' ),
				array( 'id' => 'accuracy', 'label' => 'Precisión (%)', 'type' => 'number' ),
				array( 'id' => 'users', 'label' => 'Usuarios', 'type' => 'number' ),
			),
			'rows' => array()
		);

		foreach ( $results as $result ) {
			$content_label = $result->content_type . ' #' . $result->content_id;
			$chart_data['rows'][] = array(
				'c' => array(
					array( 'v' => $content_label ),
					array( 'v' => (int) $result->interaction_count ),
					array( 'v' => $result->avg_accuracy ? round( (float) $result->avg_accuracy, 2 ) : 0 ),
					array( 'v' => (int) $result->unique_users ),
				)
			);
		}

		return $chart_data;
	}

	/**
	 * Obtener datos de caminos de aprendizaje
	 */
	private function get_learning_paths_data( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					DATE(timestamp) as date,
					COUNT(CASE WHEN action = 'start' THEN 1 END) as lessons_started,
					COUNT(CASE WHEN action = 'complete' THEN 1 END) as lessons_completed,
					COUNT(CASE WHEN action = 'test_passed' THEN 1 END) as tests_passed,
					AVG(CASE WHEN action = 'complete' AND duration IS NOT NULL THEN duration ELSE NULL END) as avg_lesson_time
				  FROM {$this->analytics->interaction_table_name}
				  {$where_clause}
				  GROUP BY DATE(timestamp)
				  ORDER BY date ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$chart_data = array(
			'cols' => array(
				array( 'id' => 'date', 'label' => 'Fecha', 'type' => 'string' ),
				array( 'id' => 'started', 'label' => 'Lecciones Iniciadas', 'type' => 'number' ),
				array( 'id' => 'completed', 'label' => 'Lecciones Completadas', 'type' => 'number' ),
				array( 'id' => 'passed', 'label' => 'Tests Aprobados', 'type' => 'number' ),
				array( 'id' => 'time', 'label' => 'Tiempo Promedio (min)', 'type' => 'number' ),
			),
			'rows' => array()
		);

		foreach ( $results as $result ) {
			$chart_data['rows'][] = array(
				'c' => array(
					array( 'v' => $result->date ),
					array( 'v' => (int) $result->lessons_started ),
					array( 'v' => (int) $result->lessons_completed ),
					array( 'v' => (int) $result->tests_passed ),
					array( 'v' => $result->avg_lesson_time ? round( (float) $result->avg_lesson_time / 60, 2 ) : 0 ), // Convertir a minutos
				)
			);
		}

		return $chart_data;
	}

	/**
	 * Obtener datos de retención de usuarios
	 */
	private function get_user_retention_data( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		// Para simplificar, vamos a calcular la retención semanal
		$where_clause = "WHERE timestamp BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		// Simple approach: calculate weekly active users
		$query = "SELECT 
					YEARWEEK(timestamp) as week,
					COUNT(DISTINCT user_id) as active_users
				  FROM {$this->analytics->interaction_table_name}
				  {$where_clause}
				  GROUP BY YEARWEEK(timestamp)
				  ORDER BY week ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$chart_data = array(
			'cols' => array(
				array( 'id' => 'week', 'label' => 'Semana', 'type' => 'string' ),
				array( 'id' => 'active_users', 'label' => 'Usuarios Activos', 'type' => 'number' ),
			),
			'rows' => array()
		);

		foreach ( $results as $result ) {
			$chart_data['rows'][] = array(
				'c' => array(
					array( 'v' => $result->week ),
					array( 'v' => (int) $result->active_users ),
				)
			);
		}

		return $chart_data;
	}

	/**
	 * Obtener datos de sentimientos de insights de IA
	 */
	private function get_ai_insights_sentiment_data( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		// Esta función requiere acceso a la tabla de análisis cualitativo
		$qualitative_table = $wpdb->prefix . 'amsawal_qualitative_analysis';
		
		$where_clause = "WHERE created_at BETWEEN %s AND %s";
		$params = array( $start_date, $end_date );

		if ( $user_id ) {
			$where_clause .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$query = "SELECT 
					DATE(created_at) as date,
					COUNT(*) as analysis_count,
					AVG(confidence_level) as avg_confidence
				  FROM {$qualitative_table}
				  {$where_clause}
				  GROUP BY DATE(created_at)
				  ORDER BY date ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		$chart_data = array(
			'cols' => array(
				array( 'id' => 'date', 'label' => 'Fecha', 'type' => 'string' ),
				array( 'id' => 'analysis', 'label' => 'Análisis Realizados', 'type' => 'number' ),
				array( 'id' => 'confidence', 'label' => 'Confianza Promedio (%)', 'type' => 'number' ),
			),
			'rows' => array()
		);

		foreach ( $results as $result ) {
			$chart_data['rows'][] = array(
				'c' => array(
					array( 'v' => $result->date ),
					array( 'v' => (int) $result->analysis_count ),
					array( 'v' => round( (float) $result->avg_confidence, 2 ) ),
				)
			);
		}

		return $chart_data;
	}

	/**
	 * AJAX: Obtener datos para gráficos
	 */
	public function ajax_get_chart_data() {
		check_ajax_referer( 'wp_amsawal_visualizations_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permiso para esta acción' ) );
		}

		$chart_type = sanitize_text_field( $_POST['chart_type'] );
		$start_date = sanitize_text_field( $_POST['start_date'] );
		$end_date = sanitize_text_field( $_POST['end_date'] );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		// Validar fechas
		if ( ! strtotime( $start_date ) || ! strtotime( $end_date ) ) {
			wp_send_json_error( array( 'message' => 'Fechas inválidas' ) );
		}

		$data = $this->get_chart_data( $chart_type, $start_date, $end_date, $user_id );

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( array( 'message' => $data->get_error_message() ) );
		}

		wp_send_json_success( array( 'data' => $data ) );
	}

	/**
	 * AJAX: Exportar gráfico
	 */
	public function ajax_export_chart() {
		check_ajax_referer( 'wp_amsawal_visualizations_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para esta acción' );
		}

		$chart_type = sanitize_text_field( $_POST['chart_type'] );
		$start_date = sanitize_text_field( $_POST['start_date'] );
		$end_date = sanitize_text_field( $_POST['end_date'] );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$format = sanitize_text_field( $_POST['format'] ?? 'png' );

		// Validar formato
		if ( ! in_array( $format, array( 'png', 'jpg', 'pdf', 'svg' ) ) ) {
			wp_die( 'Formato no soportado' );
		}

		// Obtener datos
		$data = $this->get_chart_data( $chart_type, $start_date, $end_date, $user_id );

		if ( is_wp_error( $data ) ) {
			wp_die( $data->get_error_message() );
		}

		// Generar el gráfico según el formato
		$this->generate_chart_export( $data, $chart_type, $format );
	}

	/**
	 * Generar exportación de gráfico
	 */
	private function generate_chart_export( $chart_data, $chart_type, $format ) {
		$chart_name = $this->get_chart_name( $chart_type );
		$filename = 'amsawal-' . sanitize_key( $chart_type ) . '-' . date( 'Y-m-d' ) . '.' . sanitize_key( $format );

		if ( $format === 'csv' ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			$out = fopen( 'php://output', 'w' );
			if ( ! empty( $chart_data ) ) {
				fputcsv( $out, array_keys( (array) $chart_data[0] ) );
				foreach ( $chart_data as $row ) {
					fputcsv( $out, (array) $row );
				}
			}
			fclose( $out );
		} elseif ( $format === 'json' ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			echo wp_json_encode( $chart_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		} else {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: format name */
					__( 'Formato "%s" no soportado. Usa csv o json.', 'wp-amsawal' ),
					esc_html( $format )
				),
			) );
		}
	}

	/**
	 * Obtener nombre descriptivo del tipo de gráfico
	 */
	private function get_chart_name( $chart_type ) {
		$names = array(
			'activity_trend' => 'Tendencia de Actividad',
			'engagement_over_time' => 'Compromiso a lo Largo del Tiempo',
			'performance_comparison' => 'Comparación de Rendimiento',
			'content_popularity' => 'Popularidad de Contenido',
			'learning_paths' => 'Caminos de Aprendizaje',
			'user_retention' => 'Retención de Usuarios',
			'ai_insights_sentiment' => 'Sentimiento de Insights de IA'
		);

		return $names[ $chart_type ] ?? $chart_type;
	}

	/**
	 * Renderizar contenedor de gráfico
	 */
	public function render_chart_container( $chart_type, $start_date, $end_date, $user_id = null, $title = '' ) {
		$chart_id = 'chart-' . uniqid();
		
		?>
		<div class="duo-chart-container">
			<div class="duo-chart-header">
				<h4 class="duo-chart-title"><?php echo esc_html( $title ?: $this->get_chart_name( $chart_type ) ); ?></h4>
				<div class="duo-chart-actions">
					<button class="duo-chart-export-btn" data-chart-id="<?php echo esc_attr( $chart_id ); ?>" data-chart-type="<?php echo esc_attr( $chart_type ); ?>">
						⬇️ Exportar
					</button>
				</div>
			</div>
			<div id="<?php echo esc_attr( $chart_id ); ?>" class="duo-chart" 
				 data-chart-type="<?php echo esc_attr( $chart_type ); ?>"
				 data-start-date="<?php echo esc_attr( $start_date ); ?>"
				 data-end-date="<?php echo esc_attr( $end_date ); ?>"
				 data-user-id="<?php echo absint( $user_id ); ?>"
				 style="height: 400px; width: 100%;">
				<div class="duo-chart-loading" style="display: flex; align-items: center; justify-content: center; height: 100%;">
					<span class="duo-chart-spinner"></span>
					<span>Cargando gráfico...</span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renderizar panel de control de visualizaciones
	 */
	public function render_dashboard( $start_date, $end_date, $user_id = null ) {
		?>
		<div class="duo-analytics-dashboard">
			<div class="duo-dashboard-grid">
				<div class="duo-dashboard-card">
					<?php $this->render_chart_container( 'activity_trend', $start_date, $end_date, $user_id, 'Tendencia de Actividad' ); ?>
				</div>
				
				<div class="duo-dashboard-card">
					<?php $this->render_chart_container( 'engagement_over_time', $start_date, $end_date, $user_id, 'Compromiso a lo Largo del Tiempo' ); ?>
				</div>
				
				<div class="duo-dashboard-card duo-wide">
					<?php $this->render_chart_container( 'performance_comparison', $start_date, $end_date, $user_id, 'Comparación de Rendimiento' ); ?>
				</div>
				
				<div class="duo-dashboard-card">
					<?php $this->render_chart_container( 'content_popularity', $start_date, $end_date, $user_id, 'Contenido Más Popular' ); ?>
				</div>
				
				<div class="duo-dashboard-card">
					<?php $this->render_chart_container( 'learning_paths', $start_date, $end_date, $user_id, 'Progreso en Caminos de Aprendizaje' ); ?>
				</div>
				
				<div class="duo-dashboard-card">
					<?php $this->render_chart_container( 'user_retention', $start_date, $end_date, $user_id, 'Retención de Usuarios' ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

// Inicializar el sistema de visualizaciones
function wp_amsawal_init_visualizations() {
	WP_Amsawal_Visualizations::get_instance();
}
add_action( 'init', 'wp_amsawal_init_visualizations' );

/**
 * Función auxiliar para renderizar gráficos
 */
function wp_amsawal_render_chart( $chart_type, $start_date, $end_date, $user_id = null, $title = '' ) {
	$visualizations = WP_Amsawal_Visualizations::get_instance();
	$visualizations->render_chart_container( $chart_type, $start_date, $end_date, $user_id, $title );
}

/**
 * Función auxiliar para renderizar panel de control
 */
function wp_amsawal_render_analytics_dashboard( $start_date, $end_date, $user_id = null ) {
	$visualizations = WP_Amsawal_Visualizations::get_instance();
	$visualizations->render_dashboard( $start_date, $end_date, $user_id );
}