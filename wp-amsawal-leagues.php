<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Amsawal Leagues — Seasonal league system (Bronze → Diamond).
 *
 * Leagues reset weekly. Users earn XP each week; top % move up, bottom % move down.
 * League assignments are stored in usermeta and a custom table for history.
 *
 * League tiers:
 *   1 = Bronze   (top 60%+)
 *   2 = Silver   (top 40-60%)
 *   3 = Gold     (top 20-40%)
 *   4 = Platinum (top 5-20%)
 *   5 = Diamond  (top 5%)
 *
 * Shortcode:
 *   [amsawal_league]
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*───────────────────────────────────────────────────────────────────────
 * 1. CONSTANTS & CONFIG
 *───────────────────────────────────────────────────────────────────────*/

if ( ! defined( 'WP_AMSAWAL_LEAGUE_TABLE' ) ) {
	define( 'WP_AMSAWAL_LEAGUE_TABLE', 'wp_amsawal_league_history' );
}

/**
 * League definitions: tier ID → label, color, icon, thresholds.
 */
function wp_amsawal_league_definitions() {
	return array(
		1 => array(
			'label'    => __( 'Bronce', WP_AMSAWAL_TEXTDOMAIN ),
			'icon'     => '🥉',
			'color'    => '#CD7F32',
			'threshold'=> 0.60, // top 60%
		),
		2 => array(
			'label'    => __( 'Plata', WP_AMSAWAL_TEXTDOMAIN ),
			'icon'     => '🥈',
			'color'    => '#C0C0C0',
			'threshold'=> 0.40,
		),
		3 => array(
			'label'    => __( 'Oro', WP_AMSAWAL_TEXTDOMAIN ),
			'icon'     => '🥇',
			'color'    => '#FFD700',
			'threshold'=> 0.20,
		),
		4 => array(
			'label'    => __( 'Platino', WP_AMSAWAL_TEXTDOMAIN ),
			'icon'     => '💠',
			'color'    => '#E5E4E2',
			'threshold'=> 0.05,
		),
		5 => array(
			'label'    => __( 'Diamante', WP_AMSAWAL_TEXTDOMAIN ),
			'icon'     => '💎',
			'color'    => '#B9F2FF',
			'threshold'=> 0.05, // top 5%
		),
	);
}

/**
 * Get the current season number and dates.
 *
 * @return array { season_id, start, end, is_active }
 */
function wp_amsawal_current_season() {
	$option = get_option( 'wp_amsawal_current_season', array() );
	$now    = time();

	if ( empty( $option['end'] ) || $now > $option['end'] ) {
		// Start a new season: Monday 00:00 UTC to Sunday 23:59 UTC.
		$today    = gmdate( 'Y-m-d', $now );
		$monday   = strtotime( 'monday this week 00:00:00 UTC', strtotime( $today ) );
		$sunday   = strtotime( 'sunday this week 23:59:59 UTC', strtotime( $today ) );
		$season   = isset( $option['season_id'] ) ? (int) $option['season_id'] + 1 : 1;

		$option = array(
			'season_id' => $season,
			'start'     => $monday,
			'end'       => $sunday,
		);
		update_option( 'wp_amsawal_current_season', $option );
	}

	return array(
		'season_id' => (int) $option['season_id'],
		'start'     => (int) $option['start'],
		'end'       => (int) $option['end'],
		'is_active' => $now >= (int) $option['start'] && $now <= (int) $option['end'],
	);
}

/*───────────────────────────────────────────────────────────────────────
 * 2. DATABASE — Custom table for season history
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Create the league history table on activation.
 */
