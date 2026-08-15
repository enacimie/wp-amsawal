<?php
if (!defined('ABSPATH')) exit;

function wp_amsawal_get_review_items($user_id) {
    $mastery = get_user_meta($user_id, '_wp_amsawal_item_mastery', true);
    if (!is_array($mastery)) return [];

    $items = [];
    $now = time();

    foreach ($mastery as $key => $score) {
        if (!is_numeric($score)) continue;
        $score = (float)$score;
        if ($score >= 1.0) continue;

        $priority = 'low';
        if ($score < 0.3) $priority = 'high';
        elseif ($score < 0.6) $priority = 'medium';

        $schedule = get_user_meta($user_id, '_wp_amsawal_item_schedule', true);
        $last_review = 0;
        if (isset($schedule[$key])) {
            if (is_array($schedule[$key]) && isset($schedule[$key]['next_review'])) {
                $last_review = (int)$schedule[$key]['next_review'];
            } elseif (is_numeric($schedule[$key])) {
                $last_review = (int)$schedule[$key];
            }
        }
        $days_since = ($now - $last_review) / 86400;
        $time_bonus = min($days_since / 7, 2.0);

        $final_score = (1.0 - $score) * (1.0 + $time_bonus);

        $items[] = [
            'key' => $key,
            'mastery' => $score,
            'priority' => $priority,
            'days_since' => $days_since,
            'time_bonus' => $time_bonus,
            'final_score' => $final_score,
        ];
    }

    usort($items, function($a, $b) {
        return $b['final_score'] <=> $a['final_score'];
    });

    return $items;
}

function wp_amsawal_get_lesson_scores($user_id) {
    global $wpdb;

    $scores = [];
    $table = $wpdb->prefix . 'h5p_results';
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT content_id, MAX(score) as best_score, MAX(max_score) as max_score, MAX(finished) as last_attempt
         FROM {$table}
         WHERE user_id = %d
         GROUP BY content_id
         HAVING best_score > 0",
        $user_id
    ));

    foreach ($results as $r) {
        $lesson_post = $wpdb->get_row($wpdb->prepare(
            "SELECT p.ID, p.post_title, pm.meta_value as lesson_num
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'wp_amsawal_mb_lesson'
             WHERE p.post_status = 'publish'
             AND p.post_content LIKE %s
             LIMIT 1",
            '%[h5p id="' . $r->content_id . '"]%'
        ));

        if ($lesson_post) {
            $scores[] = [
                'lesson_id' => $lesson_post->ID,
                'lesson_num' => (int)$lesson_post->lesson_num,
                'title' => $lesson_post->post_title,
                'content_id' => $r->content_id,
                'score' => (int)$r->best_score,
                'max_score' => (int)$r->max_score,
                'percentage' => round(($r->best_score / $r->max_score) * 100),
                'last_attempt' => (int)$r->last_attempt,
            ];
        }
    }

    usort($scores, function($a, $b) {
        return $a['lesson_num'] <=> $b['lesson_num'];
    });

    return $scores;
}

