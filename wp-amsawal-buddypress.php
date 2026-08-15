<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * wp-amsawal-buddypress.php — BuddyPress integration for Amsawal
 *
 * - Hides BP chrome in immersive mode where needed
 * - Adds profile link to the topbar for logged-in users
 * - Styles BuddyPress profile pages within the Amsawal theme
 *
 * @package Amsawal
 * @since   0.0.2-preta
 */

/*───────────────────────────────────────────────────────────────────────
 * 1. Hide BuddyPress chrome in specific pages
 *───────────────────────────────────────────────────────────────────────*/

add_filter( 'the_content', 'wp_amsawal_buddypress' );
function wp_amsawal_buddypress ( $content ) {
	if ( is_admin() || ! function_exists( 'buddypress' ) ) return $content;

	$pagename = get_query_var( 'pagename' );
	$bp_profile_pages = array( 'activity', 'profile', 'notifications', 'messages', 'friends', 'groups', 'settings' );
	$bp_hidden_pages = array( 'logros', 'rango' );

	if ( in_array( $pagename, $bp_hidden_pages, true ) ) {
		wp_add_inline_style( 'pure-js-style-css', '.main-navs, .users-header { display: none !important; }' );
	} elseif ( in_array( $pagename, $bp_profile_pages, true ) ) {
		wp_add_inline_style( 'pure-js-style-css', '#item-header { display: none !important; }' );
	}

	return $content;
}

/*───────────────────────────────────────────────────────────────────────
 * 2. BuddyPress profile link for the topbar stats area
 *    Hooked to wp_footer to inject a profile avatar button into .duo-topbar-stats
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_footer', 'wp_amsawal_buddypress_profile_inject' );
function wp_amsawal_buddypress_profile_inject() {
	if ( is_admin() || ! is_user_logged_in() || ! function_exists( 'buddypress' ) ) {
		return;
	}
	// Inject profile avatar into .duo-topbar-stats via JS so we do not break
	// the PHP template's static string.
	$user_id     = get_current_user_id();
	$profile_url = function_exists( 'bp_members_get_user_url' )
		? bp_members_get_user_url( $user_id )
		: ( function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : home_url( '/members/' . bp_core_get_username( $user_id ) . '/profile/' ) );
	$avatar_url  = bp_core_fetch_avatar( array(
		'item_id' => $user_id,
		'width'   => 28,
		'height'  => 28,
		'html'    => false,
		'type'    => 'thumb',
	) );
	?>
	<script>
	(function() {
		var topbarStats = document.querySelector('.duo-topbar-stats');
		if (!topbarStats || topbarStats.querySelector('.duo-topbar-profile')) return;
		var link = document.createElement('a');
		link.href = '<?php echo esc_url( $profile_url ); ?>';
		link.className = 'duo-topbar-profile duo-stat-item';
		link.title = '<?php esc_attr_e( 'Mi perfil', WP_AMSAWAL_TEXTDOMAIN ); ?>';
		var img = document.createElement('img');
		img.src = '<?php echo esc_url( $avatar_url ); ?>';
		img.width = 28;
		img.height = 28;
		img.style.borderRadius = '50%';
		img.alt = '<?php esc_attr_e( 'Mi perfil', WP_AMSAWAL_TEXTDOMAIN ); ?>';
		link.appendChild(img);
		topbarStats.appendChild(link);
	})();
	</script>
	<?php
}

/*───────────────────────────────────────────────────────────────────────
 * 3. Inject GamiPress stats (rank, XP, achievements) into BP member header
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'bp_member_header_actions', 'wp_amsawal_bp_inject_gamipress_stats' );
add_action( 'bp_before_member_home', 'wp_amsawal_bp_inject_gamipress_stats' );

function wp_amsawal_bp_inject_gamipress_stats() {
    if ( ! function_exists( 'gamipress_get_user_points' ) ) {
        return;
    }

    $user_id = bp_displayed_user_id();
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    if ( ! $user_id ) {
        return;
    }

    $coins = (int) gamipress_get_user_points( $user_id, 'monedas' );
    $xp    = (int) gamipress_get_user_points( $user_id, 'xp' );
    $streak = (int) get_user_meta( $user_id, '_wp_amsawal_streak_days', true );

    // Rank from first course
    $rank_label = '';
    $courses = function_exists( 'wp_amsawal_get_courses' ) ? wp_amsawal_get_courses() : array();
    if ( ! empty( $courses ) && function_exists( 'gamipress_get_user_rank' ) ) {
        $rank = gamipress_get_user_rank( $user_id, 'nivel' );
        if ( $rank ) {
            $rank_label = $rank->post_title;
        }
    }

    // Achievements count
    $achievements = get_user_meta( $user_id, 'amsawal_achievements', true );
    $ach_count = is_array( $achievements ) ? count( $achievements ) : 0;

    echo '<div class="duo-bp-stats-bar" style="display:flex;gap:12px;flex-wrap:wrap;margin:12px 0;padding:12px;background:rgba(var(--color-primary),0.06);border-radius:var(--radius-md,12px);">';
    echo '<span class="duo-bp-stat" title="' . esc_attr__( 'Rango', WP_AMSAWAL_TEXTDOMAIN ) . '">🛡️ ' . esc_html( $rank_label ? $rank_label : '-' ) . '</span>';
    echo '<span class="duo-bp-stat" title="' . esc_attr__( 'Monedas', WP_AMSAWAL_TEXTDOMAIN ) . '">💰 ' . esc_html( number_format_i18n( $coins ) ) . '</span>';
    echo '<span class="duo-bp-stat" title="' . esc_attr__( 'Experiencia', WP_AMSAWAL_TEXTDOMAIN ) . '">⭐ ' . esc_html( number_format_i18n( $xp ) ) . ' XP</span>';
	echo '<span class="duo-bp-stat" title="' . esc_attr__( 'Racha', WP_AMSAWAL_TEXTDOMAIN ) . '">⭐ ' . esc_html( $streak ) . ' ' . esc_html__( 'días', WP_AMSAWAL_TEXTDOMAIN ) . '</span>';
	echo '<span class="duo-bp-stat" title="' . esc_attr__( 'Logros', WP_AMSAWAL_TEXTDOMAIN ) . '">🏆 ' . esc_html( $ach_count ) . '</span>';
    echo '</div>';
}

/*───────────────────────────────────────────────────────────────────────
 * 4. Enqueue BuddyPress-specific styles on BP pages and profile pages
 *───────────────────────────────────────────────────────────────────────*/

