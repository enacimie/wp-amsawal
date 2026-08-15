<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'the_content', 'wp_amsawal_check_gamipress' );
function wp_amsawal_check_gamipress ( $content ) {
	if (is_admin()) { return $content; }
	if ( ! is_user_logged_in() ) { return $content; }
	if ( ! function_exists( 'gamipress_get_user_earned_achievement_ids' ) ) { return $content; }
	if ( ! is_main_query() ) { return $content; }
	$userid = wp_get_current_user()->ID;

	// ── 1. SISTEMA DE RACHAS DIARIAS — delegado a wp-amsawal-streaks.php ──
	// wp_amsawal_update_streak() uses _wp_amsawal_last_activity_date (unified key)
	// and handles freeze protection, milestone bonuses, and history.
	$streak = wp_amsawal_update_streak( $userid );

	// ── 2. SISTEMA DE VIDAS (Corazones) ──
	$max_lives = 5;
	$lives = get_user_meta($userid, '_wp_amsawal_lives', true);
	if ($lives === '') {
		// Inicializar vidas si es la primera vez
		$lives = $max_lives;
		update_user_meta($userid, '_wp_amsawal_lives', $lives);
		update_user_meta($userid, '_wp_amsawal_lives_last_update', current_time('timestamp'));
	} else {
		$lives = (int) $lives;
		// Recuperar vidas con el tiempo (1 vida cada 4 horas = 14400s)
		$last_update = (int) get_user_meta($userid, '_wp_amsawal_lives_last_update', true);
		$now = current_time('timestamp');
		$seconds_passed = $now - $last_update;
		
		if ($lives < $max_lives && $seconds_passed > 14400) {
			$lives_recovered = floor($seconds_passed / 14400);
			$lives = min($max_lives, $lives + $lives_recovered);
			update_user_meta($userid, '_wp_amsawal_lives', $lives);
			// Mover el timer adelante solo el equivalente a las vidas ganadas
			update_user_meta($userid, '_wp_amsawal_lives_last_update', $last_update + ($lives_recovered * 14400));
		}
	}

	// ── Lógica Original de Logros y Niveles ──
	$current_achievements = gamipress_get_user_earned_achievement_ids($userid, 'logros');
	$current_points = gamipress_get_user_points($userid, 'monedas');

	$courses = wp_amsawal_get_courses();
	if (!empty($courses)) {
		foreach ( $courses as $course ) {
			$rank = gamipress_get_user_rank($userid, 'nivel-'.$course);
			$current_rank = $rank ? $rank->menu_order : 0;
			$option_rank = 'wp_amsawal_current_rank_'.$course.'_'.$userid;
			$old_rank = get_transient( $option_rank );
			if (!$old_rank) {
				set_transient( $option_rank, $current_rank, 300 );
			}
			elseif ($current_rank != $old_rank) {
				set_transient( $option_rank, $current_rank, 300 );
				$course_label = ucfirst( $course );
				$share_text = sprintf(
					'🛡️ Nivel %d en %s — Amsawal: aprende Tamazight',
					intval( $current_rank ),
					esc_html( $course_label )
				);
				ob_start();
				wp_amsawal_show_toast(
					'¡Nuevo nivel!',
					'🛡️ Ahora estás en el nivel ' . intval( $current_rank ) . ' del curso de ' . esc_html( $course_label ),
					'level',
					$share_text
				);
				$content .= ob_get_clean();
			}
		}
	}
	$option_achievements = 'wp_amsawal_current_achievements_'.$userid;
	$option_points = 'wp_amsawal_current_points_'.$userid;
	$old_achievements = get_transient( $option_achievements );
	$old_points = get_transient( $option_points );
	if (empty($old_achievements)) {
		set_transient( $option_achievements, $current_achievements, 300 );
	}
	if ($current_achievements != $old_achievements) {
		set_transient( $option_achievements, $current_achievements, 300 );
		$new_achievement = @array_diff($current_achievements, $old_achievements);
		if (!empty($new_achievement)) {
			$achievement_title = get_the_title( (int) $new_achievement[0] );
			$share_text = sprintf(
				'🏆 Logro "%s" desbloqueado en Amsawal — aprende Tamazight',
				$achievement_title ? $achievement_title : 'nuevo'
			);
			ob_start();
			wp_amsawal_show_toast(
				'¡Logro conseguido!',
				gamipress_get_achievement_post_thumbnail( $new_achievement[0] ),
				'achievement',
				$share_text
			);
			$content .= ob_get_clean();
		}
	}
	if (!$old_points) {
		set_transient( $option_points, $current_points, 300 );
	}
	elseif ($current_points != $old_points) {
		set_transient( $option_points, $current_points, 300 );
		$share_text = sprintf(
			'🪙 %d monedas en Amsawal — aprende Tamazight',
			intval( $current_points )
		);
		ob_start();
		wp_amsawal_show_toast(
			'¡Monedas ganadas!',
			'🪙 Ahora tienes ' . intval( $current_points ) . ' monedas',
			'coin',
			$share_text
		);
		$content .= ob_get_clean();
	}
	return $content;
}

