<?php
/**
 * Header del theme Amsawal.
 *
 * Deliberadamente minimalista: el plugin (wp-amsawal-menu.php) se encarga
 * de imprimir su propio skip-link, top-bar, sidebar y mascot. Aquí solo
 * dejamos el `<head>` y abrimos `<body>`.
 *
 * @package Amsawal
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> <?php echo function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ? '' : ''; ?>>
<?php
// Hook estándar para plugins (skip-link, topbar, etc.).
// El plugin lo usa para imprimir su chrome encima del contenido.
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
}
