<?php
/**
 * @package WordPress
 */
/*
 * Template Name: Sections Page - No Sidebar
 */
get_header(); ?>
    <div class="contentwrapper">
        <section>
				<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
					/**
					 * Load the section data into the $section variable
					 *
					 * NOTES:
					 * 1. The sections should be in the correct order, so simply foreach() over them.
					 * 2. The sub-sections should also be in the correct order, so simply output them
					 */
					if ( ! empty( $mosaic_home_template ) && is_callable( [ $mosaic_home_template, 'render_sections' ] ) ) {
						$mosaic_home_template->render_sections();
					} else {
						echo '<h2>Require function <code>MosaicHomeTemplateRender->render_sections()</code> is missing!</h2>';
					}
				endwhile; endif; ?>
        </section>
    </div>
<?php get_footer();

