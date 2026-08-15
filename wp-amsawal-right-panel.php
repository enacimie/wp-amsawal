<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Right Panel — Duolingo-style sidebar with Super, Leagues, Daily Challenges
 * 
 * Usage: Include in main template where right panel should appear
 * Hooks: wp_amsawal_right_panel
 */

add_action( 'wp_amsawal_right_panel', 'wp_amsawal_render_right_panel' );
function wp_amsawal_render_right_panel() {
    if (!is_user_logged_in()) return;
    
    $userid = get_current_user_id();
    ?>
    
    <aside class="duo-right-panel" aria-label="Panel derecho">
        
        <!-- SUPER DUOLINGO CARD -->
        <div class="duo-right-card duo-super-card">
            <div class="duo-super-header">
                <span class="duo-super-badge">SUPER</span>
                <span class="duo-super-icon">🌐</span>
            </div>
            <h3 class="duo-super-title">Prueba Super gratis</h3>
            <p class="duo-super-desc">
                Sin anuncios, con prácticas personalizadas y sin límites para el nivel Legendario.
            </p>
            <button class="duo-super-btn">
                PROBAR 1 SEMANA GRATIS
            </button>
        </div>
        
        <!-- LEAGUE CARD -->
        <div class="duo-right-card duo-league-card">
            <div class="duo-league-header">
                <h3>División Esmeralda</h3>
                <a href="<?php echo esc_url(site_url('/liderazgos/')); ?>" class="duo-league-link">
                    VER LIGAS
                </a>
            </div>
            <div class="duo-league-body">
                <div class="duo-league-icon">⭐</div>
                <div class="duo-league-info">
                    <p class="duo-league-rank">Estás en el puesto <strong>#8</strong></p>
                    <p class="duo-league-xp">Esta semana has ganado <strong>273 XP</strong></p>
                </div>
            </div>
        </div>
        
        <!-- DAILY CHALLENGES CARD -->
        <div class="duo-right-card duo-challenges-card">
            <div class="duo-challenges-header">
                <h3>Desafíos del día</h3>
                <a href="#" class="duo-challenges-all">VER TODOS</a>
            </div>
            <div class="duo-challenges-list">
                
                <!-- Challenge 1: Gain XP -->
                <div class="duo-challenge-item">
                    <div class="duo-challenge-icon duo-challenge-xp">⚡</div>
                    <div class="duo-challenge-body">
                        <h4>Gana 30 XP</h4>
                        <div class="duo-challenge-progress">
                            <div class="duo-challenge-bar">
                                <div class="duo-challenge-bar-fill" style="width: 0%;"></div>
                            </div>
                            <span class="duo-challenge-count">0 / 30</span>
                        </div>
                    </div>
                    <div class="duo-challenge-reward">🏆</div>
                </div>
                
                <!-- Challenge 2: Learn time -->
                <div class="duo-challenge-item">
                    <div class="duo-challenge-icon duo-challenge-time">⏱️</div>
                    <div class="duo-challenge-body">
                        <h4>Aprende durante 10 minutos</h4>
                        <div class="duo-challenge-progress">
                            <div class="duo-challenge-bar">
                                <div class="duo-challenge-bar-fill" style="width: 0%;"></div>
                            </div>
                            <span class="duo-challenge-count">0 / 10</span>
                        </div>
                    </div>
                    <div class="duo-challenge-reward">🏆</div>
                </div>
                
                <!-- Challenge 3: Combo bonus -->
                <div class="duo-challenge-item">
                    <div class="duo-challenge-icon duo-challenge-combo">⚡</div>
                    <div class="duo-challenge-body">
                        <h4>Obtén 20 XP en bonos por combo</h4>
                        <div class="duo-challenge-progress">
                            <div class="duo-challenge-bar">
                                <div class="duo-challenge-bar-fill" style="width: 0%;"></div>
                            </div>
                            <span class="duo-challenge-count">0 / 20</span>
                        </div>
                    </div>
                    <div class="duo-challenge-reward">🏆</div>
                </div>
                
            </div>
        </div>
        
    </aside>
    
    <?php
}
