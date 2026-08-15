<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Gamification Visual Components — GamiPress Integration
 * 
 * Renders XP bar, level badges, achievement cases, streak society
 * All data synced with GamiPress points, ranks and achievements
 */

/**
 * Render gamification bar at top of content
 * Shows: XP, Level, Progress to next level
 */
add_action( 'wp_amsawal_gamification_bar', 'wp_amsawal_render_gamification_bar' );
function wp_amsawal_render_gamification_bar() {
    if (!is_user_logged_in()) return;
    
    $userid = get_current_user_id();
    
    // Get current course from context
    global $post;
    $custom_fields = get_post_custom($post->ID);
    $current_course = isset($custom_fields["wp_amsawal_mb_course"][0]) ? $custom_fields["wp_amsawal_mb_course"][0] : 'tamazight';
    
    // GamiPress: Get current points (XP)
    $current_xp = function_exists('gamipress_get_user_points') 
        ? (int) gamipress_get_user_points($userid, 'monedas') 
        : 0;
    
    // GamiPress: Get current rank/level
    $rank_type = 'nivel';
    $current_rank = function_exists('gamipress_get_user_rank') 
        ? gamipress_get_user_rank($userid, $rank_type) 
        : null;
    
    $current_level = $current_rank ? $current_rank->menu_order : 1;
    $next_level = $current_level + 1;
    
    // Calculate XP needed for next level
    // Assuming each level requires: level * 100 XP
    $xp_for_current = ($current_level - 1) * 100;
    $xp_for_next = $current_level * 100;
    $xp_in_current_level = $current_xp - $xp_for_current;
    $xp_needed = $xp_for_next - $xp_for_current;
    $progress_percent = min(100, max(0, ($xp_in_current_level / $xp_needed) * 100));
    
    // Get streak
    $streak = (int) get_user_meta($userid, '_wp_amsawal_streak_days', true);
    ?>
    
    <div class="duo-gamification-bar" role="progressbar" aria-valuenow="<?php echo esc_attr($progress_percent); ?>" aria-valuemin="0" aria-valuemax="100">
        
        <!-- Level Badge -->
        <div class="duo-gamification-level">
            <div class="duo-level-badge">
                <span class="duo-level-icon" aria-hidden="true">📚</span>
                <span class="duo-level-num"><?php echo esc_html($current_level); ?></span>
            </div>
            <span class="duo-level-label">Nivel <?php echo esc_html($current_level); ?></span>
        </div>
        
        <!-- XP Progress Bar -->
        <div class="duo-gamification-progress">
            <div class="duo-xp-bar">
                <div class="duo-xp-bar-fill" style="width: <?php echo esc_attr($progress_percent); ?>%;"></div>
            </div>
            <div class="duo-xp-info">
                <span class="duo-xp-current"><?php echo esc_html($xp_in_current_level); ?> XP</span>
                <span class="duo-xp-needed"><?php echo esc_html($xp_needed); ?> XP para nivel <?php echo esc_html($next_level); ?></span>
            </div>
        </div>
        
        <!-- Streak -->
        <div class="duo-gamification-stats">
            <div class="duo-gamification-streak" title="<?php echo esc_attr($streak . ' días de racha'); ?>">
                <span class="duo-streak-icon" aria-hidden="true">🔥</span>
                <span class="duo-streak-count"><?php echo esc_html($streak); ?></span>
            </div>
        </div>
        
    </div>
    
    <?php
}

/**
 * Render achievement cases (collectible achievements)
 * Shows earned and locked achievements from GamiPress
 */
add_action( 'wp_amsawal_achievements', 'wp_amsawal_render_achievements' );
function wp_amsawal_render_achievements() {
    if (!is_user_logged_in()) return;
    
    $userid = get_current_user_id();
    
    // GamiPress: Get earned achievements
    $earned_ids = function_exists('gamipress_get_user_earned_achievement_ids') 
        ? gamipress_get_user_earned_achievement_ids($userid, 'logros') 
        : array();
    
    // GamiPress: Get all achievements
    $all_achievements = function_exists('gamipress_get_achievements') 
        ? gamipress_get_achievements(array('post_type' => 'achievement')) 
        : array();
    
    // Limit to first 12 for display
    $all_achievements = array_slice($all_achievements, 0, 12);
    ?>
    
    <div class="duo-achievements-section">
        <div class="duo-achievements-header">
            <h2 aria-hidden="true">🏆 <span>Logros</span></h2>
            <a href="<?php echo esc_url( site_url( '/logros/' ) ); ?>" class="duo-achievements-view-all">Ver todos</a>
        </div>
        
        <div class="duo-achievements-grid">
            <?php foreach ($all_achievements as $achievement) : 
                $is_earned = in_array($achievement->ID, $earned_ids);
                $image = function_exists('gamipress_get_achievement_post_thumbnail') 
                    ? gamipress_get_achievement_post_thumbnail($achievement->ID, 'medium') 
                    : '';
                ?>
                
                <div class="duo-achievement-case <?php echo $is_earned ? 'is-earned' : 'is-locked'; ?>" 
                     title="<?php echo esc_attr($achievement->post_title); ?>">
                    
                    <div class="duo-achievement-image">
                        <?php if ($image) : ?>
                            <?php echo wp_kses_post( $image ); ?>
                        <?php else : ?>
                            <span class="duo-achievement-placeholder" aria-hidden="true">🏆</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="duo-achievement-info">
                        <h4 class="duo-achievement-title"><?php echo esc_html($achievement->post_title); ?></h4>
                        <p class="duo-achievement-desc"><?php echo esc_html(wp_trim_words($achievement->post_excerpt, 10)); ?></p>
                    </div>
                    
                    <?php if ($is_earned) : ?>
                        <div class="duo-achievement-checkmark">✅</div>
                    <?php else : ?>
                        <div class="duo-achievement-lock">🔒</div>
                    <?php endif; ?>
                    
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php
}

