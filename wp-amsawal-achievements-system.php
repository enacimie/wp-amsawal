<?php
/**
 * Achievements System for Amsawal
 *
 * Replaces the basic custom-meta system in wp-amsawal-achievements.php with a
 * GamiPress-based approach that supports many trigger types and a coin shop.
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*───────────────────────────────────────────────────────────────────────
 * 1. CATALOG — Maps slug → achievement post ID (cached via transient)
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Get all achievement posts from the 'logros' achievement-type.
 *
 * @param bool $force_refresh Force re-fetch from DB.
 * @return array Slug => WP_Post
 */
function amsawal_get_achievements_catalog( $force_refresh = false ) {
    $cache_key = 'amsawal_achievements_catalog_v1';
    $cached    = $force_refresh ? false : get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    $all = get_posts( array(
        'post_type'      => 'achievement',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    $catalog = array();
    foreach ( $all as $ach ) {
        $type_id = (int) get_post_meta( $ach->ID, '_gamipress_achievement_type', true );
        if ( ! $type_id ) {
            continue;
        }
        $type_post = get_post( $type_id );
        // Match by slug OR ID (we accept both "logros" and "logro-type")
        if ( $type_post && in_array( $type_post->post_name, array( 'logros', 'logro-type' ), true ) ) {
            $catalog[ $ach->post_name ] = $ach;
        }
    }

    set_transient( $cache_key, $catalog, HOUR_IN_SECONDS );
    return $catalog;
}

/**
 * Find achievement ID by slug.
 *
 * @param string $slug Post name of the achievement.
 * @return int Post ID or 0.
 */
function amsawal_get_achievement_id( $slug ) {
    $catalog = amsawal_get_achievements_catalog();
    return isset( $catalog[ $slug ] ) ? (int) $catalog[ $slug ]->ID : 0;
}

/**
 * Get achievement data enriched with our meta.
 *
 * @param int $achievement_id
 * @return array|null
 */
function amsawal_get_achievement_data( $achievement_id ) {
    $ach = get_post( $achievement_id );
    if ( ! $ach || $ach->post_type !== 'achievement' ) {
        return null;
    }
    return array(
        'id'          => (int) $ach->ID,
        'slug'        => $ach->post_name,
        'title'       => $ach->post_title,
        'description' => $ach->post_excerpt ?: $ach->post_content,
        'icon'        => get_post_meta( $ach->ID, '_amsawal_achievement_icon', true ) ?: '🏆',
        'category'    => get_post_meta( $ach->ID, '_amsawal_achievement_category', true ) ?: 'misc',
        'price'       => (int) get_post_meta( $ach->ID, '_amsawal_achievement_price', true ),
        'trigger'     => get_post_meta( $ach->ID, '_amsawal_achievement_trigger', true ),
    );
}

/*───────────────────────────────────────────────────────────────────────
 * 2. USER ACHIEVEMENT STATE
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Get all earned achievement IDs for a user.
 *
 * Combines GamiPress earnings + our custom shop purchase tracking.
 *
 * @param int $user_id
 * @return int[]
 */
function amsawal_get_user_earned_achievement_ids( $user_id ) {
    $earned = array();

    if ( function_exists( 'gamipress_get_user_earned_achievement_ids' ) ) {
        $earned = gamipress_get_user_earned_achievement_ids( $user_id, 'logros' );
        $earned = array_map( 'intval', (array) $earned );
    }

    // Add shop purchases that might not be in GamiPress
    $purchased = get_user_meta( $user_id, '_amsawal_purchased_achievements', true );
    if ( is_array( $purchased ) ) {
        foreach ( $purchased as $ach_id ) {
            $ach_id = (int) $ach_id;
            if ( ! in_array( $ach_id, $earned, true ) ) {
                $earned[] = $ach_id;
            }
        }
    }

    return $earned;
}

/**
 * Check if a user has earned a specific achievement.
 *
 * @param int $user_id
 * @param string $slug
 * @return bool
 */
function amsawal_user_has_achievement( $user_id, $slug ) {
    $ach_id = amsawal_get_achievement_id( $slug );
    if ( ! $ach_id ) {
        return false;
    }
    $earned = amsawal_get_user_earned_achievement_ids( $user_id );
    return in_array( $ach_id, $earned, true );
}

/**
 * Award an achievement to a user (idempotent).
 *
 * @param int    $user_id
 * @param string $slug
 * @param string $reason Optional reason for the award.
 * @return bool True if newly awarded, false if already had it or invalid.
 */
function amsawal_award_achievement( $user_id, $slug, $reason = '' ) {
    $ach_id = amsawal_get_achievement_id( $slug );
    if ( ! $ach_id ) {
        return false;
    }
    if ( amsawal_user_has_achievement( $user_id, $slug ) ) {
        return false;
    }

    if ( function_exists( 'gamipress_award_achievement_to_user' ) ) {
        gamipress_award_achievement_to_user( $ach_id, $user_id );
    }

    do_action( 'amsawal_achievement_earned', $user_id, $ach_id, $slug, $reason );
    return true;
}

/*───────────────────────────────────────────────────────────────────────
 * 3. SHOP — Buy achievements with monedas
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Attempt to purchase an achievement with coins.
 *
 * @param int $user_id
 * @param int $achievement_id
 * @return true|WP_Error
 */
function amsawal_purchase_achievement( $user_id, $achievement_id ) {
    $data = amsawal_get_achievement_data( $achievement_id );
    if ( ! $data ) {
        return new WP_Error( 'invalid_achievement', __( 'Logro no válido.', WP_AMSAWAL_TEXTDOMAIN ) );
    }
    if ( $data['price'] <= 0 ) {
        return new WP_Error( 'not_purchasable', __( 'Este logro no se puede comprar.', WP_AMSAWAL_TEXTDOMAIN ) );
    }
    if ( amsawal_user_has_achievement( $user_id, $data['slug'] ) ) {
        return new WP_Error( 'already_owned', __( 'Ya tienes este logro.', WP_AMSAWAL_TEXTDOMAIN ) );
    }

    $balance = (int) gamipress_get_user_points( $user_id, 'monedas' );
    if ( $balance < $data['price'] ) {
        return new WP_Error( 'insufficient_coins', sprintf(
            /* translators: 1: required, 2: current */
            __( 'Monedas insuficientes. Necesitas %1$d, tienes %2$d.', WP_AMSAWAL_TEXTDOMAIN ),
            $data['price'],
            $balance
        ) );
    }

    // Deduct coins
    gamipress_deduct_points_to_user( $user_id, $data['price'], 'monedas', array(
        'admin_id' => 0,
        'reason'   => sprintf( 'Compra de logro: %s', $data['title'] ),
    ) );

    // Track in user meta
    $purchased = get_user_meta( $user_id, '_amsawal_purchased_achievements', true );
    if ( ! is_array( $purchased ) ) {
        $purchased = array();
    }
    if ( ! in_array( $achievement_id, $purchased, true ) ) {
        $purchased[] = $achievement_id;
        update_user_meta( $user_id, '_amsawal_purchased_achievements', $purchased );
    }

    // Award via GamiPress so it shows in their earnings
    if ( function_exists( 'gamipress_award_achievement_to_user' ) ) {
        gamipress_award_achievement_to_user( $achievement_id, $user_id );
    }

    do_action( 'amsawal_achievement_purchased', $user_id, $achievement_id, $data );
    return true;
}

/**
 * Buy a way to unlock a lesson/section you couldn't pass normally.
 * Pays 50 monedas to "skip" the requirement and mark lesson as complete.
 *
 * @param int $user_id
 * @param int $lesson_id Page ID of the lesson.
 * @return true|WP_Error
 */
function amsawal_unlock_lesson_with_coins( $user_id, $lesson_id ) {
    $cost = 50;
    $balance = (int) gamipress_get_user_points( $user_id, 'monedas' );
    if ( $balance < $cost ) {
        return new WP_Error( 'insufficient_coins', sprintf(
            /* translators: 1: required, 2: current */
            __( 'Monedas insuficientes. Necesitas %1$d, tienes %2$d.', WP_AMSAWAL_TEXTDOMAIN ),
            $cost,
            $balance
        ) );
    }

    $completed = get_user_meta( $user_id, '_wp_amsawal_completed_items', true );
    if ( ! is_array( $completed ) ) {
        $completed = array();
    }
    if ( in_array( 'lesson-' . $lesson_id, $completed, true ) ) {
        return new WP_Error( 'already_completed', __( 'Esta lección ya está completada.', WP_AMSAWAL_TEXTDOMAIN ) );
    }

    gamipress_deduct_points_to_user( $user_id, $cost, 'monedas', array(
        'admin_id' => 0,
        'reason'   => sprintf( 'Desbloqueo de lección %d', $lesson_id ),
    ) );

    $completed[] = 'lesson-' . $lesson_id;
    update_user_meta( $user_id, '_wp_amsawal_completed_items', $completed );

    $lessons_count = (int) get_user_meta( $user_id, '_amsawal_lessons_completed', true );
    update_user_meta( $user_id, '_amsawal_lessons_completed', $lessons_count + 1 );

    do_action( 'amsawal_lesson_unlocked_with_coins', $user_id, $lesson_id, $cost );
    return true;
}

/*───────────────────────────────────────────────────────────────────────
 * 4. TRIGGER EVALUATION — Called from various hooks
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Check and award lesson-count achievements.
 * Hook: amsawal_lesson_complete
 */
function amsawal_check_lesson_count_achievements( $user_id, $is_repeat = 0 ) {
    if ( $is_repeat ) {
        return;
    }
    $count = (int) get_user_meta( $user_id, '_amsawal_lessons_completed', true );

    $milestones = array(
        1  => 'primera-leccion',
        5  => 'estudiante-aplicado',
        10 => 'erudito',
        19 => 'maestro-tarifit',
    );

    foreach ( $milestones as $needed => $slug ) {
        if ( $count >= $needed ) {
            amsawal_award_achievement( $user_id, $slug );
        }
    }
}
add_action( 'amsawal_lesson_complete', 'amsawal_check_lesson_count_achievements', 10, 2 );

/**
 * Award section-completion achievement.
 * Hook: amsawal_section_complete (fired by bridge when a section boundary is hit)
 */
function amsawal_check_section_achievement( $user_id, $section_number ) {
    $slugs = array(
        1 => 'alfabetizador',
        2 => 'saludador',
        3 => 'contador',
        4 => 'familiar',
        5 => 'descriptivo',
    );
    if ( isset( $slugs[ $section_number ] ) ) {
        amsawal_award_achievement( $user_id, $slugs[ $section_number ] );
    }
}
add_action( 'amsawal_section_complete', 'amsawal_check_section_achievement', 10, 2 );

/**
 * Award streak achievements.
 * Hook: amsawal_streak_updated
 */
function amsawal_check_streak_achievements( $user_id, $streak_days ) {
    $milestones = array(
        3   => 'principiante-fuego',
        7   => 'racha-semanal',
        14  => 'racha-quincenal',
        30  => 'racha-mensual',
        60  => 'racha-bimestral',
        90  => 'racha-trimestral',
        365 => 'racha-anual',
    );
    foreach ( $milestones as $needed => $slug ) {
        if ( $streak_days >= $needed ) {
            amsawal_award_achievement( $user_id, $slug );
        }
    }
}
add_action( 'amsawal_streak_updated', 'amsawal_check_streak_achievements', 10, 2 );

/**
 * Award league-tier achievement.
 * Hook: amsawal_league_tier_reached
 */
function amsawal_check_league_achievement( $user_id, $tier ) {
    $slugs = array(
        1 => 'top-bronce',
        2 => 'top-plata',
        3 => 'top-oro',
        4 => 'top-platino',
        5 => 'top-diamante',
    );
    if ( isset( $slugs[ $tier ] ) ) {
        amsawal_award_achievement( $user_id, $slugs[ $tier ] );
    }
}
add_action( 'amsawal_league_tier_reached', 'amsawal_check_league_achievement', 10, 2 );

/**
 * Award perfect-score achievements.
 * Hook: amsawal_perfect_score
 */
function amsawal_check_perfect_score_achievements( $user_id ) {
    amsawal_award_achievement( $user_id, 'perfeccionista' );

    $count_key = '_amsawal_perfect_lessons_count';
    $count = (int) get_user_meta( $user_id, $count_key, true );
    update_user_meta( $user_id, $count_key, $count + 1 );

    if ( $count + 1 >= 10 ) {
        amsawal_award_achievement( $user_id, 'diez-perfectas' );
    }
}
add_action( 'amsawal_perfect_score', 'amsawal_check_perfect_score_achievements' );

/**
 * Award mastery achievements based on count of items at 100%.
 * Hook: amsawal_mastery_updated
 */
function amsawal_check_mastery_achievements( $user_id ) {
    $mastery = get_user_meta( $user_id, '_wp_amsawal_item_mastery', true );
    if ( ! is_array( $mastery ) ) {
        return;
    }
    $count_at_max = 0;
    foreach ( $mastery as $val ) {
        if ( (float) $val >= 1.0 ) {
            $count_at_max++;
        }
    }
    if ( $count_at_max >= 5 ) {
        amsawal_award_achievement( $user_id, 'memoria-perfecta' );
    }
    if ( $count_at_max >= 25 ) {
        amsawal_award_achievement( $user_id, 'cerebro-total' );
    }
    if ( $count_at_max >= 50 ) {
        amsawal_award_achievement( $user_id, 'polyglota-nato' );
    }
}
add_action( 'amsawal_mastery_updated', 'amsawal_check_mastery_achievements' );

/**
 * Award time-based achievements.
 * Hook: amsawal_lesson_complete
 */
function amsawal_check_time_achievements( $user_id, $is_repeat = 0, $score = 0, $duration = 0, $time = null ) {
    // Perfect score
    if ( $score >= 100 ) {
        do_action( 'amsawal_perfect_score', $user_id );
    }

    // Speed learner (under 30 seconds)
    if ( $duration > 0 && $duration < 30 && $score >= 70 ) {
        amsawal_award_achievement( $user_id, 'velocista' );
    }

    // Lessons in a day
    $today = current_time( 'Y-m-d' );
    $key   = '_amsawal_lessons_' . $today;
    $count = (int) get_user_meta( $user_id, $key, true );
    update_user_meta( $user_id, $key, $count + 1 );
    if ( $count + 1 >= 5 ) {
        amsawal_award_achievement( $user_id, 'maquina-diaria' );
    }

    // Time of day
    $hour = $time ? (int) wp_date( 'G', $time ) : (int) wp_date( 'G' );
    if ( $hour < 8 ) {
        amsawal_award_achievement( $user_id, 'madrugador' );
    }
    if ( $hour >= 0 && $hour < 6 ) {
        amsawal_award_achievement( $user_id, 'noctambulo' );
    }

    // Weekend warrior
    $day_of_week = $time ? wp_date( 'N', $time ) : wp_date( 'N' );
    if ( $day_of_week >= 6 ) {
        $weekend_key = '_amsawal_lessons_weekend_' . wp_date( 'Y-W', $time );
        $weekend_count = (int) get_user_meta( $user_id, $weekend_key, true );
        update_user_meta( $user_id, $weekend_key, $weekend_count + 1 );
        if ( $weekend_count + 1 >= 3 ) {
            amsawal_award_achievement( $user_id, 'fin-de-semana' );
        }
    }
}
add_action( 'amsawal_lesson_complete_extended', 'amsawal_check_time_achievements', 10, 5 );

/**
 * Award social achievements.
 * Hooked to BuddyPress's native friendship-accepted action, since BuddyPress
 * friends is the platform's source of truth for the social graph.
 */
function amsawal_check_social_achievements( $user_id ) {
    $friends_count = function_exists( 'friends_get_friend_user_ids' )
        ? count( friends_get_friend_user_ids( $user_id ) )
        : 0;

    if ( $friends_count >= 1 ) {
        amsawal_award_achievement( $user_id, 'amistoso' );
    }
    if ( $friends_count >= 10 ) {
        amsawal_award_achievement( $user_id, 'mariposa-social' );
    }
}

/**
 * BuddyPress fires friends_friendship_accepted with
 * ( $friendship_id, $initiator_user_id, $friend_user_id, $friendship ).
 * Both users gain a friend, so award social achievements to both.
 */
function amsawal_on_friendship_accepted( $friendship_id, $initiator_user_id, $friend_user_id ) {
    amsawal_check_social_achievements( $initiator_user_id );
    amsawal_check_social_achievements( $friend_user_id );
}
add_action( 'friends_friendship_accepted', 'amsawal_on_friendship_accepted', 10, 3 );

/*───────────────────────────────────────────────────────────────────────
 * 5. AJAX ENDPOINTS
 *───────────────────────────────────────────────────────────────────────*/

// Get all achievements with earned status
add_action( 'wp_ajax_amsawal_get_achievements', 'amsawal_ajax_get_achievements' );
function amsawal_ajax_get_achievements() {
    check_ajax_referer( 'amsawal_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( __( 'No autorizado', WP_AMSAWAL_TEXTDOMAIN ), 403 );
    }
    $user_id = get_current_user_id();

    $catalog = amsawal_get_achievements_catalog( true );
    $earned  = amsawal_get_user_earned_achievement_ids( $user_id );

    $items = array();
    foreach ( $catalog as $slug => $ach ) {
        $data = amsawal_get_achievement_data( $ach->ID );
        if ( $data ) {
            $data['earned']   = in_array( $ach->ID, $earned, true );
            $data['progress'] = amsawal_get_achievement_progress( $user_id, $data );
            $items[] = $data;
        }
    }

    // Sort: earned first, then by category, then by title
    usort( $items, function( $a, $b ) {
        if ( $a['earned'] !== $b['earned'] ) {
            return $a['earned'] ? -1 : 1;
        }
        if ( $a['category'] !== $b['category'] ) {
            return strcmp( $a['category'], $b['category'] );
        }
        return strcmp( $a['title'], $b['title'] );
    } );

    wp_send_json_success( $items );
}

/**
 * Compute a 0-100 progress for an achievement (best-effort).
 */
function amsawal_get_achievement_progress( $user_id, $data ) {
    $trigger = json_decode( $data['trigger'], true );
    if ( ! is_array( $trigger ) ) {
        return $data['earned'] ? 100 : 0;
    }
    $type   = $trigger['type'] ?? '';
    $target = $trigger['count'] ?? 1;

    switch ( $type ) {
        case 'lessons_completed':
            $current = (int) get_user_meta( $user_id, '_amsawal_lessons_completed', true );
            return min( 100, (int) round( ( $current / max( 1, $target ) ) * 100 ) );

        case 'streak':
            $current = (int) get_user_meta( $user_id, '_wp_amsawal_streak_days', true );
            return min( 100, (int) round( ( $current / max( 1, $target ) ) * 100 ) );

        case 'mastery_count':
            $mastery = get_user_meta( $user_id, '_wp_amsawal_item_mastery', true );
            $count   = is_array( $mastery ) ? count( array_filter( $mastery, function( $v ) { return (float) $v >= 1.0; } ) ) : 0;
            return min( 100, (int) round( ( $count / max( 1, $target ) ) * 100 ) );

        case 'perfect_count':
            $count = (int) get_user_meta( $user_id, '_amsawal_perfect_lessons_count', true );
            return min( 100, (int) round( ( $count / max( 1, $target ) ) * 100 ) );

        default:
            return $data['earned'] ? 100 : 0;
    }
}

// Buy achievement
add_action( 'wp_ajax_amsawal_buy_achievement', 'amsawal_ajax_buy_achievement' );
function amsawal_ajax_buy_achievement() {
    check_ajax_referer( 'amsawal_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( __( 'No autorizado', WP_AMSAWAL_TEXTDOMAIN ), 403 );
    }
    $user_id        = get_current_user_id();
    $achievement_id = absint( $_POST['achievement_id'] ?? 0 );

    $result = amsawal_purchase_achievement( $user_id, $achievement_id );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message(), 400 );
    }
    $new_balance = (int) gamipress_get_user_points( $user_id, 'monedas' );
    wp_send_json_success( array(
        'message'      => __( '¡Logro comprado!', WP_AMSAWAL_TEXTDOMAIN ),
        'new_balance'  => $new_balance,
    ) );
}

// Unlock lesson with coins
add_action( 'wp_ajax_amsawal_unlock_lesson', 'amsawal_ajax_unlock_lesson' );
function amsawal_ajax_unlock_lesson() {
    check_ajax_referer( 'amsawal_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( __( 'No autorizado', WP_AMSAWAL_TEXTDOMAIN ), 403 );
    }
    $user_id   = get_current_user_id();
    $lesson_id = absint( $_POST['lesson_id'] ?? 0 );

    $result = amsawal_unlock_lesson_with_coins( $user_id, $lesson_id );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message(), 400 );
    }
    wp_send_json_success( array(
        'message' => __( 'Lección desbloqueada con monedas.', WP_AMSAWAL_TEXTDOMAIN ),
    ) );
}