function wp_amsawal_leagues_create_table() {
	global $wpdb;
	$table   = $wpdb->prefix . 'amsawal_league_history';
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$table} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		season_id INT UNSIGNED NOT NULL,
		league_tinyint TINYINT UNSIGNED NOT NULL DEFAULT 1,
		xp_earned INT UNSIGNED NOT NULL DEFAULT 0,
		start_position INT UNSIGNED DEFAULT NULL,
		end_position INT UNSIGNED DEFAULT NULL,
		promoted TINYINT(1) NOT NULL DEFAULT 0,
		demoted TINYINT(1) NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_user_season (user_id, season_id),
		KEY idx_season_user (season_id, user_id),
		KEY idx_user_season (user_id, season_id),
		KEY idx_season_league (season_id, league_tinyint)
	) {$charset};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Run table creation on admin_init.
 * dbDelta() es idempotente: si la tabla existe con el esquema correcto, no hace nada.
 */
add_action( 'admin_init', function() {
	if ( get_option( 'wp_amsawal_leagues_table_version', '0' ) !== '1' ) {
		wp_amsawal_leagues_create_table();
		update_option( 'wp_amsawal_leagues_table_version', '1' );
	}
});

/*───────────────────────────────────────────────────────────────────────
 * 3. LEAGUE ASSIGNMENT LOGIC
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Get a user's current league tier (1-5).
 *
 * @param int $user_id
 * @param int $season_id Optional, defaults to current season.
 * @return int League tier 1-5.
 */
function wp_amsawal_get_user_league( $user_id, $season_id = 0 ) {
	if ( ! $season_id ) {
		$season = wp_amsawal_current_season();
		$season_id = $season['season_id'];
	}

	global $wpdb;
	$table = $wpdb->prefix . 'amsawal_league_history';
	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT league_tinyint FROM {$table} WHERE user_id = %d AND season_id = %d LIMIT 1",
		$user_id, $season_id
	) );

	if ( $row ) {
		return (int) $row->league_tinyint;
	}

	// Check usermeta fallback.
	$meta = get_user_meta( $user_id, '_amsawal_league', true );
	if ( $meta && is_numeric( $meta ) ) {
		return (int) $meta;
	}

	return 1; // Default: Bronze.
}

/**
 * Set a user's league tier for a season.
 */
function wp_amsawal_set_user_league( $user_id, $tier, $season_id = 0 ) {
	$tier = max( 1, min( 5, (int) $tier ) );

	if ( ! $season_id ) {
		$season = wp_amsawal_current_season();
		$season_id = $season['season_id'];
	}

	global $wpdb;
	$table = $wpdb->prefix . 'amsawal_league_history';

	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$table} WHERE user_id = %d AND season_id = %d LIMIT 1",
		$user_id, $season_id
	) );

	if ( $existing ) {
		$wpdb->update( $table,
			array( 'league_tinyint' => $tier ),
			array( 'user_id' => $user_id, 'season_id' => $season_id ),
			array( '%d' ),
			array( '%d', '%d' )
		);
	} else {
		$wpdb->insert( $table,
			array(
				'user_id'        => $user_id,
				'season_id'      => $season_id,
				'league_tinyint' => $tier,
			),
			array( '%d', '%d', '%d' )
		);
	}

	// Also update usermeta for quick access.
	update_user_meta( $user_id, '_amsawal_league', $tier );
	update_user_meta( $user_id, '_amsawal_league_season', $season_id );
}

/*───────────────────────────────────────────────────────────────────────
 * 4. WEEKLY RESET — Cron + manual trigger
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Schedule weekly league reset.
 */
function wp_amsawal_leagues_schedule_cron() {
	if ( ! wp_next_scheduled( 'wp_amsawal_league_weekly_reset' ) ) {
		$monday = strtotime( 'next monday 01:00:00 UTC' );
		wp_schedule_single_event( $monday, 'wp_amsawal_league_weekly_reset' );
	}
}
add_action( 'init', 'wp_amsawal_leagues_schedule_cron' );

add_action( 'wp_amsawal_league_weekly_reset', 'wp_amsawal_league_weekly_reset' );

/**
 * Perform the weekly reset:
 * 1. Record final standings for current season.
 * 2. Calculate new league assignments for next season.
 * 3. Distribute end-of-season rewards.
 * 4. Start new season.
 */