add_filter( 'the_content', 'wp_amsawal_leaders_tables_gamipress' );
function wp_amsawal_leaders_tables_gamipress ($content) {
	if (is_admin()) { return $content; }
	$pagename = get_query_var('pagename');
	if ($pagename != "liderazgos")  {
		return $content;
	}

	$content .= '<div class="duo-container" id="duo-main-content" tabindex="-1">';
	$content .= '<div class="duo-courses-header"><h2>🏆 '.esc_html__( 'Ligas', WP_AMSAWAL_TEXTDOMAIN ).'</h2><p>'.esc_html__( 'Compite con tus amigos y sube en la clasificación — actualizado cada 30 s', WP_AMSAWAL_TEXTDOMAIN ).'</p></div>';

	// ── Monedas section: tabs with real-time shortcodes ──
	$content .= '<div class="duo-leader-section">';
	$content .= '<div class="duo-leader-section-header"><h2>🪙 ' . esc_html__( 'Monedas', WP_AMSAWAL_TEXTDOMAIN ) . '</h2></div>';
	$content .= '<div class="duo-leader-tabs">';
	$content .= '<button class="duo-tab active" data-tab="monedas-top10">🌍 ' . esc_html__( 'Top 10', WP_AMSAWAL_TEXTDOMAIN ) . '</button>';
	$content .= '<button class="duo-tab" data-tab="monedas-amigos">👥 ' . esc_html__( 'Amigos', WP_AMSAWAL_TEXTDOMAIN ) . '</button>';
	$content .= '</div>';
	$content .= '<div class="duo-tab-content active" id="monedas-top10">' . do_shortcode( '[amsawal_leaderboard type="monedas" limit="10"]' ) . '</div>';
	$content .= '<div class="duo-tab-content" id="monedas-amigos">' . do_shortcode( '[amsawal_leaderboard type="monedas" limit="10" friends="1"]' ) . '</div>';
	$content .= '</div>';

	// ── Per-course sections with real-time shortcodes ──
	$all_courses = wp_amsawal_get_courses();
	if (!empty($all_courses)) {
		foreach ($all_courses as $course) {
			$course_label = ucfirst($course);

			$content .= '<div class="duo-leader-section">';
			$content .= '<div class="duo-leader-section-header"><h2>📚 ' . sprintf( esc_html__( 'Curso de %s', WP_AMSAWAL_TEXTDOMAIN ), esc_html( $course_label ) ) . '</h2></div>';
			$content .= '<div class="duo-leader-tabs">';
			$content .= '<button class="duo-tab active" data-tab="'.esc_attr($course).'-top10">🌍 ' . esc_html__( 'Top 10', WP_AMSAWAL_TEXTDOMAIN ) . '</button>';
			$content .= '<button class="duo-tab" data-tab="'.esc_attr($course).'-amigos">👥 ' . esc_html__( 'Amigos', WP_AMSAWAL_TEXTDOMAIN ) . '</button>';
			$content .= '</div>';
			$content .= '<div class="duo-tab-content active" id="'.esc_attr($course).'-top10">' . do_shortcode( '[amsawal_leaderboard type="' . esc_attr( $course ) . '" limit="10"]' ) . '</div>';
			$content .= '<div class="duo-tab-content" id="'.esc_attr($course).'-amigos">' . do_shortcode( '[amsawal_leaderboard type="' . esc_attr( $course ) . '" limit="10" friends="1"]' ) . '</div>';
			$content .= '</div>';
		}
	}

	$content .= '</div>';
	return $content;
}


/**
 * Devuelve un array de objetos con la meta de GamiPress de cada usuario,
 * cacheado 5 minutos en un transient.
 *
 * Estructura por usuario (stdClass):
 *   ID                                  int
 *   nickname                            string
 *   _gamipress_monedas_points           int
 *   _gamipress_monedas_points_awarded   int
 *   _gamipress_nivel-<curso>_rank       int   (post ID del rank)
 *   <post_type del rank>                int   (menu_order = nivel)
 *
 * El transient se invalida automáticamente al expirar (5 min). Para
 * forzar invalidación inmediata (p. ej. desde otro plugin que otorga
 * puntos): delete_transient('wp_amsawal_leaderboard_meta_v1').
 */
