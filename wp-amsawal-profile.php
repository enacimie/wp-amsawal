<?php
/**
 * Profile page integration
 * Registers /i/{username}/ rewrite rule and loads profile template
 */

if (!defined('ABSPATH')) exit;

// Register rewrite rule for profile pages
function wp_amsawal_register_profile_rewrite() {
    add_rewrite_rule(
        '^i/([^/]+)/?$',
        'index.php?amsawal_profile=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^i/?$',
        'index.php?amsawal_profile=self',
        'top'
    );
}
add_action('init', 'wp_amsawal_register_profile_rewrite');

// Add query var
function wp_amsawal_add_profile_query_var($vars) {
    $vars[] = 'amsawal_profile';
    return $vars;
}
add_filter('query_vars', 'wp_amsawal_add_profile_query_var');

// Load profile template
function wp_amsawal_load_profile_template($template) {
    $username = get_query_var('amsawal_profile');
    
    if (empty($username)) {
        return $template;
    }
    
    // Redirect to login if not logged in
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(home_url('/i/' . ($username === 'self' ? '' : $username))));
        exit;
    }
    
    // Get user
    $user = ($username === 'self') 
        ? wp_get_current_user() 
        : get_user_by('login', urldecode($username));
    
    if (!$user) {
        status_header(404);
        nocache_headers();
    }
    
    // Store profile data globally
    global $wp_amsawal_current_profile;
    $wp_amsawal_current_profile = $user;
    
    // Load our custom template
    $plugin_template = plugin_dir_path(__FILE__) . 'templates/profile-template.php';
    
    if (file_exists($plugin_template)) {
        return $plugin_template;
    }
    
    return $template;
}
add_filter('template_include', 'wp_amsawal_load_profile_template');

// Flush rewrite rules on activation
function wp_amsawal_flush_profile_rewrites() {
    wp_amsawal_register_profile_rewrite();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wp_amsawal_flush_profile_rewrites');