function wp_amsawal_league_weekly_reset() {
	$season = wp_amsawal_current_season();
	if ( ! $season['is_active'] ) {
		return; // Season already ended.
	}

	// 1. Get all users ranked by weekly XP.
	$rankings = wp_amsawal_get_weekly_rankings();
	$total    = count( $rankings );

	if ( 0 === $total ) {
		return;
	}

	// 2. Assign leagues based on percentile position.
	$leagues = wp_amsawal_league_definitions();
	global $wpdb;
	$table = $wpdb->prefix . 'amsawal_league_history';

	// Iterate tiers highest-to-lowest; assign the first (best) tier whose
	// threshold the user's percentile qualifies for.
	$tiers_desc = array();
	foreach ( $leagues as $t => $def ) {
		$tiers_desc[ $t ] = $def;
	}
	krsort( $tiers_desc );

	foreach ( $rankings as $pos => $entry ) {
		$uid  = (int) $entry['user_id'];
		$xp   = (int) $entry['xp'];
		$pct  = $pos / $total; // 0 = top, 1 = bottom.

		// Determine new tier: highest qualifying tier wins.
		$new_tier = 1; // default: Bronze
		foreach ( $tiers_desc as $tier => $def ) {
			if ( $pct < $def['threshold'] ) {
				$new_tier = $tier;
				break;
			}
		}

		// Compare with old tier for promotion/demotion.
		$old_tier = wp_amsawal_get_user_league( $uid, $season['season_id'] );
		$promoted = $new_tier > $old_tier ? 1 : 0;
		$demoted  = $new_tier < $old_tier ? 1 : 0;

		// Update history record.
		$wpdb->update( $table,
			array(
				'end_position' => $pos + 1,
				'promoted'     => $promoted,
				'demoted'      => $demoted,
			),
			array( 'user_id' => $uid, 'season_id' => $season['season_id'] ),
			array( '%d', '%d', '%d' ),
			array( '%d', '%d' )
		);

		// Assign to new season.
		wp_amsawal_set_user_league( $uid, $new_tier, $season['season_id'] + 1 );

		// Send notification.
		if ( $promoted ) {
			wp_amsawal_league_notify( $uid, 'promoted', $new_tier );
		} elseif ( $demoted ) {
			wp_amsawal_league_notify( $uid, 'demoted', $new_tier );
		}
	}

	// 3. Distribute rewards.
	wp_amsawal_distribute_season_rewards( $rankings, $season['season_id'] );

	// 4. Clear old leaderboard cache.
	if ( function_exists( 'wp_amsawal_invalidate_leaderboard_cache' ) ) {
		wp_amsawal_invalidate_leaderboard_cache();
	} else {
		delete_transient( 'wp_amsawal_leaderboard_meta_v1' );
	}
}

/**
 * Get all users ranked by XP earned this week.
 *
 * @return array Array of { user_id, xp } sorted descending.
 */
function wp_amsawal_get_weekly_rankings() {
	global $wpdb;

	// Use the _gamipress_monedas_points_awarded meta if available (weekly awarded).
	// Fallback: use total points (not ideal for weekly, but works).
	$season = wp_amsawal_current_season();
	$week_start = $season['start'];

	// Try to get weekly points from GamiPress log table.
	$log_table = $wpdb->prefix . 'gamipress_logs';
	$exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM information_schema.tables
		 WHERE table_schema = %s AND table_name = %s",
		DB_NAME,
		$log_table
	));

	if ( $exists ) {
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, SUM(meta_value) as xp
			 FROM {$log_table}
			 WHERE type = 'points_award'
			   AND date >= %s
			 GROUP BY user_id
			 ORDER BY xp DESC",
			gmdate( 'Y-m-d H:i:s', $week_start )
		));

		if ( ! empty( $rows ) ) {
			$result = array();
			foreach ( $rows as $r ) {
				$result[] = array(
					'user_id' => (int) $r->user_id,
					'xp'      => (int) $r->xp,
				);
			}
			return $result;
		}
	}

	// Fallback: use gamipress monedas points.
	$meta_users = wp_amsawal_get_leaderboard_meta();
	$result = array();
	foreach ( $meta_users as $u ) {
		$xp = (int) ( $u->_gamipress_monedas_points ?? 0 );
		if ( $xp > 0 ) {
			$result[] = array(
				'user_id' => (int) $u->ID,
				'xp'      => $xp,
			);
		}
	}
	usort( $result, function( $a, $b ) {
		return $b['xp'] - $a['xp'];
	} );

	return $result;
}

