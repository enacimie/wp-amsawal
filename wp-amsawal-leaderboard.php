<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Amsawal Leaderboard — Shortcode + AJAX real-time refresh.
 *
 * Shortcode:
 *   [amsawal_leaderboard type="monedas" limit="10"]
 *   [amsawal_leaderboard type="tamazight" limit="10" friends="1"]
 *
 * Attributes:
 *   type    – 'monedas' (default) or course slug (e.g. 'tamazight').
 *   limit   – how many users to show (default 10, max 50).
 *   friends – '1' to show only friends (requires BuddyPress).
 *
 * The leaderboard data is computed via wp_amsawal_get_leaderboard_meta()
 * (5-min transient cache in wp-amsawal-gamipress.php). The shortcode renders
 * a server-side skeleton; the JS polls the AJAX endpoint every 30 s for live
 * updates and animates position changes.
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*───────────────────────────────────────────────────────────────────────
 * 1. SHORTCODE
 *───────────────────────────────────────────────────────────────────────*/

add_shortcode( 'amsawal_leaderboard', 'wp_amsawal_leaderboard_shortcode' );

function wp_amsawal_leaderboard_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'type'    => 'monedas',
			'limit'   => 10,
			'friends' => '0',
			'period'  => 'all-time',  // all-time, weekly, monthly
		),
		$atts,
		'amsawal_leaderboard'
	);

	$type    = sanitize_text_field( $atts['type'] );
	$limit   = min( (int) $atts['limit'], 50 ); // Increased max for virtualization
	$friends = ( '1' === $atts['friends'] || 'true' === $atts['friends'] );
	$period  = sanitize_text_field( $atts['period'] );
	if ( ! in_array( $period, array( 'all-time', 'weekly', 'monthly' ), true ) ) {
		$period = 'all-time';
	}
	if ( $limit < 3 ) {
		$limit = 10;
	}

	// Build initial data server-side for instant render (no skeleton flash).
	$initial = wp_amsawal_leaderboard_data( $type, $limit, $friends, $period );

	ob_start();
	?>
	<div
		class="duo-leaderboard"
		data-type="<?php echo esc_attr( $type ); ?>"
		data-limit="<?php echo esc_attr( $limit ); ?>"
		data-friends="<?php echo esc_attr( $friends ? '1' : '0' ); ?>"
		data-period="<?php echo esc_attr( $period ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_amsawal_leaderboard' ) ); ?>"
		data-virtualize="<?php echo esc_attr( $limit > 30 ? '1' : '0' ); ?>"
		role="region"
		aria-label="<?php echo esc_attr( sprintf( __( 'Clasificación de %s %s (top %d) en tiempo real', WP_AMSAWAL_TEXTDOMAIN ), $type === 'monedas' ? __( 'monedas', WP_AMSAWAL_TEXTDOMAIN ) : ucfirst( $type ), $friends ? __( 'entre amigos', WP_AMSAWAL_TEXTDOMAIN ) : __( 'global', WP_AMSAWAL_TEXTDOMAIN ), (int) $limit ) ); ?>"
		aria-live="polite"
		aria-busy="false"
	>
		<div class="duo-leaderboard-header">
			<h2 class="duo-leaderboard-title">
				<?php echo esc_html( wp_amsawal_leaderboard_title( $type ) ); ?>
			</h2>
			<div class="duo-leaderboard-controls">
			<button type="button" class="duo-leaderboard-refresh duo-btn duo-btn--ghost" aria-label="<?php echo esc_attr__( 'Actualizar clasificación', WP_AMSAWAL_TEXTDOMAIN ); ?>">
				<span class="duo-nav-icon duo-nav-icon--sm" aria-hidden="true">🔄</span>
				<span class="duo-leaderboard-live-dot" aria-hidden="true"></span>
			</button>
			</div>
		</div>

		<div class="duo-leaderboard-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Período', WP_AMSAWAL_TEXTDOMAIN ); ?>">
			<button type="button" class="duo-leaderboard-tab <?php echo 'all-time' === $period ? 'active' : ''; ?>"
				role="tab"
				aria-selected="<?php echo 'all-time' === $period ? 'true' : 'false'; ?>"
				aria-controls="duo-lb-panel"
				data-period="all-time"
				id="duo-lb-tab-all-time"
				tabindex="<?php echo 'all-time' === $period ? '0' : '-1'; ?>"
			><?php esc_html_e( 'Todos', WP_AMSAWAL_TEXTDOMAIN ); ?></button>
			<button type="button" class="duo-leaderboard-tab <?php echo 'weekly' === $period ? 'active' : ''; ?>"
				role="tab"
				aria-selected="<?php echo 'weekly' === $period ? 'true' : 'false'; ?>"
				aria-controls="duo-lb-panel"
				data-period="weekly"
				id="duo-lb-tab-weekly"
				tabindex="<?php echo 'weekly' === $period ? '0' : '-1'; ?>"
			><?php esc_html_e( 'Semanal', WP_AMSAWAL_TEXTDOMAIN ); ?></button>
			<button type="button" class="duo-leaderboard-tab <?php echo 'monthly' === $period ? 'active' : ''; ?>"
				role="tab"
				aria-selected="<?php echo 'monthly' === $period ? 'true' : 'false'; ?>"
				aria-controls="duo-lb-panel"
				data-period="monthly"
				id="duo-lb-tab-monthly"
				tabindex="<?php echo 'monthly' === $period ? '0' : '-1'; ?>"
			><?php esc_html_e( 'Mensual', WP_AMSAWAL_TEXTDOMAIN ); ?></button>
		</div>

		<div class="duo-leaderboard-list" role="tabpanel" id="duo-lb-panel" aria-labelledby="duo-lb-tab-<?php echo esc_attr( $period ); ?>">
			<?php echo wp_amsawal_leaderboard_render( $initial, $type ); ?>
		</div>

		<div class="duo-leaderboard-sr-status sr-only" aria-live="assertive" aria-atomic="true"></div>
	</div>
	<?php
	return ob_get_clean();
}


