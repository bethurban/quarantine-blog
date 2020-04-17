<?php
/**
 * @package WordPress
 */
get_header(); ?>
    <div class="contentwrapper">
        <section class="page search">
            <article>
                <h1>Search Results for "<em><?php the_search_query() ?></em>"</h1>
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
                        <div <?php post_class() ?>>
                            <h3 id="post-<?php the_ID(); ?>">
                                <a href="<?php the_permalink() ?>" rel="bookmark"
                                   title="Permanent Link to <?php the_title_attribute(); ?>">
									<?php
									$title = get_the_title();
									$keys  = explode( " ", $s );
									$title = preg_replace( '/(' . implode( '|', $keys ) . ')/iu',
										'<strong class="search-excerpt">\0</strong>',
										$title );
									echo $title;
									?></a>
                            </h3>
                            <small class="date"><?php the_time() ?></small>
                        </div>
					<?php endwhile; ?>
                    <div class="navigation">
                        <div class="alignleft"><?php next_posts_link( '&laquo; Older Entries' ) ?></div>
                        <div class="alignright"><?php previous_posts_link( 'Newer Entries &raquo;' ) ?></div>
                    </div>
				<?php else : ?>
                    <h2 class="center">No posts found. Try a different search?</h2>
					<?php get_search_form(); ?>
                    <div class="spacer"></div>
				<?php endif; ?>
            </article>
            <aside>
				<?php mosaic_get_sidebar( "default_sidebar", "" ); ?>
            </aside>
        </section>
    </div>
<?php get_footer(); ?>