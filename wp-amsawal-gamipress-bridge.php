<?php
/**
 * GamiPress ↔ Amsawal Bridge
 *
 * Server-side H5P completion → game logic → transient → JS polls for feedback
 *
 * Architecture:
 *
 * 1. H5P saves result → h5p_alter_user_result fires (server-side, authoritative)
 * 2. This bridge runs ALL game logic (mastery, SM-2, lives, rank, XP, streak)
 * 3. Result stored in transient (30s TTL)
 * 4. Frontend JS polls amsawal_get_gamipress_feedback AJAX endpoint
 * 5. JS shows feedback bar with CONTINUE button
 *
 * The old wp_amsawal_track_item AJAX endpoint remains as fallback for
 * content types where h5p_alter_user_result doesn't fire.
 *
 * @package wp-amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*───────────────────────────────────────────────────────────────────────
 * Helper: Get next lesson URL
 *───────────────────────────────────────────────────────────────────────*/

function amsawal_get_next_lesson_url( $user_id = 0, $post_id = 0 ) {
    if ( $post_id > 0 ) {
        $current_post = get_post( $post_id );
    } else {
        $current_post = get_post();
    }
    
    if ( ! $current_post || $current_post->post_type !== 'page' ) {
        return '';
    }

    $siblings = get_pages( array(
        'parent'      => $current_post->post_parent,
        'sort_column' => 'menu_order, ID',
        'sort_order'  => 'ASC',
    ) );

    $lessons = array_values( array_filter( $siblings, function( $p ) {
        return strpos( $p->post_title, 'Módulo' ) !== 0;
    } ) );

    if ( empty( $lessons ) ) {
        return '';
    }

    foreach ( $lessons as $index => $sibling ) {
        if ( (int) $sibling->ID === (int) $current_post->ID ) {
            if ( isset( $lessons[ $index + 1 ] ) ) {
                return get_permalink( $lessons[ $index + 1 ]->ID );
            }
            break;
        }
    }

    return '';
}

/*───────────────────────────────────────────────────────────────────────
 * Helper: Store feedback in transient (merge with existing)
 *───────────────────────────────────────────────────────────────────────*/

function amsawal_store_feedback( $user_id, $feedback_data ) {
    $transient_key = 'amsawal_feedback_' . $user_id;

    $existing = get_transient( $transient_key );
    if ( $existing && is_array( $existing ) ) {
        $feedback_data = array_merge( $existing, $feedback_data );
    }

    set_transient( $transient_key, $feedback_data, 30 );
}

/*───────────────────────────────────────────────────────────────────────
 * MAIN LISTENER: H5P result saved → full game logic → transient
 *
 * Hook: h5p_alter_user_result (4 args via do_action_ref_array)
 * Fires BEFORE the result is saved to the database.
 *───────────────────────────────────────────────────────────────────────*/

