<?php
/**
 * @package WordPress
 */
// NOTE: This page serves as the "Default Template"
get_header(); ?>
    <div class="contentwrapper">
        <section class="page 404">
            <div id="page_featured">
                <img class="featured_image" src="<?php echo TEMPLATE_URL ?>/images/featured.jpg" alt="">
            </div>
            <article>
                <h1>We are very sorry but...</h1>
                <p>You have found a page that has been moved or no longer exists. Please use the navigation menu above
                    or the search option below.<br><br><cite>-Thank you!</cite></p>
				<?php include( TEMPLATEPATH . '/searchform.php' ); ?>
            </article>
            <aside>
				<?php mosaic_get_sidebar( "default_sidebar", "", TRUE ); ?>
            </aside>
        </section>
    </div>
<?php get_footer(); ?>