/*───────────────────────────────────────────────────────────────────────
 * 5. REWARDS
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Distribute end-of-season rewards based on final league.
 */
function wp_amsawal_distribute_season_rewards( $rankings, $season_id ) {
	$rewards = array(
		5 => array( 'points' => 500, 'badge' => 'diamond-league' ),
		4 => array( 'points' => 300, 'badge' => 'platinum-league' ),
		3 => array( 'points' => 200, 'badge' => 'gold-league' ),
		2 => array( 'points' => 100, 'badge' => 'silver-league' ),
		1 => array( 'points' => 50,  'badge' => 'bronze-league' ),
	);

	foreach ( $rankings as $entry ) {
		$uid  = (int) $entry['user_id'];
		$tier = wp_amsawal_get_user_league( $uid, $season_id );
		$reward = isset( $rewards[ $tier ] ) ? $rewards[ $tier ] : $rewards[1];

		// Award points via GamiPress.
		if ( function_exists( 'gamipress_award_points_to_user' ) ) {
			gamipress_award_points_to_user( $uid, (int) $reward['points'], 'monedas', array(
				'admin_id' => 0,
				'reason'   => 'league_season_reward',
			) );
		}

		// Award badge.
		if ( function_exists( 'gamipress_award_achievement_to_user' ) ) {
			$badge_id = get_option( 'wp_amsawal_badge_' . $reward['badge'] );
			if ( $badge_id ) {
				gamipress_award_achievement_to_user( $badge_id, $uid );
			}
		}

		// Fire league tier hook for achievement triggers
		do_action( 'amsawal_league_tier_reached', $uid, $tier );

		// Store reward in history.
		global $wpdb;
		$table = $wpdb->prefix . 'amsawal_league_history';
		$wpdb->update( $table,
			array( 'xp_earned' => $reward['points'] ),
			array( 'user_id' => $uid, 'season_id' => $season_id ),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}
}

/*───────────────────────────────────────────────────────────────────────
 * 6. NOTIFICATIONS
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Send league promotion/demotion notification.
 */
function wp_amsawal_league_notify( $user_id, $type, $tier ) {
	$leagues = wp_amsawal_league_definitions();
	$league  = $leagues[ $tier ];

	if ( 'promoted' === $type ) {
		$title   = sprintf( __( '🏆 ¡Felicidades! Subiste a %s', WP_AMSAWAL_TEXTDOMAIN ), $league['label'] );
		$message = sprintf(
			/* translators: %s: league name */
			__( 'Has sido promocionado a la liga %s esta semana. ¡Sigue así!', WP_AMSAWAL_TEXTDOMAIN ),
			$league['icon'] . ' ' . $league['label']
		);
	} else {
		$title   = sprintf( __( '📈 Has bajado a %s', WP_AMSAWAL_TEXTDOMAIN ), $league['label'] );
		$message = sprintf(
			/* translators: %s: league name */
			__( 'Esta semana terminaste en la liga %s. ¡No te rindas, la próxima semana puedes subir!', WP_AMSAWAL_TEXTDOMAIN ),
			$league['icon'] . ' ' . $league['label']
		);
	}

	// WordPress notification.
	if ( function_exists( 'wp_insert_post' ) ) {
		$notif = array(
			'post_type'   => 'amsawal_notification',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_content'=> $message,
			'meta_input'  => array(
				'_amsawal_notif_user' => $user_id,
				'_amsawal_notif_type' => 'league_' . $type,
				'_amsawal_notif_read' => 0,
			),
		);
		wp_insert_post( $notif );
	}

	// BuddyPress activity.
	if ( function_exists( 'bp_activity_add' ) ) {
		bp_activity_add( array(
			'user_id'   => $user_id,
			'content'   => $title . "\n" . $message,
			'component' => 'amsawal',
			'type'      => 'league_' . $type,
		) );
	}
}

/*───────────────────────────────────────────────────────────────────────
 * 7. SHORTCODE — [amsawal_league]
 *───────────────────────────────────────────────────────────────────────*/

add_shortcode( 'amsawal_league', 'wp_amsawal_league_shortcode' );

function wp_amsawal_league_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'user_id' => 0,
	), $atts, 'amsawal_league' );

	$user_id = $atts['user_id'] ? (int) $atts['user_id'] : get_current_user_id();
	if ( ! $user_id ) {
		return '<p class="duo-league-login">' . esc_html__( 'Inicia sesión para ver tu liga.', WP_AMSAWAL_TEXTDOMAIN ) . '</p>';
	}

	$season  = wp_amsawal_current_season();
	$tier    = wp_amsawal_get_user_league( $user_id, $season['season_id'] );
	$leagues = wp_amsawal_league_definitions();
	$current = $leagues[ $tier ];

	// Season dates.
	$start = wp_date( 'd M', $season['start'] );
	$end   = wp_date( 'd M Y', $season['end'] );

	// Time remaining.
	$remaining = $season['end'] - time();
	if ( $remaining < 0 ) $remaining = 0;
	$days  = floor( $remaining / DAY_IN_SECONDS );
	$hours = floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );

	if ( $days > 0 ) {
		$time_left = sprintf( __( '%dd %dh restantes', WP_AMSAWAL_TEXTDOMAIN ), $days, $hours );
	} else {
		$time_left = sprintf( __( '%dh restantes', WP_AMSAWAL_TEXTDOMAIN ), $hours );
	}

	// User position in league.
	$position = wp_amsawal_get_user_position_in_league( $user_id, $tier, $season['season_id'] );

	ob_start();
	?>
	<div class="duo-league-card" role="region" aria-label="<?php echo esc_attr__( 'Tu liga actual', WP_AMSAWAL_TEXTDOMAIN ); ?>">
		<div class="duo-league-header">
			<span class="duo-league-icon" style="color:<?php echo esc_attr( $current['color'] ); ?>"><?php echo wp_kses_post( $current['icon'] ); ?></span>
			<div class="duo-league-info">
				<h3 class="duo-league-name"><?php echo esc_html( $current['label'] ); ?></h3>
				<span class="duo-league-season"><?php echo esc_html( sprintf( __( 'Temporada #%d', WP_AMSAWAL_TEXTDOMAIN ), $season['season_id'] ) ); ?></span>
			</div>
			<div class="duo-league-timer">
				<span class="duo-league-timer-icon" aria-hidden="true">⏱</span>
				<span class="duo-league-timer-text"><?php echo esc_html( $time_left ); ?></span>
			</div>
		</div>
		<div class="duo-league-progress">
			<div class="duo-league-progress-label">
				<span><?php echo esc_html( $start ); ?></span>
				<span><?php echo esc_html( $end ); ?></span>
			</div>
			<div class="duo-league-progress-bar-wrapper">
				<div class="duo-league-progress-bar" style="width:<?php echo esc_attr( wp_amsawal_season_progress_pct() ); ?>%"></div>
			</div>
		</div>
		<?php if ( $position ) : ?>
		<div class="duo-league-position">
			<?php
			printf(
				/* translators: 1: position number, 2: league icon */
				__( 'Puesto %1$s %2$s', WP_AMSAWAL_TEXTDOMAIN ),
				'<strong>#' . esc_html( $position ) . '</strong>',
				wp_kses_post( $current['icon'] )
			);
			?>
		</div>
		<?php endif; ?>

		<div class="duo-league-tiers">
			<?php foreach ( $leagues as $t => $def ) : ?>
				<div class="duo-league-tier <?php echo $t === $tier ? 'duo-league-tier--active' : ''; ?>" style="--tier-color:<?php echo esc_attr( $def['color'] ); ?>">
					<span class="duo-league-tier-icon"><?php echo wp_kses_post( $def['icon'] ); ?></span>
					<span class="duo-league-tier-name"><?php echo esc_html( $def['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/*───────────────────────────────────────────────────────────────────────
 * 8. HELPER FUNCTIONS
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Get a user's position within a specific league tier.
 */
function wp_amsawal_get_user_position_in_league( $user_id, $tier, $season_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'amsawal_league_history';

	$position = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) + 1 FROM {$table}
		 WHERE season_id = %d AND league_tinyint = %d AND xp_earned > (
			SELECT COALESCE(xp_earned, 0) FROM {$table}
			WHERE user_id = %d AND season_id = %d LIMIT 1
		 )",
		$season_id, $tier, $user_id, $season_id
	) );

	return $position ? (int) $position : null;
}

