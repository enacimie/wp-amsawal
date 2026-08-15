<?php
/**
 * profile-template.php — Complete profile page template
 *
 * This file is loaded via template_include filter when
 * the amsawal_profile query var is set.
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_amsawal_current_profile;
$user     = $wp_amsawal_current_profile;
$user_id  = $user ? $user->ID : 0;
$is_own   = ( get_current_user_id() === $user_id );
$stats    = $user_id ? amsawal_get_profile_stats( $user_id ) : null;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $user ? esc_html( $user->display_name ) . ' - Perfil' : 'Perfil no encontrado'; ?> | <?php bloginfo( 'name' ); ?></title>
<?php wp_head(); ?>
</head>
<body <?php body_class('duo-profile-page'); ?>>
<?php wp_body_open(); ?>
<div class="duo-profile-wrapper">
<?php if ( ! $user ) : ?>
    <div class="duo-profile-error">
        <p><?php esc_html_e( 'Usuario no encontrado', WP_AMSAWAL_TEXTDOMAIN ); ?></p>
    </div>
<?php else : ?>
<div class="duo-page duo-profile-container">

    <!-- Profile Header (unified) -->
    <?php
    $coins    = (int) $stats['coins'];
    $streak   = (int) $stats['streak'];
    $subtitle = $is_own
        ? __( 'Tu perfil personal de aprendizaje', WP_AMSAWAL_TEXTDOMAIN )
        : sprintf(
            /* translators: %s: user display name */
            __( 'Perfil de %s', WP_AMSAWAL_TEXTDOMAIN ),
            $user->display_name
        );
    ?>
    <header class="duo-page-header" style="align-items:center;">
        <div style="display:flex;align-items:center;gap:20px;flex:1;min-width:200px;">
            <div class="duo-profile-avatar" style="width:80px;height:80px;border-radius:50%;overflow:hidden;flex-shrink:0;">
                <?php echo get_avatar( $user_id, 120 ); ?>
            </div>
            <div>
                <h1 class="duo-page-title" style="margin:0;"><?php echo esc_html( $user->display_name ); ?></h1>
                <p class="duo-page-subtitle" style="margin:4px 0 0;">@<?php echo esc_html( $user->user_login ); ?> · <?php echo esc_html( $subtitle ); ?></p>
                <?php if ( $is_own ) : ?>
                    <a href="<?php echo esc_url( wp_logout_url() ); ?>" class="duo-btn duo-btn--ghost" style="margin-top:8px;font-size:12px;padding:4px 12px;">
                        <?php esc_html_e( 'Cerrar sesión', WP_AMSAWAL_TEXTDOMAIN ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="duo-page-stats">
            <div class="duo-page-stat">
                <span class="duo-page-stat-icon" aria-hidden="true">🔥</span>
                <span class="duo-page-stat-value"><?php echo (int) $streak; ?></span>
                <span class="duo-page-stat-label"><?php esc_html_e( 'Racha', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
            </div>
            <div class="duo-page-stat duo-page-stat--coins">
                <span class="duo-page-stat-icon" aria-hidden="true">💰</span>
                <span class="duo-page-stat-value"><?php echo number_format_i18n( $coins ); ?></span>
                <span class="duo-page-stat-label"><?php esc_html_e( 'Monedas', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
            </div>
        </div>
    </header>

    <!-- Stats Grid (unified) -->
    <div class="duo-grid duo-grid-4" style="margin-bottom: 24px;">
        <div class="duo-stat-card">
            <div class="duo-stat-icon" aria-hidden="true">⭐</div>
            <span class="duo-stat-value"><?php echo max( 1, (int) $stats['level'] ); ?></span>
            <span class="duo-stat-label"><?php echo esc_html( $stats['rank_label'] ?: __( 'Nivel', WP_AMSAWAL_TEXTDOMAIN ) ); ?></span>
        </div>
        <div class="duo-stat-card">
            <div class="duo-stat-icon" aria-hidden="true">📚</div>
            <span class="duo-stat-value"><?php echo (int) $stats['lessons_completed']; ?></span>
            <span class="duo-stat-label"><?php esc_html_e( 'Lecciones', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
        </div>
        <div class="duo-stat-card">
            <div class="duo-stat-icon" aria-hidden="true">🏆</div>
            <span class="duo-stat-value"><?php echo (int) $stats['achievements_count']; ?></span>
            <span class="duo-stat-label"><?php esc_html_e( 'Logros', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
        </div>
    </div>

    <!-- Achievements Section (unified) -->
    <section style="margin-bottom: 32px;">
        <h2 class="duo-section-title" aria-hidden="true">🏆 <span><?php esc_html_e( 'Logros', WP_AMSAWAL_TEXTDOMAIN ); ?></span> <span style="color:var(--duo-text-light);font-size:14px;font-weight:500;">(<?php echo (int) $stats['achievements_count']; ?>)</span></h2>
        <p class="duo-section-desc"><?php esc_html_e( 'Insignias que has desbloqueado', WP_AMSAWAL_TEXTDOMAIN ); ?></p>
        <div class="duo-grid">
            <?php 
            $all_achievements = function_exists( 'amsawal_get_achievements_catalog' ) ? amsawal_get_achievements_catalog() : array();
            $legacy_achievements = function_exists( 'amsawal_get_achievements' ) ? amsawal_get_achievements() : array();
            if ( ! empty( $stats['achievements'] ) ) : ?>
                <?php foreach ( $stats['achievements'] as $ach_id ) : 
                    // Try GamiPress system first, then legacy system
                    if ( isset( $all_achievements[ $ach_id ] ) && function_exists( 'amsawal_get_achievement_data' ) ) {
                        $ach_data = amsawal_get_achievement_data( $all_achievements[ $ach_id ]->ID );
                    } elseif ( isset( $legacy_achievements[ $ach_id ] ) ) {
                        $ach_data = array(
                            'icon' => $legacy_achievements[ $ach_id ]['icon'] ?? '🏆',
                            'title' => $legacy_achievements[ $ach_id ]['name'] ?? $ach_id,
                            'description' => $legacy_achievements[ $ach_id ]['description'] ?? '',
                        );
                    } else {
                        $ach_data = array(
                            'icon' => '🏆',
                            'title' => $ach_id,
                            'description' => '',
                        );
                    }
                    $ach_data = is_array( $ach_data ) ? $ach_data : array(
                        'icon' => '🏆',
                        'title' => $ach_id,
                        'description' => '',
                    );
                ?>
                    <div class="duo-card duo-achievement-card is-earned" style="text-align:center;padding:16px;">
                        <div class="duo-achievement-icon" style="font-size:48px;margin-bottom:8px;"><?php echo esc_html( $ach_data['icon'] ?? '🏆' ); ?></div>
                        <h3 class="duo-achievement-title" style="margin:0 0 4px;"><?php echo esc_html( $ach_data['title'] ?? $ach_id ); ?></h3>
                        <p class="duo-achievement-desc" style="margin:0 0 8px;"><?php echo esc_html( $ach_data['description'] ?? '' ); ?></p>
                        <?php if ( isset( $ach_data['completed_at'] ) ) : ?>
                            <time datetime="<?php echo esc_attr( $ach_data['completed_at'] ); ?>" style="font-size:12px;color:var(--duo-text-light);">
                                <?php echo esc_html( human_time_diff( strtotime( $ach_data['completed_at'] ), current_time( 'timestamp' ) ) . ' ' . __( 'atrás', WP_AMSAWAL_TEXTDOMAIN ) ); ?>
                            </time>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="duo-empty" style="grid-column: 1 / -1;">
                    <div class="duo-empty-icon" aria-hidden="true">🏆</div>
                    <h3 class="duo-empty-title"><?php esc_html_e( 'Aún no hay logros', WP_AMSAWAL_TEXTDOMAIN ); ?></h3>
                    <p class="duo-empty-desc"><?php esc_html_e( '¡Sigue aprendiendo para desbloquear tu primer logro!', WP_AMSAWAL_TEXTDOMAIN ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Recent Activity (unified) -->
    <section style="margin-bottom: 32px;">
        <h2 class="duo-section-title" aria-hidden="true">📊 <span><?php esc_html_e( 'Actividad Reciente', WP_AMSAWAL_TEXTDOMAIN ); ?></span></h2>
        <p class="duo-section-desc"><?php esc_html_e( 'Tus últimas acciones en la plataforma', WP_AMSAWAL_TEXTDOMAIN ); ?></p>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php if ( ! empty( $stats['recent_activity'] ) ) : ?>
                <?php foreach ( $stats['recent_activity'] as $act ) : ?>
                    <div class="duo-card" style="display:flex;align-items:center;gap:16px;padding:12px 16px;">
                        <div style="font-size:24px;flex-shrink:0;"><?php echo wp_kses_post( $act['icon'] ?? '<span class="dashicons dashicons-admin-post" aria-hidden="true"></span>' ); ?></div>
                        <div style="flex:1;">
                            <p style="margin:0;font-size:14px;font-weight:600;color:var(--duo-text);"><?php echo esc_html( $act['title'] ?? '' ); ?></p>
                            <?php if ( ! empty( $act['timestamp'] ) ) : ?>
                                <time datetime="<?php echo esc_attr( $act['timestamp'] ); ?>" style="font-size:12px;color:var(--duo-text-light);">
                                    <?php echo esc_html( human_time_diff( strtotime( $act['timestamp'] ), current_time( 'timestamp' ) ) . ' ' . __( 'atrás', WP_AMSAWAL_TEXTDOMAIN ) ); ?>
                                </time>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $act['reward'] ) ) : ?>
                            <div style="font-weight:600;color:#d97706;white-space:nowrap;" aria-hidden="true">💰 +<?php echo esc_html( $act['reward'] ); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="duo-empty">
                    <div class="duo-empty-icon" aria-hidden="true">📊</div>
                    <p class="duo-empty-desc"><?php esc_html_e( 'No hay actividad reciente', WP_AMSAWAL_TEXTDOMAIN ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ( $is_own ) : ?>
    <div class="duo-card" style="text-align:center;margin-top: 32px;">
        <a href="<?php echo esc_url( site_url( '/mis-resultados/' ) ); ?>" class="duo-btn duo-btn--primary">📊 <?php esc_html_e( 'Ver mis resultados completos', WP_AMSAWAL_TEXTDOMAIN ); ?></a>
    </div>
    <?php endif; ?>

</div><!-- .duo-page -->
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