function amsawal_on_h5p_result_saved( $data, $result_id, $content_id, $user_id ) {
    error_log( '[Amsawal Bridge] h5p_alter_user_result fired: content_id=' . $content_id . ', user_id=' . $user_id . ', result_id=' . $result_id );
    
    if ( ! $user_id || $user_id !== get_current_user_id() ) {
        error_log( '[Amsawal Bridge] User ID mismatch or not logged in' );
        return;
    }

    // Get H5P content title
    global $wpdb;
    $title = $wpdb->get_var( $wpdb->prepare(
        "SELECT title FROM {$wpdb->prefix}h5p_contents WHERE id = %d",
        $content_id
    ) );
    
    error_log( '[Amsawal Bridge] H5P content title: ' . $title );

    // Calculate percentage
    $score     = isset( $data['score'] ) ? (int) $data['score'] : 0;
    $max_score = isset( $data['max_score'] ) ? (int) $data['max_score'] : 1;
    $pct       = $max_score > 0 ? round( ( $score / $max_score ) * 100 ) : 0;
    $success   = $pct >= 50;
    
    error_log( '[Amsawal Bridge] Score: ' . $score . '/' . $max_score . ' = ' . $pct . '%, success=' . ( $success ? 'yes' : 'no' ) );

    // Behavior event: an H5P activity was completed (regardless of pass/fail).
    do_action( 'amsawal_quiz_complete', $user_id, $content_id, $score );

    $item_text = 'h5p-' . $content_id;

    // ── 1. Mastery update (+0.15 / -0.40) ──
    wp_amsawal_update_mastery( $user_id, $item_text, $success );

    // ── 2. SM-2 spaced repetition ──
    wp_amsawal_update_sm2( $user_id, $item_text, $success );

    // ── 3. Record completion ──
    $completed = get_user_meta( $user_id, '_amsawal_completed_items', true );
    if ( ! is_array( $completed ) ) {
        $completed = array();
    }

    $is_new = ! in_array( $item_text, $completed, true );
    if ( $is_new ) {
        $completed[] = $item_text;
        update_user_meta( $user_id, '_amsawal_completed_items', $completed );
    }

    // ── 4. GamiPress rank progression ──
    // Subir de nivel SOLO al completar una sección completa del curso.
    // Las ligas (Bronce/Plata/Oro/Platino/Diamante) son la competición semanal separada.
    $rank_up   = false;
    $new_level = 0;

    if ( $success && $is_new && function_exists( 'gamipress_get_user_rank' ) ) {
        $allpages = wp_amsawal_get_courses();

        // Section boundaries — la última lección de cada sección dispara el level up
        $section_endings = array( 4, 8, 11, 15, 19 );

        if ( ! empty( $allpages ) && in_array( $lesson_number, $section_endings, true ) ) {
            foreach ( $allpages as $course_slug ) {
                $rank_type = 'nivel';
                $rank_obj  = gamipress_get_user_rank( $user_id, $rank_type );
                $current   = $rank_obj ? (int) $rank_obj->menu_order : 0;

                // Solo subir si la sección actual completa supera el nivel actual
                // (1ra sección completa → nivel 1, 2da → nivel 2, etc.)
                $expected_level = array_search( $lesson_number, $section_endings, true ) + 1;

                if ( $expected_level > $current ) {
                    if ( function_exists( 'gamipress_update_user_rank' ) ) {
                        // Buscar el rank correspondiente al nivel esperado
                        $target_rank = get_posts( array(
                            'post_type'      => 'nivel',
                            'posts_per_page' => 1,
                            'meta_key'       => '_gamipress_priority',
                            'meta_value'     => $expected_level,
                            'orderby'        => 'menu_order',
                            'order'          => 'ASC',
                        ) );
                        if ( ! empty( $target_rank ) ) {
                            gamipress_update_user_rank( $user_id, $target_rank[0]->ID, 0 );
                        } else {
                            // Fallback al sistema de upgrade
                            gamipress_upgrade_user_to_next_rank( $user_id, $rank_type );
                        }
                    }
                    $rank_up   = true;
                    $new_level = $expected_level;

                    if ( function_exists( 'gamipress_award_points_to_user' ) ) {
                        gamipress_award_points_to_user( $user_id, 25, 'monedas', array(
                            'admin_id' => 0,
                            'reason'   => sprintf( 'Sección %d completada: %s', $expected_level, $course_slug ),
                        ) );
                    }

                    do_action( 'amsawal_level_up', $user_id, $new_level );
                }
            }
        }
    }

    // ── 6. XP + streak + achievements ──
    $xp_earned = 0;
    $new_streak = 0;
    $is_repeat = ! $is_new;  // Track if this is a repeat attempt

    if ( $success ) {
        // Track repeat attempts for statistics
        if ( $is_repeat ) {
            $repeat_count = (int) get_user_meta( $user_id, '_amsawal_repeat_' . $item_text, true );
            update_user_meta( $user_id, '_amsawal_repeat_' . $item_text, $repeat_count + 1 );
        }

        if ( $is_new ) {
            $lessons_completed = (int) get_user_meta( $user_id, 'amsawal_lessons_completed', true );
            update_user_meta( $user_id, 'amsawal_lessons_completed', $lessons_completed + 1 );

            $today = current_time( 'Y-m-d' );
            $last_date = get_user_meta( $user_id, '_amsawal_last_lesson_date', true );
            if ( $last_date !== $today ) {
                update_user_meta( $user_id, '_amsawal_last_lesson_date', $today );
                update_user_meta( $user_id, 'amsawal_lessons_today', 1 );
            } else {
                $lessons_today = (int) get_user_meta( $user_id, 'amsawal_lessons_today', true );
                update_user_meta( $user_id, 'amsawal_lessons_today', $lessons_today + 1 );
            }

            $lesson_xp = 10;
            if ( $pct >= 100 ) {
                $lesson_xp += 10;
            } elseif ( $pct >= 90 ) {
                $lesson_xp += 5;
            }
        } else {
            // Repeat attempt: reduced XP (1-3 XP based on score)
            $lesson_xp = 1;
            if ( $pct >= 100 ) {
                $lesson_xp = 3;
            } elseif ( $pct >= 90 ) {
                $lesson_xp = 2;
            }
        }

        amsawal_award_xp( $user_id, $lesson_xp, 'Lección completada: ' . $item_text . ( $is_repeat ? ' (repaso)' : '' ) );
        $xp_earned = $lesson_xp;

        $new_streak = wp_amsawal_update_streak( $user_id );

        if ( function_exists( 'wp_amsawal_award_streak_freeze' ) ) {
            wp_amsawal_award_streak_freeze( $user_id, $new_streak );
        }

        do_action( 'amsawal_lesson_complete', $user_id, $is_repeat ? 1 : 0 );
        do_action( 'amsawal_activity_tracked', $user_id, 'lesson_complete', array(
            'item_text' => $item_text,
            'score'     => $pct,
            'is_repeat' => $is_repeat,
        ) );

        // Section completion achievement hook
        if ( $success && $is_new && $lesson_number > 0 ) {
            $section_endings = array( 4, 8, 11, 15, 19 );
            if ( in_array( $lesson_number, $section_endings, true ) ) {
                $section_num = array_search( $lesson_number, $section_endings, true ) + 1;
                do_action( 'amsawal_section_complete', $user_id, $section_num );
            }
        }

        // Streak updated hook
        do_action( 'amsawal_streak_updated', $user_id, $new_streak );

        // Mastery updated hook
        do_action( 'amsawal_mastery_updated', $user_id );

        // Extended lesson complete with time + duration
        do_action( 'amsawal_lesson_complete_extended', $user_id, $is_repeat ? 1 : 0, $pct, 0, current_time( 'timestamp' ) );

        // Perfect score hook
        if ( $pct >= 100 ) {
            do_action( 'amsawal_perfect_score', $user_id );
        }
    }

    // ── 7. Invalidate leaderboard cache ──
    if ( function_exists( 'wp_amsawal_invalidate_leaderboard_cache' ) ) {
        wp_amsawal_invalidate_leaderboard_cache( 'monedas' );
    } else {
        delete_transient( 'wp_amsawal_leaderboard_meta_v1' );
    }

    // ── 8. Get current coins from GamiPress ──
    $coins = function_exists( 'gamipress_get_user_points' )
        ? (int) gamipress_get_user_points( $user_id, 'monedas' )
        : 0;

    // ── 9. Resolve next lesson URL ──
    // Find the page that contains this H5P content
    $page_id = 0;
    $lesson_number = 0;
    $page_query = $wpdb->get_row( $wpdb->prepare(
        "SELECT ID, meta_value as lesson_num FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'wp_amsawal_mb_lesson'
         WHERE p.post_content LIKE %s 
         AND p.post_type = 'page' 
         AND p.post_status = 'publish' 
         LIMIT 1",
        '%[h5p id="' . $content_id . '"%'
    ) );
    
    if ( $page_query ) {
        $page_id = (int) $page_query->ID;
        $lesson_number = (int) $page_query->lesson_num;
        error_log( '[Amsawal Bridge] Found page ID: ' . $page_id . ', lesson: ' . $lesson_number . ' for H5P content: ' . $content_id );
    }
    
    $next_lesson_url = amsawal_get_next_lesson_url( $user_id, $page_id );
    
    // ── 10. Check if section completed ──
    $section_completed = null;
    if ( $success && $lesson_number > 0 ) {
        // Section boundaries
        $section_boundaries = array(
            4 => array('title' => 'Sección 1', 'desc' => 'Alfabeto y Fonología'),
            8 => array('title' => 'Sección 2', 'desc' => 'Saludos y Presentaciones'),
            11 => array('title' => 'Sección 3', 'desc' => 'Números y Tiempo'),
            15 => array('title' => 'Sección 4', 'desc' => 'Familia y Personas'),
            19 => array('title' => 'Sección 5', 'desc' => 'Adjetivos y Descripciones'),
        );
        
        if ( isset( $section_boundaries[ $lesson_number ] ) ) {
            $section_completed = $section_boundaries[ $lesson_number ];
        }
    }

    // ── 10. Store feedback in transient for JS to pick up ──
    $feedback_data = array(
        'type'            => 'h5p_complete',
        'title'           => $title ? wp_strip_all_tags( $title ) : __( 'Actividad completada', 'wp-amsawal' ),
        'pct'             => $pct,
        'score'           => $score,
        'max_score'       => $max_score,
        'xp_earned'       => $xp_earned,
        'coins'           => $coins,
        'rank_up'         => $rank_up,
        'new_level'       => $new_level,
        'is_new'          => $is_new,
        'new_streak'      => $new_streak,
        'completed'       => count( $completed ),
        'next_lesson_url' => $next_lesson_url,
        'section_completed' => $section_completed,
        'timestamp'       => current_time( 'timestamp' ),
    );
    
    error_log( '[Amsawal Bridge] Storing feedback in transient: ' . json_encode( $feedback_data ) );

    amsawal_store_feedback( $user_id, $feedback_data );
    
    error_log( '[Amsawal Bridge] Feedback stored successfully for user ' . $user_id );
}
add_action( 'h5p_alter_user_result', 'amsawal_on_h5p_result_saved', 10, 4 );

