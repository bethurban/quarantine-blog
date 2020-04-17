<?php


// Unregister some of the default WordPress widgets
function acg_core_unregister_widgets() {
	if ( function_exists( "unregister_widget" ) ) {
		unregister_widget( 'WP_Widget_Recent_Posts' );
	}
}

class acg_post_slider extends WP_Widget {

	function __construct() {
		parent::__construct( 'acg_post_slider', '- Page Slideshow', [ 'description' => 'The recent pages slideshow.' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];

		$count = ( isset( $instance["count"] ) ) ? ( $instance["count"] * 1 ) : 5;
		$cat   = (int) $instance['category'];
		$size  = ( $instance['size'] ) ? $instance['size'] : 'thumbnail';
		echo PHP_EOL . $before_widget;

		$spec_post = get_option( 'acg_site_options' );
		$spec_post = ( empty( $spec_post['lead_story_post'] ) ) ? NULL : $spec_post['lead_story_post'];

		$query_args = [
			"post_type"      => "page",
			"posts_per_page" => $count
		];

		if ( $spec_post ) {
			$query_args['post__in'] = [ $spec_post ];
			define( 'LEAD_POST_ID', $spec_post );
		} else if ( $cat ) {
			$query_args['category__in'] = $cat;
		}

		$wp = new WP_Query( $query_args );

		$tab = "\t";

		$pager        = '';
		$pager_thumbs = ( $instance['thumbnails'] ) ? TRUE : FALSE;
		if ( $pager_thumbs ) {
			$pager       = '<div id="bx-pager">' . PHP_EOL;
			$pager_count = 0;
		}

		if ( $wp->have_posts() ) {
			echo PHP_EOL . '<ul class="bxslider">' . PHP_EOL;
			while ( $wp->have_posts() ) {
				$wp->the_post();
				$post_title = '<p class="slide_title">' . get_the_title() . '</p><p>' . get_the_excerpt() . '</p>' . PHP_EOL;

				echo $tab . '<li>' . PHP_EOL;
				//echo $tab . $tab . '<div class="slide_image">' . PHP_EOL;

				$attr = [
					'title' => $post_title,
				];

				if ( has_post_thumbnail() ) {
					echo $tab . $tab . $tab;
					the_post_thumbnail( $size, $attr );
					if ( $pager_thumbs ) {
						$pager .= $tab . '<a data-slide-index="' . $pager_count++ . '" href="">' . get_the_post_thumbnail( get_the_ID(), 'thumbnail' ) . '</a>' . PHP_EOL;
					}
				}

				echo $tab . '</li>' . PHP_EOL;
			}
			echo '</ul>' . PHP_EOL;
		}
		if ( $pager_thumbs ) {
			$pager .= '</div>' . PHP_EOL;
		}

		echo $pager;

		echo $after_widget . PHP_EOL;
	}

	function update( $new_instance, $old_instance ) {
		$instance               = $old_instance;
		$instance['count']      = strip_tags( stripslashes( $new_instance["count"] ) );
		$instance['category']   = strip_tags( stripslashes( $new_instance["category"] ) );
		$instance['size']       = $new_instance['size'];
		$instance['thumbnails'] = ( isset( $new_instance['thumbnails'] ) ) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$default  = [
			'title'         => 'Latest News',
			'image'         => '',
			'count'         => '5',
			'category'      => '',
			'img_loc'       => 'second',
			'title_loc'     => 'top',
			'author'        => '',
			'date'          => '',
			'excerpt'       => '',
			'learn_more'    => '',
			'category_link' => '',
			'thumbnails'    => 0
		];
		$instance = wp_parse_args( (array) $instance, $default );

		$count      = esc_attr( $instance['count'] );
		$thumbnails = esc_attr( $instance['thumbnails'] );
		$category   = esc_attr( $instance['category'] );
		$size       = esc_attr( $instance['size'] );
		$sizes      = [
			"thumbnail" => "Thumbnail",
			"medium"    => "Medium",
			"large"     => "Large",
			"full"      => "Full Size"
		];
		$sizelist   = '<select name="' . $this->get_field_name( "size" ) . '">';
		foreach ( $sizes as $val => $title ) {
			$sizelist .= '<option value="' . $val . '"';
			$sizelist .= ( $val == $size ) ? ' selected="Selected"' : '';
			$sizelist .= '>' . $title . '</option>';
		}
		$sizelist .= '</select>';

		$catlist = wp_dropdown_categories( "echo=0&name=" . $this->get_field_name( "category" ) . "&selected=" . $category . "&hierarchical=1" );
		$checked = ( $thumbnails ) ? ' checked' : '';
		?>
        <p><label for="<?php echo $this->get_field_id( 'count' ); ?>"># Pages: </label><input size="3" type="text"
                                                                                              name="<?php echo $this->get_field_name( 'count' ); ?>"
                                                                                              value="<?php echo $count; ?>"/>
        </p>
        <p><label for="<?php echo $this->get_field_id( 'category' ); ?>">Category: </label><?php echo $catlist; ?></p>
        <p><label for="<?php echo $this->get_field_id( 'img_loc' ); ?>">Image Size: </label><?php echo $sizelist; ?></p>
        <p><label for="<?php echo $this->get_field_id( 'thumbnails' ); ?>">Nav uses Thumbnails: </label><input
                    type="checkbox" name="<?php echo $this->get_field_name( 'thumbnails' ); ?>"
                    id="<?php echo $this->get_field_id( 'thumbnail' ); ?>"<?php echo $checked; ?> /></p>
	<?php }
}

class acg_facebook_twitter extends WP_Widget {

	function __construct() {
		parent::__construct( 'acg_facebook_twitter', 'Connect With Me', [ 'description' => 'Social Media Links' ] );
	}

	function widget( $args, $instance ) {
		echo $args['before_widget'];
		echo ( ! empty( $instance['title'] ) ) ? $args['before_title'] . $instance["title"] . $args['after_title'] : '';
		echo ( ! empty( $instance["facebook-page"] ) ) ? '<a target="_blank" class="facebook" href="' . $instance["facebook-page"] . '"><span class="follow">Facebook</span><i class="fa fa-facebook"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["twitter-page"] ) ) ? '<a target="_blank" class="twitter" href="' . $instance["twitter-page"] . '"><span class="follow">Twitter</span><i class="fa fa-twitter"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["google"] ) ) ? '<a target="_blank" class="google" href="' . $instance["google"] . '"><span class="follow">Google</span><i class="fa fa-google-plus"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["linkedin"] ) ) ? '<a target="_blank" class="linkedin" href="' . $instance["linkedin"] . '"><span class="follow">Linkedin</span><i class="fa fa-linkedin"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["pintrest"] ) ) ? '<a target="_blank" class="pintrest" href="' . $instance["pintrest"] . '"><span class="follow">Pintrest</span><i class="fa fa-pinterest-p"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["instagram"] ) ) ? '<a target="_blank" class="instagram" href="' . $instance["instagram"] . '"><span class="follow">Instagram</span><i class="fa fa-instagram"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["youtube-page"] ) ) ? '<a target="_blank" class="youtube" href="' . $instance["youtube-page"] . '"><span class="follow">YouTube</span><i class="fa fa-youtube-play"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["amazon"] ) ) ? '<a target="_blank" class="amazon" href="' . $instance["amazon"] . '"><span class="follow">Amazon</span><i class="fa fa-amazon"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["custom1"] ) ) ? '<a target="_blank" class="custom custom-1" href="' . $instance["custom1"] . '"><span class="follow"></span><i class="fa fa-custom-1"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["custom2"] ) ) ? '<a target="_blank" class="custom custom-2" href="' . $instance["custom2"] . '"><span class="follow"></span><i class="fa fa-custom-2"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["rss-feed"] ) ) ? '<a target="_blank" class="rss" href="' . get_bloginfo( "rss2_url" ) . '"><span class="follow">RSS</span><i class="fa fa-rss"></i></a>' . PHP_EOL : "";
		echo ( ! empty( $instance["email-address"] ) ) ? '<a class="email" href="mailto:' . $instance["email-address"] . '"><span class="follow">Email</span><i class="fa fa-envelope"></i></a>' . PHP_EOL : "";
		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		foreach ( $new_instance as $k => $v ) {
			$instance[ $k ] = $v;
		}
		$instance["rss-feed"]              = ( isset( $new_instance["rss-feed"] ) ) ? 1 : 0;
		$instance["show-addtoany"]         = ( isset( $new_instance["show-addtoany"] ) ) ? 1 : 0;
		$instance["addtoany-menu-onclick"] = ( isset( $new_instance["addtoany-menu-onclick"] ) ) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$default = [
			'title'         => '',
			'facebook-page' => '',
			'twitter-page'  => '',
			'google'        => '',
			'linkedin'      => '',
			'pintrest'      => '',
			'instagram'     => '',
			'youtube-page'  => '',
			'amazon'        => '',
			'custom1'       => '',
			'custom2'       => '',
			'rss-feed'      => '',
			'email-address' => '',
		];

		$instance = wp_parse_args( (array) $instance, $default );

		foreach ( $default AS $k => $v ) {
			if ( $k != "rss-feed" && $k != "show-addtoany" && $k != "addtoany-menu-onclick" ) {
				echo PHP_EOL . '<p><label for="' . $this->get_field_name( $k ) . '">' . __( ucwords( str_replace( "-", " ", $k ) ) ) . ': <input type="text" class="widefat" id="' . $this->get_field_id( $k ) . '" name="' . $this->get_field_name( $k ) . '" value="' . esc_attr( $instance[ $k ] ) . '" /></label></p>';
			} else {
				$checked = ( $instance[ $k ] ) ? ' checked="checked"' : '';
				echo PHP_EOL . '<p><label for="' . $this->get_field_name( $k ) . '">' . __( ucwords( str_replace( "-", " ", $k ) ) ) . ': <input type="checkbox" id="' . $this->get_field_id( $k ) . '" name="' . $this->get_field_name( $k ) . '" value="1"' . $checked . ' /></label></p>';
			}
		}
	}
}

