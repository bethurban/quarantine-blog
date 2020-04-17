<?php
/**
 * The Template for displaying all single posts.
 */

$show_sidebar = MosaicTheme::get_option( 'single_sidebar' );
$scheme       = MosaicTheme::get_option( ( 'single_scheme' ) );

get_header();
?>
    <div class="contentwrapper <?php echo $scheme; ?>">
		<?php do_action( 'mosaic_top_of_post', [ 'archive', get_post_type() ] ) ?>
        <section class="blog single">
			<?php if ( 'left' == $show_sidebar ) { ?>
                <aside class="sidebar blog-sidebar single-sidebar left-sidebar">
					<?php mosaic_get_sidebar( "blog_sidebar", "", TRUE ); ?>
                </aside>
			<?php } ?>
            <article>
				<?php if ( have_posts() ) :
					MosaicPostDisplay::init( 'single' );
					while ( have_posts() ) :
						the_post();
						?>
                        <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
							<?php MosaicPostDisplay::display(); ?>
                        </div>
                        <div class="navigation">
							<?php
							get_prev_nav_with_title( '<i class="fa fa-chevron-left"></i><span>Previous Post</span>' );
							get_next_nav_with_title( '<span>Next Post</span><i class="fa fa-chevron-right"></i>' );
							?>
                        </div>
					<?php endwhile;
				else: ?>
                    <p>Sorry, no posts matched your criteria.</p>
				<?php endif; ?>
            </article>
			<?php if ( 'right' == $show_sidebar ) { ?>
                <aside class="sidebar blog-sidebar single-sidebar right-sidebar">
					<?php mosaic_get_sidebar( "blog_sidebar", "", TRUE ); ?>
                </aside>
			<?php } ?>
        </section>
    </div>
<?php get_footer(); ?>