/*───────────────────────────────────────────────────────────────────────
 * 2. DATA HELPER — Reuses existing wp_amsawal_get_leaderboard_meta()
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Returns leaderboard data as an array of user objects with computed fields:
 *   id, name, avatar_url, xp, position, position_change (int: positive = up ranks).
 *
 * @param string $type    'monedas' or course slug.
 * @param int    $limit   Number of top users.
 * @param bool   $friends Only include friends (requires BuddyPress).
 * @param string $period  'all-time', 'weekly', or 'monthly'.
 * @return array
 */

/*───────────────────────────────────────────────────────────────────────
 * 2. GRANULAR CACHE INVALIDATION
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Invalidate leaderboard cache with granularity by type/course/friends.
 *
 * @param string $type    Leaderboard type ('monedas' or course slug).
 */
function wp_amsawal_invalidate_leaderboard_cache( $type = '' ) {
	// Invalidate the main meta cache (used by all leaderboard types).
	delete_transient( 'wp_amsawal_leaderboard_meta_v1' );

	// Also invalidate any type-specific caches if they exist.
	if ( $type ) {
		$type_cache_key = 'wp_amsawal_leaderboard_meta_' . sanitize_title( $type );
		delete_transient( $type_cache_key );
	}

	// Fire action for WebSocket broadcast.
	do_action( 'wp_amsawal_leaderboard_invalidated', $type, array() );
}