/**
 * Render Streak Society panel
 * Shows streak progress, milestones, freeze streaks
 */
add_action( 'wp_amsawal_streak_panel', 'wp_amsawal_render_streak_panel' );
function wp_amsawal_render_streak_panel() {
    if (!is_user_logged_in()) return;
    
    $userid = get_current_user_id();
    $streak = (int) get_user_meta($userid, '_wp_amsawal_streak_days', true);
    
    // Milestones: 7, 14, 30, 60, 90, 365 days
    $milestones = array(7, 14, 30, 60, 90, 365);
    $current_milestone = 0;
    $next_milestone = $milestones[0];
    
    foreach ($milestones as $ms) {
        if ($streak >= $ms) {
            $current_milestone = $ms;
            $next_milestone = $ms;
        } else {
            $next_milestone = $ms;
            break;
        }
    }

    $milestone_progress = $current_milestone > 0 && $next_milestone > $current_milestone
        ? ($streak - $current_milestone) / ($next_milestone - $current_milestone) * 100
        : ($next_milestone > 0 ? ($streak / $next_milestone) * 100 : 100);
    ?>
    
    <div class="duo-streak-panel">
        <div class="duo-streak-panel-header">
            <h3 aria-hidden="true">🔥 <span>Racha de Fuego</span></h3>
            <span class="duo-streak-total"><?php echo esc_html($streak); ?> días</span>
        </div>
        
        <div class="duo-streak-progress-container">
            <div class="duo-streak-progress-bar">
                <div class="duo-streak-progress-fill" style="width: <?php echo esc_attr($milestone_progress); ?>%;"></div>
            </div>
            <p class="duo-streak-milestone-text">
                <?php if ($streak >= 365) : ?>
                    ¡Eres una leyenda! <span aria-hidden="true">🏆</span>
                <?php else : ?>
                    <?php echo esc_html($next_milestone - $streak); ?> días para el siguiente hito
                <?php endif; ?>
            </p>
        </div>
        
        <div class="duo-streak-milestones">
            <?php foreach ($milestones as $ms) : 
                $is_unlocked = $streak >= $ms;
                ?>
                <div class="duo-streak-milestone <?php echo $is_unlocked ? 'is-unlocked' : 'is-locked'; ?>">
                    <span class="duo-milestone-num"><?php echo esc_html($ms); ?></span>
                    <span class="duo-milestone-icon"><?php echo $is_unlocked ? '✅' : '🔒'; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Streak Freeze -->
        <div class="duo-streak-freeze">
            <div class="duo-freeze-icon" aria-hidden="true">❄️</div>
            <div class="duo-freeze-info">
                <h4>Proteger racha</h4>
                <p>Usa un congelador para no perder tu racha si fallas un día</p>
            </div>
            <button class="duo-freeze-btn" disabled>Sin congeladores</button>
        </div>
    </div>
    
    <?php
}

/**
 * Render level up modal (triggered via AJAX when GamiPress rank changes)
 */
function wp_amsawal_render_level_up_modal($old_level, $new_level, $course) {
    $section_names = array(
        1 => 'Alfabeto y Fonología',
        2 => 'Saludos y Presentaciones',
        3 => 'Números y Tiempo',
        4 => 'Familia y Personas',
        5 => 'Adjetivos y Descripciones',
    );
    $section_name = isset( $section_names[ $new_level ] ) ? $section_names[ $new_level ] : '';
    ?>
    <div class="duo-level-up-overlay" role="dialog" aria-modal="true" aria-labelledby="duo-level-up-title">
        <div class="duo-level-up-card">
            <div class="duo-level-up-icon" aria-hidden="true">🎉</div>
            <h2 id="duo-level-up-title">¡Sección <?php echo esc_html($new_level); ?> completada!</h2>
            <p>Has dominado <strong><?php echo esc_html($section_name); ?></strong></p>

            <div class="duo-level-up-rewards">
                <div class="duo-reward-item">
                    <span class="duo-reward-icon" aria-hidden="true">💰</span>
                    <span class="duo-reward-text">+25 monedas</span>
                </div>
                <div class="duo-reward-item">
                    <span class="duo-reward-icon" aria-hidden="true">🏅</span>
                    <span class="duo-reward-text">Logro de sección desbloqueado</span>
                </div>
            </div>

            <button class="duo-level-up-continue" onclick="this.closest('.duo-level-up-overlay').remove()">
                CONTINUAR
            </button>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler for level up detection
 * Polls GamiPress rank and triggers modal when changed
 */
add_action('wp_ajax_wp_amsawal_check_level_up', 'wp_amsawal_ajax_check_level_up');
function wp_amsawal_ajax_check_level_up() {
    check_ajax_referer('wp_amsawal_gamification', '_ajax_nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'));
        return;
    }

    $userid = get_current_user_id();
    $course = sanitize_text_field($_POST['course'] ?? 'tamazight');
    $old_level = (int) ($_POST['old_level'] ?? 1);

    $rank_type = 'nivel';
    $current_rank = function_exists('gamipress_get_user_rank')
        ? gamipress_get_user_rank($userid, $rank_type)
        : null;

    $current_level = $current_rank ? $current_rank->menu_order : 1;

    if ($current_level > $old_level) {
        wp_send_json_success(array(
            'leveled_up' => true,
            'old_level' => $old_level,
            'new_level' => $current_level,
            'course' => $course
        ));
    } else {
        wp_send_json_success(array(
            'leveled_up' => false,
            'current_level' => $current_level
        ));
    }
}