add_action( 'wp_enqueue_scripts', 'wp_amsawal_buddypress_styles' );
function wp_amsawal_buddypress_styles() {
	// Load styles on BuddyPress pages and /i/ profile pages
	if ( ! function_exists( 'buddypress' ) ) {
		return;
	}
	$is_bp_page = is_buddypress();
	$is_profile_page = get_query_var( 'amsawal_profile' );

	if ( ! $is_bp_page && ! $is_profile_page ) {
		return;
	}
	wp_add_inline_style( 'pure-js-style-css', wp_amsawal_buddypress_css() );
}

/*───────────────────────────────────────────────────────────────────────
 * 4. Inline CSS for BuddyPress integration
 *───────────────────────────────────────────────────────────────────────*/

function wp_amsawal_buddypress_css() {
	return '
/* ═══════════════════════════════════════════════════════════════════ */
/* AMSAWAL THEME — BUDDYPRESS INTEGRATION                           */
/* ═══════════════════════════════════════════════════════════════════ */

/* ── Global Wrapper ── */
.buddypress-wrap,
.bp-user #buddypress,
.directory #buddypress {
	max-width: 800px;
	margin: 80px auto 40px;
	padding: 20px;
}

/* ── Member Profile Header ── */
#item-header {
	background: linear-gradient(135deg, var(--amsawal-primary) 0%, var(--amsawal-primary-dark) 100%);
	border-radius: 16px;
	padding: 30px;
	margin-bottom: 24px;
	color: #fff;
	box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

#item-header-avatar img {
	border-radius: 50%;
	border: 4px solid rgba(255,255,255,0.3);
	width: 120px;
	height: 120px;
	box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

#item-header-content {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

#item-header-content h2,
#item-header-content .user-nicename,
#item-header-content .member-type {
	color: #fff !important;
	margin: 0;
	padding: 0;
}

#item-header-content h2 {
	font-size: 28px;
	font-weight: 700;
}

#item-header-content .user-nicename {
	font-size: 16px;
	opacity: 0.9;
}

#item-header-content .member-type {
	font-size: 14px;
	opacity: 0.8;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

/* ── Profile Navigation ── */
#object-nav,
.bp-user .bp-navs {
	background: #fff;
	border-radius: 12px;
	padding: 8px;
	margin-bottom: 20px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

#object-nav ul,
.bp-navs ul {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	list-style: none;
	margin: 0;
	padding: 0;
}

#object-nav li a,
.bp-navs li a {
	display: block;
	padding: 10px 16px;
	border-radius: 8px;
	font-weight: 600;
	font-size: 14px;
	color: #2c3e50 !important;
	text-decoration: none !important;
	transition: all 0.2s;
	border: 2px solid transparent;
}

#object-nav li a:hover,
.bp-navs li a:hover {
	background: #f8f9fa;
	border-color: var(--amsawal-primary-light, #e3f2fd);
}

#object-nav li.current a,
.bp-navs li.current a {
	background: var(--amsawal-primary) !important;
	color: #fff !important;
	border-color: var(--amsawal-primary) !important;
}

/* ── Profile Content Area ── */
.bp-user #item-body,
.buddypress #item-body {
	background: #fff;
	border-radius: 12px;
	padding: 24px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* ── Activity Stream ── */
.activity-list .activity-item {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 10px;
	padding: 20px;
	margin-bottom: 16px;
	transition: box-shadow 0.2s;
}

.activity-list .activity-item:hover {
	box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.activity-header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 12px;
}

.activity-avatar img {
	border-radius: 50%;
	width: 48px;
	height: 48px;
}

.activity-meta {
	flex: 1;
}

.activity-user {
	font-weight: 600;
	color: #2c3e50;
	text-decoration: none;
}

.activity-user:hover {
	color: var(--amsawal-primary);
}

.activity-time {
	font-size: 13px;
	color: #6c757d;
}

.activity-content {
	margin-left: 60px;
}

/* ── Member Directory ── */
#members-list {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
	gap: 20px;
	list-style: none;
	padding: 0;
	margin: 0;
}

#members-list .item {
	background: #fff;
	border: 2px solid #e5e7eb;
	border-radius: 12px;
	padding: 20px;
	text-align: center;
	transition: all 0.2s;
}