function wp_amsawal_leaderboard_data( $type, $limit = 10, $friends = false, $period = 'all-time' ) {
	if ( ! function_exists( 'wp_amsawal_get_leaderboard_meta' ) ) {
		return array();
	}

	$all = wp_amsawal_get_leaderboard_meta();
	if ( empty( $all ) ) {
		return array();
	}

	// Friends-only filter (BuddyPress).
	if ( $friends && function_exists( 'wp_amsawal_friends_leaders_gamipress' ) ) {
		$all = wp_amsawal_friends_leaders_gamipress( $all );
	}

	// Period-based filtering: use weekly/monthly awarded points if available.
	// Only 'monedas' has granular date-stamped data; course leaderboards use
	// usermeta (total) which lacks per-period granularity, so force all-time.
	if ( 'monedas' !== $type ) {
		$period = 'all-time';
	} elseif ( 'all-time' !== $period ) {
		// Try to use GamiPress log table for period-specific points.
		global $wpdb;
		$log_table = $wpdb->prefix . 'gamipress_logs';
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM information_schema.tables
			 WHERE table_schema = %s AND table_name = %s",
			DB_NAME,
			$log_table
		));

		if ( $table_exists ) {
			$now   = time();
			$since = 'weekly' === $period ? $now - ( 7 * DAY_IN_SECONDS ) : $now - ( 30 * DAY_IN_SECONDS );

			$period_rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT user_id, SUM(meta_value) as period_xp
				 FROM {$log_table}
				 WHERE type = 'points_award'
				   AND date >= %s
				 GROUP BY user_id",
				gmdate( 'Y-m-d H:i:s', $since )
			) );

			if ( ! empty( $period_rows ) ) {
				$period_map = array();
				foreach ( $period_rows as $pr ) {
					$period_map[ (int) $pr->user_id ] = (int) $pr->period_xp;
				}
				// Override XP values with period-specific XP.
				foreach ( $all as $u ) {
					$uid = (int) $u->ID;
					$u->_gamipress_monedas_points = isset( $period_map[ $uid ] ) ? $period_map[ $uid ] : 0;
				}
			}
		}
		// If table doesn't exist, silently fall back to all-time (already loaded).
	}

	// Sort by the right field.
	if ( 'monedas' === $type ) {
		$sorted = wp_amsawal_sort_leaders_gamipress( $all, '_gamipress_monedas_points' );
		$xp_get = function ( $u ) {
			return (int) ( $u->_gamipress_monedas_points ?? 0 );
		};
	} else {
		$rank_key = 'nivel';
		$sorted   = wp_amsawal_sort_leaders_gamipress( $all, $rank_key );
		$xp_get   = function ( $u ) use ( $rank_key ) {
			return (int) ( $u->{$rank_key} ?? 0 );
		};
	}

	$result = array();
	$pos    = 1;
	$user_id_current = get_current_user_id();
	$is_logged_in     = is_user_logged_in();

	foreach ( $sorted as $u ) {
		if ( $pos > $limit && (int) $u->ID !== $user_id_current ) {
			continue;
		}

		// Only track position change for the current viewer (one transient, not per-user).
		$position_change = 0;
		if ( $is_logged_in && (int) $u->ID === $user_id_current ) {
			$prev_key = 'wp_amsawal_leaderboard_pos_' . $user_id_current . '_' . $type;
			$prev_pos = get_transient( $prev_key );
			if ( false !== $prev_pos && is_numeric( $prev_pos ) ) {
				$position_change = (int) $prev_pos - $pos; // positive = moved UP.
			}
			// Persist current position for next poll (single write per viewer×type).
			set_transient( $prev_key, $pos, 30 * MINUTE_IN_SECONDS );
		}

		$xp = $xp_get( $u );
		if ( 0 === $xp && 'monedas' !== $type ) {
			$pos++;
			continue; // skip users with zero rank in this course.
		}

		$result[] = array(
			'id'              => (int) $u->ID,
			'name'            => $u->nickname ?? __( 'Usuario', WP_AMSAWAL_TEXTDOMAIN ),
			'avatar_url'      => function_exists( 'get_avatar_url' ) ? get_avatar_url( (int) $u->ID, array( 'size' => 64 ) ) : '',
			'xp'              => $xp,
			'position'        => $pos,
			'position_change' => $position_change,
			'is_me'           => ( (int) $u->ID === $user_id_current ),
		);
		$pos++;
	}

	return $result;
}


/*───────────────────────────────────────────────────────────────────────
 * 3. RENDER HELPERS
 *───────────────────────────────────────────────────────────────────────*/

/**
 * Returns the title string for a leaderboard type.
 */
function wp_amsawal_leaderboard_title( $type ) {
	if ( 'monedas' === $type ) {
		return __( 'Monedas (Top)', WP_AMSAWAL_TEXTDOMAIN );
	}
	return sprintf(
		/* translators: %s: course name */
		__( 'Top %s', WP_AMSAWAL_TEXTDOMAIN ),
		ucfirst( $type )
	);
}

