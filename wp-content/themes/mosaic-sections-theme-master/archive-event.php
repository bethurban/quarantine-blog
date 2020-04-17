<?php

/**
 * @package Mosaic Strategies Group Sections Theme
 * @author  Mosaic Strategies Group (www.mosaicstg.com)
 */

$archive_content = MosaicTheme::get_option('archive_content', 'excerpt');

get_header(); ?>
    <div class="contentwrapper">
        <section class="blog archive event-list">
            <div class="featured-hero-wrapper">
                <div class="hero-title-wrapper">
                    <h1>Events</h1>
                </div>
            </div>
            <article>
                <aside>
					<?php mosaic_get_sidebar( "event_sidebar", "", TRUE ); ?>
                </aside>
                <div class="event-list-inner">
					<?php if ( have_posts() ) : ?>
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

						<?php while ( have_posts() ) : the_post();
							$start_date    = mosaicEventStartDate( $post->ID );
							$start_day     = mosaicEventStartDate( $post->ID, 'D' );
							$start_time    = mosaicEventMeta( $post->ID, 'start_time' );
							$event_address = mosaicEventAddress( $post->ID );

							if ( $start_date ) { ?>
                                <div <?php post_class() ?>>
									<?php
									$permalink = get_the_permalink();
									if ( MosaicTheme::get_option( 'stay_in_category' ) ) {
										$permalink = add_query_arg( 'cat_specific', get_query_var( 'cat' ), $permalink );
									}
									?>
                                    <div class="event-date">
                                        <div class="event-day">
                                            <h3>
												<?php echo $start_day; ?>
                                            </h3>
                                        </div>
                                        <div class="event-day-month">
                                            <h3>
												<?php echo $start_date; ?>
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="event-info">
										<?php if ( has_post_thumbnail( $post->ID ) ) { ?>
                                            <div class="event-featured-image">
                                                <img src="<?php the_post_thumbnail_url( 'full' ); ?>">
                                            </div>
										<?php } ?>
                                        <div class="event-description">
                                            <h3 id="post-<?php the_ID(); ?>" class="blog-title"><a
                                                        href="<?php echo $permalink; ?>"
                                                        rel="bookmark"
                                                        title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
                                            </h3>
											<?php if ( $event_address || $start_time ) { ?>
                                                <div class="event-text">
                                                    <div class="event-time">
														<?php echo $start_time; ?>
                                                    </div>
													<?php if ( $start_date && $event_address ) { ?>
                                                        <div class="event-time-divider">
                                                            <i class="fa fa-circle fa-align-center"
                                                               aria-hidden="true"></i>
                                                        </div>
													<?php } ?>
                                                    <div class="event-location">
														<?php echo $event_address; ?>
                                                    </div>
                                                </div>
											<?php } ?>
                                            <div class="event-excerpt entry entry_<?php echo $archive_content; ?>">
												<?php
												//Requires use of 'Read more' tag on post to set cut off for post
												the_content( 'Read More' );
												edit_post_link( 'Edit Event' );
												?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
							<?php } ?>
						<?php endwhile; ?>

						<?php
						MosaicTheme::navigation( 'archive' );
					else:
						if ( is_category() ) { // If this is a category archive
							printf( "<h2 class='center'>Sorry, but there aren't any Events in the %s category yet.</h2>", single_cat_title( '', FALSE ) );
						} else if ( is_date() ) { // If this is a date archive
							echo( "<h2>Sorry, but there aren't any events with this date.</h2>" );
						} else if ( is_author() ) { // If this is a category archive
							$userdata = get_userdatabylogin( get_query_var( 'author_name' ) );
							printf( "<h2 class='center'>Sorry, but there aren't any events by %s yet.</h2>", $userdata->display_name );
						} else {
							echo( "<h2 class='center'>No events found.</h2>" );
						}
//						require_once( TEMPLATEPATH . '/searchform.php' );
					endif; ?>
                </div>
            </article>
        </section>
    </div>
<?php get_footer(); ?>