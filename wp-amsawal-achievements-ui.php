<?php
/**
 * Achievements Wall UI - Amsawal
 *
 * Renders the full achievement catalog with earned/locked state and a shop section.
 * Used on the dedicated /logros page or as a shortcode.
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*───────────────────────────────────────────────────────────────────────
 * 1. SHORTCODE [amsawal_achievements_wall]
 *───────────────────────────────────────────────────────────────────────*/

add_shortcode( 'amsawal_achievements_wall', 'amsawal_render_achievements_wall' );
function amsawal_render_achievements_wall( $atts = array() ) {
    if ( ! is_user_logged_in() ) {
        return '<p class="duo-notice">' . esc_html__( 'Inicia sesión para ver tus logros.', WP_AMSAWAL_TEXTDOMAIN ) . '</p>';
    }

    $user_id = get_current_user_id();
    $catalog = amsawal_get_achievements_catalog( true );
    $earned  = amsawal_get_user_earned_achievement_ids( $user_id );

    $coins = (int) gamipress_get_user_points( $user_id, 'monedas' );

    // Group by category
    $by_cat = array();
    foreach ( $catalog as $slug => $ach ) {
        $data = amsawal_get_achievement_data( $ach->ID );
        if ( ! $data ) {
            continue;
        }
        $data['earned']   = in_array( $ach->ID, $earned, true );
        $data['progress'] = amsawal_get_achievement_progress( $user_id, $data );
        $by_cat[ $data['category'] ][] = $data;
    }

    $cat_labels = array(
        'lesson'   => array( 'icon' => '📚',         'title' => 'Lecciones',  'desc' => 'Logros por completar lecciones del curso' ),
        'section'  => array( 'icon' => '📍',       'title' => 'Secciones',  'desc' => 'Logros al completar cada sección completa' ),
        'streak'   => array( 'icon' => '⭐',  'title' => 'Rachas',     'desc' => 'Logros por días consecutivos de práctica' ),
        'league'   => array( 'icon' => '📊',    'title' => 'Ligas',      'desc' => 'Logros por posición en la competición semanal' ),
        'mastery'  => array( 'icon' => '🏆',       'title' => 'Maestría',   'desc' => 'Logros por rendimiento y dominio del contenido' ),
        'social'   => array( 'icon' => '👥',       'title' => 'Social',     'desc' => 'Logros por interacción con otros estudiantes' ),
        'shop'     => array( 'icon' => '🛒',         'title' => 'Tienda',     'desc' => 'Insignias premium que puedes comprar con monedas' ),
        'special'  => array( 'icon' => '⭐',  'title' => 'Especiales', 'desc' => 'Logros especiales por hábitos o momentos únicos' ),
    );

    $total_earned = 0;
    $total_count  = 0;
    foreach ( $by_cat as $cat_items ) {
        foreach ( $cat_items as $item ) {
            $total_count++;
            if ( $item['earned'] ) {
                $total_earned++;
            }
        }
    }
    $pct = $total_count > 0 ? round( ( $total_earned / $total_count ) * 100 ) : 0;

    $subtitle = sprintf(
        /* translators: 1: earned, 2: total */
        __( '%1$d de %2$d logros desbloqueados', WP_AMSAWAL_TEXTDOMAIN ),
        $total_earned,
        $total_count
    );

    ob_start();
    ?>
    <div class="duo-page duo-achievements-wall" data-nonce="<?php echo esc_attr( wp_create_nonce( 'amsawal_nonce' ) ); ?>" data-ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

        <?php echo amsawal_render_page_header( __( 'Mis Logros', WP_AMSAWAL_TEXTDOMAIN ), $subtitle, '🏆' ); ?>

        <div class="duo-achievements-overview duo-card" style="margin-bottom: 24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <strong style="font-size:18px;color:var(--duo-text);"><?php echo (int) $total_earned; ?> / <?php echo (int) $total_count; ?></strong>
                    <span style="color:var(--duo-text-light);margin-left:6px;"><?php esc_html_e( 'logros conseguidos', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:200px;max-width:400px;">
                    <div class="duo-achievements-progress-bar" style="flex:1;height:10px;background:var(--duo-border);border-radius:5px;overflow:hidden;">
                        <div class="duo-achievements-progress-fill" style="height:100%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:5px;width:<?php echo esc_attr($pct);?>%;transition:width 0.4s ease;"></div>
                    </div>
                    <span style="font-weight:600;color:var(--duo-text);"><?php echo (int) $pct; ?>%</span>
                </div>
            </div>
        </div>

        <?php foreach ( $cat_labels as $cat_key => $cat_meta ) :
            if ( empty( $by_cat[ $cat_key ] ) ) {
                continue;
            }
            ?>
            <section class="duo-achievements-category" data-category="<?php echo esc_attr( $cat_key ); ?>">
                <h2 class="duo-achievements-cat-title"><?php echo esc_html( $cat_meta['icon'] ); ?> <?php echo esc_html( $cat_meta['title'] ); ?></h2>
                <p class="duo-achievements-cat-desc"><?php echo esc_html( $cat_meta['desc'] ); ?></p>
                <div class="duo-achievements-grid">
                    <?php foreach ( $by_cat[ $cat_key ] as $ach ) : ?>
                        <div class="duo-achievement-card <?php echo $ach['earned'] ? 'is-earned' : 'is-locked'; ?>"
                             data-id="<?php echo esc_attr( $ach['id'] ); ?>"
                             data-slug="<?php echo esc_attr( $ach['slug'] ); ?>"
                             data-price="<?php echo esc_attr( $ach['price'] ); ?>">
                            <div class="duo-achievement-icon" aria-hidden="true">
                                <?php echo wp_kses_post( $ach['icon'] ); ?>
                            </div>
                            <div class="duo-achievement-info">
                                <h3 class="duo-achievement-title"><?php echo esc_html( $ach['title'] ); ?></h3>
                                <p class="duo-achievement-desc"><?php echo esc_html( $ach['description'] ); ?></p>
                                <?php if ( ! $ach['earned'] && $ach['progress'] > 0 ) : ?>
                                    <div class="duo-achievement-progress-bar">
                                        <div class="duo-achievement-progress-fill" style="width: <?php echo esc_attr( $ach['progress'] ); ?>%"></div>
                                    </div>
                                    <span class="duo-achievement-progress-pct"><?php echo (int) $ach['progress']; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="duo-achievement-footer">
                                <?php if ( $ach['earned'] ) : ?>
                                    <span class="duo-achievement-status is-earned">✅ <?php esc_html_e( 'Conseguido', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
                                <?php elseif ( $ach['category'] === 'shop' && $ach['price'] > 0 ) : ?>
                                    <button type="button" class="duo-achievement-buy"
                                            data-id="<?php echo esc_attr( $ach['id'] ); ?>"
                                            aria-label="<?php echo esc_attr( sprintf( __( 'Comprar %s por %d monedas', WP_AMSAWAL_TEXTDOMAIN ), $ach['title'], $ach['price'] ) ); ?>">
                                        💰 <?php echo (int) $ach['price']; ?>
                                    </button>
                                <?php else : ?>
                                    <span class="duo-achievement-status is-locked">🔒 <?php esc_html_e( 'Bloqueado', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="duo-achievements-toast" role="status" aria-live="polite"></div>
    </div>

    <script>
    (function() {
        const wall = document.querySelector('.duo-achievements-wall');
        if (!wall) return;
        const nonce = wall.dataset.nonce;
        const ajaxurl = wall.dataset.ajaxurl;
        const toast = wall.querySelector('.duo-achievements-toast');

        function showToast(message, isError) {
            toast.textContent = message;
            toast.className = 'duo-achievements-toast ' + (isError ? 'is-error' : 'is-success');
            toast.style.opacity = '1';
            setTimeout(() => { toast.style.opacity = '0'; }, 3000);
        }

        wall.querySelectorAll('.duo-achievement-buy').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                btn.disabled = true;
                const formData = new FormData();
                formData.append('action', 'amsawal_buy_achievement');
                formData.append('nonce', nonce);
                formData.append('achievement_id', id);

                fetch(ajaxurl, { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.data.message, false);
                            // Update card visually
                            const card = btn.closest('.duo-achievement-card');
                            card.classList.remove('is-locked');
                            card.classList.add('is-earned');
                            btn.replaceWith(Object.assign(document.createElement('span'), {
                                className: 'duo-achievement-status is-earned',
                                textContent: '✅ Conseguido'
                            }));
                            // Update coins display
                            const coinsEl = wall.querySelector('.duo-coin-value');
                            if (coinsEl && data.data.new_balance !== undefined) {
                                coinsEl.textContent = data.data.new_balance.toLocaleString();
                            }
                        } else {
                            showToast(data.data || 'Error', true);
                            btn.disabled = false;
                        }
                    })
                    .catch(err => {
                        showToast('Error de red', true);
                        btn.disabled = false;
                    });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}


/*───────────────────────────────────────────────────────────────────────
 * 2. SHOP PAGE — [amsawal_shop]
 *───────────────────────────────────────────────────────────────────────*/

add_shortcode( 'amsawal_shop', 'amsawal_render_shop' );
function amsawal_render_shop( $atts = array() ) {
    if ( ! is_user_logged_in() ) {
        return '<p class="duo-notice">' . esc_html__( 'Inicia sesión para acceder a la tienda.', WP_AMSAWAL_TEXTDOMAIN ) . '</p>';
    }

    $user_id = get_current_user_id();
    $coins   = (int) gamipress_get_user_points( $user_id, 'monedas' );

    $shop_items = get_option( 'wp_amsawal_shop_items', array() );

    ob_start();
    ?>
    <div class="duo-page duo-shop" data-nonce="<?php echo esc_attr( wp_create_nonce( 'amsawal_nonce' ) ); ?>" data-ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
        <?php echo amsawal_render_page_header( __( 'Tienda de Insignias', WP_AMSAWAL_TEXTDOMAIN ), __( 'Usa tus monedas para comprar insignias exclusivas', WP_AMSAWAL_TEXTDOMAIN ), '🛒' ); ?>

        <div class="duo-grid duo-grid-3">
            <?php foreach ( $shop_items as $slug => $item ) :
                $ach_data = amsawal_get_achievement_data( $item['id'] );
                $owned    = amsawal_user_has_achievement( $user_id, $slug );
                $can_buy  = ! $owned && $coins >= $item['price'];
                ?>
                <div class="duo-card duo-shop-item <?php echo $owned ? 'is-owned' : ( $can_buy ? 'is-buyable' : 'is-locked' ); ?>"
                     data-id="<?php echo esc_attr( $item['id'] ); ?>"
                     data-slug="<?php echo esc_attr( $slug ); ?>"
                     data-price="<?php echo esc_attr( $item['price'] ); ?>">
                    <div class="duo-shop-item-icon" aria-hidden="true"><?php echo wp_kses_post( $item['icon'] ); ?></div>
                    <h3 class="duo-shop-item-title"><?php echo esc_html( $item['title'] ); ?></h3>
                    <?php if ( $ach_data ) : ?>
                        <p class="duo-shop-item-desc"><?php echo esc_html( $ach_data['description'] ); ?></p>
                    <?php endif; ?>
                    <div class="duo-shop-item-footer">
                        <?php if ( $owned ) : ?>
                            <span class="duo-shop-item-status">✅ <?php esc_html_e( 'Comprado', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
                        <?php else : ?>
                            <span class="duo-shop-item-price">💰 <?php echo (int) $item['price']; ?></span>
                            <button type="button" class="duo-btn duo-btn--success duo-shop-buy-btn" <?php disabled( ! $can_buy ); ?>>
                                <?php echo $can_buy ? esc_html__( 'Comprar', WP_AMSAWAL_TEXTDOMAIN ) : esc_html__( 'Sin monedas', WP_AMSAWAL_TEXTDOMAIN ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="duo-shop-toast" role="status" aria-live="polite"></div>
    </div>

    <script>
    (function() {
        const shop = document.querySelector('.duo-shop');
        if (!shop) return;
        const nonce = shop.dataset.nonce;
        const ajaxurl = shop.dataset.ajaxurl;
        const toast = shop.querySelector('.duo-shop-toast');

        function showToast(message, isError) {
            toast.textContent = message;
            toast.className = 'duo-shop-toast ' + (isError ? 'is-error' : 'is-success');
            toast.style.opacity = '1';
            setTimeout(() => { toast.style.opacity = '0'; }, 3000);
        }

        shop.querySelectorAll('.duo-shop-buy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const item = this.closest('.duo-shop-item');
                const id = item.dataset.id;
                btn.disabled = true;
                const formData = new FormData();
                formData.append('action', 'amsawal_buy_achievement');
                formData.append('nonce', nonce);
                formData.append('achievement_id', id);

                fetch(ajaxurl, { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.data.message, false);
                            item.classList.remove('is-buyable', 'is-locked');
                            item.classList.add('is-owned');
                            // Update balance
                            const balanceEl = shop.querySelector('.duo-coin-value');
                            if (balanceEl && data.data.new_balance !== undefined) {
                                balanceEl.textContent = data.data.new_balance.toLocaleString();
                            }
                            // Update footer
                            const footer = item.querySelector('.duo-shop-item-footer');
                            footer.innerHTML = '<span class="duo-shop-item-status">✅ Comprado</span>';
                        } else {
                            showToast(data.data || 'Error', true);
                            btn.disabled = false;
                        }
                    })
                    .catch(err => {
                        showToast('Error de red', true);
                        btn.disabled = false;
                    });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}


/*───────────────────────────────────────────────────────────────────────
 * 3. UNLOCK LESSON BUTTON — Renders when a lesson is failed/locked
 *───────────────────────────────────────────────────────────────────────*/

add_shortcode( 'amsawal_unlock_lesson', 'amsawal_render_unlock_lesson_button' );
function amsawal_render_unlock_lesson_button( $atts = array() ) {
    if ( ! is_user_logged_in() ) {
        return '';
    }
    $atts = shortcode_atts( array(
        'lesson_id' => 0,
        'cost'      => 50,
    ), $atts );
    $lesson_id = absint( $atts['lesson_id'] );
    $cost      = absint( $atts['cost'] );

    $user_id = get_current_user_id();
    $coins   = (int) gamipress_get_user_points( $user_id, 'monedas' );
    $can_buy = $coins >= $cost;

    ob_start();
    ?>
    <div class="duo-unlock-lesson" data-nonce="<?php echo esc_attr( wp_create_nonce( 'amsawal_nonce' ) ); ?>" data-ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
        <p class="duo-unlock-lesson-msg">
            <?php esc_html_e( '¿No puedes superar esta lección? Desbloquéala con monedas.', WP_AMSAWAL_TEXTDOMAIN ); ?>
        </p>
        <button type="button" class="duo-unlock-lesson-btn" <?php disabled( ! $can_buy ); ?>
                data-lesson="<?php echo esc_attr( $lesson_id ); ?>"
                data-cost="<?php echo esc_attr( $cost ); ?>">
            🔓 <?php esc_html_e( 'Desbloquear por', WP_AMSAWAL_TEXTDOMAIN ); ?> 💰 <?php echo (int) $cost; ?>
        </button>
        <p class="duo-unlock-lesson-balance">
            <?php esc_html_e( 'Tienes', WP_AMSAWAL_TEXTDOMAIN ); ?> 💰 <?php echo (int) $coins; ?>
        </p>
        <div class="duo-unlock-lesson-toast" role="status" aria-live="polite"></div>
    </div>
    <?php
    $html = ob_get_clean();

    // Add inline JS for the unlock action
    $html .= '<script>
    (function() {
        const wrap = document.querySelector(".duo-unlock-lesson");
        if (!wrap) return;
        const nonce = wrap.dataset.nonce;
        const ajaxurl = wrap.dataset.ajaxurl;
        const toast = wrap.querySelector(".duo-unlock-lesson-toast");

        function showToast(message, isError) {
            toast.textContent = message;
            toast.className = "duo-unlock-lesson-toast " + (isError ? "is-error" : "is-success");
            toast.style.opacity = "1";
            setTimeout(() => { toast.style.opacity = "0"; }, 3000);
        }

        const btn = wrap.querySelector(".duo-unlock-lesson-btn");
        btn.addEventListener("click", function() {
            btn.disabled = true;
            const formData = new FormData();
            formData.append("action", "amsawal_unlock_lesson");
            formData.append("nonce", nonce);
            formData.append("lesson_id", btn.dataset.lesson);

            fetch(ajaxurl, { method: "POST", body: formData, credentials: "same-origin" })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.data.message, false);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.data || "Error", true);
                        btn.disabled = false;
                    }
                });
        });
    })();
    </script>';

    return $html;
}


/*───────────────────────────────────────────────────────────────────────
 * 4. SHORTCODE [amsawal_my_stats] — Personal stats for the current user
 *───────────────────────────────────────────────────────────────────────*/

add_shortcode( 'amsawal_my_stats', 'amsawal_render_my_stats' );
function amsawal_render_my_stats( $atts = array() ) {
    if ( ! is_user_logged_in() ) {
        return '<p class="duo-notice">' . esc_html__( 'Inicia sesión para ver tus estadísticas.', WP_AMSAWAL_TEXTDOMAIN ) . '</p>';
    }

    $user_id = get_current_user_id();
    $stats   = amsawal_get_profile_stats( $user_id );
    $earned  = amsawal_get_user_earned_achievement_ids( $user_id );
    $catalog = amsawal_get_achievements_catalog( true );

    $coins        = (int) $stats['coins'];
    $streak       = (int) $stats['streak'];
    $level        = (int) $stats['level'];
    $rank_label   = $stats['rank_label'] ?: __( 'Sin nivel', WP_AMSAWAL_TEXTDOMAIN );
    $lessons      = (int) $stats['lessons_completed'];
    $lives        = (int) $stats['lives'];
    $achievements = count( $earned );
    $total_ach    = count( $catalog );
    $pct          = $total_ach > 0 ? round( ( $achievements / $total_ach ) * 100 ) : 0;

    $next_milestone = $stats['next_milestone'];
    $next_streak_label = '';
    if ( $next_milestone ) {
        $next_streak_label = sprintf(
            /* translators: 1: remaining days, 2: target days, 3: bonus */
            __( 'Faltan %1$d días para racha de %2$d días (+%3$d monedas)', WP_AMSAWAL_TEXTDOMAIN ),
            $next_milestone['remaining'],
            $next_milestone['days'],
            $next_milestone['bonus']
        );
    } elseif ( $streak >= 365 ) {
        $next_streak_label = __( '¡Has alcanzado el máximo de racha! 🏆', WP_AMSAWAL_TEXTDOMAIN );
    }

    // Fetch H5P results for the activities section
    $h5p_html = '';
    if ( function_exists( 'wp_amsawal_render_h5p_results' ) ) {
        $h5p_html = wp_amsawal_render_h5p_results( $user_id );
    }

    ob_start();
    ?>
    <div class="duo-page">
        <?php
        $subtitle = sprintf(
            /* translators: %d: level number */
            __( 'Tu progreso en Tamazight — Nivel %d', WP_AMSAWAL_TEXTDOMAIN ),
            max( 1, $level )
        );
        echo amsawal_render_page_header( __( 'Mis Resultados', WP_AMSAWAL_TEXTDOMAIN ), $subtitle, '📊' );
        ?>

        <!-- Main stats grid -->
        <div class="duo-grid duo-grid-4" style="margin-bottom: 24px;">
            <div class="duo-stat-card">
                <div class="duo-stat-icon">⭐</div>
                <span class="duo-stat-value"><?php echo (int) $streak; ?></span>
                <span class="duo-stat-label"><?php esc_html_e( 'Racha actual', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
            </div>
            <div class="duo-stat-card">
                <div class="duo-stat-icon">⭐</div>
                <span class="duo-stat-value"><?php echo max( 1, (int) $level ); ?></span>
                <span class="duo-stat-label"><?php echo esc_html( $rank_label ); ?></span>
            </div>
            <div class="duo-stat-card">
                <div class="duo-stat-icon">📚</div>
                <span class="duo-stat-value"><?php echo (int) $lessons; ?></span>
                <span class="duo-stat-label"><?php esc_html_e( 'Lecciones', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
            </div>
        </div>

        <!-- Secondary stats -->
        <div class="duo-grid duo-grid-3" style="margin-bottom: 24px;">
            <div class="duo-stat-card">
                <div class="duo-stat-icon">🏆</div>
                <span class="duo-stat-value"><?php echo (int) $achievements; ?> / <?php echo (int) $total_ach; ?></span>
                <span class="duo-stat-label"><?php esc_html_e( 'Logros (', WP_AMSAWAL_TEXTDOMAIN ); ?><?php echo (int) $pct; ?>%)</span>
            </div>
            <div class="duo-stat-card">
                <div class="duo-stat-icon">💰</div>
                <span class="duo-stat-value"><?php echo number_format_i18n( $coins ); ?></span>
                <span class="duo-stat-label"><?php esc_html_e( 'Monedas', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
            </div>
            <div class="duo-stat-card">
                <div class="duo-stat-icon">📍</div>
                <span class="duo-stat-value" style="font-size:18px;"><?php echo $next_streak_label ? esc_html( $next_streak_label ) : '—'; ?></span>
            </div>
        </div>

        <!-- Quick links -->
        <div class="duo-card" style="text-align:center;margin-bottom: 24px;">
            <h3 style="margin:0 0 12px;"><?php esc_html_e( 'Acciones rápidas', WP_AMSAWAL_TEXTDOMAIN ); ?></h3>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url( site_url( '/logros/' ) ); ?>" class="duo-btn duo-btn--primary">🏆 <?php esc_html_e( 'Ver mis logros', WP_AMSAWAL_TEXTDOMAIN ); ?></a>
                <a href="<?php echo esc_url( site_url( '/tienda/' ) ); ?>" class="duo-btn duo-btn--warning">🛒 <?php esc_html_e( 'Ir a la tienda', WP_AMSAWAL_TEXTDOMAIN ); ?></a>
                <a href="<?php echo esc_url( site_url( '/liderazgos/' ) ); ?>" class="duo-btn duo-btn--ghost">🏆 <?php esc_html_e( 'Ver clasificación', WP_AMSAWAL_TEXTDOMAIN ); ?></a>
            </div>
        </div>

        <!-- H5P Activities section -->
        <?php if ( ! empty( $h5p_html ) ) : ?>
            <?php echo $h5p_html; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

