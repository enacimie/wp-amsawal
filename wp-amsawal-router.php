<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * wp-amsawal-router.php — Activity completion router
 *
 * Bridges H5P xAPI completion events to GamiPress rank progression,
 * mastery tracking, SM-2 spaced repetition scheduling, lives
 * management, XP awards, and achievement checks.
 *
 * Provides the AJAX handlers the frontend JS calls:
 *   - wp_amsawal_track_item           (mastery + SM-2 + lives + rank-up)
 *   - wp_amsawal_h5p_completion_check (completion feedback)
 *
 * Also provides:
 *   - amsawal_award_xp()         shared XP award via GamiPress points
 *   - wp_amsawal_get_due_items() items due for review (SM-2)
 *
 * @package Amsawal
 * @since   0.0.3-pre
 */

/*───────────────────────────────────────────────────────────────────────
 * 1. amsawal_award_xp() — shared XP award via GamiPress points
 *───────────────────────────────────────────────────────────────────────*/

if ( ! function_exists( 'amsawal_award_xp' ) ) {
    function amsawal_award_xp( $user_id, $xp, $reason = '' ) {
        $xp = max( 0, (int) $xp );
        if ( $xp <= 0 || ! $user_id ) {
            return false;
        }

        if ( function_exists( 'gamipress_award_points_to_user' ) ) {
            gamipress_award_points_to_user( $user_id, $xp, 'monedas', array(
                'admin_id' => 0,
                'reason'   => $reason ? $reason : 'XP earned',
            ) );
        } else {
            // Fallback: store as user meta when GamiPress is not active
            $current = (int) get_user_meta( $user_id, '_amsawal_xp', true );
            update_user_meta( $user_id, '_amsawal_xp', $current + $xp );
        }

        // Track daily XP for quests
        $today = current_time( 'Y-m-d' );
        $daily_xp_key = '_wp_amsawal_xp_today_' . $today;
        $daily_xp = (int) get_user_meta( $user_id, $daily_xp_key, true );
        update_user_meta( $user_id, $daily_xp_key, $daily_xp + $xp );

        do_action( 'amsawal_xp_awarded', $user_id, $xp, $reason );
        return true;
    }
}

/*───────────────────────────────────────────────────────────────────────
 * 2. Mastery algorithm (+0.15 success, -0.40 failure)
 *    Starts at 0.5, capped at [0, 1].
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_update_mastery( $user_id, $item_text, $success ) {
    $mastery = get_user_meta( $user_id, '_wp_amsawal_item_mastery', true );
    if ( ! is_array( $mastery ) ) $mastery = array();

    $current = isset( $mastery[ $item_text ] ) ? (float) $mastery[ $item_text ] : 0.5;

    if ( $success ) {
        $new_mastery = min( 1.0, round( $current + 0.15, 2 ) );
    } else {
        $new_mastery = max( 0.0, round( $current - 0.40, 2 ) );
    }

    $mastery[ $item_text ] = $new_mastery;
    update_user_meta( $user_id, '_wp_amsawal_item_mastery', $mastery );

    return $new_mastery;
}

/*───────────────────────────────────────────────────────────────────────
 * 3. SM-2 scheduling — updates repetition, interval, easiness_factor
 *    following the SuperMemo-2 algorithm.
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_update_sm2( $user_id, $item_text, $success ) {
    $schedule = get_user_meta( $user_id, '_wp_amsawal_item_schedule', true );
    if ( ! is_array( $schedule ) ) $schedule = array();

    $item   = isset( $schedule[ $item_text ] ) ? $schedule[ $item_text ] : array();
    $reps   = isset( $item['repetitions'] ) ? (int) $item['repetitions'] : 0;
    $ef     = isset( $item['easiness_factor'] ) ? (float) $item['easiness_factor'] : 2.5;
    $interval = isset( $item['interval'] ) ? (int) $item['interval'] : 0;

    // SM-2 quality: quality 4 = correct, quality 1 = blackout failure
    $quality = $success ? 4 : 1;

    if ( $quality < 3 ) {
        // Failed: reset repetitions and interval
        $reps     = 0;
        $interval = 1;
    } else {
        // Success: advance
        $reps++;
        if ( $reps === 1 ) {
            $interval = 1;
        } elseif ( $reps === 2 ) {
            $interval = 6;
        } else {
            $interval = round( $interval * $ef );
        }
    }

    // Update easiness factor
    $ef = $ef + ( 0.1 - ( 5 - $quality ) * ( 0.08 + ( 5 - $quality ) * 0.02 ) );
    $ef = max( 1.3, $ef );

    // Compute next review date
    $next_review = current_time( 'timestamp' ) + ( $interval * DAY_IN_SECONDS );

    $schedule[ $item_text ] = array(
        'repetitions'      => $reps,
        'interval'         => $interval,
        'easiness_factor'  => round( $ef, 2 ),
        'next_review'      => $next_review,
    );

    update_user_meta( $user_id, '_wp_amsawal_item_schedule', $schedule );

    return $schedule[ $item_text ];
}

/*───────────────────────────────────────────────────────────────────────
 * 4. wp_amsawal_get_due_items() — items with next_review <= now
 *───────────────────────────────────────────────────────────────────────*/