/*───────────────────────────────────────────────────────────────────────
 * Listener: GamiPress achievement awarded (enriches transient)
 *
 * Real hook: gamipress_award_achievement (5 args)
 * @param int    $user_id
 * @param int    $achievement_id
 * @param string $trigger
 * @param int    $site_id
 * @param array  $args
 *───────────────────────────────────────────────────────────────────────*/

function amsawal_on_achievement_earned( $user_id, $achievement_id, $trigger, $site_id, $args ) {
    if ( $user_id !== get_current_user_id() ) {
        return;
    }

    $achievement = get_post( $achievement_id );
    if ( ! $achievement ) {
        return;
    }

    $feedback_data = array(
        'achievement_title' => $achievement->post_title,
        'next_lesson_url'   => amsawal_get_next_lesson_url( $user_id ),
    );

    amsawal_store_feedback( $user_id, $feedback_data );
}
add_action( 'gamipress_award_achievement', 'amsawal_on_achievement_earned', 10, 5 );

/*───────────────────────────────────────────────────────────────────────
 * Listener: GamiPress rank updated (enriches transient)
 *
 * Real hook: gamipress_update_user_rank (5 args)
 * @param int      $user_id
 * @param WP_Post  $new_rank
 * @param WP_Post  $old_rank
 * @param int      $admin_id
 * @param int      $achievement_id
 *───────────────────────────────────────────────────────────────────────*/

