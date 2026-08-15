<?php
/**
 * F14-1: Real-time Analytics Dashboard
 * Displays live user engagement metrics
 */

if (!defined('ABSPATH')) exit;

/**
 * F14-3: Data export functionality (CSV download).
 * Requires nonce verification to prevent CSRF.
 */
add_action('admin_init', function() {
    if (!isset($_GET['amsawal_export'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Acceso denegado', 'wp-amsawal'));
    }

    check_admin_referer('amsawal_analytics_export', 'amsawal_export_nonce');

    $export_type = sanitize_text_field($_GET['amsawal_export']);

    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_user_interactions';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="amsawal-analytics-' . sanitize_key($export_type) . '-' . current_time('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['ID', 'User ID', 'Interaction Type', 'Subtype', 'Action', 'Result Data', 'Created At']);

    $limit = 1000;
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, user_id, interaction_type, interaction_subtype, action, result_data, created_at FROM %i ORDER BY created_at DESC LIMIT %d",
        $table,
        $limit
    ));

    if ($rows) {
        foreach ($rows as $row) {
            fputcsv($output, [
                $row->id,
                $row->user_id,
                $row->interaction_type,
                $row->interaction_subtype,
                $row->action,
                $row->result_data,
                $row->created_at
            ]);
        }
    }

    fclose($output);
    exit;
});

/**
 * Inline styles for the analytics admin page.
 * Uses admin_enqueue_scripts instead of admin_head echo.
 */
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'amsawal_page_amsawal-analytics') {
        return;
    }
    wp_add_inline_style('common', '
        .amsawal-analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .amsawal-analytics-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .amsawal-analytics-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #646970;
        }
        .amsawal-analytics-value {
            font-size: 36px;
            font-weight: 700;
            color: #2c5f8d;
            margin: 0;
        }
        .amsawal-analytics-label {
            font-size: 12px;
            color: #646970;
        }
        .amsawal-analytics-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .amsawal-chart {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 200px;
            padding: 20px 0;
        }
        .amsawal-chart-bar {
            flex: 1;
            background: linear-gradient(180deg, #3498db 0%, #2c5f8d 100%);
            border-radius: 4px 4px 0 0;
            position: relative;
            min-height: 20px;
            transition: opacity 0.2s;
        }
        .amsawal-chart-bar:hover {
            opacity: 0.8;
        }
        .amsawal-chart-bar span {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: #646970;
        }
        .amsawal-chart-bar strong {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #2c5f8d;
        }
        .amsawal-export-btn {
            margin-left: 10px;
        }
    ');
});

/**
 * Main analytics dashboard renderer.
 */
