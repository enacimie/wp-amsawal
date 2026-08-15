<?php
/**
 * Template para páginas individuales.
 *
 * @package Amsawal
 */
get_header();
?>
<main id="duo-main-content" class="amsawal-site-main amsawal-page" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
