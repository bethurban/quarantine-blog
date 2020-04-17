<?php

/**
 * Shortcode for Buttons
 *
 * @param array $args
 *
 * @return string
 */
function mosaic_button( $args ) {
	// OLD CODE from other shortcode for reference
//	$default    = [ 'link' => '#', 'link-text' => 'Button Text' ];
//	$attributes = shortcode_atts( $default, $attr );
//	return '<a href="' . $attributes['link'] . '" class="button-outline shortcode-button">' . $attributes['link-text'] . '</a>';

	$args = shortcode_atts( [
		'url'        => '',
		'link'       => '',
		'link-text'  => 'Button Text',
		'text'       => '',
		'background' => '',
		'class'      => '',
		'width'      => 150,
		'new_window' => FALSE,
		'no_outline' => FALSE,
		'no_wrap'    => FALSE
	], $args );

	// Merging two shortcode arguments from two separate shortcodes
	if ( empty( $args['url'] ) || ! empty( $args['link'] ) ) {
		$args['url'] = $args['link'];
	}

	if ( empty( $args['text'] ) || ! empty( $args['link-text'] ) ) {
		$args['text'] = $args['link-text'];
	}

	$outline = ' button-outline';
	if ( $args['no_outline'] ) {
		$outline = '';
	}

	$args['class'] = trim( 'button' . $outline . ' shortcode-button ' . $args['class'] );
	$style         = ( $args['background'] ) ? ' style="background: ' . $args['background'] . ';"' : '';

	$content    = '';
	$wrap_style = '';

	if ( stripos( $args['class'], 'flip' ) !== FALSE ) {
		$wrap_style .= 'width:' . $args['width'] . 'px;';
	}

	if ( ! $args['no_wrap'] ) {
		$content .= '<span class="shortcode-button-wrapper ' . ( ! strpos( $args['class'], 'button-outline' ) ? $args['class'] : '' ) . '" style="' . $wrap_style . '">';
	}

	$new_window = ( $args['new_window'] ) ? ' target="_blank"' : '';

	if ( stripos( $args['class'], 'flip' ) !== FALSE ) {
		$content .= '<a class="button flip" ' . $style . ' href="' . $args['url'] . '" ' . $new_window . '><span>' . $args['text'] . '</span></a>';
		$content .= '<a class="button flop" ' . $style . ' href="' . $args['url'] . '" ' . $new_window . '><span>' . $args['text'] . '</span></a>';
	} else {
		$content .= '<a class="' . $args['class'] . '" ' . $style . ' href="' . $args['url'] . '" ' . $new_window . '><span data-hover="' . $args['text'] . '">' . $args['text'] . '</span></a>';
	}

	if ( ! $args['no_wrap'] ) {
		$content .= '</span>';
	}

	return $content;
}

add_shortcode( 'mosaic_button', 'mosaic_button' );
add_shortcode( 'button', 'mosaic_button' );


/**
 * Shortcode to display blog posts (or custom post types) anywhere.
 * TODO: Support post thumbnail?
 *
 * Sample "full" usage:
 * [mosaic_post_feed type="events" count="5" class="custom-class" title="false" read_more="See Event" order="title"
 * ascending="true" excerpt="false"]
 *
 * Arguments supported:
 * type: the post type. [default = post]
 * count: the number of posts to display [default = 3]
 * class: custom css classes to assign to the container
 * title: whether to show the title or not.  (Supports "off", "no", "0", or "false" as falsey values, all others are
 * truthy) [default = true] excerpt: whether to show the excerpt or the full post.  Set to false to show full post.
 * (Supports "false", "off", "no", etc") [default = true] read_more: The string to use for the "read more" link, or
 * falsey value to not show the link. order: the post field to sort the posts by.  Supports built-in WP values
 * (including date, title, ID, author, and others) ascending: whether the order should be ascending or descending.  Set
 * to falsey value ("false", "off", etc) for DESCENDING
 *
 * @param $args - array of arguments
 *
 * @return string
 */
