#!/usr/bin/env python3
"""Fase 14: Analytics Dashboard - Panel de análisis en tiempo real"""

def apply_f14_1_realtime_dashboard():
    """F14-1: Real-time analytics dashboard"""
    dashboard_php = """<?php
/**
 * F14-1: Real-time Analytics Dashboard
 * Displays live user engagement metrics
 */

if (!defined('ABSPATH')) exit;

function amsawal_analytics_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('Acceso denegado');
    }
    
    global $wpdb;
    
    // Get today's stats
    $today = current_time('Y-m-d');
    
    $active_users = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE DATE(created_at) = %s",
        $today
    ));
    
    $total_interactions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE DATE(created_at) = %s",
        $today
    ));
    
    $lessons_completed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE event_type = 'lesson_complete' AND DATE(created_at) = %s",
        $today
    ));
    
    $quizzes_taken = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE event_type = 'quiz_complete' AND DATE(created_at) = %s",
        $today
    ));
    
    // Get top lessons
    $top_lessons = $wpdb->get_results($wpdb->prepare(
        "SELECT lesson_id, COUNT(*) as count 
         FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE event_type = 'lesson_start' 
         GROUP BY lesson_id 
         ORDER BY count DESC 
         LIMIT 5"
    ));
    
    // Get engagement by hour
    $hourly_engagement = $wpdb->get_results($wpdb->prepare(
        "SELECT HOUR(created_at) as hour, COUNT(*) as count 
         FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE DATE(created_at) = %s
         GROUP BY HOUR(created_at)
         ORDER BY hour",
        $today
    ));
    
    ?>
    <div class="wrap">
        <h1> Analytics Dashboard - Tiempo Real</h1>
        
        <div class="amsawal-analytics-grid">
            <div class="amsawal-analytics-card">
                <h3>Usuarios Activos Hoy</h3>
                <div class="amsawal-analytics-value"><?php echo number_format($active_users); ?></div>
                <div class="amsawal-analytics-label">Últimas 24 horas</div>
            </div>
            
            <div class="amsawal-analytics-card">
                <h3>Interacciones Totales</h3>
                <div class="amsawal-analytics-value"><?php echo number_format($total_interactions); ?></div>
                <div class="amsawal-analytics-label">Eventos registrados</div>
            </div>
            
            <div class="amsawal-analytics-card">
                <h3>Lecciones Completadas</h3>
                <div class="amsawal-analytics-value"><?php echo number_format($lessons_completed); ?></div>
                <div class="amsawal-analytics-label">Hoy</div>
            </div>
            
            <div class="amsawal-analytics-card">
                <h3>Quizzes Realizados</h3>
                <div class="amsawal-analytics-value"><?php echo number_format($quizzes_taken); ?></div>
                <div class="amsawal-analytics-label">Hoy</div>
            </div>
        </div>
        
        <div class="amsawal-analytics-section">
            <h2>Top 5 Lecciones Más Populares</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Lección ID</th>
                        <th>Veces Iniciada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_lessons as $lesson): ?>
                    <tr>
                        <td><?php echo esc_html($lesson->lesson_id); ?></td>
                        <td><?php echo number_format($lesson->count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="amsawal-analytics-section">
            <h2>Engagement por Hora</h2>
            <div class="amsawal-chart">
                <?php foreach ($hourly_engagement as $hour_data): ?>
                <div class="amsawal-chart-bar" style="height: <?php echo ($hour_data->count / max(array_column($hourly_engagement, 'count'))) * 100; ?>%">
                    <span><?php echo $hour_data->hour; ?>:00</span>
                    <strong><?php echo $hour_data->count; ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <style>
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
            margin: 10px 0;
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
    </style>
    <?php
}

// Register admin page
add_action('admin_menu', function() {
    add_submenu_page(
        'amsawal',
        'Analytics Dashboard',
        '📊 Analytics',
        'manage_options',
        'amsawal-analytics',
        'amsawal_analytics_dashboard'
    );
});
"""
    
    with open('wp-amsawal-analytics.php', 'w', encoding='utf-8') as f:
        f.write(dashboard_php)
    print("✅ F14-1: Real-time analytics dashboard created")
    return True

def apply_f14_2_user_behavior_tracking():
    """F14-2: Enhanced user behavior tracking"""
    with open('wp-amsawal-data-collection.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    tracking_code = """
// F14-2: Enhanced user behavior tracking
function amsawal_track_behavior($event_type, $user_id, $metadata = []) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    $data = [
        'user_id' => $user_id,
        'event_type' => $event_type,
        'lesson_id' => $metadata['lesson_id'] ?? null,
        'h5p_id' => $metadata['h5p_id'] ?? null,
        'metadata' => json_encode($metadata),
        'created_at' => current_time('mysql')
    ];
    
    $wpdb->insert($table, $data);
    
    // Trigger action for extensibility
    do_action('amsawal_behavior_tracked', $event_type, $user_id, $metadata);
    
    return $wpdb->insert_id;
}

// Track specific behaviors
add_action('amsawal_lesson_start', function($user_id, $lesson_id) {
    amsawal_track_behavior('lesson_start', $user_id, ['lesson_id' => $lesson_id]);
}, 10, 2);

