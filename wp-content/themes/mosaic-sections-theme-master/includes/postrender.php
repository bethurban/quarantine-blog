<?php

/**
 * Class MosaicPostDisplay
 *
 * Handles displaying a single post (can support custom post types as well) based on
 * the display configuration defined in the dashboard.
 *
 * Outputs the defined "fields" (title, content, featured image, etc) in the defined order.
 *
 * Attempts to "smartly" do some things such as wrap the title, content, etc. in a "wrapper"
 * so that relevant CSS styling can be applied.  This gives the "content" a column, for example,
 * so that the featured image can be on the left side, and the 'column' of content can be on the right
 * (or vice-versa).
 *
 * Note the wrapping does not work well if the featured image is in the middle of these fields :)
 */
class MosaicPostDisplay {
	public static $type = '';
	public static $display = [];
	public static $fields = [];
	public static $has_thumbnail = FALSE;
	public static $thumbnail_first = FALSE;
	public static $thumbnail_last = FALSE;
	public static $column_open = [];
	public static $column_closed = [];
	public static $featured_rendered = FALSE;
	public static $title_plus_meta = FALSE;

	public static function init( $type ) {
		self::$type    = $type;
		self::$display = MosaicTheme::get_display_options( self::$type, TRUE );
		self::$fields  = array_keys( self::$display );;
		self::$has_thumbnail   = ( in_array( 'featured', self::$fields ) );
		self::$thumbnail_first = ( 'featured' == _::get( self::$fields, 0 ) );
		self::$thumbnail_last  = ( 'featured' == _::get( self::$fields, count( self::$fields ) - 1 ) );

		$last = '';
		foreach ( self::$display AS $key => $options ) {
			if ( ( 'title' == $last && 'meta' == $key ) || ( 'meta' == $last && 'title' == $key ) ) {
				self::$title_plus_meta = $key;
				break;
			}

			$last = $key;
		}

		add_filter( 'post_class', [ __CLASS__, 'post_class' ] );
	}