function amsawal_analytics_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Acceso denegado', 'wp-amsawal'));
    }

    global $wpdb;
    $today = current_time('Y-m-d');

    $active_users = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}amsawal_user_interactions
         WHERE DATE(created_at) = %s",
        $today
    ));

    $total_interactions = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}amsawal_user_interactions
         WHERE DATE(created_at) = %s",
        $today
    ));

    $lessons_completed = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}amsawal_user_interactions
         WHERE interaction_type = 'lesson_complete' AND DATE(created_at) = %s",
        $today
    ));

    $quizzes_taken = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}amsawal_user_interactions
         WHERE interaction_type = 'quiz_complete' AND DATE(created_at) = %s",
        $today
    ));

    $top_lessons = $wpdb->get_results($wpdb->prepare(
        "SELECT JSON_EXTRACT(result_data, '$.content_id') as lesson_id, COUNT(*) as count
         FROM {$wpdb->prefix}amsawal_user_interactions
         WHERE interaction_type = 'lesson_start'
         GROUP BY JSON_EXTRACT(result_data, '$.content_id')
         ORDER BY count DESC
         LIMIT 5"
    ));

    $hourly_engagement = $wpdb->get_results($wpdb->prepare(
        "SELECT HOUR(created_at) as hour, COUNT(*) as count
         FROM {$wpdb->prefix}amsawal_user_interactions
         WHERE DATE(created_at) = %s
         GROUP BY HOUR(created_at)
         ORDER BY hour",
        $today
    ));

    $export_nonce = wp_create_nonce('amsawal_analytics_export');
    $max_hourly = 0;
    foreach ($hourly_engagement as $hd) {
        if ($hd->count > $max_hourly) {
            $max_hourly = $hd->count;
        }
    }
    ?>
    <div class="wrap">
        <h1>Analytics Dashboard - Tiempo Real
            <a href="<?php echo esc_url(add_query_arg([
                'amsawal_export' => 'interactions',
                'amsawal_export_nonce' => $export_nonce,
            ], admin_url('admin.php'))); ?>" class="button amsawal-export-btn">
                <?php esc_html_e('Exportar CSV', 'wp-amsawal'); ?>
            </a>
        </h1>

        <div class="amsawal-analytics-grid">
            <div class="amsawal-analytics-card">
                <h3><?php esc_html_e('Usuarios Activos Hoy', 'wp-amsawal'); ?></h3>
                <div class="amsawal-analytics-value"><?php echo esc_html(number_format($active_users)); ?></div>
                <div class="amsawal-analytics-label"><?php esc_html_e('Últimas 24 horas', 'wp-amsawal'); ?></div>
            </div>

            <div class="amsawal-analytics-card">
                <h3><?php esc_html_e('Interacciones Totales', 'wp-amsawal'); ?></h3>
                <div class="amsawal-analytics-value"><?php echo esc_html(number_format($total_interactions)); ?></div>
                <div class="amsawal-analytics-label"><?php esc_html_e('Eventos registrados', 'wp-amsawal'); ?></div>
            </div>

            <div class="amsawal-analytics-card">
                <h3><?php esc_html_e('Lecciones Completadas', 'wp-amsawal'); ?></h3>
                <div class="amsawal-analytics-value"><?php echo esc_html(number_format($lessons_completed)); ?></div>
                <div class="amsawal-analytics-label"><?php esc_html_e('Hoy', 'wp-amsawal'); ?></div>
            </div>

            <div class="amsawal-analytics-card">
                <h3><?php esc_html_e('Quizzes Realizados', 'wp-amsawal'); ?></h3>
                <div class="amsawal-analytics-value"><?php echo esc_html(number_format($quizzes_taken)); ?></div>
                <div class="amsawal-analytics-label"><?php esc_html_e('Hoy', 'wp-amsawal'); ?></div>
            </div>
        </div>

        <div class="amsawal-analytics-section">
            <h2><?php esc_html_e('Top 5 Lecciones Más Populares', 'wp-amsawal'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Lección ID', 'wp-amsawal'); ?></th>
                        <th><?php esc_html_e('Veces Iniciada', 'wp-amsawal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_lessons as $lesson) : ?>
                    <tr>
                        <td><?php echo esc_html($lesson->lesson_id); ?></td>
                        <td><?php echo esc_html(number_format($lesson->count)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="amsawal-analytics-section">
            <h2><?php esc_html_e('Engagement por Hora', 'wp-amsawal'); ?></h2>
            <div class="amsawal-chart">
                <?php foreach ($hourly_engagement as $hour_data) :
                    $pct = $max_hourly > 0 ? ($hour_data->count / $max_hourly) * 100 : 0;
                ?>
                <div class="amsawal-chart-bar" style="height: <?php echo esc_attr($pct); ?>%">
                    <span><?php echo esc_html($hour_data->hour); ?>:00</span>
                    <strong><?php echo esc_html($hour_data->count); ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

add_action('admin_menu', function() {
    add_submenu_page(
        'wp-amsawal-admin',
        'Analytics Dashboard',
        'Analytics',
        'manage_options',
        'amsawal-analytics',
        'amsawal_analytics_dashboard'
    );
});

/**
 * F14-4: User retention analysis.
 */
function amsawal_get_retention_stats($days = 30) {
    global $wpdb;

    $table = $wpdb->prefix . 'amsawal_user_interactions';

    $total_users = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->users}
         WHERE DATE(user_registered) >= DATE_SUB(%s, INTERVAL %d DAY)",
        current_time('Y-m-d'),
        $days
    ));

    $active_7d = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM $table
         WHERE created_at >= DATE_SUB(%s, INTERVAL 7 DAY)",
        current_time('Y-m-d')
    ));

    $active_30d = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM $table
         WHERE created_at >= DATE_SUB(%s, INTERVAL 30 DAY)",
        current_time('Y-m-d')
    ));

    return [
        'total_users'  => $total_users,
        'active_7d'    => $active_7d,
        'active_30d'   => $active_30d,
        'retention_7d'  => $total_users > 0 ? round(($active_7d / $total_users) * 100, 2) : 0,
        'retention_30d' => $total_users > 0 ? round(($active_30d / $total_users) * 100, 2) : 0,
    ];
}

/**
 * F14-5: User engagement scoring (0-100).
 */
function amsawal_get_user_engagement_level($user_id) {
    if (function_exists('gamipress_get_user_rank_id')) {
        $rank_id = gamipress_get_user_rank_id($user_id, 'nivel');
        if ($rank_id) {
            $priority = get_post_meta($rank_id, '_gamipress_priority', true);
            if ($priority) return (int) $priority;
        }
    }
    return 1;
}

function amsawal_calculate_engagement_score($user_id) {
    global $wpdb;

    $table = $wpdb->prefix . 'amsawal_user_interactions';

    $activity = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table
         WHERE user_id = %d AND created_at >= DATE_SUB(%s, INTERVAL 30 DAY)",
        $user_id,
        current_time('Y-m-d')
    ));

    $streak       = (int) (get_user_meta($user_id, '_wp_amsawal_streak_days', true) ?: 0);
    $level        = amsawal_get_user_engagement_level($user_id);
    $achievements = function_exists( 'amsawal_get_user_earned_achievement_ids' )
        ? (array) amsawal_get_user_earned_achievement_ids( $user_id )
        : (array) ( get_user_meta( $user_id, 'amsawal_achievements', true ) ?: array() );

    $score  = 0;
    $score += min($activity * 2, 40);
    $score += min($streak * 3, 30);
    $score += min($level * 2, 20);
    $score += min(count($achievements) * 5, 10);

    return min($score, 100);
}

/**
 * Display engagement score on user profile.
 */
add_action('show_user_profile', function($user) {
    $score = amsawal_calculate_engagement_score($user->ID);
    $pct   = esc_attr($score);
    ?>
    <h3><?php esc_html_e('Engagement Score', 'wp-amsawal'); ?></h3>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Score', 'wp-amsawal'); ?></th>
            <td>
                <div style="background: #e5e5e5; border-radius: 10px; overflow: hidden; width: 200px;">
                    <div style="background: linear-gradient(90deg, #27ae60 0%, #2c5f8d 100%); width: <?php echo intval($pct); ?>%; padding: 8px; color: #fff; text-align: center; font-weight: bold;">
                        <?php echo esc_html($score); ?>/100
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <?php
});

/**
 * F15-4: Capability check helpers.
 */
function amsawal_check_admin_capability() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Acceso denegado', 'wp-amsawal')]);
    }
}

function amsawal_check_editor_capability() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Acceso denegado', 'wp-amsawal')]);
    }
}
