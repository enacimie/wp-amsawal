<?php
/**
 * Template para posts individuales (no usado por el plugin pero
 * requerido para no caer al fallback en sitios con blog).
 *
 * @package Amsawal
 */
get_header();
?>
<main id="duo-main-content" class="amsawal-site-main amsawal-single" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