if ( ! function_exists( 'wp_amsawal_get_due_items' ) ) {
    function wp_amsawal_get_due_items( $user_id ) {
        $schedule = get_user_meta( $user_id, '_wp_amsawal_item_schedule', true );
        if ( ! is_array( $schedule ) ) return array();

        $now     = current_time( 'timestamp' );
        $due     = array();

        foreach ( $schedule as $item_text => $item_data ) {
            if ( isset( $item_data['next_review'] ) && (int) $item_data['next_review'] <= $now ) {
                $due[ $item_text ] = $item_data;
            }
        }

        return $due;
    }
}

/*───────────────────────────────────────────────────────────────────────
 * 5. wp_amsawal_ajax_track_item — H5P activity completion handler
 *    Updates mastery, SM-2 schedule, lives, and GamiPress rank.
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_wp_amsawal_track_item', 'wp_amsawal_ajax_track_item' );
function wp_amsawal_ajax_track_item() {
    check_ajax_referer( 'wp_amsawal_track_item', '_ajax_nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not logged in' ) );
    }

    $user_id   = get_current_user_id();
    $item_text = isset( $_POST['item_text'] ) ? sanitize_text_field( wp_unslash( $_POST['item_text'] ) ) : '';
    $content_id = isset( $_POST['content_id'] ) ? absint( $_POST['content_id'] ) : 0;

    // Verify score from H5P database instead of trusting client-sent values
    $score     = 0;
    $max_score = 1;
    $pct       = 0;
    $success   = false;

    if ( $content_id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT score, max_score FROM {$wpdb->prefix}h5p_results WHERE user_id = %d AND content_id = %d ORDER BY finished DESC LIMIT 1",
            $user_id,
            $content_id
        ) );
        if ( $row ) {
            $score     = max( 0, (float) $row->score );
            $max_score = max( 1, (float) $row->max_score );
            $pct       = (int) round( ( $score / $max_score ) * 100 );
            $success   = $pct >= 50;
        }
    } else {
        // Fallback: use client-sent values only when no content_id available
        $success   = ! empty( $_POST['success'] ) && $_POST['success'] === '1';
        $score     = isset( $_POST['score'] ) ? max( 0, (float) $_POST['score'] ) : 0;
        $max_score = isset( $_POST['max_score'] ) ? max( 1, (float) $_POST['max_score'] ) : 1;
        $pct       = (int) round( ( $score / $max_score ) * 100 );
    }

    if ( empty( $item_text ) ) {
        wp_send_json_error( array( 'message' => 'Missing item_text' ) );
    }

    // ── 1. Update mastery (+0.15 / -0.40) ──
    $new_mastery = wp_amsawal_update_mastery( $user_id, $item_text, $success );

    // ── 2. Update SM-2 schedule (spaced repetition) ──
    $schedule_data = wp_amsawal_update_sm2( $user_id, $item_text, $success );

    // ── 3. Record completion for rank progression ──
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
    $rank_up         = false;
    $new_level       = 0;

    if ( $success && $is_new && function_exists( 'gamipress_get_user_rank' ) ) {
        $allpages = wp_amsawal_get_courses();

        // Section boundaries — la última lección de cada sección dispara el level up
        $section_endings = array( 4, 8, 11, 15, 19 );

        if ( ! empty( $allpages ) && in_array( $lesson_number, $section_endings, true ) ) {
            foreach ( $allpages as $course_slug ) {
                $rank_type = 'nivel';
                $rank_obj  = gamipress_get_user_rank( $user_id, $rank_type );
                $current   = $rank_obj ? (int) $rank_obj->menu_order : 0;

                $expected_level = array_search( $lesson_number, $section_endings, true ) + 1;

                if ( $expected_level > $current ) {
                    if ( function_exists( 'gamipress_update_user_rank' ) ) {
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

    // ── 5b. ALWAYS resolve next lesson URL (not just on rank-up) ──
    $next_lesson_url = '';

    if ( $success && $is_new ) {
        $current_post = get_post();
        if ( $current_post && $current_post->post_type === 'page' ) {
            $siblings = get_pages( array(
                'parent'      => $current_post->post_parent,
                'sort_column' => 'menu_order, ID',
                'sort_order'  => 'ASC',
            ) );

            // Filter out module pages (section dividers, not lessons)
            $lessons = array_values( array_filter( $siblings, function( $p ) {
                return strpos( $p->post_title, 'Módulo' ) !== 0;
            } ) );

            foreach ( $lessons as $index => $child ) {
                if ( (int) $child->ID === (int) $current_post->ID ) {
                    if ( isset( $lessons[ $index + 1 ] ) ) {
                        $next_lesson_url = get_permalink( $lessons[ $index + 1 ]->ID );
                    }
                    break;
                }
            }
        }
    }

    // ── 6. Achievement counters + XP ──
    $xp_earned = 0;
    $new_streak = 0;
    if ( $success && $is_new ) {
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

        // Award XP (scaled by score percentage)
        $lesson_xp = 10;
        // Bonus XP for high scores: 90%+ gets +5, 100% gets +10
        if ( $pct >= 100 ) {
            $lesson_xp += 10;
        } elseif ( $pct >= 90 ) {
            $lesson_xp += 5;
        }
        amsawal_award_xp( $user_id, $lesson_xp, 'Lección completada: ' . $item_text );
        $xp_earned = $lesson_xp;

        // Update streak
        $new_streak = wp_amsawal_update_streak( $user_id );

        // Award streak freeze if milestone
        wp_amsawal_award_streak_freeze( $user_id, $new_streak );

        // Fire data collection actions
        do_action( 'amsawal_lesson_complete', $user_id, 0 );
        do_action( 'amsawal_activity_tracked', $user_id, 'lesson_complete', array(
            'item_text' => $item_text,
            'score'     => $pct,
        ) );
    }

    // ── 7. Invalidate leaderboard cache (granular: always invalidate XP/monedas) ──
    if ( function_exists( 'wp_amsawal_invalidate_leaderboard_cache' ) ) {
        wp_amsawal_invalidate_leaderboard_cache( 'monedas' );
    } else {
        delete_transient( 'wp_amsawal_leaderboard_meta_v1' );
    }

    wp_send_json_success( array(
        'new_mastery'     => $new_mastery,
        'schedule'        => $schedule_data,
        'rank_up'         => $rank_up,
        'new_level'       => $new_level,
        'is_new'          => $is_new,
        'completed'       => count( $completed ),
        'next_lesson_url' => $next_lesson_url,
        'new_streak'      => isset( $new_streak ) ? $new_streak : 0,
        'pct'             => $pct,
        'xp_earned'       => $xp_earned,
    ) );
}

/*───────────────────────────────────────────────────────────────────────
 * 6. wp_amsawal_h5p_completion_check — feedback after H5P completion
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_wp_amsawal_h5p_completion_check', 'wp_amsawal_ajax_h5p_completion_check' );
function wp_amsawal_ajax_h5p_completion_check() {
    check_ajax_referer( 'wp_amsawal_track_item', '_ajax_nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not logged in' ) );
    }

    $user_id    = get_current_user_id();
    $content_id = isset( $_POST['content_id'] ) ? absint( $_POST['content_id'] ) : 0;

    if ( ! $content_id ) {
        wp_send_json_error( array( 'message' => 'Missing content_id' ) );
    }

    // 1) Read the real score H5P recorded in h5p_results
    $pct    = 0;
    $score  = 0;
    $max    = 0;
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT score, max_score FROM {$wpdb->prefix}h5p_results WHERE user_id = %d AND content_id = %d LIMIT 1",
        $user_id,
        $content_id
    ) );
    if ( $row ) {
        $score = (int) $row->score;
        $max   = max( 1, (int) $row->max_score );
        $pct   = (int) round( ( $score / $max ) * 100 );
    }

    // 2) Mark as completed if score >= 50% or result exists
    $completed = get_user_meta( $user_id, '_amsawal_completed_items', true );
    if ( ! is_array( $completed ) ) {
        $completed = array();
    }
    // Use item_text key for consistency with track_item handler
    $is_done = $row && $pct >= 50;
    if ( $is_done ) {
        // Also check for legacy h5p-key entries
        $h5p_key = 'h5p-' . $content_id;
        $already = in_array( $h5p_key, $completed, true ) || count( array_filter( $completed, function( $v ) use ( $content_id ) {
            return strpos( $v, 'h5p-' . $content_id ) === 0;
        } ) ) > 0;
        if ( ! $already ) {
            $completed[] = $h5p_key;
            update_user_meta( $user_id, '_amsawal_completed_items', $completed );
        }
    }

    // 3) Current coins
    $coins = function_exists( 'gamipress_get_user_points' )
        ? (int) gamipress_get_user_points( $user_id, 'monedas' )
        : 0;

    wp_send_json_success( array(
        'pct'       => $pct,
        'completed' => $is_done,
        'coins'     => $coins,
        'score'     => $score,
        'max_score' => $max,
    ) );
}
