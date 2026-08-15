<?php
/**
 * Amsawal Home Progress
 * Enqueue home progress CSS
 */

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', 'wp_amsawal_enqueue_home_progress');
function wp_amsawal_enqueue_home_progress() {
    if (is_page('inicio') || is_front_page()) {
        wp_enqueue_style(
            'amsawal-home-progress',
            plugin_dir_url(__FILE__) . 'css/modules/_home-progress.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'css/modules/_home-progress.css')
        );
    }
}
