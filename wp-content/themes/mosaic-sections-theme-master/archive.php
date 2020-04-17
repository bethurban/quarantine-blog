<?php
/**
 * @package Alpha Channel Group Base Theme
 * @author  Alpha Channel Group (www.alphachannelgroup.com)
 */

$show_sidebar = MosaicTheme::get_option( 'archive_sidebar' );
$scheme       = MosaicTheme::get_option( ( 'archive_scheme' ) );
MosaicPostDisplay::init( 'archive' );

get_header(); ?>
    <div class="contentwrapper <?php echo $scheme; ?>">
		<?php do_action( 'mosaic_top_of_post', [ 'archive', get_post_type() ] ) ?>
        <section class="blog archive">
			<?php if ( 'left' == $show_sidebar ) { ?>
                <aside class="sidebar blog-sidebar archive-sidebar left-sidebar">
					<?php mosaic_get_sidebar( "blog_sidebar", "", TRUE ); ?>
                </aside>
			<?php } ?>
            <article>
				<?php if ( have_posts() ) : ?>
                    <h2>
						<?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
						<?php /* If this is a category archive */
						if ( is_category() ) { ?>
							<?php single_cat_title(); ?>
							<?php /* If this is a tag archive */
						} elseif ( is_tag() ) { ?>
                            Stuff in the &#8216;<?php single_tag_title(); ?>&#8217; Category
							<?php /* If this is a daily archive */
						} elseif ( is_day() ) { ?>
                            Archive for <?php the_time( 'F jS, Y' ); ?>
							<?php /* If this is a monthly archive */
						} elseif ( is_month() ) { ?>
                            Archive for <?php the_time( 'F, Y' ); ?>
							<?php /* If this is a yearly archive */
						} elseif ( is_year() ) { ?>
                            Archive for <?php the_time( 'Y' ); ?>
							<?php /* If this is an author archive */
						} elseif ( is_author() ) { ?>
                            Author Archive
							<?php /* If this is a paged archive */
						} elseif ( isset( $_GET['paged'] ) && ! empty( $_GET['paged'] ) ) { ?>
                            Blog Archives
						<?php } ?>
                    </h2>
					<?php while ( have_posts() ) :
						the_post(); ?>
                        <div <?php post_class() ?>>
							<?php
							$permalink = get_the_permalink();
							if ( MosaicTheme::get_option( 'stay_in_category' ) ) {
								$permalink = add_query_arg( 'cat_specific', get_query_var( 'cat' ), $permalink );
							}
							MosaicPostDisplay::display();
							?>
                        </div>
					<?php endwhile;
					MosaicTheme::navigation( 'archive' );
				else:
					if ( is_category() ) { // If this is a category archive
						printf( "<h2 class='center'>Sorry, but there aren't any posts in the %s category yet.</h2>", single_cat_title( '', FALSE ) );
					} else if ( is_date() ) { // If this is a date archive
						echo( "<h2>Sorry, but there aren't any posts with this date.</h2>" );
					} else if ( is_author() ) { // If this is a category archive
						$userdata = get_userdatabylogin( get_query_var( 'author_name' ) );
						printf( "<h2 class='center'>Sorry, but there aren't any posts by %s yet.</h2>", $userdata->display_name );
					} else {
						echo( "<h2 class='center'>No posts found.</h2>" );
					}
					require_once( TEMPLATEPATH . '/searchform.php' );
				endif; ?>
            </article>
			<?php if ( 'left' == $show_sidebar ) { ?>
                <aside class="sidebar blog-sidebar archive-sidebar left-sidebar">
					<?php mosaic_get_sidebar( "blog_sidebar", "", TRUE ); ?>
                </aside>
			<?php } ?>
        </section>
    </div>
<?php get_footer(); ?>