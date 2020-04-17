<?php
/**
 * The Template for displaying all single events.
 */
get_header(); ?>
  <div class="contentwrapper">
    <section class="blog single event-single">
      <div class="featured-hero-wrapper">
        <div class="hero-title-wrapper">
          <h1><?php the_title(); ?></h1>
        </div>
      </div>
      <article>
        <aside>
          <div class="event-details">
            <h2>Event Details</h2>
			  <?php
			  if ( get_post_meta( $post->ID, '_mosaic_events_start_date', TRUE ) ) {
				  $start_date = mosaicEventStartDate( $post->ID, 'F j, Y' );
				  $end_date   = mosaicEventEndDate( $post->ID, 'F j, Y' );

				  $start_time = mosaicEventMeta( $post->ID, 'start_time' );
				  $end_time   = mosaicEventMeta( $post->ID, 'end_time' );

				  $date              = "{$start_date}";
				  $has_same_end_date = ( $start_date === $end_date );

				  if ( $start_time ) {
					  $date .= " {$start_time}";
				  }

				  if ( ! $has_same_end_date ) {
					  $date .= " - {$end_date}";
				  }

				  if ( $end_time ) {
					  $separator = $has_same_end_date ? ( $start_time ? ' - ' : ' ending ' ) : ' ';
					  $date      .= "{$separator}{$end_time}";
				  }
				  ?>
                <div class="event-single-time">
                  <h3>Time</h3>
                  <div class="time">
					  <?php echo $date; ?>
                  </div>
                </div>
				  <?php
			  }
			  if ( get_post_meta( $post->ID, '_mosaic_events_address', TRUE ) ) {
				  $address    = get_post_meta( $post->ID, '_mosaic_events_address', TRUE );
				  $city       = get_post_meta( $post->ID, '_mosaic_events_city', TRUE );
				  $state      = get_post_meta( $post->ID, '_mosaic_events_state', TRUE );
				  $zip_code   = get_post_meta( $post->ID, '_mosaic_events_zip_code', TRUE );
				  $venue_name = get_post_meta( $post->ID, '_mosaic_events_venue_name', TRUE );
				  ?>
                <div class="event-single-location">
                  <h3>Location</h3>
					<?php if ( $venue_name ) { ?>
                      <div class="venue">
						  <?php echo $venue_name; ?>
                      </div>
					<?php } ?>
                  <div class="address">
					  <?php echo $address; ?>
                  </div>
					<?php if ( $city ) { ?>
                      <div class="address">
						  <?php echo $city . ', ' . $state . ' ' . $zip_code; ?>
                      </div>
						<?php
					} ?>
                </div>
				  <?php
			  }
			  ?>
          </div>
        </aside>
        <div class="event-single-inner">
			<?php if ( has_post_thumbnail() ) {
				?>
              <div class="event-featured-image">
                <img src="<?php the_post_thumbnail_url( 'full' ); ?>">
              </div>
				<?php
			} ?>
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
              <h1 class="entry-title"><?php the_title(); ?></h1>
              <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
				  <?php
				  /**
				   *
				   * !!!!!! STOP !!!!!!
				   *
				   * DEAR DEVELOPER:
				   * PLEASE DO NOT remove the date and / or author, or change the class on the title above (entry-title).
				   *
				   * These are required for Structured Data validation in Webmaster Tools.
				   *
				   * If you need to reorganize it, be sure it validates on the hentry validator.
				   *
				   * If you need to remove it, do so with css, NOT by removing it.
				   */
				  ?>
                <span class="structured-data">
                                        <span class="posted-on">Posted On </span><span
                          class="date post-date updated"><?php the_time() ?></span>
                                        <span class="posted-by">by </span><span class="vcard author post-author"><span
                            class="fn"><?php the_author(); ?></span></span>
                                    </span>
                <div class="entry">
					<?php the_content( '<p class="serif">Read the rest of this entry &raquo;</p>' ); ?>
					<?php wp_link_pages( [
						'before'         => '<p><strong>Pages:</strong> ',
						'after'          => '</p>',
						'next_or_number' => 'number'
					] ); ?>
                </div>
              </div>
              <div class="navigation">
				  <?php
				  get_prev_nav_with_title( '<i class="fa fa-chevron-left"></i><span>Previous Post</span>' );
				  get_next_nav_with_title( '<span>Next Post</span><i class="fa fa-chevron-right"></i>' );
				  ?>
              </div>
              <div class="event-tag-container">
				  <?php $categories = get_the_terms( $post->ID, 'eventcategory' );
				  $locations        = get_the_terms( $post->ID, 'eventlocation' );
				  if ( $locations || $categories ) {
					  ?>
                    <div class="event-tag-headline">
                      <h3>
                        Tags
                      </h3>
                    </div>
					  <?php
				  }
				  if ( $locations ) {
					  foreach ( $locations as $location ) {
						  echo '<div class="event-tag">';
						  echo '<a class="button-outline" href="' . get_home_url() . '/location/' . $location->slug . '">';
						  echo $location->name;
						  echo '</a>';
						  echo '</div>';
					  }
				  }
				  if ( $categories ) {
					  foreach ( $categories as $category ) {
						  echo '<div class="event-tag">';
						  echo '<a class="button-outline" href="' . get_home_url() . '/eventcat/' . $category->slug . '">';
						  echo $category->name;
						  echo '</a>';
						  echo '</div>';
					  }
				  }
				  ?>
              </div>
			<?php endwhile;
			else: ?>
              <p>Sorry, no posts matched your criteria.</p>
			<?php endif; ?>
        </div>
      </article>

    </section>
  </div>
<?php get_footer(); ?>