class acg_subnav extends WP_Widget {
	function __construct() {
		parent::__construct( 'acg_subnav', 'Custom Subnavigation', [ 'description' => 'Subnavigation specifically designed for this theme.  Lists child pages for either the current page or a page of your choice.' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$options     = $instance;
		$title       = $options["title"];
		$mode        = (int) $options['mode'];
		$haschildren = 0;
		$post_id     = NULL;
		$children    = '';

		if ( $mode == 1 ) {
			global $post;
			if ( ! isset( $post ) ) {
				return;
			}
			// Does this post have a parent?
			$parent  = $post->post_parent;
			$post_id = $post->ID;
		} else {
			$parent = (int) $options['page_id'];
			$post   = get_post( $parent );
		}
		// If there IS a parent, then set it as the parent
		if ( $parent != $post->ID ) {
			$gp = get_post( $parent );
			$gp = $gp->post_parent;
			if ( $gp ) {
				$parent = $gp;
			}
		}
		// If a parent is not set, then list the children of the current page
		if ( ! $parent ) {
			$children = wp_list_pages( "title_lie=&child_of=" . $post->ID . "&echo=0&depth=1" );
			if ( $children ) {
				$haschildren = 1;
				$parent      = $post->ID;
			}
		}
		// Otherwise, if the parent IS set, AND there are children
		if ( $parent || $haschildren ) {
			$depth = ( $options["child-page-depth"] ) ? $options["child-page-depth"] : 1;
			if ( isset( $options["show-siblings"] ) && $options["show-siblings"] ) {
				$siblings = get_post( $post->post_parent );
				if ( $siblings && $siblings->ID != $parent ) {
					$siblingpid = $siblings->ID;
					$siblings   = wp_list_pages( "title_lie=&child_of=" . $siblings->ID . "&echo=0&depth=" . max( 1, $depth - 1 ) );
					$depth      = 1;
				} else {
					$siblings = "";
				}
			}
			$children = wp_list_pages( 'echo=0&title_li=&depth=' . $depth . '&child_of=' . $parent );
			// If we found siblings, then parse the children and insert the siblings
			if ( $siblings ) {
				// Prepare siblings list as a child ul....
				$pos      = stripos( $siblings, "<ul>" );
				$end      = stripos( $siblings, "</ul>" );
				$siblings = '<ul class="children">' . substr( $siblings, $pos + 4, $end - $pos + 1 );
				// Insert sibling list in the appropriate location
				$pos      = stripos( $children, "page-item-" . $siblingpid );
				$pos      = stripos( $children, "</li>", $pos );
				$children = substr( $children, 0, $pos ) . $siblings . substr( $children, $pos );
			} else {
				if ( ! isset( $options["show-current-page-children"] ) || $options["show-current-page-children"] ) {
					// Test if the CURRENT page has children
					$subchildren = wp_list_pages( "title_lie=&child_of=" . $post->ID . "&echo=0&depth=1" );
					if ( $subchildren ) {
						// Quick test to see if ALREADY listed...
						$pos  = stripos( $subchildren, "page-item-" ) + 10;
						$end  = stripos( $subchildren, '"', $pos + 1 );
						$page = substr( $subchildren, $pos, $end - $pos );
						if ( stripos( $children, "page-item-" . $page ) === FALSE ) {
							$pos         = stripos( $subchildren, "<ul>" );
							$end         = stripos( $subchildren, "</ul>" );
							$subchildren = '<ul class="children">' . substr( $subchildren, $pos + 4, $end - $pos + 1 );
							$pos         = stripos( $children, "page-item-" . $post->ID );
							$pos         = stripos( $children, "</li>", $pos );
							$children    = substr( $children, 0, $pos ) . $subchildren . substr( $children, $pos );
						}
					}
				}
			}
			if ( ( $mode == 0 && $options["show-parent"] ) || $children ) {
				echo PHP_EOL;
				echo $args['before_widget'];
				echo ( $title ) ? $args['before_title'] . $title . $args['after_title'] : '';
				echo PHP_EOL;
				echo '<ul id="acg_subnav">';
				echo ( isset( $options["show-parent"] ) && $options["show-parent"] ) ? '<li class="parent"><a href="' . get_permalink( $parent ) . '">' . get_the_title( $parent ) . '</a></li>' . PHP_EOL : '';
				echo $children;
				echo '</ul>';
				echo PHP_EOL;
				echo $args['after_widget'];
				echo PHP_EOL;
			};
		}
		if ( ! $children ) {
			echo PHP_EOL;
			echo $args['before_widget'];
			echo '<p class="spacer"></p>' . PHP_EOL;
			echo $args['after_widget'];
			echo PHP_EOL;
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		foreach ( $new_instance as $k => $v ) {
			$instance[ $k ] = $v;
		}
		$instance["mode"]                       = ( isset( $new_instance["mode"] ) ) ? 1 : 0;
		$instance["show-parent"]                = ( isset( $new_instance["show-parent"] ) ) ? 1 : 0;
		$instance["show-siblings"]              = ( isset( $new_instance["show-siblings"] ) ) ? 1 : 0;
		$instance["show-current-page-children"] = ( isset( $new_instance["show-current-page-children"] ) ) ? 1 : 0;

		/* var_dump($instance);
		die(); */

		return $instance;
	}

	function form( $instance ) {

		$default = [
			'title'                      => '',
			'mode'                       => 0,
			'page_id'                    => '',
			'show-parent'                => 0,
			'show-siblings'              => 0,
			'show-current-page-children' => 1,
			'child-page-depth'           => 1
		];

		$instance = wp_parse_args( (array) $instance, $default );
		foreach ( $default as $k => $v ) {
			$v = $instance[ $k ];
			if ( $k == 'show-parent' ) {
				echo '<p><strong><u>Subnavigation Settings:</u></strong></p>';
			}
			if ( $k == 'mode' ) {
				echo '<p><strong><u>Display Subnav For Page:</u></strong></p>';
				$checked = ( $instance[ $k ] ) ? ' checked="checked"' : '';
				echo PHP_EOL . '<p><label for="' . $this->get_field_name( $k ) . '">Auto-detect parent page: <input type="checkbox" id="' . $this->get_field_id( $k ) . '" name="' . $this->get_field_name( $k ) . '" value="1"' . $checked . ' /></label></p>';
			} else if ( $k == 'page_id' ) {
				$args = [
					'name'     => $this->get_field_name( $k ),
					'echo'     => 0,
					'selected' => $v
				];

				echo PHP_EOL . '<p><label for="' . $this->get_field_name( $k ) . '">Or page (always show):' . wp_dropdown_pages( $args ) . '</label></p>';
			} else if ( $k != "show-parent" && $k != "show-siblings" && $k != "show-current-page-children" ) {
				$size = ( $k == 'child-page-depth' ) ? ' size="2"' : ' class="widefat"';
				echo PHP_EOL . '<p><label for="' . $this->get_field_name( $k ) . '">' . __( ucwords( str_replace( "-", " ", $k ) ) ) . ': <input type="text" id="' . $this->get_field_id( $k ) . '" name="' . $this->get_field_name( $k ) . '" value="' . esc_attr( $instance[ $k ] ) . '"' . $size . ' /></label></p>';
			} else {
				$checked = ( $instance[ $k ] ) ? ' checked="checked"' : '';
				echo PHP_EOL . '<p><label for="' . $this->get_field_name( $k ) . '">' . __( ucwords( str_replace( "-", " ", $k ) ) ) . ': <input type="checkbox" id="' . $this->get_field_id( $k ) . '" name="' . $this->get_field_name( $k ) . '" value="1"' . $checked . ' /></label></p>';
			}
		}
	}
}


class acg_youtube_embed extends WP_Widget {

	function __construct() {
		parent::__construct( 'acg_youtube_embed', ' - YouTube Embed Video', [ 'description' => 'Display a YouTube video.' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$title       = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$embed_code  = apply_filters( 'embed_code', $instance['embed_code'], $instance );
		$description = apply_filters( 'widget_title', empty( $instance['description'] ) ? '' : $instance['description'], $instance, $this->id_base );
		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		echo '<div>';
		if ( stripos( $embed_code, "/v/" ) ) {
			echo '<object width="388" height="250"><param name="movie" value="http://' . $embed_code . '"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://' . $embed_code . '" type="application/x-shockwave-flash" width="425" height="349" allowscriptaccess="always" allowfullscreen="true"></embed></object>';
		} else {
			echo '<iframe width="388" height="250" src="http://' . $embed_code . '" frameborder="0" allowfullscreen></iframe>';
		}
		echo ( $description ) ? '<p>' . $description . '</p>' : '';
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['description'] = $new_instance['description'];
		$vid                     = $new_instance["embed_code"];
		$pos                     = stripos( $vid, "www.youtube.com" );
		$end                     = stripos( $vid, '"', $pos + 1 );
		if ( $end ) {
			$vid = substr( $vid, $pos, $end - $pos );
		} else {
			$vid = substr( $vid, $pos );
		}
		$instance['embed_code'] = $vid;

		return $instance;
	}

	function form( $instance ) {
		$instance    = wp_parse_args( (array) $instance, [
			'title'       => '',
			'embed_code'  => '',
			'description' => 'Keyword rich description of the video.  Try to make it a decent sized paragraph for search engine purposes.'
		] );
		$title       = strip_tags( $instance['title'] );
		$embed_code  = esc_textarea( $instance['embed_code'] );
		$description = esc_textarea( $instance['description'] );
		?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/></p>
        <p>Embed Code: <textarea class="widefat" rows="3" cols="20"
                                 id="<?php echo $this->get_field_id( 'embed_code' ); ?>"
                                 name="<?php echo $this->get_field_name( 'embed_code' ); ?>"><?php echo $embed_code; ?></textarea>
        </p>
        <p>Description: <textarea class="widefat" rows="4" cols="20"
                                  id="<?php echo $this->get_field_id( 'description' ); ?>"
                                  name="<?php echo $this->get_field_name( 'description' ); ?>"><?php echo $description; ?></textarea>
        </p>
		<?php
	}
}

class acg_contact_info extends WP_Widget {

	function __construct() {
		parent::__construct( 'acg_contact_info', ' - Contact Information', [ 'description' => 'Display the contact information for your company.' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$class = $instance['class'];
		if ( $class ) {
			$start         = stripos( $before_widget, 'class="' ) + 8;
			$start         = stripos( $before_widget, '"', $start );
			$before_widget = substr( $before_widget, 0, $start ) . ' ' . $class . substr( $before_widget, $start );
		}
		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		echo '<div>';
		foreach ( $instance AS $key => $value ) {
			if ( stripos( $key, 'map_' ) === FALSE ) {
				if ( $value ) {
					echo '<div class="acg_contact_' . $key . '"><span>' . ucwords( str_replace( '_', ' ', $key ) ) . ': </span>' . $value . '</div>';
				}
			}
		}
		$map_option = $instance['map_option'];
		if ( $map_option && ! empty( $instance['map_address'] ) ) {
			if ( $map_option == 'both' || $map_option == 'map' ) {
				if ( function_exists( 'acg_google_maps' ) ) {
					$width  = max( 100, min( 1000, (int) $instance['map_width'] ) );
					$height = max( 100, min( 1000, (int) $instance['map_height'] ) );
					$args   = [
						'address' => $instance['map_address'],
						'width'   => $width,
						'height'  => $height
					];
					echo acg_google_maps( $args );
				} else {
					echo '<!-- No map shown, because map function not found -->';
				}
			}
			if ( $map_option == 'both' || $map_option == 'link' ) {
				echo '<div class="acg_contact_map_link"><a href="https://maps.google.com/maps?q=' . $instance['name'] . ' ' . $instance['map_address'] . '" target="_blank">' . $instance['map_link_text'] . '</a></div>';
			}
		} else {
			echo '<!-- No map shown, because map address not provided -->';
		}
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		foreach ( $new_instance AS $key => $value ) {
			$instance[ $key ] = $value;
		}
		var_dump( $instance );

		return $instance;
	}

	function form( $instance ) {
		$defaults = [
			'title'         => '',
			'class'         => '',
			'name'          => '',
			'address'       => '',
			'city'          => '',
			'state'         => '',
			'zip'           => '',
			'phone'         => '',
			'fax'           => '',
			'email'         => '',
			'map_address'   => '',
			'map_option'    => '',
			'map_width'     => '200',
			'map_height'    => '200',
			'map_link_text' => 'Map &amp; Directions'
		];

		$exclude  = [
			'map_option'
		];
		$instance = wp_parse_args( (array) $instance, $defaults );

		foreach ( $instance AS $key => $value ) {
			if ( ! in_array( $key, $exclude ) ) {
				echo '<p><label for="' . $this->get_field_id( $key ) . '">' . __( ucwords( str_replace( '_', ' ', $key ) ) ) . '</label><input class="widefat id="' . $this->get_field_id( $key ) . '" name="' . $this->get_field_name( $key ) . '" value="' . $value . '" /></p>';
			}
		}

		$options = [
			''     => 'None...',
			'link' => 'Link Only',
			'map'  => 'Map Only',
			'both' => 'Map and Link'
		];
		echo '<p><label for="' . $this->get_field_id( 'map_option' ) . '">Map Display</label><br><select name="' . $this->get_field_name( 'map_option' ) . '">';
		foreach ( $options AS $value => $text ) {
			echo '<option value="' . $value . '"';
			echo ( $value == $instance['map_option'] ) ? ' selected' : '';
			echo '>' . $text . '</option>';
		}
		echo '</select></p>';
	}
}

class acg_recent_posts extends WP_Widget {

	function __construct() {
		parent::__construct( 'acg_recent_posts', 'Custom Recent Posts', [ 'description' => 'Recent Posts, designed custom for this theme.' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$title  = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$number = $instance["number"] * 1;
		$number = ( $number ) ? $number : 4;
		$catq   = ( (int) $instance["categoryid"] ) ? '&cat=' . (int) $instance["categoryid"] : '';
		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		add_filter( 'excerpt_length', 'recent_posts_excerpt_length', 999 );
		$recentposts = new WP_Query();
		$recentposts->query( "post_type=post&posts_per_page=" . $number . $catq );
		if ( $recentposts->have_posts() ) :
			echo '<ul>';
			while ( $recentposts->have_posts() ) : $recentposts->the_post();
				$link = '<a class="read-more" href="' . get_permalink() . '">'; ?>
                <li><?php
					if ( $instance["showtitle"] ) {
						echo '<h3>';
						echo ( $instance["titlelink"] ) ? $link . get_the_title() . '</a>' : get_the_title();
						echo '</h3>';
					}
					$link = ( $instance["readmore"] ) ? '<a class="read-more" href="' . get_permalink() . '">' . $instance["readmore"] . '</a>' : '';
					if ( $instance["showexcerpt"] ) {
						echo '<div class="excerpt">';
						recent_posts_excerpt_dynamic( $post, 55, $link );
						echo '</div>';
						$link = "";
					}
					echo $link; ?>
                </li>
			<?php endwhile;
			echo '</ul>';
		endif;
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance["showtitle"]   = ( isset( $new_instance["showtitle"] ) ) ? 1 : 0;
		$instance["showexcerpt"] = ( isset( $new_instance["showexcerpt"] ) ) ? 1 : 0;
		$instance["titlelink"]   = ( isset( $new_instance["titlelink"] ) ) ? 1 : 0;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['number']      = strip_tags( $new_instance['number'] );
		$instance['readmore']    = $new_instance['readmore'];
		$instance["categoryid"]  = $new_instance["categoryid"];

		return $instance;
	}

	function form( $instance ) {
		$instance   = wp_parse_args( (array) $instance, [
			'title'       => '',
			'number'      => '4',
			'showtitle'   => 1,
			'showexcerpt' => 1,
			'titlelink'   => 1,
			'readmore'    => 'Read More &raquo;',
			'categoryid'  => ''
		] );
		$title      = strip_tags( $instance['title'] );
		$number     = strip_tags( $instance['number'] );
		$readmore   = strip_tags( $instance['readmore'] );
		$categoryid = $instance['categoryid'];
		?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/></p>
        <p><label for="<?php echo $this->get_field_id( 'categoryid' ); ?>"><?php _e( 'Category:' ); ?></label>
			<?php wp_dropdown_categories( [
				'name'            => $this->get_field_name( 'categoryid' ),
				'selected'        => $categoryid,
				'hierarchical'    => 1,
				'show_option_all' => '- All Categories -'
			] ); ?></p>
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of Posts to show:' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'number' ); ?>"
                   name="<?php echo $this->get_field_name( 'number' ); ?>" type="text"
                   value="<?php echo esc_attr( $number ); ?>" size="3"/></p>
		<?php $checked = ( $instance["showtitle"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "showtitle" ); ?>">Show Post Title: <input type="checkbox"
                                                                                                    id="<?php echo $this->get_field_id( "showtitle" ); ?>"
                                                                                                    name="<?php echo $this->get_field_name( "showtitle" ); ?>"
                                                                                                    value="1"<?php echo $checked; ?> /></label>
        </p>
		<?php $checked = ( $instance["showexcerpt"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "showexcerpt" ); ?>">Show Excerpt: <input type="checkbox"
                                                                                                   id="<?php echo $this->get_field_id( "showexcerpt" ); ?>"
                                                                                                   name="<?php echo $this->get_field_name( "showexcerpt" ); ?>"
                                                                                                   value="1"<?php echo $checked; ?> /></label>
        </p>
		<?php $checked = ( $instance["titlelink"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "titlelink" ); ?>">Post Title is Link: <input type="checkbox"
                                                                                                       id="<?php echo $this->get_field_id( "titlelink" ); ?>"
                                                                                                       name="<?php echo $this->get_field_name( "titlelink" ); ?>"
                                                                                                       value="1"<?php echo $checked; ?> /></label>
        </p>
        <p><label for="<?php echo $this->get_field_id( 'readmore' ); ?>"><?php _e( 'Read More Link:' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'readmore' ); ?>"
                   name="<?php echo $this->get_field_name( 'readmore' ); ?>" type="text"
                   value="<?php echo esc_attr( $readmore ); ?>"/></p>

		<?php
	}
}

function acg_core_remove_dashboard_widgets() {
	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	global $wp_meta_boxes;
	// Remove desired widgets
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links'] );
	// unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins'] );
	// unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
	// unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']); 

	// Then unset the side and primary
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );
}

function recent_posts_excerpt_dynamic( $post, $length, $link = "" ) { // Outputs an excerpt of variable length (in characters)
	$text = $post->post_exerpt;
	if ( '' == $text ) {
		$text = get_the_content( '' );
		$text = apply_filters( 'the_content', $text );
		$text = str_replace( ']]>', ']]>', $text );
	}
	$text  = strip_shortcodes( $text ); // optional, recommended
	$text  = strip_tags( $text ); // use ' $text = strip_tags($text,'<p><a>'); ' to keep some formats; optional
	$chars = [ " ", ".", ",", ":", "-", "\n", "\r" ];
	foreach ( $chars as $char ) {
		$pos  = stripos( $text, $char, $length );
		$text = ( $pos ) ? substr( $text, 0, $pos ) : $text;
	}
	$text .= "..." . $link;
	echo apply_filters( 'the_excerpt', $text );
}


// Create the function use in the action hook
function acg_core_dashboard_widgets() {
	wp_add_dashboard_widget( 'acg_dashboard_widget', 'WordPress Theme: ' . ACG_THEME_NAME, 'acg_dashboard_widget' );
	wp_add_dashboard_widget( 'acg_hotlink_widget', 'Migration Monitor', 'acg_hotlink_widget' );

	// Forcing it to the top...
	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	global $wp_meta_boxes;

	// Get the regular dashboard widgets array 
	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

	// Backup and delete our new dashbaord widget from the end of the array
	$acg_widget_backup = [
		'acg_dashboard_widget' => $normal_dashboard['acg_dashboard_widget'],
		'acg_hotlink_widget'   => $normal_dashboard['acg_hotlink_widget']
	];
	unset( $normal_dashboard['acg_dashboard_widget'] );
	unset( $normal_dashboard['acg_hotlink_widget'] );
	// Merge the two arrays together so our widget is at the beginning
	$sorted_dashboard = array_merge( $acg_widget_backup, $normal_dashboard );
	// Save the sorted array back into the original metaboxes 
	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
}


//  Recent Posts With Featured Image
class acg_recent_posts_featured_image_author extends WP_Widget {

	function __construct() {
		parent::__construct( 'recent_posts_featured_image_author', '- Recent Posts Super Widget', [ 'description' => 'Recent posts widget with a huge amount of flexibility to cover virtually any need for displaying recent posts.' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$title        = $instance["title"];
		$type         = $instance['type'];
		$image        = ( isset( $instance["image"] ) ) ? ( $instance["image"] ) : '';
		$count        = ( isset( $instance["count"] ) ) ? ( $instance["count"] * 1 ) : 5;
		$img_loc      = $instance["img_loc"];
		$title_loc    = $instance["title_loc"];
		$author       = ( isset( $instance["author"] ) ) ? ( $instance['author'] ) : '';
		$author_intro = $instance["author_intro"];
		$date         = ( isset( $instance["date"] ) ) ? ( $instance['date'] ) : '';
		$date_intro   = $instance["date_intro"];
		$date_format  = $instance["date_format"];
		$excerpt      = ( isset( $instance["excerpt"] ) ) ? ( $instance['excerpt'] ) : '';
		$learn_more   = ( isset( $instance["learn_more"] ) ) ? ( $instance['learn_more'] ) : '';
		$category_id  = (int) $instance["category_id"];
		$page_id      = (int) $instance["page_id"];
		$taxonomy     = $instance['taxonomy'];
		if ( $taxonomy ) {
			$taxonomy = json_decode( $taxonomy );
		}
		$category_link_loc = ( isset( $instance["category_link_loc"] ) ) ? $instance['category_link_loc'] : '';
		$category_link     = ( isset( $instance["category_link"] ) ) ? ( $instance['category_link'] ) : '';

		$category_url  = ( $category_id ) ? get_category_link( $category_id ) : get_permalink( $page_id );
		$category_link = ( $category_link ) ? '<a class="view_all_link" href="' . $category_url . '">' . $category_link . '</a>' . PHP_EOL : '';
		$show_category = ( isset( $instance["show_category"] ) ) ? ( $instance['show_category'] ) : '';
		$fancy_hover   = ( isset( $instance["use_fancy_hover"] ) && $instance['fancy_hover'] ) ? ' class ="fancy_hover"' : '';
		$widget_title  = PHP_EOL . $before_title . $title . $after_title . PHP_EOL;

		$placeholder_image = '<img src="' . $image . ' " class="wp-post-image alignleft" title="' . $image . '" alt="' . $title . '"/>' . PHP_EOL;

		echo PHP_EOL . $before_widget;

		global $post;
		$args = [
			'post_type'      => $type,
			'posts_per_page' => $count,
			'post__not_in'   => (array) $post->ID
		];

		if ( $taxonomy ) {
			$tax_query = [];
			foreach ( $taxonomy AS $tax => $id ) {
				if ( $id == 'related' ) {
					$taxes = get_the_terms( $post->ID, $tax );
					$ids   = [];
					if ( ! empty( $taxes ) ) {
						$ids = array_map( function ( $tax ) {
							return $tax->term_id;
						}, $taxes );
					}

					$id = $ids;
				}

				if ( $id ) {
					$tax_query[] = [
						'taxonomy' => $tax,
						'field'    => 'term_id',
						'terms'    => $id,
					];
				}
			}

			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = $tax_query;
			}
		}

		$wp = new WP_Query( $args );

		if ( $category_link && ( $category_link_loc == 'top' || $category_link_loc == 'both' ) ) {
			echo $category_link;
		}

		echo ( $title_loc && ( $title_loc == 'top' ) ) ? $widget_title : '';

		if ( $wp->have_posts() ) {
			echo '<ul' . $fancy_hover . '>' . PHP_EOL;
			while ( $wp->have_posts() ) {
				$wp->the_post();
				$post_title = '<a href="' . get_permalink() . '">' . get_the_title() . '</a>' . PHP_EOL;

				echo '<li class="widget_post_content">';
				if ( $date ) {
					$date_intro = ( $date_intro ) ? '<div class="intro">' . $date_intro . '</div>' : '';
					echo '<div class="date">' . $date_intro . get_the_date( $date_format ) . '</div>';
				}
				// Image loc == '' when it is to be surpressed
				if ( $img_loc ) {

					$image = '';
					if ( has_post_thumbnail() ) {
						$size  = ( $instance['img_size'] ) ? $instance['img_size'] : 'medium';
						$image = get_the_post_thumbnail( get_the_ID(), $size );
//						$image = get_the_post_thumbnail(get_the_ID(), 'thumbnail', 'full');
					} else {
						$image = $placeholder_image;
					}
					$image = '<div class="image"><a href="' . get_permalink() . '">' . $image . '</a></div>';


					if ( $img_loc == "first" ) {
						echo $image;
						echo '<div class="content">';
					}
					echo ( $title && ( $title_loc == 'post' ) ) ? $widget_title : '';
					echo $post_title;
					if ( $img_loc == "second" ) {
						echo $image;
						echo '<div class="content">';
					}
				} else {
					echo '<div class="content">';
					echo $post_title;
				}

				echo '<div class="widget_entry">';
				echo ( $author || $date ) ? '<p class="dateauthor">' . PHP_EOL : '';
				if ( $author ) {
					$author_intro = ( $author_intro ) ? '<span class="intro">' . $author_intro . '</span>' : '';
					echo '<span class="author">' . $author_intro . get_the_author() . '</span>';
				}
				echo ( $author || $date ) ? '</p>' . PHP_EOL : '';
				if ( $excerpt ) {
					the_excerpt();
				}
				if ( $show_category ) {
					$categories = get_the_category();
					if ( $categories ) {
						$category = $categories[0]->cat_name;
						echo '<div class="category">' . $category . '</div>';
					}
				}
				if ( $learn_more ) {
					echo '<div class="learn_more"><a class="learn_more" href="' . get_permalink() . '">' . $learn_more . '</a></div>';
				}
				if ( $img_loc == "last" ) {
					$image;
				}
				echo '</div></div></li>';
			}
			echo '</ul>';
		}

		if ( $category_link && ( $category_link_loc == 'bottom' || $category_link_loc == 'both' ) ) {
			echo $category_link;
		}
		echo $after_widget . PHP_EOL;
	}

	function update( $new_instance, $old_instance ) {
		$instance                      = $old_instance;
		$instance['title']             = strip_tags( stripslashes( $new_instance["title"] ) );
		$instance['type']              = strip_tags( stripslashes( $new_instance['type'] ) );
		$instance['image']             = strip_tags( stripslashes( $new_instance["image"] ) );
		$instance['count']             = strip_tags( stripslashes( $new_instance["count"] ) );
		$instance['category_id']       = strip_tags( stripslashes( $new_instance["category_id"] ) );
		$instance['page_id']           = strip_tags( stripslashes( $new_instance["page_id"] ) );
		$instance['img_loc']           = strip_tags( stripslashes( $new_instance["img_loc"] ) );
		$instance['img_size']          = strip_tags( stripslashes( $new_instance["img_size"] ) );
		$instance['title_loc']         = strip_tags( stripslashes( $new_instance["title_loc"] ) );
		$instance['author']            = ( isset( $new_instance["author"] ) ) ? 1 : 0;
		$instance['author_intro']      = $new_instance["author_intro"];
		$instance['date']              = ( isset( $new_instance["date"] ) ) ? 1 : 0;
		$instance['date_intro']        = $new_instance["date_intro"];
		$instance['date_format']       = $new_instance["date_format"];
		$instance['excerpt']           = ( isset( $new_instance["excerpt"] ) ) ? 1 : 0;
		$instance['learn_more']        = $new_instance["learn_more"];
		$instance['category_link']     = $new_instance["category_link"];
		$instance['category_link_loc'] = $new_instance["category_link_loc"];
		$instance['show_category']     = ( isset( $new_instance["show_category"] ) ) ? 1 : 0;
		$instance['use_fancy_hover']   = ( isset( $new_instance["use_fancy_hover"] ) ) ? 1 : 0;
		$instance['read_more_text']    = strip_tags( stripslashes( $new_instance["read_more_text"] ) );
		$instance['taxonomy']          = json_encode( $new_instance["taxonomy"] );

		return $instance;
	}

	function form( $instance ) {
		$default = [
			'title'             => 'Recent Posts',
			'type'              => 'post',
			'image'             => '',
			'count'             => '5',
			'category_id'       => '',
			'page_id'           => '',
			'img_loc'           => 'second',
			'img_size'          => 'thumbnail',
			'title_loc'         => 'top',
			'author'            => '',
			'author_intro'      => 'by',
			'date'              => '',
			'date_intro'        => 'posted on',
			'date_format'       => 'm/d/Y',
			'excerpt'           => '',
			'learn_more'        => 'Learn More',
			'category_link'     => 'View All',
			'category_link_loc' => '',
			'show_category'     => 0,
			'use_fancy_hover'   => 0,
			'taxonomy'          => '',
		];

		$instance          = wp_parse_args( (array) $instance, $default );
		$title             = esc_attr( $instance['title'] );
		$type              = esc_attr( $instance['type'] );
		$image             = esc_attr( $instance['image'] );
		$count             = esc_attr( $instance['count'] );
		$category_id       = esc_attr( $instance['category_id'] );
		$page_id           = esc_attr( $instance['page_id'] );
		$learn_more        = esc_attr( $instance['learn_more'] );
		$category_link     = esc_attr( $instance['category_link'] );
		$category_link_loc = esc_attr( $instance['category_link_loc'] );
		$img_loc           = esc_attr( $instance["img_loc"] );
		$img_size          = esc_attr( $instance["img_size"] );
		$title_loc         = esc_attr( $instance["title_loc"] );
		$author            = esc_attr( $instance["author"] );
		$author_intro      = esc_attr( $instance["author_intro"] );
		$date_intro        = esc_attr( $instance["date_intro"] );
		$date_format       = esc_attr( $instance["date_format"] );
		$show_category     = esc_attr( $instance["show_category"] );
		$use_fancy_hover   = esc_attr( $instance['use_fancy_hover'] );
		$taxonomy          = json_decode( $instance['taxonomy'] );

		$args     = [ 'public' => TRUE, 'publicly_queryable' => TRUE ];
		$types    = get_post_types( $args, 'objects' );
		$typelist = '<select name="' . $this->get_field_name( "type" ) . '">';

		foreach ( $types as $post_type ) {
			if ( $post_type->name != 'attachment' ) {
				$typelist .= '<option value="' . $post_type->name . '"';
				$typelist .= ( $type == $post_type->name ) ? ' selected="selected"' : '';
				$typelist .= '>' . $post_type->labels->name . '</option>';
			}
		}
		$typelist .= '</select>';

		$type_identifier = 'select[name="' . str_replace( '[', '\\[', str_replace( ']', '\\]', $this->get_field_name( 'type' ) ) ) . '"]';

		$locs    = [
			""       => " - No Image -",
			"first"  => "First (before post title)",
			"second" => "Second (after post title)",
			"last"   => "Last"
		];
		$loclist = '<select name="' . $this->get_field_name( "img_loc" ) . '">';

		foreach ( $locs as $val => $loc ) {
			$loclist .= '<option value="' . $val . '"';
			$loclist .= ( $val == $img_loc ) ? ' selected="selected"' : '';
			$loclist .= '>' . $loc . '</option>';
		}
		$loclist .= '</select>';

		$locs = [ "top" => "Top (before posts)", "post" => "In Post" ];

		$sizes    = [
			"thumbnail" => "Thumbnail",
			"medium"    => "Medium",
			"large"     => "Large",
			"full"      => "Full",
		];
		$sizelist = '<select name="' . $this->get_field_name( "img_size" ) . '">';

		foreach ( $sizes as $val => $size ) {
			$sizelist .= '<option value="' . $val . '"';
			$sizelist .= ( $val == $img_size ) ? ' selected="selected"' : '';
			$sizelist .= '>' . $size . '</option>';
		}
		$sizelist .= '</select>';

		$locs      = [ "top" => "Top (before posts)", "post" => "In Post" ];
		$titlelist = '<select name="' . $this->get_field_name( "title_loc" ) . '">';
		foreach ( $locs as $val => $loc ) {
			$titlelist .= '<option value="' . $val . '"';
			$titlelist .= ( $val == $title_loc ) ? ' selected="selected"' : '';
			$titlelist .= '>' . $loc . '</option>';
		}
		$titlelist .= '</select>';

		$locs       = [ "" => "- None -", "top" => "Top", "bottom" => "Bottom", "both" => "Both" ];
		$catloclist = '<select name="' . $this->get_field_name( "category_link_loc" ) . '">';
		foreach ( $locs as $val => $loc ) {
			$catloclist .= '<option value="' . $val . '"';
			$catloclist .= ( $val == $category_link_loc ) ? ' selected="selected"' : '';
			$catloclist .= '>' . $loc . '</option>';
		}
		$catloclist .= '</select>';

		$formats = [
			'm/d/Y'                => '08/14/2013',
			'm/d/Y \a\t g:ia'      => '08/14/2013 at 1:25pm',
			'm/d/Y \a\t H:i'       => '08/14/2013 at 13:25',
			'Y-m-d'                => '2013-08-14',
			'Y-m-d \a\t g:ia'      => '2013-08-14 at 1:25pm',
			'Y-m-d \a\t H:i'       => '2013-08-14 at 13:25',
			'M jS, Y'              => 'Aug 14th, 2013',
			'F jS, Y'              => 'August 14th, 2013',
			'F jS, Y \a\t g:ia'    => 'August 14th, 2013 at 1:25pm',
			'F jS, Y \a\t H:i'     => 'August 14th, 2013 at 13:25',
			'D, M jS, Y'           => 'Thu, Aug 14th, 2013',
			'D, M jS, Y \a\t g:ia' => 'Thu, Aug 14th, 2013 at 1:25pm',
			'D, M jS, Y \a\t H:i'  => 'Thu, Aug 14th, 2013 at 13:25',
			'l, M jS, Y'           => 'Thursday, Aug 14th, 2013',
			'l, M jS, Y \a\t g:ia' => 'Thursday, Aug 14th, 2013 at 1:25pm',
			'l, M jS, Y \a\t H:i'  => 'Thursday, Aug 14th, 2013 at 13:25'
		];

		$dateformatlist = '<select style="width: 175px;font-size: 8pt;" name="' . $this->get_field_name( "date_format" ) . '">';
		foreach ( $formats as $val => $format ) {
			$dateformatlist .= '<option value="' . $val . '"';
			$dateformatlist .= ( $val == $date_format ) ? ' selected="selected"' : '';
			$dateformatlist .= '>' . $format . '</option>';
		}
		$dateformatlist .= '</select>';

		$page_id_selector    = 'p-' . $this->get_field_id( 'page_id' );
		$category_identifier = 'select[name="' . str_replace( '[', '\\[', str_replace( ']', '\\]', $this->get_field_name( 'category_id' ) ) ) . '"]';
		?>
        <p><label for="<?php echo $this->get_field_id( 'type' ); ?>">Post Types </label><?php echo $typelist; ?></p>
        <p><label for="<?php echo $this->get_field_id( 'title_loc' ); ?>">Widget Title
                Location </label><?php echo $titlelist; ?></p>
        <p><label for="<?php echo $this->get_field_name( "title" ); ?>">Widget Title: <input type="text" class="widefat"
                                                                                             id="<?php echo $this->get_field_id( "title" ); ?>"
                                                                                             name="<?php echo $this->get_field_name( "title" ); ?>"
                                                                                             value="<?php echo $title; ?>"/></label>
        </p>

        <legend style="font-weight:bold; color: #555; border-bottom: 1px solid #555;">Recent Posts</legend>
        <p><label for="<?php echo $this->get_field_id( 'count' ); ?>">Number of Posts: </label><input size="3"
                                                                                                      type="text"
                                                                                                      name="<?php echo $this->get_field_name( 'count' ); ?>"
                                                                                                      value="<?php echo $count; ?>"/>
        </p>
		<?php

		// Generate the variety of taxonomies available for the selected post type
		$taxonomies = get_object_taxonomies( $type );

		foreach ( $taxonomies AS $taxon ) {
			$tax = get_taxonomy( $taxon );

			if ( ! $tax->show_ui ) {
				continue;
			}

			$terms            = get_terms( $tax->name );
			$selected         = ( ! empty( $taxonomy->{$tax->name} ) ) ? $taxonomy->{$tax->name} : '';
			$related_selected = ( $selected == 'related' ) ? ' selected' : '';

			echo '<p class="taxonomy" data-post-type="' . $type . '"><label for="' . $this->get_field_id( 'taxonomy][' . $tax->name . ']' ) . '">' . $tax->labels->name . '</label>';

			echo '<select name="' . $this->get_field_name( 'taxonomy][' . $tax->name . ']' ) . '">';
			echo '<option value=""> - All ' . $tax->labels->name . ' - </option>';
			echo '<option value="related"' . $related_selected . '> - Automatic (Related) - </option>';

			foreach ( $terms AS $term ) {
				echo '<option value="' . $term->term_id . '"';
				echo ( $selected == $term->term_id ) ? ' selected' : '';
				echo '>' . $term->name . '</option>';
			}

			echo '</select></p>';

		}
		?>
        <legend style="font-weight:bold; color: #555; border-bottom: 1px solid #555;">View All Link</legend>
        <p id="<?php echo $page_id_selector; ?>"><label for="">View All Links
                To</label><?php wp_dropdown_pages( 'name=' . $this->get_field_name( 'page_id' ) . '&selected=' . $page_id ); ?>
        </p>
        <p><label
                    for="<?php echo $this->get_field_id( 'category_link_loc' ); ?>">Location: </label><?php echo $catloclist; ?>
        </p>
        <p><label for="<?php echo $this->get_field_id( 'category_link' ); ?>">Text: </label><input type="text"
                                                                                                   name="<?php echo $this->get_field_name( 'category_link' ); ?>"
                                                                                                   value="<?php echo $category_link; ?>"/>
        </p>

        <legend style="font-weight:bold; color: #555; border-bottom: 1px solid #555;">Image</legend>
        <p><label for="<?php echo $this->get_field_id( 'img_loc' ); ?>">Location: </label><?php echo $loclist; ?></p>
        <p><label for="<?php echo $this->get_field_id( 'img_size' ); ?>">Image Size: </label><?php echo $sizelist; ?>
        </p>
        <p><label for="<?php echo $this->get_field_name( "image" ); ?>">Static Image URL<br/><span
                        style="color: #888;font-size: 8pt;">(overrides featured product image)</span>:<input type="text"
                                                                                                             class="widefat"
                                                                                                             id="<?php echo $this->get_field_id( "image" ); ?>"
                                                                                                             name="<?php echo $this->get_field_name( "image" ); ?>"
                                                                                                             value="<?php echo $image; ?>"/></label>
        </p>

        <legend style="font-weight:bold; color: #555; border-bottom: 1px solid #555;">Author</legend>
        <p><input class="checkbox" type="checkbox" <?php checked( $instance['author'], TRUE ) ?>
                  id="<?php echo $this->get_field_id( 'author' ); ?>"
                  name="<?php echo $this->get_field_name( 'author' ); ?>"/>
            <label for="<?php echo $this->get_field_id( 'author' ); ?>">Display Author</label></p>
        <p><label for="<?php echo $this->get_field_id( 'author_intro' ); ?>">Author Intro Text</label><input
                    class="widefat" type="text" name="<?php echo $this->get_field_name( 'author_intro' ); ?>"
                    value="<?php echo $author_intro; ?>"/></p>

        <legend style="font-weight:bold; color: #555; border-bottom: 1px solid #555;">Date</legend>
        <p><input class="checkbox" type="checkbox" <?php checked( $instance['date'], TRUE ) ?>
                  id="<?php echo $this->get_field_id( 'date' ); ?>"
                  name="<?php echo $this->get_field_name( 'date' ); ?>"/>
            <label for="<?php echo $this->get_field_id( 'date' ); ?>">Display Date</label></p>
        <p><label for="<?php echo $this->get_field_id( 'date_intro' ); ?>">Intro Text</label><input class="widefat"
                                                                                                    type="text"
                                                                                                    name="<?php echo $this->get_field_name( 'date_intro' ); ?>"
                                                                                                    value="<?php echo $date_intro; ?>"/>
        </p>
        <p><label
                    for="<?php echo $this->get_field_id( 'date_format' ); ?>">Format: </label><?php echo $dateformatlist; ?>
        </p>

        <legend style="font-weight:bold; color: #555; border-bottom: 1px solid #555;">Excerpt</legend>
        <p><input class="checkbox" type="checkbox" <?php checked( $instance['excerpt'], TRUE ) ?>
                  id="<?php echo $this->get_field_id( 'excerpt' ); ?>"
                  name="<?php echo $this->get_field_name( 'excerpt' ); ?>"/>
            <label for="<?php echo $this->get_field_id( 'excerpt' ); ?>">Display Excerpt</label></p>

        <p><label for="<?php echo $this->get_field_id( 'category_link' ); ?>">Link Text: </label><input type="text"
                                                                                                        name="<?php echo $this->get_field_name( 'learn_more' ); ?>"
                                                                                                        value="<?php echo $learn_more; ?>"/>
        </p>
        <legend style="font-weight:bold; color: #555; border-bottom: 1px solid #555;">Hover Effects</legend>
        <p><input class="checkbox" type="checkbox" <?php checked( $instance['use_fancy_hover'], TRUE ) ?>
                  id="<?php echo $this->get_field_id( 'use_fancy_hover' ); ?>"
                  name="<?php echo $this->get_field_name( 'use_fancy_hover' ); ?>"/>
            <label for="<?php echo $this->get_field_id( 'use_fancy_hover' ); ?>">Use Fancy Hover Effects</label></p>
        <p><input class="checkbox" type="checkbox" <?php checked( $instance['show_category'], TRUE ) ?>
                  id="<?php echo $this->get_field_id( 'show_category' ); ?>"
                  name="<?php echo $this->get_field_name( 'show_category' ); ?>"/>
            <label for="<?php echo $this->get_field_id( 'show_category' ); ?>">Show Category Name</label></p>

        <script>
          jQuery( '<?php echo $category_identifier; ?>' ).change( function () {
            toggleACGSWPageId();
          } );
          jQuery( '<?php echo $type_identifier; ?>' ).change( function () {
            toggleACGSWTaxonomy();
          } );
          jQuery( function () {
            toggleACGSWPageId();
          } );

          function toggleACGSWTaxonomy() {
            var type  = jQuery( '<?php echo $type_identifier; ?>' );
            var sel   = type.val();
            var tax   = type.closest( '.widget-content' ).find( 'p.taxonomy' );
            var exist = tax.attr( 'data-post-type' );
            type.closest( '.widget-content' ).find( '.update-tax' ).remove();
            if ( sel == exist ) {
              tax.show();
            } else {
              tax.hide().last().after( '<p class="update-tax"><strong> - Click Save to select Categories - </strong></p>' );
            }
          }

          function toggleACGSWPageId() {
            var pageid = jQuery( 'p#<?php echo $page_id_selector; ?>' );
            if ( jQuery( '<?php echo $category_identifier; ?>' ).val() == '0' ) {
              pageid.slideDown();
            } else {
              pageid.slideUp();
            }
          }
        </script>
	<?php }
}


/**
 * TODO: CLEAN THIS UP
 *
 */
class acg_contact_form extends WP_Widget {

	function __construct() {
		parent::__construct( 'acg_contact_form', 'Contact Form', [ 'description' => 'Contact Us/Send Us a Message form.' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$options   = $instance;
		$drss_name = $drss_email = $drss_phone = $drss_message = $drss_contact_error = "";
		$showform  = TRUE;
		$link      = get_permalink( $instance["page"] );
		if ( isset( $_POST["drss_contact_submit"] ) ) {
			$drss_name    = $_POST["drss_name"];
			$drss_email   = $_POST["drss_email"];
			$drss_phone   = $_POST["drss_phone"];
			$drss_message = $_POST["drss_message"];
			$contact      = acgValidateInput( $drss_email, TRUE, "e" );
			if ( $contact ) {
				$drss_contact_error .= 'Please enter a valid e-mail address.<br />';
			}
			// Anti-spam techniques
			if ( strlen( $drss_message ) > 400 ) {
				$drss_contact_error .= 'Your message is too long.<br />';
			}
			if ( stripos( $drss_message, "<a href=" ) !== FALSE || stripos( $drss_message, "[link=" ) !== FALSE || stripos( $drss_message, "[url=" ) !== FALSE ) {
				$drss_contact_error .= 'No link code allowed.<br />';
			}
			if ( stripos( $drss_message, "<script" ) !== FALSE ) {
				$drss_contact_error .= 'Shame on you. Really?!?<br />';
			}
			if ( substr_count( $drss_message, "http" ) > 3 ) {
				$drss_contact_error .= 'Too many links.<br />';
			}
			if ( $_POST['disclaimer'] != 'on' ) {
				$drss_contact_error .= 'Please verify you have read the disclaimer.<br />';
			}
			if ( isset( $options["enforce"] ) && $options["enforce"] ) {
				// Lets check the referer and be sure it's coming from a form submitted on this site....
				$server = ( isset( $_SERVER["SERVER_NAME"] ) ) ? $_SERVER["SERVER_NAME"] : str_replace( "http://", "", get_bloginfo( "url" ) );
				$refer  = ( isset( $_SERVER["HTTP_REFERER"] ) ) ? $_SERVER["HTTP_REFERER"] : '';
				// If not, don't do anything other than die silently.
				if ( stripos( $refer, $server ) === FALSE ) {
					$drss_contact_error .= "You must submit from this domain.<br />";
				} else {
					// $drss_contact_error.= "Success!<br />" . $refer .  " contains " . $server . "<br />";
				}
			}

			if ( ! $drss_contact_error ) {
				$sendmessage = 'From: ' . $drss_name . "\n";
				$sendmessage .= 'E-mail: ' . $drss_email . "\n";
				$sendmessage .= 'Phone: ' . $drss_phone . "\n";
				$sendmessage .= "Message: \n" . stripslashes( $drss_message ) . "\n";

				$admin_email = get_option( 'admin_email' );
				$bccheaders  = "";
				$sendto      = $options["sendto"];
				$send        = explode( ";", $sendto );
				foreach ( $send as $e ) {
					if ( $e ) {
						$bccheaders .= "\nBcc:" . trim( $e );
					}
				}
				$headers .= "\r\n\\";
				wp_mail( $send[0], "Contact Us Form from " . get_bloginfo( "url" ), $sendmessage, $headers . $bccheaders );
				// TODO: make this message define-able in the widget.
				$sendmessage = "Thank you for contacting us.  We strive to answer all inquiries within 5 business days. The message you sent is:\n\n";
				$sendmessage .= stripslashes( $drss_message );
				// echo "<br>" . $drss_email;
				// echo "<br>" . $headers;
				wp_mail( $drss_email, "Contact Form Submission", $sendmessage, $headers );
				$showform = FALSE;
			}
		}
		echo $before_widget;
		if ( $showform ) {
			echo '<h3>' . $options["title"] . '</h3><span>Bold</span> labels are required.' . LF;
			echo '<form method="post">';
			echo ( $drss_contact_error ) ? acgP( $drss_contact_error, "error" ) : "";
			echo acgFormInput( "drss_name", "Name", $drss_name );
			echo '<div><input type="text" name="drss_email" placeholder="E-Mail address" value="' . $drss_email . '" required /></div>';
			echo acgFormInput( "drss_phone", "phone", $drss_phone );
			echo acgFormInput( "drss_message", "Brief description of your legal issue", $drss_message, "area", "a" );
			echo acgFormInput( "disclaimer", 'I have read the <a href="' . $link . '"> disclaimer</a>.', $disclaimer, "disclaimer", "c" );
			echo acgDiv( acgSubmitInput( "drss_contact_submit", "Send", "submit" ), 'submit' );
			echo '</form>';
		} else {
			$send = explode( ";", $options["sendto"] );
			echo '<h3>Thank You!</h3>';
			echo '<p>Your message has been sent. Someone will be in touch with you shortly.</p>';
			echo $options["script"];
		}
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['title']  = stripslashes( $new_instance["title"] );
		$instance['sendto'] = stripslashes( $new_instance["sendto"] );
		$instance['script'] = stripslashes( $new_instance["script"] );
		$instance['page']   = strip_tags( stripslashes( $new_instance['page'] ) );

		return $instance;
	}

	function form( $instance ) {
		$default      = [ 'title' => __( 'Contact Us' ), 'sendto' => '', 'script' => '', 'page_id' => '' ];
		$instance     = wp_parse_args( (array) $instance, $default );
		$title_id     = $this->get_field_id( 'title' );
		$title_name   = $this->get_field_name( 'title' );
		$sendto_id    = $this->get_field_id( 'sendto' );
		$sendto_name  = $this->get_field_name( 'sendto' );
		$script_id    = $this->get_field_id( 'script' );
		$script_name  = $this->get_field_name( 'script' );
		$enforce_id   = $this->get_field_id( 'enforce' );
		$enforce_name = $this->get_field_name( 'enforce' );
		$enforced     = ( $instance["enforce"] ) ? ' checked="checked"' : '';
		$server       = ( isset( $_SERVER["SERVER_NAME"] ) ) ? $_SERVER["SERVER_NAME"] : str_replace( "http://", "", get_bloginfo( "url" ) );
		$args         = [
			'echo' => 0,
			'name' => 'page_id'
		];
		echo PHP_EOL . '<p><label for="' . $title_name . '">' . __( 'Title' ) . ': <input type="text" class="widefat" id="' . $title_id . '" name="' . $title_name . '" value="' . esc_attr( $instance['title'] ) . '" /><label></p>';
		if ( $instance ) {
			$page = esc_attr( $instance["page"] );
		}
		$default  = [ 'page' => '' ];
		$args     = [
			'selected' => $page,
			'echo'     => 1,
			'name'     => $this->get_field_name( 'page' ),
		];
		$instance = wp_parse_args( (array) $default, $args );
		?>
        <p><label for="<?php echo $this->get_field_id( 'page' ); ?>">Page to Display
            Disclaimer: </label><?php wp_dropdown_pages( $args ); ?></p><?php


		echo PHP_EOL . '<p><label for="' . $sendto_name . '">' . __( 'Send-to email(s)' ) . ':<br><span style="font-size:8pt;">(Separate e-mails with a semicolon ; )</span> <input type="text" class="widefat" id="' . $sendto_id . '" name="' . $sendto_name . '" value="' . esc_attr( $instance['sendto'] ) . '" /><label></p>';
		echo PHP_EOL . '<p>Send-from e-mail: <a style="font-size: 8pt;" href="options-general.php">(change)</a><br />' . get_option( 'admin_email' );
		echo PHP_EOL . '<p><label for="' . $script_name . '">' . __( 'Conversion Script(s)' ) . ':<br><span style="font-size:8pt;">(Must include the &lt;script&gt; tags)</span><br><textarea class="widefat" rows="10" cols="20" id="' . $script_id . '" name="' . $script_name . '" style="font-size: 9pt;color: #009; font-family: Fixed-width;">' . esc_attr( $instance['script'] ) . '</textarea><label></p>';
		echo PHP_EOL . '<p><label for="' . $enforce_id . '"><input type="checkbox" name="' . $enforce_name . '" id="' . $enforce_id . '"' . $enforced . '" />&nbsp;' . __( 'Deny unless submitted from this domain?' ) . ' (<small>' . $server . '</small>)</label></p>';
	}
}


/**
 * Text widget class
 */
class acg_classy_text_widget extends WP_Widget {

	function __construct() {
		parent::__construct( 'acg_classy_text_widget', '- Classy Text', [ 'description' => 'Arbitrary Text or HTML, with a class you can assign' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$text  = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance );
		$class = $instance['class'];
		if ( $class ) {
			$start         = stripos( $before_widget, 'class="' ) + 8;
			$start         = stripos( $before_widget, '"', $start );
			$before_widget = substr( $before_widget, 0, $start ) . ' ' . $class . substr( $before_widget, $start );
		}
		echo $before_widget;
		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		} ?>
        <div class="textwidget"><?php echo ! empty( $instance['filter'] ) ? wpautop( $text ) : $text; ?></div>
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['class'] = strip_tags( $new_instance['class'] );
		if ( current_user_can( 'unfiltered_html' ) ) {
			$instance['text'] = $new_instance['text'];
		} else {
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes( $new_instance['text'] ) ) );
		} // wp_filter_post_kses() expects slashed
		$instance['filter'] = isset( $new_instance['filter'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, [ 'title' => '', 'class' => '', 'text' => '' ] );
		$title    = strip_tags( $instance['title'] );
		$class    = strip_tags( $instance['class'] );
		$text     = esc_textarea( $instance['text'] );
		?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/></p>
        <p><label for="<?php echo $this->get_field_id( 'class' ); ?>"><?php _e( 'Class(es):' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'class' ); ?>"
                   name="<?php echo $this->get_field_name( 'class' ); ?>" type="text"
                   value="<?php echo esc_attr( $class ); ?>"/>
            <span>May include spaces for multiple classes</span></p>

        <textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id( 'text' ); ?>"
                  name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo $text; ?></textarea>

        <p><input id="<?php echo $this->get_field_id( 'filter' ); ?>"
                  name="<?php echo $this->get_field_name( 'filter' ); ?>"
                  type="checkbox" <?php checked( isset( $instance['filter'] ) ? $instance['filter'] : 0 ); ?> />&nbsp;<label
                    for="<?php echo $this->get_field_id( 'filter' ); ?>"><?php _e( 'Automatically add paragraphs' ); ?></label>
        </p>
		<?php
	}
}

function acg_page_category() {
	register_taxonomy_for_object_type( 'category', 'page' );
}

add_action( 'admin_init', 'acg_page_category' );
// Remove unwanted widgets that don't play nice with this theme
add_action( 'widgets_init', 'acg_core_unregister_widgets' );
// Hoook into the 'wp_dashboard_setup' action to remove the widgets our function
add_action( 'wp_dashboard_setup', 'acg_core_remove_dashboard_widgets' );
register_widget( 'acg_recent_posts_featured_image_author' );
register_widget( 'acg_facebook_twitter' );
register_widget( 'acg_subnav' );
register_widget( 'acg_youtube_embed' );
register_widget( 'acg_recent_posts' );
register_widget( 'acg_contact_info' );
register_widget( 'acg_post_slider' );
register_widget( 'acg_classy_text_widget' );
