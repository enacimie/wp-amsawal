<?php
/**
 * Plantilla principal (fallback) del theme Amsawal.
 *
 * Como el plugin renderiza su propio contenido mediante `the_content`
 * (o `render_block` en block themes), este template es deliberadamente
 * minimalista: solo imprime el contenido de la página/posts tal como
 * lo haya procesado el plugin.
 *
 * @package Amsawal
 */
get_header();
?>
<main id="duo-main-content" class="amsawal-site-main" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
