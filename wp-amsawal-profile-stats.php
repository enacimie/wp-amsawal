<?php
/**
 * Amsawal Profile Stats Helper
 *
 * Helper function to retrieve user profile statistics
 *
 * @package amsawal
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get profile statistics for a user
 *
 * @param int $user_id User ID
 * @return array Profile statistics
 */
function amsawal_get_profile_stats( $user_id ) {
    $stats = array(
        'streak'                => 0,
        'coins'                 => 0,
        'level'                 => 0,
        'lessons_completed'     => 0,
        'achievements_count'    => 0,
        'xp'                    => 0,
        'achievements'          => array(),
        'recent_activity'       => array(),
        'rank_label'            => '',
        'next_milestone'        => null,
    );

    if ( ! $user_id ) {
        return $stats;
    }

    // Streak (days) — uses the unified meta key from wp-amsawal-streaks.php
    $stats['streak'] = (int) get_user_meta( $user_id, '_wp_amsawal_streak_days', true );

    // Next streak milestone
    if ( function_exists( 'wp_amsawal_get_next_streak_milestone' ) ) {
        $stats['next_milestone'] = wp_amsawal_get_next_streak_milestone( $stats['streak'] );
    }

    // Coins (GamiPress points)
    if ( function_exists( 'gamipress_get_user_points' ) ) {
        $stats['coins'] = intval( gamipress_get_user_points( $user_id, 'monedas' ) );
    } else {
        $stats['coins'] = (int) get_user_meta( $user_id, '_amsawal_xp', true );
    }

    // Level / rank from GamiPress
    $courses = function_exists( 'wp_amsawal_get_courses' ) ? wp_amsawal_get_courses() : array();
    if ( ! empty( $courses ) && function_exists( 'gamipress_get_user_rank' ) ) {
        $rank = gamipress_get_user_rank( $user_id, 'nivel' );
        $stats['level'] = $rank ? (int) $rank->menu_order : 0;
        $stats['rank_label'] = $rank ? $rank->post_title : '';
    } else {
        $stats['level'] = (int) get_user_meta( $user_id, 'amsawal_level', true );
    }

    // Lessons completed
    $stats['lessons_completed'] = (int) get_user_meta( $user_id, 'amsawal_lessons_completed', true );

    // Achievements (custom Amsawal achievement system)
    $achievements = get_user_meta( $user_id, 'amsawal_achievements', true );
    if ( is_array( $achievements ) ) {
        $stats['achievements_count'] = count( $achievements );
        $stats['achievements'] = $achievements;
    }

    // XP (from GamiPress or user meta)
    if ( function_exists( 'gamipress_get_user_points' ) ) {
        $stats['xp'] = intval( gamipress_get_user_points( $user_id, 'xp' ) );
    } else {
        $stats['xp'] = (int) get_user_meta( $user_id, '_amsawal_xp', true );
    }

    // Recent activity (last 10 items)
    $activity = get_user_meta( $user_id, 'amsawal_recent_activity', true );
    if ( is_array( $activity ) && ! empty( $activity ) ) {
        $stats['recent_activity'] = array_slice( array_reverse( $activity ), 0, 10 );
    }

    return apply_filters( 'amsawal_profile_stats', $stats, $user_id );
}