function wp_amsawal_generate_review_session($user_id, $count = 7) {
    global $wpdb;

    $review_items = wp_amsawal_get_review_items($user_id);
    if (empty($review_items)) return null;

    $selected = array_slice($review_items, 0, $count);

    $questions = [];
    foreach ($selected as $item) {
        $lesson_post = null;
        $content_id = null;

        if (strpos($item['key'], 'h5p-') === 0) {
            $content_id = (int)str_replace('h5p-', '', $item['key']);
        }

        if ($content_id) {
            $lesson_post = $wpdb->get_row($wpdb->prepare(
                "SELECT p.ID, pm.meta_value as lesson_num
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'wp_amsawal_mb_lesson'
                 WHERE p.post_status = 'publish'
                 AND p.post_content LIKE %s
                 LIMIT 1",
                '%[h5p id="' . $content_id . '"]%'
            ));
        }

        if (!$lesson_post) {
            $lessons = $wpdb->get_results(
                "SELECT p.ID, pm.meta_value as lesson_num
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'wp_amsawal_mb_lesson'
                 WHERE p.post_status = 'publish'
                 AND pm.meta_value IS NOT NULL
                 ORDER BY CAST(pm.meta_value AS UNSIGNED)
                 LIMIT 19"
            );

            foreach ($lessons as $lesson) {
                $bank_key = '_wp_amsawal_ai_' . $lesson->ID . '_multiple-choice_0';
                $bank_data = get_post_meta($lesson->ID, $bank_key, true);
                if ($bank_data) {
                    $lesson_post = $lesson;
                    break;
                }
            }
        }

        if (!$lesson_post) continue;

        $bank_key = '_wp_amsawal_ai_' . $lesson_post->ID . '_multiple-choice_0';
        $bank_data = get_post_meta($lesson_post->ID, $bank_key, true);

        if (!$bank_data) continue;

        $bank = json_decode($bank_data, true);
        if (!$bank || !isset($bank['questions'])) continue;

        $random_question = $bank['questions'][array_rand($bank['questions'])];

        $questions[] = [
            'lesson_id' => $lesson_post->ID,
            'lesson_num' => (int)$lesson_post->lesson_num,
            'question' => $random_question['question'],
            'options' => $random_question['options'],
            'item_key' => $item['key'],
            'mastery' => $item['mastery'],
            'priority' => $item['priority'],
        ];
    }

    return [
        'questions' => $questions,
        'total' => count($questions),
    ];
}

function wp_amsawal_update_review_mastery($user_id, $item_key, $correct) {
    $mastery = get_user_meta($user_id, '_wp_amsawal_item_mastery', true);
    if (!is_array($mastery)) $mastery = [];

    $current = isset($mastery[$item_key]) && is_numeric($mastery[$item_key]) ? (float)$mastery[$item_key] : 0.5;

    if ($correct) {
        $mastery[$item_key] = min(1.0, $current + 0.1);
    } else {
        $mastery[$item_key] = max(0.0, $current - 0.2);
    }

    update_user_meta($user_id, '_wp_amsawal_item_mastery', $mastery);

    $schedule = get_user_meta($user_id, '_wp_amsawal_item_schedule', true);
    if (!is_array($schedule)) $schedule = [];

    $existing = isset($schedule[$item_key]) && is_array($schedule[$item_key]) ? $schedule[$item_key] : [
        'repetitions' => 0,
        'interval' => 1,
        'easiness_factor' => 2.5,
        'next_review' => 0,
    ];

    if ($correct) {
        $existing['repetitions'] = (int)$existing['repetitions'] + 1;
        $existing['interval'] = max(1, (int)$existing['interval'] + 1);
        $existing['easiness_factor'] = min(3.0, (float)$existing['easiness_factor'] + 0.1);
    } else {
        $existing['repetitions'] = 0;
        $existing['interval'] = 1;
        $existing['easiness_factor'] = max(1.3, (float)$existing['easiness_factor'] - 0.2);
    }

    $existing['next_review'] = time() + ((int)$existing['interval'] * 86400);
    $schedule[$item_key] = $existing;
    update_user_meta($user_id, '_wp_amsawal_item_schedule', $schedule);
}