function amsawal_on_rank_up( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) {
    if ( $user_id !== get_current_user_id() ) {
        return;
    }

    if ( ! $new_rank ) {
        return;
    }

    $feedback_data = array(
        'rank_up'         => true,
        'new_level'       => $new_rank->menu_order,
        'rank_title'      => $new_rank->post_title,
        'next_lesson_url' => amsawal_get_next_lesson_url( $user_id ),
    );

    amsawal_store_feedback( $user_id, $feedback_data );
}
add_action( 'gamipress_update_user_rank', 'amsawal_on_rank_up', 10, 5 );

/*───────────────────────────────────────────────────────────────────────
 * AJAX Endpoint: Get pending feedback from transient
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_amsawal_get_gamipress_feedback', 'amsawal_ajax_get_gamipress_feedback' );

function amsawal_ajax_get_gamipress_feedback() {
    error_log( '[Amsawal Bridge AJAX] Endpoint called' );
    
    check_ajax_referer( 'amsawal_gamipress_feedback', '_ajax_nonce' );

    if ( ! is_user_logged_in() ) {
        error_log( '[Amsawal Bridge AJAX] User not logged in' );
        wp_send_json_error( array( 'message' => 'Not logged in' ) );
    }

    $user_id = get_current_user_id();
    $transient_key = 'amsawal_feedback_' . $user_id;
    $feedback_data = get_transient( $transient_key );
    
    error_log( '[Amsawal Bridge AJAX] User ID: ' . $user_id . ', Transient key: ' . $transient_key );
    error_log( '[Amsawal Bridge AJAX] Feedback data: ' . ( $feedback_data ? json_encode( $feedback_data ) : 'NULL' ) );

    if ( $feedback_data && is_array( $feedback_data ) ) {
        delete_transient( $transient_key );

        if ( function_exists( 'gamipress_get_user_points' ) ) {
            $feedback_data['coins'] = (int) gamipress_get_user_points( $user_id, 'monedas' );
        }
        
        error_log( '[Amsawal Bridge AJAX] Returning feedback data' );
        wp_send_json_success( $feedback_data );
    }

    error_log( '[Amsawal Bridge AJAX] No feedback data available' );
    wp_send_json_success( null );
}

/*───────────────────────────────────────────────────────────────────────
 * AJAX Endpoint: Debug — check pipeline status
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_amsawal_debug_feedback', 'amsawal_ajax_debug_feedback' );

function amsawal_ajax_debug_feedback() {
    check_ajax_referer( 'amsawal_gamipress_feedback', '_ajax_nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not logged in' ) );
    }

    $user_id = get_current_user_id();
    $transient_key = 'amsawal_feedback_' . $user_id;
    $pending = get_transient( $transient_key );

    $debug = array(
        'user_id'           => $user_id,
        'pending_feedback'  => $pending ?: null,
        'gamipress_active'  => function_exists( 'gamipress_get_user_points' ),
        'user_points'       => function_exists( 'gamipress_get_user_points' )
            ? (int) gamipress_get_user_points( $user_id, 'monedas' )
            : 0,
        'current_post_id'   => get_the_ID(),
        'hooks_registered'  => array(
            'h5p_alter_user_result'        => has_action( 'h5p_alter_user_result', 'amsawal_on_h5p_result_saved' ),
            'gamipress_award_achievement'   => has_action( 'gamipress_award_achievement', 'amsawal_on_achievement_earned' ),
            'gamipress_update_user_rank'   => has_action( 'gamipress_update_user_rank', 'amsawal_on_rank_up' ),
        ),
    );

    wp_send_json_success( $debug );
}

/*───────────────────────────────────────────────────────────────────────
 * Localize bridge nonce for AJAX
 *───────────────────────────────────────────────────────────────────────*/