function wp_amsawal_get_leaderboard_meta() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache_key = 'wp_amsawal_leaderboard_meta_v1';
	$cached = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		$cache = $cached;
		return $cache;
	}

	global $wpdb;
	$meta_users = array();
	// Traer solo usuarios con meta de GamiPress para no cargar N filas vacías.
	// 1 query para los IDs, 1 query para todas las metas relevantes.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID, u.user_nicename AS nickname, um.meta_key, um.meta_value
		 FROM {$wpdb->users} u
		 INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
		 WHERE um.meta_key IN (
		   'nickname',
		   '_gamipress_monedas_points',
		   '_gamipress_monedas_points_awarded'
		 )
		 OR um.meta_key LIKE %s
		 ORDER BY u.ID",
			'\_gamipress\_nivel-%\_rank'
		)
	);
	if ( empty( $rows ) ) {
		$cache = array();
		set_transient( $cache_key, $cache, 5 * MINUTE_IN_SECONDS );
		return $cache;
	}

	$by_id = array();
	foreach ( $rows as $r ) {
		$id = (int) $r->ID;
		if ( ! isset( $by_id[$id] ) ) {
			$by_id[$id] = (object) array(
				'ID'                                  => $id,
				'nickname'                            => $r->nickname,
				'_gamipress_monedas_points'           => 0,
				'_gamipress_monedas_points_awarded'   => 0,
			);
		}
		if ( 'nickname' === $r->meta_key ) {
			$by_id[$id]->nickname = $r->meta_value;
		} elseif ( isset( $by_id[$id]->{$r->meta_key} ) ) {
			$by_id[$id]->{$r->meta_key} = (int) $r->meta_value;
		} elseif ( 0 === strpos( $r->meta_key, '_gamipress_nivel-' ) ) {
			// _gamipress_nivel-<curso>_rank → guardamos el rank post ID
			$by_id[$id]->{$r->meta_key} = (int) $r->meta_value;
		}
	}

	// Resolver menu_order del rank en una sola query (post_type conocido = nivel-<curso>)
	$rank_ids = array();
	foreach ( $by_id as $u ) {
		foreach ( get_object_vars( $u ) as $k => $v ) {
			if ( 0 === strpos( $k, '_gamipress_nivel-' ) && $v ) {
				$rank_ids[] = (int) $v;
			}
		}
	}
	$rank_ids = array_unique( array_filter( $rank_ids ) );
	if ( ! empty( $rank_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $rank_ids ), '%d' ) );
		$params       = $rank_ids;
		$rank_rows    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_type, menu_order FROM {$wpdb->posts} WHERE ID IN ($placeholders)",
				$params
			)
		);
		$rank_index   = array();
		foreach ( $rank_rows as $rr ) {
			$rank_index[ (int) $rr->ID ] = $rr;
		}
		foreach ( $by_id as $u ) {
			foreach ( get_object_vars( $u ) as $k => $v ) {
				if ( 0 === strpos( $k, '_gamipress_nivel-' ) && isset( $rank_index[ (int) $v ] ) ) {
					$post_data               = $rank_index[ (int) $v ];
					$u->{$post_data->post_type} = (int) $post_data->menu_order;
				}
			}
		}
	}

	$meta_users = array_values( $by_id );
	$cache      = $meta_users;
	set_transient( $cache_key, $meta_users, 5 * MINUTE_IN_SECONDS );
	return $meta_users;
}



function wp_amsawal_sort_leaders_gamipress ($meta_users, $order_by) {
	if (empty($meta_users) || !is_array($meta_users)) return array();
	usort ($meta_users, function ($a_obj, $b_obj) use ($order_by) {
			if(!isset($a_obj->$order_by)) { return -1; }
			if(!isset($b_obj->$order_by)) { return 1; }
			$a = $a_obj->$order_by;
			$b = $b_obj->$order_by;
			if ($a == $b) { return 0; }
			elseif ($a < $b) { return 1; }
			else { return -1; }
		});
	return $meta_users;
}


function wp_amsawal_friends_leaders_gamipress ($meta_users) {
	if (!function_exists('friends_get_friend_user_ids') || !function_exists('bp_loggedin_user_id')) return array();
	if (empty($meta_users) || !is_array($meta_users)) return array();
	$me = bp_loggedin_user_id();
	$friends = friends_get_friend_user_ids($me);
	$meta_friends = array();
	foreach ($meta_users as $user) {
		if ($user->ID == $me) {
			$meta_friends[] = $user;
		}
		elseif (in_array($user->ID, $friends, true)) {
			$meta_friends[] = $user;
		}
	}
	return $meta_friends;
}