/**
 * Renders the leaderboard list HTML from a data array.
 */
function wp_amsawal_leaderboard_render( $data, $type ) {
	if ( empty( $data ) ) {
		return '<div class="duo-empty-state"><div class="duo-empty-state__art" aria-hidden="true">( •_•)</div><p>' . esc_html__( 'Aún no hay jugadores clasificados. ¡Sé el primero!', WP_AMSAWAL_TEXTDOMAIN ) . '</p></div>';
	}

	$rank_emoji = array( 1 => '🏆', 2 => '🏆', 3 => '🏆' );
	$xp_label   = 'monedas' === $type ? 'monedas' : 'xp';
	$html       = '';

	foreach ( $data as $u ) {
		$pos         = (int) $u['position'];
		$change      = (int) $u['position_change'];
		$emoji       = isset( $rank_emoji[ $pos ] ) ? $rank_emoji[ $pos ] : $pos;

		$card_class  = 'duo-leaderboard-card';
		if ( $u['is_me'] ) {
			$card_class .= ' duo-leaderboard-card--me';
		}
		if ( $pos > 10 ) {
			$card_class .= ' duo-leaderboard-card--far';
		}

		$change_class = '';
		$change_html  = '';
		if ( $change > 0 ) {
			$change_class = ' duo-leaderboard-rank-up';
			$change_html  = '<span class="duo-leaderboard-change duo-leaderboard-change--up" aria-label="' . esc_attr( sprintf( __( 'Subió %d puesto(s)', WP_AMSAWAL_TEXTDOMAIN ), $change ) ) . '">▲' . absint( $change ) . '</span>';
		} elseif ( $change < 0 ) {
			$change_class = ' duo-leaderboard-rank-down';
			$change_html  = '<span class="duo-leaderboard-change duo-leaderboard-change--down" aria-label="' . esc_attr( sprintf( __( 'Bajó %d puesto(s)', WP_AMSAWAL_TEXTDOMAIN ), abs( $change ) ) ) . '">▼' . absint( abs( $change ) ) . '</span>';
		}

		$html .= '<div class="' . esc_attr( $card_class ) . '" data-user-id="' . esc_attr( $u['id'] ) . '" data-position="' . esc_attr( $pos ) . '" data-xp="' . esc_attr( $u['xp'] ) . '">';
		$html .= '<span class="duo-leaderboard-rank' . esc_attr( $change_class ) . '">' . wp_kses_post( $emoji ) . '</span>';

		// Profile URL: BuddyPress domain or internal /i/username fallback.
		$profile_url = '';
		if ( function_exists( 'bp_members_get_user_url' ) ) {
			$profile_url = bp_members_get_user_url( $u['id'] );
		} elseif ( function_exists( 'bp_core_get_user_domain' ) ) {
			$profile_url = bp_core_get_user_domain( $u['id'] );
		}
		if ( empty( $profile_url ) ) {
			$user_login = get_userdata( $u['id'] );
			if ( $user_login ) {
				$profile_url = site_url( '/i/' . rawurlencode( $user_login->user_login ) . '/' );
			}
		}

		if ( ! empty( $u['avatar_url'] ) && false === strpos( $u['avatar_url'], 'gravatar.com/avatar/' ) ) {
			$avatar_html = '<img src="' . esc_url( $u['avatar_url'] ) . '" alt="" loading="lazy" width="40" height="40" />';
		} else {
			$initials = mb_strtoupper( mb_substr( $u['name'], 0, 1 ) );
			$avatar_html = '<span class="duo-leaderboard-avatar-initials">' . esc_html( $initials ) . '</span>';
		}
		if ( $profile_url ) {
			$html .= '<a class="duo-leaderboard-avatar" href="' . esc_url( $profile_url ) . '">' . $avatar_html . '</a>';
		} else {
			$html .= '<span class="duo-leaderboard-avatar">' . $avatar_html . '</span>';
		}
		$html .= '<span class="duo-leaderboard-name">';
		if ( $profile_url ) {
			$html .= '<a class="duo-leaderboard-name-link" href="' . esc_url( $profile_url ) . '">' . esc_html( $u['name'] ) . '</a>';
		} else {
			$html .= esc_html( $u['name'] );
		}
		if ( $change_html ) {
			$html .= ' ' . $change_html;
		}
		$html .= '</span>';
		$html .= '<span class="duo-leaderboard-xp">' . esc_html( $xp_label . ' ' . number_format_i18n( $u['xp'] ) ) . '</span>';
		$html .= '</div>';
	}

	return $html;
}


