<?php
/**
 * Amsawal Streaks — Advanced streak management
 *
 * Handles daily streak tracking, streak freezes, multipliers,
 * and streak-based rewards.
 *
 * @package Amsawal
 * @since   0.0.3-pre
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*───────────────────────────────────────────────────────────────────────
 * 1. Streak calculation and update
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_update_streak( $user_id ) {
    $today = current_time( 'Y-m-d' );
    $last_activity = get_user_meta( $user_id, '_wp_amsawal_last_activity_date', true );
    $streak = (int) get_user_meta( $user_id, '_wp_amsawal_streak_days', true );

    if ( $last_activity === $today ) {
        // Already active today, just return current streak
        return $streak;
    }

    // Use WP timezone for yesterday calculation (Docker-safe)
    $yesterday = date( 'Y-m-d', strtotime( '-1 days', current_time( 'timestamp' ) ) );

    if ( $last_activity === $yesterday || empty( $last_activity ) ) {
        // Consecutive day or first activity
        $streak++;
        update_user_meta( $user_id, '_wp_amsawal_streak_days', $streak );

        // Award bonus coins for milestone streaks
        if ( in_array( $streak, array( 3, 7, 14, 30, 60, 90, 365 ) ) ) {
            $bonus = wp_amsawal_calculate_streak_bonus( $streak );
            if ( function_exists( 'gamipress_award_points_to_user' ) ) {
                gamipress_award_points_to_user( $user_id, $bonus, 'monedas', array(
                    'admin_id' => 0,
                    'reason'   => sprintf( 'Racha de %d días', $streak ),
                ) );
            }
        }
    } else {
        // Streak broken — check freeze before resetting
        $freeze_used = wp_amsawal_use_streak_freeze( $user_id );
        if ( $freeze_used ) {
            // Freeze protects the streak: keep current streak count
            // but don't increment (user wasn't active yesterday)
        } else {
            $streak = 1;
            update_user_meta( $user_id, '_wp_amsawal_streak_days', $streak );
        }
    }

    update_user_meta( $user_id, '_wp_amsawal_last_activity_date', $today );

    // Store streak history for analytics (cap at 365 days to prevent unbounded growth)
    $streak_history = get_user_meta( $user_id, '_wp_amsawal_streak_history', true );
    if ( ! is_array( $streak_history ) ) {
        $streak_history = array();
    }
    $streak_history[ $today ] = $streak;
    if ( count( $streak_history ) > 365 ) {
        // Keep only the most recent 365 entries
        $streak_history = array_slice( $streak_history, -365, null, true );
    }
    update_user_meta( $user_id, '_wp_amsawal_streak_history', $streak_history );

    return $streak;
}

/*───────────────────────────────────────────────────────────────────────
 * 2. Streak bonus calculator
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_calculate_streak_bonus( $streak_days ) {
    $bonuses = array(
        3   => 10,
        7   => 25,
        14  => 50,
        30  => 100,
        60  => 200,
        90  => 350,
        365 => 1000,
    );
    
    return isset( $bonuses[ $streak_days ] ) ? $bonuses[ $streak_days ] : 0;
}

/*───────────────────────────────────────────────────────────────────────
 * 3. Streak freeze system
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_use_streak_freeze( $user_id ) {
    $freezes = (int) get_user_meta( $user_id, '_wp_amsawal_streak_freezes', true );
    
    if ( $freezes > 0 ) {
        update_user_meta( $user_id, '_wp_amsawal_streak_freezes', $freezes - 1 );
        
        // Mark that freeze was used
        $today = current_time( 'Y-m-d' );
        update_user_meta( $user_id, '_wp_amsawal_freeze_used_date', $today );
        
        return true;
    }
    
    return false;
}

/*───────────────────────────────────────────────────────────────────────
 * 4. Check if streak should be protected by freeze
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_check_streak_freeze( $user_id ) {
    $today = current_time( 'Y-m-d' );
    $last_activity = get_user_meta( $user_id, '_wp_amsawal_last_activity_date', true );
    $freeze_date = get_user_meta( $user_id, '_wp_amsawal_freeze_used_date', true );
    
    // If freeze was used yesterday, protect the streak
    $yesterday = date( 'Y-m-d', strtotime( 'yesterday' ) );
    
    if ( $freeze_date === $yesterday && $last_activity !== $today ) {
        return true; // Streak protected
    }
    
    return false;
}

/*───────────────────────────────────────────────────────────────────────
 * 5. Award streak freeze at milestones
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_award_streak_freeze( $user_id, $streak_days ) {
    // Award freeze every 7 days
    if ( $streak_days > 0 && $streak_days % 7 === 0 ) {
        $freezes = (int) get_user_meta( $user_id, '_wp_amsawal_streak_freezes', true );
        update_user_meta( $user_id, '_wp_amsawal_streak_freezes', $freezes + 1 );
        return true;
    }
    return false;
}

/*───────────────────────────────────────────────────────────────────────
 * 6. Get streak statistics
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_get_streak_stats( $user_id ) {
    $current_streak = (int) get_user_meta( $user_id, '_wp_amsawal_streak_days', true );
    $streak_history = get_user_meta( $user_id, '_wp_amsawal_streak_history', true );
    $freezes = (int) get_user_meta( $user_id, '_wp_amsawal_streak_freezes', true );
    
    $longest_streak = 0;
    $total_days = 0;
    
    if ( is_array( $streak_history ) && ! empty( $streak_history ) ) {
        $longest_streak = max( $streak_history );
        $total_days = count( $streak_history );
    }
    
    return array(
        'current'        => $current_streak,
        'longest'        => $longest_streak,
        'total_days'     => $total_days,
        'freezes'        => $freezes,
        'next_milestone' => wp_amsawal_get_next_streak_milestone( $current_streak ),
    );
}

/*───────────────────────────────────────────────────────────────────────
 * 7. Get next milestone
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_get_next_streak_milestone( $current_streak ) {
    $milestones = array( 3, 7, 14, 30, 60, 90, 365 );
    
    foreach ( $milestones as $milestone ) {
        if ( $current_streak < $milestone ) {
            return array(
                'days'    => $milestone,
                'remaining' => $milestone - $current_streak,
                'bonus'   => wp_amsawal_calculate_streak_bonus( $milestone ),
            );
        }
    }
    
    return null; // All milestones achieved
}

/*───────────────────────────────────────────────────────────────────────
 * 8. AJAX endpoint: get streak info
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_wp_amsawal_get_streak_info', 'wp_amsawal_ajax_get_streak_info' );
function wp_amsawal_ajax_get_streak_info() {
    check_ajax_referer( 'wp_amsawal_track_item', '_ajax_nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Not logged in' ) );
    }
    
    $user_id = get_current_user_id();
    $stats = wp_amsawal_get_streak_stats( $user_id );
    
    wp_send_json_success( $stats );
}