function wp_amsawal_show_leaders ($meta, $content_type, $param1, $param2 = null) {
	$content = '<div class="duo-leader-list">';
	$i = 1;
	$found = false;
	global $current_user;
	wp_get_current_user();

	$rank_emoji = array(1 => '🥇', 2 => '🥈', 3 => '🥉');

	foreach ($meta as $user) {
		$level = 'nivel-'.$content_type;
		$test = true;
		if ($content_type != 'monedas') {
			if (empty($user->{$level}) ||  $user->{$level} == 0) {
				$test = false;
				if ($current_user->ID == $user->ID) {
					$found = true;
				}
			}
		}
		
		$is_me = ($current_user->ID == $user->ID);
		$card_class = $is_me ? 'duo-leader-card duo-leader-card--me' : 'duo-leader-card';

		$profile_url = '';
		if ( function_exists( 'bp_members_get_user_url' ) ) {
			$profile_url = bp_members_get_user_url( (int) $user->ID );
		} elseif ( function_exists( 'bp_core_get_user_domain' ) ) {
			$profile_url = bp_core_get_user_domain( (int) $user->ID );
		}

		if ($i <= 10 && $test) {
			$emoji = isset($rank_emoji[$i]) ? $rank_emoji[$i] : $i;

			$score = ($content_type == 'monedas') ? $user->{$param1} : $user->{$level};
			$score_label = ($content_type == 'monedas') ? '🪙' : '⭐';

			$content .= '<div class="'.$card_class.'">';
			$content .= '<span class="duo-leader-rank">'.$emoji.'</span>';
			$avatar = get_avatar($user->ID, 40);
			$has_gravatar = (strpos($avatar, 'gravatar.com/avatar/') === false || strpos($avatar, 'd=wavatar') !== false || strpos($avatar, 'd=mm') !== false || strpos($avatar, 'd=mp') !== false);
			$initials = strtoupper(mb_substr($user->nickname ?: ($user->display_name ?: 'U'), 0, 1));
			if ($has_gravatar) {
				$avatar_html = '<span class="duo-leader-avatar" aria-hidden="true"><span class="duo-leader-avatar-initials">'.esc_html($initials).'</span></span>';
			} else {
				$avatar_html = '<span class="duo-leader-avatar">'.$avatar.'</span>';
			}
			if ($profile_url) {
				$content .= '<a class="duo-leader-avatar-link" href="'.esc_url($profile_url).'">'.$avatar_html.'</a>';
			} else {
				$content .= $avatar_html;
			}
			$content .= '<span class="duo-leader-name">';
			if ($profile_url) {
				$content .= '<a class="duo-leader-name-link" href="'.esc_url($profile_url).'">'.esc_html($user->nickname).'</a>';
			} else {
				$content .= esc_html($user->nickname);
			}
			$content .= '</span>';
			$content .= '<span class="duo-leader-score">'.esc_html($score_label).' '.esc_html($score).'</span>';
			if (isset($param2)) {
				$content .= '<span class="duo-leader-total">📊 '.esc_html($user->{$param2}).'</span>';
			}
			$content .= '</div>';

			if ($is_me) $found = true;
		}
		elseif ($found) {
			break;
		}
		elseif ($is_me) {
			$score = ($content_type == 'monedas') ? $user->{$param1} : $user->{$level};
			$score_label = ($content_type == 'monedas') ? '🪙' : '⭐';
			$content .= '<div class="duo-leader-card duo-leader-card--me duo-leader-card--far">';
			$content .= '<span class="duo-leader-rank">'.$i.'</span>';
			$avatar = get_avatar($user->ID, 40);
			$has_gravatar = (strpos($avatar, 'gravatar.com/avatar/') === false || strpos($avatar, 'd=wavatar') !== false || strpos($avatar, 'd=mm') !== false || strpos($avatar, 'd=mp') !== false);
			$initials = strtoupper(mb_substr($user->nickname ?: ($user->display_name ?: 'U'), 0, 1));
			if ($has_gravatar) {
				$avatar_html = '<span class="duo-leader-avatar" aria-hidden="true"><span class="duo-leader-avatar-initials">'.esc_html($initials).'</span></span>';
			} else {
				$avatar_html = '<span class="duo-leader-avatar">'.$avatar.'</span>';
			}
			if ($profile_url) {
				$content .= '<a class="duo-leader-avatar-link" href="'.esc_url($profile_url).'">'.$avatar_html.'</a>';
				$content .= '<span class="duo-leader-name"><a class="duo-leader-name-link" href="'.esc_url($profile_url).'">'.esc_html($user->nickname).'</a></span>';
			} else {
				$content .= $avatar_html;
				$content .= '<span class="duo-leader-name">'.esc_html($user->nickname).'</span>';
			}
			$content .= '<span class="duo-leader-score">'.esc_html($score_label).' '.esc_html($score).'</span>';
			$content .= '</div>';
			break;
		}
		$i++;
	}
	
	$content .= '</div>';
	return $content;
}