function mosaic_post_feed( $args ) {
	$default = [
		'type'      => 'post',
		'count'     => 3,
		'class'     => '',
		'title'     => 1,
		'offset'    => 0,
		'read_more' => 'Read More',
		'order'     => 'date',
		'excerpt'   => 1,
		'ascending' => 0,
		'date'      => 1,
		'image'     => 0
	];

	$args = shortcode_atts( $default, $args );

	$off = [ 'false', 'off', 'no', '0' ];

	$valid = [ 'ID', 'author', 'title', 'name', 'date', 'parent', 'rand' ];

	if ( ! in_array( $args['order'], $valid ) ) {
		return '<strong>Invalid "ORDER" argument provided.  Valid options are: ' . implode( ', ', $valid ) . '</strong>';
	}

	$order = ( in_array( $args['ascending'], $off ) ) ? 'DESC' : 'ASC';

	$query_args = [
		'post_type'      => $args['type'],
		'posts_per_page' => (int) $args['count'],
		'orderby'        => $args['order'],
		'order'          => $order,
		'offset'         => $args['offset']
	];

	add_filter( 'excerpt_more', function () {
		return '...';
	} );

	$query = new WP_Query( $query_args );

	$show_title          = ( ! in_array( $args['title'], $off ) );
	$show_excerpt        = ( ! in_array( $args['title'], $off ) );
	$show_read_more      = ( ! in_array( $args['read_more'], $off ) );
	$show_date           = ( ! in_array( $args['date'], $off ) );
	$show_featured_image = ( ! in_array( $args['image'], $off ) );

	$content = '';
	$content .= '<div class="mosaic-posts-shortcode posts-' . $args['type'] . ' ' . $args['class'] . '">';

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$content .= '<div class="post post-' . $args['type'] . '">';

			if ( $show_featured_image ) {
				$image = get_the_post_thumbnail( NULL, 'full' );

				if ( ! empty( $image ) ) {
					$content .= '<div class="post-featured-image">' . $image . '</div>';
				}
			}

			if ( $show_title ) {
				$content .= '<h2>' . get_the_title() . '</h2>';
			}

			if ( $show_date ) {
				$post_date = get_the_date( 'F j, Y' );
				$content   .= '<div class="post-date"><span class="post-meta">Posted </span>' . $post_date . '</div>';
			}

			if ( $show_excerpt ) {
				$content .= apply_filters( 'the_content', get_the_excerpt() );
			} else {
				$content .= apply_filters( 'the_content', get_the_content() );
			}

			if ( $show_read_more ) {
				$content .= '<span class="read-more-wrapper"><a class="read-more button-outline" href="' . get_the_permalink() . '">' . $args['read_more'] . '</a></span>';
			}

			$content .= '</div>';
		}
	}

	$content .= '</div>';

	return $content;
}

add_shortcode( 'mosaic_post_feed', 'mosaic_post_feed' );

/**
 * Shortcode for embedding video from Youtube/Vimeo with or wihtout a placeholder
 *
 * @param $atts
 *
 * @return string
 */
function mosaic_video_embed( $atts ) {
	$default = [
		'video'               => '',
		'image'               => '',
		'hide_related_videos' => FALSE
	];

	$atts = shortcode_atts( $default, $atts );

	if ( ! $atts['video'] ) {
		return 'This shortcode requires a video URL';
	}

	$url_pattern = '/.*(youtube|vimeo).*/m';

	// Check video URL => should only be YouTube or Vimeo
	if ( ! preg_match( $url_pattern, $atts['video'] ) ) {
		return 'Only YouTube or Vimeo embed URLs are supported';
	}

	$is_vimeo   = ( preg_match( '/.*vimeo.*/m', $atts['video'] ) );
	$is_youtube = ( preg_match( '/.*youtube.*/m', $atts['video'] ) );

	$type       = $is_vimeo ? 'vimeo' : ( $is_youtube ? 'youtube' : '' );
	$video_type = ( $type ) ? ( 'data-video-type=' . $type ) : '';

	$hide_related_videos = '';

	if ( $is_youtube && $atts['hide_related_videos'] ) {
		$hide_related_videos = ' data-hide-related-videos="true"';
	}

	$is_lazy_loading = FALSE;

	// Only looping through `$attrs` to check for 'human' error
	foreach ( $atts as $key => $value ) {
		if ( $key != ( 'video' | 'image' ) && 'lazyload' == $value ) {
			$is_lazy_loading = TRUE;
		}
	}

	$content = '<div class="mst-video-wrapper">';

	$lazyload = '';

	if ( $is_lazy_loading ) {
		$lazyload = 'lazyload';
	}

	$content .= '<div class="mst-video ' . $lazyload . '" data-video="' . $atts['video'] . '" data-image="' . $atts['image'] . '" ' . $video_type . ' ' . $hide_related_videos . '></div></div>';

	return $content;
}