/**
 * Get season progress percentage (0-100).
 */
function wp_amsawal_season_progress_pct() {
	$season = wp_amsawal_current_season();
	$total = $season['end'] - $season['start'];
	if ( $total <= 0 ) return 100;

	$elapsed = time() - $season['start'];
	$pct = ( $elapsed / $total ) * 100;
	return max( 0, min( 100, $pct ) );
}

/**
 * Get league history for a user.
 *
 * @param int $user_id
 * @param int $limit Number of past seasons to return.
 * @return array
 */
function wp_amsawal_get_league_history( $user_id, $limit = 10 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'amsawal_league_history';

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT season_id, league_tinyint, xp_earned, start_position, end_position, promoted, demoted
		 FROM {$table}
		 WHERE user_id = %d
		 ORDER BY season_id DESC
		 LIMIT %d",
		$user_id, $limit
	) );

	if ( empty( $rows ) ) {
		return array();
	}

	$leagues = wp_amsawal_league_definitions();
	$result = array();
	foreach ( $rows as $r ) {
		$tier = (int) $r->league_tinyint;
		$def  = isset( $leagues[ $tier ] ) ? $leagues[ $tier ] : $leagues[1];
		$result[] = array(
			'season_id'      => (int) $r->season_id,
			'league'         => $def['label'],
			'league_icon'    => $def['icon'],
			'league_color'   => $def['color'],
			'xp_earned'      => (int) $r->xp_earned,
			'start_position' => $r->start_position ? (int) $r->start_position : null,
			'end_position'   => $r->end_position ? (int) $r->end_position : null,
			'promoted'       => (bool) $r->promoted,
			'demoted'        => (bool) $r->demoted,
		);
	}

	return $result;
}