function wp_amsawal_review_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="duo-page"><div class="duo-page-header"><div class="duo-page-header-text"><h1 class="duo-page-title">Repaso Inteligente</h1></div></div><p>Debes iniciar sesión para acceder al repaso.</p></div>';
    }

    wp_enqueue_script('wp-amsawal-review');
    wp_enqueue_style('wp-amsawal-review');

    $user_id = get_current_user_id();
    $review_items = wp_amsawal_get_review_items($user_id);
    $lesson_scores = wp_amsawal_get_lesson_scores($user_id);

    $high_priority = array_filter($review_items, function($i) { return $i['priority'] === 'high'; });
    $medium_priority = array_filter($review_items, function($i) { return $i['priority'] === 'medium'; });

    $nonce = wp_create_nonce('wp_amsawal_review');

    ob_start();
    ?>
    <div class="duo-page">
        <div class="duo-page-header">
            <div class="duo-page-header-text">
                <h1 class="duo-page-title">Repaso Inteligente</h1>
                <p class="duo-page-subtitle">Refuerza lo aprendido priorizando los temas que más te cuestan</p>
            </div>
        </div>

        <div class="duo-review-stats">
            <div class="duo-stat-card">
                <div class="duo-stat-icon" aria-hidden="true"><?php echo wp_amsawal_nav_icon('target'); ?></div>
                <div class="duo-stat-content">
                    <div class="duo-stat-value"><?php echo count($review_items); ?></div>
                    <div class="duo-stat-label">Items por repasar</div>
                </div>
            </div>
            <div class="duo-stat-card duo-stat-card--urgent">
                <div class="duo-stat-icon" aria-hidden="true"><?php echo wp_amsawal_nav_icon('star'); ?></div>
                <div class="duo-stat-content">
                    <div class="duo-stat-value"><?php echo count($high_priority); ?></div>
                    <div class="duo-stat-label">Prioridad alta</div>
                </div>
            </div>
            <div class="duo-stat-card duo-stat-card--medium">
                <div class="duo-stat-icon" aria-hidden="true"><?php echo wp_amsawal_nav_icon('warning'); ?></div>
                <div class="duo-stat-content">
                    <div class="duo-stat-value"><?php echo count($medium_priority); ?></div>
                    <div class="duo-stat-label">Prioridad media</div>
                </div>
            </div>
            <div class="duo-stat-card">
                <div class="duo-stat-icon" aria-hidden="true"><?php echo wp_amsawal_nav_icon('book'); ?></div>
                <div class="duo-stat-content">
                    <div class="duo-stat-value"><?php echo count($lesson_scores); ?></div>
                    <div class="duo-stat-label">Lecciones completadas</div>
                </div>
            </div>
        </div>

        <div class="duo-review-actions">
            <button class="duo-btn duo-btn--primary duo-btn--lg" id="start-quick-review" data-nonce="<?php echo esc_attr($nonce); ?>">
                <span aria-hidden="true"><?php echo wp_amsawal_nav_icon('flash'); ?></span>
                Repaso Rápido (7 preguntas)
            </button>
        </div>

        <div class="duo-review-lessons">
            <h2>Lecciones Superadas</h2>
            <?php if (empty($lesson_scores)): ?>
                <div class="duo-empty-state">
                    <p>Aún no has completado ninguna lección. ¡Sigue aprendiendo!</p>
                </div>
            <?php else: ?>
                <div class="duo-lessons-grid">
                    <?php foreach ($lesson_scores as $lesson): ?>
                        <div class="duo-lesson-card" data-lesson-id="<?php echo $lesson['lesson_id']; ?>">
                            <div class="duo-lesson-card-header">
                                <span class="duo-lesson-num">L<?php echo str_pad($lesson['lesson_num'], 2, '0', STR_PAD_LEFT); ?></span>
                                <span class="duo-lesson-score <?php echo $lesson['percentage'] >= 80 ? 'duo-lesson-score--high' : ($lesson['percentage'] >= 50 ? 'duo-lesson-score--medium' : 'duo-lesson-score--low'); ?>">
                                    <?php echo $lesson['percentage']; ?>%
                                </span>
                            </div>
                            <div class="duo-lesson-card-title"><?php echo esc_html($lesson['title']); ?></div>
                            <button class="duo-btn duo-btn--sm duo-btn--outline" data-review-lesson="<?php echo $lesson['lesson_id']; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
                                Repasar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="duo-review-modal" id="review-modal" role="dialog" aria-modal="true" aria-label="Sesión de repaso" style="display: none;">
        <div class="duo-review-modal-backdrop"></div>
        <div class="duo-review-modal-content">
            <button class="duo-review-modal-close" id="close-review-modal" aria-label="Cerrar">&times;</button>
            <div class="duo-review-modal-header">
                <h2>Sesión de Repaso</h2>
                <div class="duo-review-progress-bar">
                    <div class="duo-review-progress-fill" id="review-progress-fill"></div>
                </div>
                <div class="duo-review-progress-text">
                    <span id="review-current">1</span> / <span id="review-total">7</span>
                </div>
            </div>
            <div class="duo-review-question" id="review-question"></div>
            <div class="duo-review-options" id="review-options" role="radiogroup" aria-label="Opciones de respuesta"></div>
            <div class="duo-review-feedback" id="review-feedback" style="display: none;"></div>
            <div class="duo-review-actions-modal">
                <button class="duo-btn duo-btn--primary" id="next-question" style="display: none;">
                    Siguiente →
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('amsawal_review', 'wp_amsawal_review_shortcode');