add_shortcode( 'mst-video', 'mosaic_video_embed' );

/**
 * Shortcode for tooltips
 * Usage: [tip icon="image, words, or leave empty and use background-image" description="Put your description here.  It
 * can be long!"]
 *
 * @param array $args
 *
 * @return string
 */
function mosaic_tool_tips( $args ) {
	$args = shortcode_atts( [
		'description' => '',
		'icon'        => ''
	], $args );

	$content = '';

	$content .= '<span class="tooltip">';
	$content .= '<span class="tip-icon">';
	$content .= $args['icon'];
	$content .= '</span>';
	$content .= '<span class="tip-content">';
	$content .= $args['description'];
	$content .= '</span>';
	$content .= '</span>';

	return $content;
}

add_shortcode( 'tip', 'mosaic_tool_tips' );

function mosaic_alternate_h1( $args, $content = '' ) {
	$args = shortcode_atts( [
		'size'  => 'h2',
		'bold'  => '',
		'align' => 'left'
	], $args );

	$falsey = [
		'false',
		'0',
		'no',
		'off'
	];

	$truthy = [
		'true',
		'1',
		'bold'
	];

	$semi = [
		'semi-bold',
		'semi',
		'400'
	];

	$bold = ( in_array( $args['bold'], $falsey ) ) ? 'non-strong' : 'semi-strong';
	$bold = ( in_array( $args['bold'], $semi ) ) ? 'semi-strong' : $bold;
	$bold = ( in_array( $args['bold'], $truthy ) ) ? 'strong' : $bold;

	$class = "{$args['size']} {$bold} text-{$args['align']}";

	$content = "<h1 class=\"{$class}\">{$content}</h1>";

	return $content;
}

add_shortcode( 'h1', 'mosaic_alternate_h1' );

/**
 * Shortcode to allow an e-mail to be displayed in a format that won't get picked up by robots .
 * Usage: [ google_map address = "123 Main Street" width = "425" heigth = "350" zoom = "14"]
 *
 * @param array $atts
 *
 * @return string
 */
function mosaic_google_maps( $atts ) {
	$address        = '';
	$framesrc       = '';
	$width          = '';
	$height         = '';
	$zoom           = '';
	$largerlink     = '';
	$directionslink = '';

	extract( shortcode_atts( [
		'address'        => '',
		'width'          => '425',
		'height'         => '350',
		'zoom'           => '14',
		'largerlink'     => 1,
		'directionslink' => 0,
		'framesrc'       => ''
	], $atts ) );

	$daddress       = $address;
	$address        = str_replace( " ", " + ", $address );
	$src            = ( $framesrc ) ? $framesrc : 'http://maps.google.com/maps?q=' . $address . '&amp;oe=utf-8&amp;hq=&amp;hnear=' . $address . '&amp;z=' . $zoom . '&amp;iwloc=A&amp;output=embed';
	$directionslink = ( strtolower( $directionslink ) == "FALSE" || strtolower( $directionslink ) == "no" || $directionslink == "0" ) ? 0 : 1;
	$str            = ( $directionslink ) ? '<a class="google - directions" href="http://maps.google.com/maps?daddr=' . $address . '">Get Directions to ' . $daddress . '</a>' : "";
	$str            .= '<iframe width="' . $width . '" height="' . $height . '" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="' . $src . '"></iframe>';
	if ( $largerlink ) {
		if ( strtolower( $largerlink ) == "yes" || $largerlink == "1" ) {
			$str .= '<br /><small><a href="' . str_ireplace( "source=embed", "output=embed", $src ) . '" target="_map">View Larger Map</a></small>';
		} else {
			$str .= '<br /><small>View <a href="' . str_ireplace( "output=embed", "source=embed", $src ) . '" target="_map">' . $largerlink . '</a> in a larger map</a></small>';
		}
	}

	return $str;
}

add_shortcode( 'google_map', 'mosaic_google_maps' );


