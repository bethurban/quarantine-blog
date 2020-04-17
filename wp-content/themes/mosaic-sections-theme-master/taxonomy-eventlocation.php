<?php

//echo $GLOBALS['wp_query']->request;
/**
 * @package Alpha Channel Group Base Theme
 * @author  Alpha Channel Group (www.alphachannelgroup.com)
 */

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
					<?php mosaic_get_sidebar( "event_taxonomy_sidebar", "", TRUE ); ?>
                </aside>
                <div class="event-list-inner">
                    <?php get_template_part('loop', 'event-taxonomy'); ?>
                </div>
            </article>
        </section>
    </div>
<?php get_footer(); ?>