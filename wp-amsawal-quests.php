<?php
/**
 * Página de Desafíos del Día (Quests)
 * Muestra desafíos activos y daily quests automáticos
 */

if (!defined('ABSPATH')) exit;

// Registrar shortcode
add_shortcode('amsawal_quests', 'wp_amsawal_quests_shortcode');

add_action('wp_enqueue_scripts', function() {
    if (is_page('quests')) {
        wp_enqueue_style('wp-amsawal-quests', plugin_dir_url(__FILE__) . 'css/modules/_quests.css', [], filemtime(plugin_dir_path(__FILE__) . 'css/modules/_quests.css'));
    }
});

function wp_amsawal_quests_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="duo-page"><p>Debes iniciar sesión para ver tus desafíos.</p></div>';
    }
    
    $user_id = get_current_user_id();
    
    // Obtener desafíos activos del sistema de challenges
    $active_challenges = [];
    if (function_exists('amsawal_get_active_challenges')) {
        $active_challenges = amsawal_get_active_challenges($user_id);
    }
    
    // Obtener daily quests automáticos
    $streak = (int)get_user_meta($user_id, '_wp_amsawal_streak_days', true);
    $streak_for_quest = min(7, $streak);
    
    $today = current_time('Y-m-d');
    $daily_xp_key = '_wp_amsawal_xp_today_' . $today;
    $daily_xp = (int)get_user_meta($user_id, $daily_xp_key, true);
    $daily_xp_goal = 50;
    $daily_xp_pct = min(100, round(($daily_xp / $daily_xp_goal) * 100));
    
    // Obtener lecciones completadas hoy
    $lessons_today_key = '_wp_amsawal_lessons_completed_today_' . $today;
    $lessons_today = (int)get_user_meta($user_id, $lessons_today_key, true);
    $lessons_today_goal = 3;
    $lessons_today_pct = min(100, round(($lessons_today / $lessons_today_goal) * 100));
    
    ob_start();
    ?>
    <div class="duo-page">
        <div class="duo-page-header">
            <div class="duo-page-header-text">
                <h1><?php esc_html_e('Desafíos del día', 'amsawal'); ?></h1>
                <p class="duo-page-subtitle"><?php esc_html_e('Completa desafíos diarios para ganar XP y mantener tu racha', 'amsawal'); ?></p>
            </div>
        </div>
        
        <?php if (!empty($active_challenges)): ?>
        <section class="duo-quest-section">
            <h2><?php esc_html_e('Desafíos activos', 'amsawal'); ?></h2>
            <?php foreach ($active_challenges as $challenge): ?>
                <?php
                $progress_pct = $challenge->target_value > 0 
                    ? min(100, round(($challenge->progress / $challenge->target_value) * 100))
                    : 0;
                $days_remaining = strtotime($challenge->ends_at) > time() 
                    ? ceil((strtotime($challenge->ends_at) - time()) / 86400)
                    : 0;
                ?>
                <div class="duo-quest-card <?php echo $challenge->completed ? 'duo-quest-completed' : ''; ?>">
                    <div class="duo-quest-icon" aria-hidden="true">🏆</div>
                    <div class="duo-quest-body">
                        <h3 class="duo-quest-title"><?php echo esc_html($challenge->challenge_type); ?></h3>
                        <div class="duo-quest-progress">
                            <div class="duo-quest-progress-bar-wrapper">
                                <div class="duo-quest-progress-bar" style="width: <?php echo $progress_pct; ?>%"></div>
                            </div>
                            <span class="duo-quest-progress-text">
                                <?php echo esc_html($challenge->progress); ?>/<?php echo esc_html($challenge->target_value); ?>
                                <?php if ($challenge->completed): ?>
                                    ✓ <?php esc_html_e('Completado', 'amsawal'); ?>
                                <?php elseif ($days_remaining > 0): ?>
                                    • <?php printf(esc_html__('%d días restantes', 'amsawal'), $days_remaining); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
        
        <section class="duo-quest-section">
            <h2><?php esc_html_e('Daily Quests', 'amsawal'); ?></h2>
            <p class="duo-quest-intro"><?php esc_html_e('Completa estos desafíos diarios para ganar XP adicional', 'amsawal'); ?></p>
            
            <!-- Racha diaria (7 días) -->
            <div class="duo-quest-card">
                <div class="duo-quest-icon" aria-hidden="true">🔥</div>
                <div class="duo-quest-body">
                    <h3 class="duo-quest-title"><?php esc_html_e('Racha de 7 días', 'amsawal'); ?></h3>
                    <p class="duo-quest-desc"><?php esc_html_e('Estudia 7 días consecutivos', 'amsawal'); ?></p>
                    <div class="duo-quest-progress">
                        <div class="duo-quest-progress-bar-wrapper">
                            <div class="duo-quest-progress-bar" style="width: <?php echo round(($streak_for_quest / 7) * 100); ?>%"></div>
                        </div>
                        <span class="duo-quest-progress-text">
                            <?php echo $streak_for_quest; ?>/7 <?php esc_html_e('días', 'amsawal'); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- XP diario (50 XP) -->
            <div class="duo-quest-card">
                <div class="duo-quest-icon" aria-hidden="true">⭐</div>
                <div class="duo-quest-body">
                    <h3 class="duo-quest-title"><?php esc_html_e('Ganar 50 XP', 'amsawal'); ?></h3>
                    <p class="duo-quest-desc"><?php esc_html_e('Completa actividades para ganar XP hoy', 'amsawal'); ?></p>
                    <div class="duo-quest-progress">
                        <div class="duo-quest-progress-bar-wrapper">
                            <div class="duo-quest-progress-bar" style="width: <?php echo $daily_xp_pct; ?>%"></div>
                        </div>
                        <span class="duo-quest-progress-text">
                            <?php echo $daily_xp; ?>/<?php echo $daily_xp_goal; ?> XP
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Lecciones hoy (3 lecciones) -->
            <div class="duo-quest-card">
                <div class="duo-quest-icon" aria-hidden="true">📚</div>
                <div class="duo-quest-body">
                    <h3 class="duo-quest-title"><?php esc_html_e('Completar 3 lecciones', 'amsawal'); ?></h3>
                    <p class="duo-quest-desc"><?php esc_html_e('Completa 3 lecciones hoy', 'amsawal'); ?></p>
                    <div class="duo-quest-progress">
                        <div class="duo-quest-progress-bar-wrapper">
                            <div class="duo-quest-progress-bar" style="width: <?php echo $lessons_today_pct; ?>%"></div>
                        </div>
                        <span class="duo-quest-progress-text">
                            <?php echo $lessons_today; ?>/<?php echo $lessons_today_goal; ?> <?php esc_html_e('lecciones', 'amsawal'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="duo-quest-section">
            <h2><?php esc_html_e('Consejos para hoy', 'amsawal'); ?></h2>
            <div class="duo-quest-tips">
                <div class="duo-quest-tip">
                    <span class="duo-quest-tip-icon">💡</span>
                    <div>
                        <strong><?php esc_html_e('Estudia 15 minutos diarios', 'amsawal'); ?></strong>
                        <p><?php esc_html_e('Una sesión corta pero consistente es más efectiva que sesiones largas esporádicas', 'amsawal'); ?></p>
                    </div>
                </div>
                <div class="duo-quest-tip">
                    <span class="duo-quest-tip-icon">🔄</span>
                    <div>
                        <strong><?php esc_html_e('Revisa tus lecciones anteriores', 'amsawal'); ?></strong>
                        <p><?php esc_html_e('La repetición espaciada mejora la retención a largo plazo', 'amsawal'); ?></p>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php
    return ob_get_clean();
}
