<?php
/**
 * @package Mosaic Strategies Group Section Theme
 * @author  Mosaic Strategies Group (www.mosaicstg.com)
 */
/*
 * Template Name: Blog
 */

$blog_content_class = trim( MosaicTheme::get_option( 'blog_content', 'full' ) );
$blog_title         = MosaicTheme::get_option( 'blog_page_title' );
$scheme             = MosaicTheme::get_option( ( 'blog_scheme' ) );

if ( $blog_title ) {
	$blog_title = '<h1 class="bloghead">' . $blog_title . '</h1>';
}

$show_sidebar = MosaicTheme::get_option( 'blog_sidebar' );
MosaicPostDisplay::init( 'blog' );

/**
 * Note: the output of the blog is heavily controllable via filters.
 * For example, to change the size of the post thumbnail here:
 *
 * add_filter('mosaic_blog_featured_image_size', function() { return 'large'; });
 *
 * For more info on the filters available, check out the MosaicPostDisplay class in the functions.php file
 *
 * Additionally, the structure is such that it's simple to hide / show things via CSS.
 *
 * For example, when showing the "date and author", if you don't want the words "Posted On", simply add
 * the following to your custom css:
 *
 * .structured-data .posted-on {
 *     display: none;
 * }
 *
 * Which would hide the "Posted On" on the blog, archive, and single.
 *
 * Additionally, classes are provided so you can independently address the blog listing, vs the archive, vs the single.
 * So, to hide the "Posted On" on the blog only, you would do this:
 *
 * .listing .structured-data .posted-on {
 *     display: none;
 * }
 *
 * Or, to hide on archive only, this:
 *
 * .archive .structured-data .posted-on {
 *     display: none;
 * }
 *
 */
get_header(); ?>
    <div class="contentwrapper <?php echo $scheme; ?>">
		<?php do_action( 'mosaic_top_of_post', [ 'blog', 'post' ] ) ?>
        <section class="blog listing">
			<?php if ( 'left' == $show_sidebar ) { ?>
                <aside class="sidebar blog-sidebar left-sidebar">
					<?php mosaic_get_sidebar( "blog_sidebar", "", TRUE ); ?>
                </aside>
			<?php } ?>
            <article>
				<?php echo $blog_title; ?>
				<?php
				global $more;
				$more  = 0;
				$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
				query_posts( 'post_type=post&paged=' . $paged );
				while ( have_posts() ) :
					the_post();
					?>
                    <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
						<?php MosaicPostDisplay::display(); ?>
                    </div>
				<?php endwhile;
				MosaicTheme::navigation( 'blog' );
				?>
            </article>
			<?php if ( 'right' == $show_sidebar ) { ?>
                <aside class="sidebar blog-sidebar right-sidebar">
					<?php mosaic_get_sidebar( "blog_sidebar", "", TRUE ); ?>
                </aside>
			<?php } ?>
        </section>
    </div>
<?php get_footer();