/**
 * Get all users in a specific league tier for a season.
 *
 * @param int $tier League tier 1-5.
 * @param int $season_id
 * @param int $limit
 * @return array
 */
function wp_amsawal_get_league_members( $tier, $season_id = 0, $limit = 50 ) {
	if ( ! $season_id ) {
		$season = wp_amsawal_current_season();
		$season_id = $season['season_id'];
	}

	global $wpdb;
	$table = $wpdb->prefix . 'amsawal_league_history';

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT h.user_id, h.xp_earned, h.start_position, h.end_position,
		        u.display_name, u.user_login
		 FROM {$table} h
		 INNER JOIN {$wpdb->users} u ON u.ID = h.user_id
		 WHERE h.season_id = %d AND h.league_tinyint = %d
		 ORDER BY h.xp_earned DESC
		 LIMIT %d",
		$season_id, $tier, $limit
	) );

	return $rows;
}

/*───────────────────────────────────────────────────────────────────────
 * 9. ADMIN — Manual season reset
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'admin_menu', function() {
	add_submenu_page(
		'amsawal',
		__( 'Amsawal Leagues', WP_AMSAWAL_TEXTDOMAIN ),
		__( 'Leagues', WP_AMSAWAL_TEXTDOMAIN ),
		'manage_options',
		'amsawal-leagues',
		'wp_amsawal_leagues_admin_page'
	);
});

function wp_amsawal_leagues_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}

	if ( isset( $_POST['wp_amsawal_force_reset'] ) && check_admin_referer( 'wp_amsawal_league_reset' ) ) {
		wp_amsawal_league_weekly_reset();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Liga reiniciada correctamente.', WP_AMSAWAL_TEXTDOMAIN ) . '</p></div>';
	}

	$season  = wp_amsawal_current_season();
	$leagues = wp_amsawal_league_definitions();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Amsawal Leagues', WP_AMSAWAL_TEXTDOMAIN ); ?></h1>

		<div class="card">
			<h2><?php esc_html_e( 'Temporada Actual', WP_AMSAWAL_TEXTDOMAIN ); ?></h2>
			<p>
				<strong><?php esc_html_e( 'ID:', WP_AMSAWAL_TEXTDOMAIN ); ?></strong> #<?php echo esc_html( $season['season_id'] ); ?><br>
				<strong><?php esc_html_e( 'Inicio:', WP_AMSAWAL_TEXTDOMAIN ); ?></strong> <?php echo wp_date( 'd/m/Y H:i', $season['start'] ); ?><br>
				<strong><?php esc_html_e( 'Fin:', WP_AMSAWAL_TEXTDOMAIN ); ?></strong> <?php echo wp_date( 'd/m/Y H:i', $season['end'] ); ?><br>
				<strong><?php esc_html_e( 'Progreso:', WP_AMSAWAL_TEXTDOMAIN ); ?></strong> <?php echo esc_html( round( wp_amsawal_season_progress_pct(), 1 ) ); ?>%
			</p>
		</div>

		<div class="card" style="margin-top:16px;">
			<h2><?php esc_html_e( 'Ligas', WP_AMSAWAL_TEXTDOMAIN ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Liga', WP_AMSAWAL_TEXTDOMAIN ); ?></th>
						<th><?php esc_html_e( 'Requisito', WP_AMSAWAL_TEXTDOMAIN ); ?></th>
						<th><?php esc_html_e( 'Recompensa', WP_AMSAWAL_TEXTDOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$rewards_map = array( 5 => 500, 4 => 300, 3 => 200, 2 => 100, 1 => 50 );
					foreach ( $leagues as $t => $def ) :
						$threshold_text = $t === 5
							? __( 'Top 5%', WP_AMSAWAL_TEXTDOMAIN )
							: sprintf( __( 'Top %d%%', WP_AMSAWAL_TEXTDOMAIN ), round( $def['threshold'] * 100 ) );
					?>
					<tr>
						<td><span style="color:<?php echo esc_attr( $def['color'] ); ?>"><?php echo wp_kses_post( $def['icon'] ); ?></span> <?php echo esc_html( $def['label'] ); ?></td>
						<td><?php echo esc_html( $threshold_text ); ?></td>
						<td><?php echo esc_html( $rewards_map[ $t ] ); ?> 💰</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="card" style="margin-top:16px;">
			<h2><?php esc_html_e( 'Forzar Reinicio', WP_AMSAWAL_TEXTDOMAIN ); ?></h2>
			<p><?php esc_html_e( 'Reinicia la temporada actual y asigna nuevas ligas.', WP_AMSAWAL_TEXTDOMAIN ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'wp_amsawal_league_reset' ); ?>
				<?php submit_button( __( 'Forzar Reinicio de Liga', WP_AMSAWAL_TEXTDOMAIN ), 'secondary', 'wp_amsawal_force_reset' ); ?>
			</form>
		</div>
	</div>
	<?php
}
