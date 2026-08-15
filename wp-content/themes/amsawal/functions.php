<?php
/**
 * Amsawal theme — bootstrap.
 *
 * Filosofía: este theme es deliberadamente minimalista. El diseño visual
 * vive en el plugin (`css/pure-js-style.css`) para que un cambio en el
 * design system se propague sin tocar el theme. Aquí solo definimos:
 *
 *   1. Constantes y soporte de features (title-tag, post-thumbnails, etc.)
 *   2. Dependencia del CSS ya cargado por el plugin
 *   3. Limpieza de chrome innecesario (no emojis en admin bar, etc.)
 *   4. Soporte i18n (textdomain 'amsawal')
 *
 * @package Amsawal
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AMSAWAL_THEME_VERSION', '0.1.0' );

/**
 * Theme setup básico.
 */
function amsawal_theme_setup() {
        // title-tag dinámico (mejor SEO, evita hardcoded <title>).
        add_theme_support( 'title-tag' );
        // Imágenes destacadas para cursos y lecciones.
        add_theme_support( 'post-thumbnails' );
        // HTML5 semántico en formularios, galerías, etc.
        add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
        // Logo personalizado.
        add_theme_support( 'custom-logo', array(
                'height'      => 60,
                'width'       => 200,
                'flex-height' => true,
                'flex-width'  => true,
        ) );
        // Anchos automáticos para contenido embebido.
        add_theme_support( 'responsive-embeds' );
        // Bloque styles frontend.
        add_theme_support( 'wp-block-styles' );
        // Cargar textdomain del theme (si en el futuro se extraen strings).
        load_theme_textdomain( 'amsawal', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'amsawal_theme_setup' );

/**
 * Enqueue: depender del CSS ya cargado por el plugin + override del theme.
 *
 * El plugin YA registra y carga `pure-js-style-css` y `amsawal-brand-override`.
 * Este theme NO duplica la carga, solo asegura el orden correcto:
 *   1. pure-js-style-css (del plugin)
 *   2. amsawal-brand-override (del plugin)
 *   3. theme.css (override final del theme)
 */
function amsawal_enqueue_assets() {
        // CSS propio del theme (mínimo, solo layout chrome).
        // Depende del plugin para garantizar orden de cascada.
        wp_enqueue_style(
                'amsawal-theme-style',
                get_template_directory_uri() . '/theme.css',
                array( 'pure-js-style-css', 'amsawal-brand-override' ),
                AMSAWAL_THEME_VERSION
        );
}
add_action( 'wp_enqueue_scripts', 'amsawal_enqueue_assets', 20 );

/**
 * Limpia el admin bar default que estorba el sidebar del plugin.
 * El plugin dibuja su propio topbar + sidebar; el admin bar de WP
 * se vuelve redundante y rompe la estética.
 */
function amsawal_hide_admin_bar_for_logged_in() {
        if ( is_user_logged_in() && ! is_admin() ) {
                add_filter( 'show_admin_bar', '__return_false' );
        }
}
add_action( 'wp_head', 'amsawal_hide_admin_bar_for_logged_in', 1 );