function wp_amsawal_review_enqueue() {
    if (is_page('repaso')) {
        wp_register_script(
            'wp-amsawal-review',
            plugin_dir_url(__FILE__) . 'js/wp-amsawal-review.js',
            [],
            '1.0.0',
            true
        );
        wp_localize_script('wp-amsawal-review', 'wpAmsawalReview', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);

        wp_register_style(
            'wp-amsawal-review',
            plugin_dir_url(__FILE__) . 'css/modules/_review.css',
            [],
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'wp_amsawal_review_enqueue');

function wp_amsawal_ajax_start_review() {
    check_ajax_referer('wp_amsawal_review', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado']);
    }

    $user_id = get_current_user_id();
    $lesson_id = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : null;

    if ($lesson_id) {
        global $wpdb;
        $bank_key = '_wp_amsawal_ai_' . $lesson_id . '_multiple-choice_0';
        $bank_data = get_post_meta($lesson_id, $bank_key, true);

        if (!$bank_data) {
            wp_send_json_error(['message' => 'No se encontró el contenido de la lección']);
        }

        $bank = json_decode($bank_data, true);
        if (!$bank || !isset($bank['questions'])) {
            wp_send_json_error(['message' => 'Formato de bank inválido']);
        }

        $questions = $bank['questions'];
        shuffle($questions);
        $selected = array_slice($questions, 0, 7);

        $lesson_num = get_post_meta($lesson_id, 'wp_amsawal_mb_lesson', true);

        wp_send_json_success([
            'questions' => array_map(function($q) use ($lesson_id, $lesson_num) {
                return [
                    'lesson_id' => $lesson_id,
                    'lesson_num' => (int)$lesson_num,
                    'question' => $q['question'],
                    'options' => $q['options'],
                ];
            }, $selected),
            'total' => count($selected),
        ]);
    } else {
        $session = wp_amsawal_generate_review_session($user_id, 7);
        if (!$session) {
            wp_send_json_error(['message' => 'No hay items para repasar']);
        }
        wp_send_json_success($session);
    }
}
add_action('wp_ajax_amsawal_start_review', 'wp_amsawal_ajax_start_review');

function wp_amsawal_ajax_submit_review() {
    check_ajax_referer('wp_amsawal_review', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado']);
    }

    $user_id = get_current_user_id();
    $item_key = sanitize_text_field($_POST['item_key'] ?? '');
    $correct = (bool)($_POST['correct'] ?? false);

    if (!$item_key) {
        wp_send_json_error(['message' => 'Datos inválidos']);
    }

    wp_amsawal_update_review_mastery($user_id, $item_key, $correct);

    wp_send_json_success(['updated' => true]);
}
add_action('wp_ajax_amsawal_submit_review', 'wp_amsawal_ajax_submit_review');

function wp_amsawal_create_review_page() {
    $existing = get_page_by_path('repaso');
    if ($existing) return $existing->ID;

    $page_id = wp_insert_post([
        'post_title' => 'Repaso Inteligente',
        'post_content' => '[amsawal_review]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'repaso',
        'post_author' => 1,
    ]);

    return $page_id;
}
register_activation_hook(__FILE__, 'wp_amsawal_create_review_page');