/**
 * Shortcode to allow display "peek" of subpages
 * Usage: [subpage_peek post_parent="5" post_type="page" post_not_in="1,2,3"]
 *
 * @return string
 */
function subpage_peek() {
	global $post;

	//query subpages
	$args     = [
		'post_parent' => $post->ID,
		'post_type'   => 'page',
		'post_not_in' => [ $post->ID ]
	];
	$subpages = new WP_query( $args );

	// create output
	if ( $subpages->have_posts() ) :
		$output = '<ul class="pagination">';
		while ( $subpages->have_posts() ) : $subpages->the_post();
			$excerpt = get_the_content();
			if ( stripos( $excerpt, "<h1" ) !== FALSE && stripos( $excerpt, "<h1" ) < 10 ) {
				$excerpt = substr( $excerpt, stripos( $excerpt, "</h1>" ) + 5 );
			}
			$excerpt = rtrim( substr( strip_tags( $excerpt ), 0, 290 ), "[...]" );
			$output  .= '<li><strong><a href="' . get_permalink() . '">' . get_the_title() . '</a></strong>
	<p>' . $excerpt . '...
	<a href="' . get_permalink() . '">Read More &rarr;</a></p></li>';
		endwhile;
		$output .= '</ul>';
	else :
		$output = '<p>No subpages found.</p>';
	endif;

	// reset the query
	wp_reset_postdata();

	// return something
	return $output;
}

add_shortcode( 'subpage_peek', 'subpage_peek' );


/**
 * Shortcode to allow an e-mail to be displayed in a format that won't get picked up by robots.
 * Usage: [encrypt_email address="me@website.com"]
 *
 * @param array $args
 *
 * @return string
 */
function mosaic_encrypt_email( $args ) {
	// Expects $address and $anchor
	extract( $args );
	$address = $args['address'];
	$anchor  = $args['anchor'];

	$address       = strtolower( $address );
	$anchor        = ( strtolower( $anchor ) == $address ) ? "link" : "\"" . $anchor . "\"";
	$coded         = "";
	$unmixedkey    =
		"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.@-~!@#$%^&*()_";
	$inprogresskey = $unmixedkey;
	$mixedkey      = "";
	$unshuffled    = strlen( $unmixedkey );
	for ( $i = 1; $i <= strlen( $unmixedkey ); $i++ ) {
		$ranpos        = rand( 0, $unshuffled - 1 );
		$nextchar      = $inprogresskey[$ranpos];
		$mixedkey      .= $nextchar;
		$before        = substr( $inprogresskey, 0, $ranpos );
		$after         =
			substr( $inprogresskey, $ranpos + 1, $unshuffled - ( $ranpos + 1 ) );
		$inprogresskey = $before . '' . $after;
		$unshuffled    -= 1;
	}
	$cipher = $mixedkey;

	$shift = strlen( $address );

	$txt = "<script type=\"text/javascript\"
	language=\"javascript\">\n" .
	       "<!-" . "-\n";

	for ( $j = 0; $j < strlen( $address ); $j++ ) {
		if ( strpos( $cipher, $address[$j] ) == -1 ) {
			$chr   = $address[$j];
			$coded .= $address[$j];
		} else {
			$chr   = ( strpos( $cipher, $address[$j] ) + $shift ) %
			         strlen( $cipher );
			$coded .= $cipher[$chr];
		}
	}


	$txt .= "\ncoded = \"" . $coded . "\"\n" .
	        "  key = \"" . $cipher . "\"\n" .
	        "  shift=coded.length\n" .
	        "  link=\"\"\n" .
	        "  for (i=0; i<coded.length; i++) {\n" .
	        "    if (key.indexOf(coded.charAt(i))==-1) {\n" .
	        "      ltr = coded.charAt(i)\n" .
	        "      link += (ltr)\n" .
	        "    }\n" .
	        "    else {     \n" .
	        "      ltr = (key.indexOf(coded.charAt(i))-
			shift+key.length) % key.length\n" .
	        "      link += (key.charAt(ltr))\n" .
	        "    }\n" .
	        "  }\n" .
	        "document.write(\"<a href='mailto:\"+link+\"'>\"+" . $anchor .
	        "+\"</a>\");\n" .
	        "\n" .
	        "//-" . "->\n" .
	        "<" . "/script><noscript>N/A" .
	        "<" . "/noscript>";

	return $txt;
}