/*───────────────────────────────────────────────────────────────────────
 * 4. WIDGET — "Amsawal Leaderboard" para sidebars (Customizer-ready)
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'widgets_init', 'wp_amsawal_register_leaderboard_widget' );

function wp_amsawal_register_leaderboard_widget() {
	register_widget( 'Amsawal_Leaderboard_Widget' );
}

/**
 * Widget class: Amsawal Leaderboard
 *
 * Colocable en cualquier sidebar vía Apariencia → Widgets o el Customizer.
 * Renderiza el shortcode [amsawal_leaderboard] con las opciones configuradas.
 * El JS de polling (30 s) + animaciones de posición funciona igual que en
 * la página /liderazgos/.
 */
class Amsawal_Leaderboard_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'amsawal_leaderboard',
			__( 'Amsawal Leaderboard', WP_AMSAWAL_TEXTDOMAIN ),
			array(
				'description'                 => __( 'Clasificación en tiempo real con XP, avatares y animaciones de cambio de posición.', WP_AMSAWAL_TEXTDOMAIN ),
				'customize_selective_refresh' => true,
				'show_instance_in_rest'       => true,
			)
		);
	}

	/**
	 * Front-end: renderiza el widget.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core markup

		$title   = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$type    = ! empty( $instance['type'] ) ? sanitize_text_field( $instance['type'] ) : 'monedas';
		$limit   = ! empty( $instance['limit'] ) ? min( (int) $instance['limit'], 50 ) : 5;
		$friends = ! empty( $instance['friends'] ) ? '1' : '0';
		$period  = ! empty( $instance['period'] ) ? $instance['period'] : 'all-time';
		if ( ! in_array( $period, array( 'all-time', 'weekly', 'monthly' ), true ) ) {
			$period = 'all-time';
		}

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core markup
		}

		// Build shortcode attributes.
		$sc_atts = sprintf(
			'type="%s" limit="%d" friends="%s" period="%s"',
			esc_attr( $type ),
			$limit,
			esc_attr( $friends ),
			esc_attr( $period )
		);

		echo do_shortcode( '[amsawal_leaderboard ' . $sc_atts . ']' );

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core markup
	}

	/**
	 * Back-end: formulario de opciones del widget.
	 */
	public function form( $instance ) {
		$title   = isset( $instance['title'] ) ? $instance['title'] : '';
		$type    = isset( $instance['type'] ) ? $instance['type'] : 'monedas';
		$limit   = isset( $instance['limit'] ) ? (int) $instance['limit'] : 5;
		$friends = isset( $instance['friends'] ) ? (bool) $instance['friends'] : false;
		$period  = isset( $instance['period'] ) ? $instance['period'] : 'all-time';
		if ( ! in_array( $period, array( 'all-time', 'weekly', 'monthly' ), true ) ) {
			$period = 'all-time';
		}

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Título:', WP_AMSAWAL_TEXTDOMAIN ); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>"
				placeholder="<?php esc_attr_e( 'p.ej. Top Jugadores', WP_AMSAWAL_TEXTDOMAIN ); ?>"
			>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'type' ) ); ?>">
				<?php esc_html_e( 'Tipo de clasificación:', WP_AMSAWAL_TEXTDOMAIN ); ?>
			</label>
			<select
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'type' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>"
			>
				<option value="monedas" <?php selected( $type, 'monedas' ); ?>>
					<?php esc_html_e( 'Monedas (global)', WP_AMSAWAL_TEXTDOMAIN ); ?>
				</option>
				<?php
				// List available courses for course-specific leaderboards.
				if ( function_exists( 'wp_amsawal_get_courses' ) ) {
					$courses = wp_amsawal_get_courses();
					if ( ! empty( $courses ) ) {
						foreach ( $courses as $course ) {
							$course_slug = is_string( $course ) ? $course : ( $course->post_name ?? '' );
							$course_label = is_string( $course ) ? ucfirst( $course ) : ( $course->post_title ?? $course_slug );
							if ( empty( $course_slug ) ) {
								continue;
							}
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( $course_slug ),
								selected( $type, $course_slug, false ),
								esc_html( $course_label )
							);
						}
					}
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">
				<?php esc_html_e( 'Número de jugadores:', WP_AMSAWAL_TEXTDOMAIN ); ?>
			</label>
			<input
				class="tiny-text"
				id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"
				type="number"
				step="1"
				min="3"
				max="50"
				value="<?php echo esc_attr( $limit ); ?>"
			>
			<span class="description"><?php esc_html_e( 'Entre 3 y 50. En sidebars se recomienda 5.', WP_AMSAWAL_TEXTDOMAIN ); ?></span>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'period' ) ); ?>">
				<?php esc_html_e( 'Período por defecto:', WP_AMSAWAL_TEXTDOMAIN ); ?>
			</label>
			<select
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'period' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'period' ) ); ?>"
			>
				<option value="all-time" <?php selected( $period, 'all-time' ); ?>>
					<?php esc_html_e( 'Todo el tiempo', WP_AMSAWAL_TEXTDOMAIN ); ?>
				</option>
				<option value="weekly" <?php selected( $period, 'weekly' ); ?>>
					<?php esc_html_e( 'Semanal', WP_AMSAWAL_TEXTDOMAIN ); ?>
				</option>
				<option value="monthly" <?php selected( $period, 'monthly' ); ?>>
					<?php esc_html_e( 'Mensual', WP_AMSAWAL_TEXTDOMAIN ); ?>
				</option>
			</select>
		</p>

		<p>
			<input
				class="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'friends' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'friends' ) ); ?>"
				type="checkbox"
				value="1"
				<?php checked( $friends ); ?>
			>
			<label for="<?php echo esc_attr( $this->get_field_id( 'friends' ) ); ?>">
				<?php esc_html_e( 'Solo amigos (requiere BuddyPress)', WP_AMSAWAL_TEXTDOMAIN ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Sanitiza y guarda las opciones del widget.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance            = array();
		$instance['title']   = sanitize_text_field( $new_instance['title'] );
		$instance['type']    = sanitize_text_field( $new_instance['type'] );
		$instance['limit']   = min( max( (int) $new_instance['limit'], 3 ), 50 );
		$instance['friends'] = ! empty( $new_instance['friends'] ) ? 1 : 0;
		$instance['period']  = isset( $new_instance['period'] ) ? sanitize_text_field( $new_instance['period'] ) : 'all-time';
		if ( ! in_array( $instance['period'], array( 'all-time', 'weekly', 'monthly' ), true ) ) {
			$instance['period'] = 'all-time';
		}
		return $instance;
	}
}


/*───────────────────────────────────────────────────────────────────────
 * 5. AJAX ENDPOINT — Real-time leaderboard refresh
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_ajax_amsawal_leaderboard_refresh', 'wp_amsawal_leaderboard_ajax' );

function wp_amsawal_leaderboard_ajax() {
	check_ajax_referer( 'wp_amsawal_leaderboard', '_ajax_nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Debes iniciar sesión' ) );
	}

	$type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'monedas';
	$limit   = isset( $_POST['limit'] ) ? min( (int) $_POST['limit'], 50 ) : 10;
	$friends = isset( $_POST['friends'] ) && '1' === $_POST['friends'];
	$period  = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : 'all-time';
	if ( $limit < 3 ) {
		$limit = 10;
	}

	// Validate period.
	if ( ! in_array( $period, array( 'all-time', 'weekly', 'monthly' ), true ) ) {
		$period = 'all-time';
	}

	$data = wp_amsawal_leaderboard_data( $type, $limit, $friends, $period );

	wp_send_json_success( array(
		'users'  => $data,
		'type'   => $type,
		'title'  => wp_amsawal_leaderboard_title( $type ),
		'period' => $period,
		'cached' => time(),
	) );
}