function amsawal_localize_gamipress_nonce() {
    if ( ! wp_script_is( 'pure-js-script-js', 'enqueued' ) ) {
        return;
    }

    wp_localize_script( 'pure-js-script-js', 'wpAmsawalBridge', array(
        'nonce'   => wp_create_nonce( 'amsawal_gamipress_feedback' ),
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'userId'  => get_current_user_id(),
        'postId'   => get_the_ID(),
    ) );
}
add_action( 'wp_enqueue_scripts', 'amsawal_localize_gamipress_nonce', 100 );

/*───────────────────────────────────────────────────────────────────────
 * Inject deferred feedback on page load
 *───────────────────────────────────────────────────────────────────────*/

function amsawal_inject_feedback_data() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    $transient_key = 'amsawal_feedback_' . $user_id;
    $feedback_data = get_transient( $transient_key );

    if ( $feedback_data ) {
        delete_transient( $transient_key );

        if ( function_exists( 'gamipress_get_user_points' ) ) {
            $feedback_data['coins'] = (int) gamipress_get_user_points( $user_id, 'monedas' );
        }
    }

    wp_add_inline_script(
        'pure-js-script-js',
        'window.wpAmsawalFeedback = ' . wp_json_encode( $feedback_data ?: null ) . ';',
        'before'
    );
}
add_action( 'wp_enqueue_scripts', 'amsawal_inject_feedback_data', 101 );