#members-list .item:hover {
	border-color: var(--amsawal-primary);
	box-shadow: 0 4px 12px rgba(0,0,0,0.1);
	transform: translateY(-2px);
}

#members-list .item-avatar {
	margin-bottom: 12px;
}

#members-list .item-avatar img {
	border-radius: 50%;
	border: 3px solid var(--amsawal-primary-light, #e3f2fd);
	width: 80px;
	height: 80px;
}

#members-list .item-title {
	font-weight: 600;
	font-size: 16px;
	margin: 8px 0;
}

#members-list .item-title a {
	color: #2c3e50;
	text-decoration: none;
}

#members-list .item-title a:hover {
	color: var(--amsawal-primary);
}

/* ── Buttons ── */
.buddypress input[type="submit"],
.buddypress a.button,
.buddypress button.button,
.bp-buttons a {
	background: var(--amsawal-primary) !important;
	color: #fff !important;
	border: none !important;
	border-radius: 8px !important;
	padding: 10px 20px !important;
	font-weight: 600 !important;
	cursor: pointer;
	transition: all 0.2s !important;
	text-decoration: none !important;
	display: inline-block !important;
}

.buddypress input[type="submit"]:hover,
.buddypress a.button:hover,
.buddypress button.button:hover,
.bp-buttons a:hover {
	background: var(--amsawal-primary-dark) !important;
	transform: translateY(-1px);
	box-shadow: 0 4px 8px rgba(0,153,204,0.3) !important;
}

/* ── Notifications ── */
.notifications .notification-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.notifications .notification-list li {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 10px;
	padding: 16px 20px;
	margin-bottom: 12px;
	transition: background 0.2s;
}

.notifications .notification-list li:hover {
	background: #f8f9fa;
}

.notifications .notification-list li.unread {
	border-left: 4px solid var(--amsawal-primary);
	background: #f0f9ff;
}

/* ── Messages ── */
.bp-messages #message-threads {
	border: 1px solid #e5e7eb;
	border-radius: 12px;
	overflow: hidden;
	background: #fff;
}

.bp-messages #message-threads .thread {
	border-bottom: 1px solid #e5e7eb;
	padding: 12px 20px;
	transition: background 0.2s;
}

.bp-messages #message-threads .thread:hover {
	background: #f8f9fa;
}

.bp-messages #message-threads .thread:last-child {
	border-bottom: none;
}

/* ── Groups ── */
.groups-list .group-item {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 10px;
	padding: 16px;
	margin-bottom: 12px;
	display: flex;
	gap: 16px;
	align-items: center;
}

.groups-list .group-item:hover {
	box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.group-avatar img {
	border-radius: 8px;
	width: 64px;
	height: 64px;
}

/* ── Responsive ── */
@media (max-width: 768px) {
	.buddypress-wrap,
	.bp-user #buddypress,
	.directory #buddypress {
		margin: 60px 12px 40px;
		padding: 0;
	}

	#item-header {
		flex-direction: column;
		text-align: center;
		padding: 20px;
	}

	#item-header-avatar {
		margin: 0 auto 16px;
	}

	#object-nav ul,
	.bp-navs ul {
		flex-direction: column;
	}

	#members-list {
		grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		gap: 12px;
	}

	.activity-content {
		margin-left: 0;
	}
}

/* ── Leaderboard Links ── */
.duo-leaderboard-avatar-link,
.duo-leader-name-link {
	color: var(--amsawal-primary);
	text-decoration: none;
	font-weight: 600;
	transition: color 0.2s;
}

.duo-leaderboard-avatar-link:hover,
.duo-leader-name-link:hover {
	color: var(--amsawal-primary-dark);
}

/* ── Topbar Profile Avatar ── */
.duo-topbar-profile {
	display: flex;
	align-items: center;
	gap: 8px;
}

.duo-topbar-profile-avatar {
	width: 32px;
	height: 32px;
	border-radius: 50%;
	overflow: hidden;
	border: 2px solid #fff;
}

.duo-topbar-profile-avatar img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}
';
}