add_action('amsawal_lesson_complete', function($user_id, $lesson_id) {
    amsawal_track_behavior('lesson_complete', $user_id, ['lesson_id' => $lesson_id]);
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

add_action('amsawal_streak_update', function($user_id, $streak_count) {
    amsawal_track_behavior('streak_update', $user_id, ['streak' => $streak_count]);
}, 10, 2);

add_action('amsawal_level_up', function($user_id, $new_level) {
    amsawal_track_behavior('level_up', $user_id, ['level' => $new_level]);
}, 10, 2);

add_action('amsawal_achievement_unlocked', function($user_id, $achievement_id) {
    amsawal_track_behavior('achievement_unlocked', $user_id, ['achievement_id' => $achievement_id]);
}, 10, 2);
"""
    
    if 'amsawal_track_behavior' not in php:
        php += tracking_code
    
    with open('wp-amsawal-data-collection.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F14-2: Enhanced user behavior tracking added")
    return True

def apply_f14_3_export_functionality():
    """F14-3: Data export functionality"""
    with open('wp-amsawal-analytics.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    export_code = """
// F14-3: Data export functionality
add_action('admin_init', function() {
    if (isset($_GET['amsawal_export']) && current_user_can('manage_options')) {
        $export_type = sanitize_text_field($_GET['amsawal_export']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'amsawal_user_interactions';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="amsawal-analytics-' . $export_type . '-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['ID', 'User ID', 'Event Type', 'Lesson ID', 'H5P ID', 'Metadata', 'Created At']);
        
        // Get data
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 1000");
        
        foreach ($rows as $row) {
            fputcsv($output, [
                $row->id,
                $row->user_id,
                $row->event_type,
                $row->lesson_id,
                $row->h5p_id,
                $row->metadata,
                $row->created_at
            ]);
        }
        
        fclose($output);
        exit;
    }
});

// Add export buttons to dashboard
add_action('admin_head', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'amsawal-analytics') {
        echo '<style>
            .amsawal-export-btn {
                margin-left: 10px;
            }
        </style>';
    }
});
"""
    
    if 'F14-3: Data export' not in php:
        if '?>' in php:
            php = php.replace('?>', export_code + '?>')
        else:
            php = php + export_code
    
    with open('wp-amsawal-analytics.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F14-3: Data export functionality added")
    return True

def apply_f14_4_retention_analysis():
    """F14-4: User retention analysis"""
    with open('wp-amsawal-analytics.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    retention_code = """
// F14-4: User retention analysis
function amsawal_get_retention_stats($days = 30) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    // Get users who registered in the last $days days
    $total_users = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->users} 
         WHERE DATE(user_registered) >= DATE_SUB(%s, INTERVAL %d DAY)",
        current_time('Y-m-d'),
        $days
    ));
    
    // Get users who were active in the last 7 days
    $active_7d = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM $table 
         WHERE created_at >= DATE_SUB(%s, INTERVAL 7 DAY)",
        current_time('Y-m-d')
    ));
    
    // Get users who were active in the last 30 days
    $active_30d = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM $table 
         WHERE created_at >= DATE_SUB(%s, INTERVAL 30 DAY)",
        current_time('Y-m-d')
    ));
    
    return [
        'total_users' => $total_users,
        'active_7d' => $active_7d,
        'active_30d' => $active_30d,
        'retention_7d' => $total_users > 0 ? round(($active_7d / $total_users) * 100, 2) : 0,
        'retention_30d' => $total_users > 0 ? round(($active_30d / $total_users) * 100, 2) : 0,
    ];
}
"""
    
    if 'amsawal_get_retention_stats' not in php:
        php += retention_code
    
    with open('wp-amsawal-analytics.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F14-4: User retention analysis added")
    return True

def apply_f14_5_engagement_scoring():
    """F14-5: User engagement scoring"""
    with open('wp-amsawal-analytics.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    scoring_code = """
// F14-5: User engagement scoring
function amsawal_calculate_engagement_score($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'amsawal_user_interactions';
    
    // Get user activity in last 30 days
    $activity = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table 
         WHERE user_id = %d AND created_at >= DATE_SUB(%s, INTERVAL 30 DAY)",
        $user_id,
        current_time('Y-m-d')
    ));
    
    // Get streak
    $streak = get_user_meta($user_id, 'amsawal_streak', true) ?: 0;
    
    // Get level
    $level = get_user_meta($user_id, 'amsawal_level', true) ?: 1;
    
    // Get achievements
    $achievements = get_user_meta($user_id, 'amsawal_achievements', true) ?: [];
    
    // Calculate score (0-100)
    $score = 0;
    $score += min($activity * 2, 40); // Up to 40 points for activity
    $score += min($streak * 3, 30);   // Up to 30 points for streak
    $score += min($level * 2, 20);    // Up to 20 points for level
    $score += min(count($achievements) * 5, 10); // Up to 10 points for achievements
    
    return min($score, 100);
}

// Add engagement score to user profile
add_action('show_user_profile', function($user) {
    $score = amsawal_calculate_engagement_score($user->ID);
    ?>
    <h3>Engagement Score</h3>
    <table class="form-table">
        <tr>
            <th>Score</th>
            <td>
                <div style="background: #e5e5e5; border-radius: 10px; overflow: hidden; width: 200px;">
                    <div style="background: linear-gradient(90deg, #27ae60 0%, #2c5f8d 100%); width: <?php echo $score; ?>%; padding: 8px; color: #fff; text-align: center; font-weight: bold;">
                        <?php echo $score; ?>/100
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <?php
});
"""
    
    if 'amsawal_calculate_engagement_score' not in php:
        php += scoring_code
    
    with open('wp-amsawal-analytics.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F14-5: User engagement scoring added")
    return True

# Ejecutar todas las mejoras de analytics
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 14 - Analytics Dashboard...\n")
    
    apply_f14_1_realtime_dashboard()
    apply_f14_2_user_behavior_tracking()
    apply_f14_3_export_functionality()
    apply_f14_4_retention_analysis()
    apply_f14_5_engagement_scoring()
    
    print("\n✨ Mejoras de analytics completadas")
