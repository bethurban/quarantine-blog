<?php
/**
 * @package Mosaic Base Theme
 * @author  Mosaic Strategies Group (www.mosaicstg.com)
 */

$content_class  = '';
$sidebar_active = '';

get_header(); ?>
    <div class="contentwrapper">
        <section class="page">
			<?php
			if ( has_post_thumbnail() ) {
				echo '<div class="featured-hero-wrapper" style="background: url(';
				the_post_thumbnail_url();
				echo ') center center / cover no-repeat">';
			} else {
				echo '<div class="featured-hero-wrapper">';
			}

			if ( get_the_title() ) {
				echo '<div class="hero-title-wrapper">';
				echo '<h1>' . get_the_title() . '</h1>';
				echo '</div>';
			}
			echo '</div>';
			?>
			<?php if ( is_active_sidebar( 'default_sidebar' ) ) {
				$content_class  = 'with-sidebar';
				$sidebar_active = 'active';
			} ?>
            <article class="<?php echo $content_class; ?>">
				<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
					?>
                    <div class="post" id="post-<?php the_ID(); ?>">
						<?php the_content( '<p class="serif">Read the rest of this page &raquo;</p>' ); ?>
                    </div>
					<?php
					edit_post_link( 'Edit Page', '<div class="edit_link">', '</div>' );
					MosaicTheme::comments();
				endwhile; endif; ?>
            </article>
            <aside class="<?php echo $sidebar_active; ?>">
				<?php mosaic_get_sidebar( "default_sidebar", "" ); ?>
            </aside>
        </section>
    </div>
<?php get_footer(); ?>