	public static function display() {
		self::$column_open       = [ 'info' => FALSE, 'title' => FALSE ];
		self::$column_closed     = [ 'info' => FALSE, 'title' => FALSE ];
		self::$featured_rendered = FALSE;

		$date = get_the_date();
		$time = '';

		if ( apply_filters( 'mosaic_' . self::$type . '_include_time', FALSE ) ) {
			$time = get_the_time();
			$time = '<span class="posted-at"><span>at</span>{$time}</span>';
		}

		$author          = get_the_author();
		$structured_data = <<<HTML
	<span class="structured-data">
		<span class="posted-on">Posted On</span><span
		class="date post-date updated">{$date}</span>
		{$time}
		<span class="posted-by">by</span><span class="vcard author post-author"><span
		class="fn">{$author}</span></span>
	</span>
HTML;
		?>
		<?php

		if ( empty( self::$display ) ) {
			return;
		}

		$key = '';

		foreach ( self::$display AS $key => $data ) {
			$classes   = [];
			$classes[] = ( self::$featured_rendered ) ? 'after-featured' : '';
			$classes   = apply_filters( 'mosaic_' . self::$type . '_' . $key . '_class', $classes );

			// call this while classes is still an array
			self::maybe_open_column( $key, $classes );

			$classes = implode( ' ', $classes );
			$classes = ( $classes ) ? " {$classes}" : '';

			do_action( 'mosaic_' . self::$type . '_before_post_' . $key );

			switch ( $key ) {
				case 'title':
					echo '<h1 class="entry-title' . $classes . '"><a href="' . get_the_permalink() . '">' . apply_filters( 'mosaic_' . self::$type . '_title', get_the_title() ) . '</a></h1>';
					self::maybe_close_column( $key, TRUE );
					break;
				case 'content':
					echo '<div class="entry entry-content' . $classes . '">';
					the_content( '<p class="serif">Read the rest of this entry &raquo;</p>' );
					echo '</div>';
					break;
				case 'excerpt':
					echo '<div class="entry entry-excerpt' . $classes . '">';
					the_excerpt();
					echo '</div>';
					break;
				case 'meta':
					echo apply_filters( 'mosaic_' . self::$type . '_structured_data', $structured_data );
					self::maybe_close_column( $key, TRUE );
					break;
				case 'featured':
					self::maybe_close_column( $key );
					$size = ( 'single' == self::$type ) ? 'large' : 'medium';
					$size = apply_filters( 'mosaic_' . self::$type . '_featured_image_size', $size );
					echo '<div class="post-featured-image' . $classes . '">';
					the_post_thumbnail( $size );
					do_action( 'mosaic_' . self::$type . '_before_' . $key . '_close' );
					echo '</div>';
					self::$featured_rendered = TRUE;
					break;
				case 'source':
					$logo = apply_filters( 'get_source_logo', '', get_the_ID() );
					if ( $logo ) {
						echo apply_filters( 'mosaic_' . self::$type . '_source', '<div class="source-logo' . $classes . '"><img src="' . $logo . '" class="source-logo-img"></div>' );
					}
					break;
				case 'categories':
					$categories = get_the_category_list( ', ' );
					if ( $categories ) {
						echo '<div class="taxonomy taxonomy-categories' . $classes . '">';
						echo '<span class="taxonomy-label">' . apply_filters( 'mosaic_' . self::$type . '_category', 'Categories:' ) . '</span>';
						echo $categories;
						echo '</div>';
					}
					break;
				case 'tags':
					$tags = get_the_tag_list( '', ', ' );
					if ( $tags ) {
						echo '<div class="taxonomy taxonomy-tags' . $classes . '">';
						echo '<span class="taxonomy-label">' . apply_filters( 'mosaic_' . self::$type . '_tag_label', 'Tags:' ) . '</span>';
						echo $tags;
						echo '</div>';
					}
					break;
				case 'taxonomies':
					$taxonomies = apply_filters( 'mosaic_post_get_taxonomies', [], get_post(), self::$type );

					if ( ! empty( $taxonomies ) ) {
						do_action( 'mosaic_post_render_taxonomies', $taxonomies, get_post(), self::$type );
					}

					break;
			}

			do_action( 'mosaic_' . self::$type . '_after_post_' . $key );
		}

		self::maybe_close_column( $key );
	}

	private static function maybe_open_column( $key, $classes = [] ) {
		$classes        = implode( ' ', $classes );
		$classes        = ( $classes ) ? " {$classes}" : '';
		$do_info_column = TRUE;

		if ( ! apply_filters( 'mosaic_' . self::$type . '_post_column', TRUE ) ) {
			$do_info_column = FALSE;
		}

		if ( self::$column_open['info'] || self::$column_closed['info'] ) {
			$do_info_column = FALSE;
		}

		if ( $do_info_column && in_array( $key, [ 'title', 'content', 'excerpt', 'source', 'meta' ] ) ) {
			echo '<div class="post-info' . $classes . '">';
			self::$column_open['info'] = TRUE;
		}

		if ( 'title' == $key && self::$title_plus_meta ) {
			echo '<div class="date-and-title' . $classes . '">';
			self::$column_open['title'] = TRUE;
		}
	}

	private static function maybe_close_column( $key = '', $title_only = FALSE ) {
		if ( $key == self::$title_plus_meta && self::$column_open['title'] ) {
			echo '</div>';
		}

		if ( $title_only ) {
			return;
		}

		$do_info_column = TRUE;
		if ( ! apply_filters( 'mosaic_' . self::$type . '_post_column', TRUE ) ) {
			$do_info_column = FALSE;
		}

		if ( ! self::$column_open['info'] || self::$column_closed['info'] ) {
			$do_info_column = FALSE;
		}

		if ( $do_info_column ) {
			echo '</div>';
			self::$column_closed['info'] = TRUE;
		}
	}

	public static function post_class( $classes ) {
		if ( self::$has_thumbnail ) {
			$classes[] = 'thumbnail-included';
		}

		if ( self::$thumbnail_first ) {
			$classes[] = 'thumbnail-first';
		}

		if ( self::$thumbnail_last ) {
			$classes[] = 'thumbnail-last';
		}

		return $classes;
	}
}
