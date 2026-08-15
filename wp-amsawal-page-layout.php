<?php
/**
 * Unified Page Layout for Amsawal Gamification Pages
 *
 * Provides a consistent header (title + subtitle + coins balance) and
 * content area used by all gamification pages:
 * - /logros/ (achievements wall)
 * - /tienda/ (shop)
 * - /liderazgos/ (leaderboards)
 * - /mis-resultados/ (my stats)
 * - /i/{username}/ (profile)
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render a unified page header with title, subtitle, and coin balance.
 *
 * @param string $title       Page title (without emoji).
 * @param string $subtitle    Optional description.
 * @param string $icon        Emoji for the title (default: ⚙️).
 * @return string HTML of the header.
 */
function amsawal_render_page_header( $title, $subtitle = '', $icon = '🏆' ) {
    $user_id = get_current_user_id();
    $coins   = (int) ( function_exists( 'gamipress_get_user_points' ) && $user_id
        ? gamipress_get_user_points( $user_id, 'monedas' )
        : 0 );

    $streak = 0;
    if ( $user_id ) {
        $streak = (int) get_user_meta( $user_id, '_wp_amsawal_streak_days', true );
    }

    // Si el icono es un emoji, lo convertimos a dashicon
    if ( function_exists( 'wp_amsawal_emoji_icon' ) && mb_strlen( $icon, 'UTF-8' ) > 2 ) {
        $icon_html = wp_amsawal_emoji_icon( $icon, 'lg' );
    } else {
        $icon_html = '<span class="duo-nav-icon duo-nav-icon--lg" aria-hidden="true">' . esc_html( $icon ) . '</span>';
    }

    ob_start();
    ?>
    <div class="duo-page-header">
        <div class="duo-page-header-text">
            <h1 class="duo-page-title"><?php echo $icon_html . ' ' . esc_html( $title ); ?></h1>
            <?php if ( $subtitle ) : ?>
                <p class="duo-page-subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
        </div>
        <div class="duo-page-stats">
            <?php if ( $user_id ) : ?>
                <a href="<?php echo esc_url( site_url( '/logros/' ) ); ?>" class="duo-page-stat" aria-label="<?php esc_attr_e( 'Tu racha', WP_AMSAWAL_TEXTDOMAIN ); ?>">
                    <span class="duo-page-stat-icon" aria-hidden="true"><?php echo wp_amsawal_nav_icon('streak', 'md'); ?></span>
                    <span class="duo-page-stat-value"><?php echo (int) $streak; ?></span>
                    <span class="duo-page-stat-label"><?php esc_html_e( 'Racha', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
                </a>
            <?php endif; ?>
            <?php if ( $user_id ) : ?>
                <a href="<?php echo esc_url( site_url( '/tienda/' ) ); ?>" class="duo-page-stat duo-page-stat--coins" aria-label="<?php esc_attr_e( 'Tus monedas', WP_AMSAWAL_TEXTDOMAIN ); ?>">
                    <span class="duo-page-stat-icon" aria-hidden="true"><?php echo wp_amsawal_nav_icon('coin', 'md'); ?></span>
                    <span class="duo-page-stat-value"><?php echo number_format_i18n( $coins ); ?></span>
                    <span class="duo-page-stat-label"><?php esc_html_e( 'Monedas', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Open a unified page container.
 */
function amsawal_page_open( $class = 'duo-page' ) {
    return '<div class="' . esc_attr( $class ) . '">';
}

/**
 * Close a unified page container.
 */
function amsawal_page_close() {
    return '</div>';
}

/**
 * Render a unified section header.
 *
 * @param string $icon Emoji.
 * @param string $title Section title.
 * @param string $description Optional description.
 * @return string HTML.
 */
function amsawal_render_section_header( $icon, $title, $description = '' ) {
    ob_start();
    ?>
    <div class="duo-section-header">
        <h2 class="duo-section-title"><?php echo wp_kses_post( $icon ) . ' ' . esc_html( $title ); ?></h2>
        <?php if ( $description ) : ?>
            <p class="duo-section-desc"><?php echo esc_html( $description ); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a back link to the home page.
 */
function amsawal_back_link( $label = null, $url = '' ) {
    if ( ! $label ) {
        $label = __( '← Volver al inicio', WP_AMSAWAL_TEXTDOMAIN );
    }
    if ( ! $url ) {
        $url = site_url( '/' );
    }
    return '<a href="' . esc_url( $url ) . '" class="duo-back-link">' . esc_html( $label ) . '</a>';
}