add_shortcode( 'encrypt_email', 'mosaic_encrypt_email' );

function mosaic_div_shortcode( $atts, $content = NULL ) {
	$default = [
		'class' => '',
		'id'    => '',
		'other' => '',
		'align' => ''
	];

	$atts = wp_parse_args( $atts, $default );

	$classes = [ $atts['class'] ];
	if ( in_array( 'inline-contents', $atts ) || ! empty( $atts['inline-contents'] ) ) {
		$classes[] = 'inline-contents';
	}

	if ( $atts['align'] ) {
		$classes[] = "text-{$atts['align']}";
	}

	$classes = trim( implode( ' ', $classes ) );

	$div = '<div';
	$div .= ( $classes ) ? ' class="' . $classes . '"' : '';
	$div .= ( $atts['id'] ) ? ' id="' . $atts['id'] . '"' : '';
	$div .= ( $atts['other'] ) ? ' ' . $atts['other'] : '';
	$div .= '>';

	return $div . apply_filters( 'the_content', $content ) . '</div>';
}

add_shortcode( 'div', 'mosaic_div_shortcode' );

function mosaic_row_shortcode( $atts, $content = NULL ) {
	$default = [
		'class' => '',
		'style' => ''
	];

	$atts = wp_parse_args( $atts, $default );

	if ( $atts['class'] ) {
		$atts['class'] = ' ' . $atts['class'];
	}

	$style = ( $atts['style'] ) ? ' style="' . $atts['style'] . '"' : '';

	MosaicSocialMedia::ignore();
	$content = '<div class="row' . $atts['class'] . '"' . $style . '>' . apply_filters( 'the_content', $content ) . '</div>';
	MosaicSocialMedia::ignore( FALSE );

	return $content;
}

function mosaic_col_shortcode( $atts, $content = NULL ) {
	$default = [
		'count' => '2',
		'class' => '',
		'style' => ''
	];

	$atts = wp_parse_args( $atts, $default );

	if ( $atts['class'] ) {
		$atts['class'] = ' ' . $atts['class'];
	}

	$style = ( $atts['style'] ) ? ' style="' . $atts['style'] . '"' : '';

	MosaicSocialMedia::ignore();
	$content = '<div class="col col-' . $atts['count'] . $atts['class'] . '"' . $style . '>' . apply_filters( 'the_content', $content ) . '</div>';
	MosaicSocialMedia::ignore( FALSE );

	return $content;
}

add_shortcode( 'row', 'mosaic_row_shortcode' );
add_shortcode( 'col', 'mosaic_col_shortcode' );

function mosaic_search( $atts, $content = NULL ) {

	$content = get_search_form( FALSE );

	return $content;
}

add_shortcode( 'search_form', 'mosaic_search' );

function mosaic_side_by_side( $atts, $content = NULL ) {

	$default = [
		'cols'    => 2,
		'padding' => 50
	];

	$atts = wp_parse_args( $atts, $default );

	$return = '<div class="sidebyside sidebyside-' . $atts['cols'] . ' padding-' . $atts['padding'] . '">';

	MosaicSocialMedia::ignore();
	$return .= apply_filters( 'the_content', $content );
	MosaicSocialMedia::ignore( FALSE );

	$return .= '</div>';

	return $return;
}

add_shortcode( 'sidebyside', 'mosaic_side_by_side' );

function mosaic_fix_shortcodes( $content ) {
	$array   = [
		'<p>['    => '[',
		']</p>'   => ']',
		']<br />' => ']'
	];
	$content = strtr( $content, $array );

	return $content;
}

function mosaic_span_shortcode( $atts, $content = NULL ) {
	$default = [
		'class' => '',
		'style' => ''
	];

	$atts = wp_parse_args( $atts, $default );

	$class = ( $atts['class'] ) ? ' class="' . $atts['class'] . '"' : '';
	$style = ( $atts['style'] ) ? ' style="' . $atts['style'] . '"' : '';

	$content = '<span' . $class . $style . '>' . $content . '</span>';

	return $content;
}

add_shortcode( 'span', 'mosaic_span_shortcode' );

add_filter( 'the_content', 'mosaic_fix_shortcodes' );
