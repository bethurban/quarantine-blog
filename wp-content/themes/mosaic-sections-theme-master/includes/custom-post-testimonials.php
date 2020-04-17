<?php

use MosaicSections\CustomPostType\MosaicCustomPostType;

class MosaicTestimonials extends MosaicCustomPostType {

	const POST_TYPE = 'testimonial';

	public function __construct() {
		parent::__construct( self::POST_TYPE );

		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
	}

	public function wp_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-easing', get_template_directory_uri() . '/js/jquery.easing.1.2.js' );
	}

	function register_post_type() {
		register_post_type( self::POST_TYPE,
			[
				'labels'          => [
					'name'               => __( 'Testimonials', 'mosaic' ),
					'singular_name'      => __( 'Testimonial', 'mosaic' ),
					'add_new'            => __( 'Add New', 'mosaic' ),
					'add_new_item'       => __( 'Add Testimonial', 'mosaic' ),
					'new_item'           => __( 'Add Testimonial', 'mosaic' ),
					'view_item'          => __( 'View Testimonial', 'mosaic' ),
					'search_items'       => __( 'Search Testimonials', 'mosaic' ),
					'edit_item'          => __( 'Edit Testimonial', 'mosaic' ),
					'all_items'          => __( 'All Testimonials', 'mosaic' ),
					'not_found'          => __( 'No Testimonials found', 'mosaic' ),
					'not_found_in_trash' => __( 'No Testimonials found in Trash', 'mosaic' )
				],
				'taxonomies'      => [ 'testicat' ],
				'public'          => TRUE,
				'show_ui'         => TRUE,
				'capability_type' => 'post',
				'hierarchical'    => FALSE,
				'rewrite'         => [ 'slug' => self::POST_TYPE, 'with_front' => FALSE ],
				'query_var'       => TRUE,
				'supports'        => [ 'title', 'revisions', 'thumbnail', 'editor' ],
				'menu_position'   => 10,
				'menu_icon'       => 'dashicons-megaphone',
				'has_archive'     => TRUE
			]
		);
	}

	function register_taxonomy() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = [
			'name'              => _x( 'Categories', 'taxonomy general name', 'mosaic' ),
			'singular_name'     => _x( 'Category', 'taxonomy singular name', 'mosaic' ),
			'search_items'      => __( 'Search Categories', 'mosaic' ),
			'all_items'         => __( 'All Categories', 'mosaic' ),
			'parent_item'       => __( 'Parent Category', 'mosaic' ),
			'parent_item_colon' => __( 'Parent Category:', 'mosaic' ),
			'edit_item'         => __( 'Edit Category', 'mosaic' ),
			'update_item'       => __( 'Update Category', 'mosaic' ),
			'add_new_item'      => __( 'Add New Category', 'mosaic' ),
			'new_item_name'     => __( 'New Category Name', 'mosaic' ),
			'menu_name'         => __( 'Testimonial Categories', 'mosaic' )
		];

		register_taxonomy( 'testicat', self::POST_TYPE, [
			'hierarchical' => TRUE,
			'labels'       => $labels,
			'query_var'    => TRUE,
			'rewrite'      => [ 'slug' => 'testicat' ]
		] );
	}

	public function admin_init() {
		add_meta_box( 'mosaic_testimonials', __( 'Testimonial Info', 'mosaic' ), [
			$this,
			'meta_box'
		], 'testimonial', 'normal', 'core' );
	}

	public function meta_box() {
		global $post;

		// Load the option values from the db
		$options = get_post_meta( $post->ID, '_acg_testimonials_meta', TRUE );

		if ( ! $options ) {
			$options = [
				'company' => '',
				'name'    => '',
				'title'   => '',
				'address' => '',
				'website' => ''
			];
		}

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'testimonials_nonce' );
		echo '<p><label for="acg_testimonial[company]">Company Name:</label>';
		echo '<input class="widefat" type="text" name="acg_testimonial[company]" value="' . $options['company'] . '" /></p>';
		echo '<p><label for="acg_testimonial[name]">Name:</label>';
		echo '<input class="widefat" type="text" name="acg_testimonial[name]" value="' . $options['name'] . '" /></p>';
		echo '<p><label for="acg_testimonial[title]">Title</label>';
		echo '<input class="widefat" type="text" name="acg_testimonial[title]" value="' . $options['title'] . '" /></p>';
		echo '<p>Address:<br>';
		echo '<textarea style="width: 100%; height: 50px;" name="acg_testimonial[address]">' . $options['address'] . '</textarea></p>';
		echo '<p><label for="acg_testimonial[website]">Website:</label>';
		echo '<input class="widefat" type="text" name="acg_testimonial[website]" value="' . $options['website'] . '" /></p>';
	}

	public function save_post( $post_id ) {
		global $wpdb;
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST['testimonials_nonce'] ) || ! wp_verify_nonce( $_POST['testimonials_nonce'], plugin_basename( __FILE__ ) ) ) {
			return $post_id;
		}

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
		// to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check permissions
		if ( 'testimonial' != $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		//make sure we have the published post ID and not a revision
		if ( $parent_id = wp_is_post_revision( $post_id ) ) {
			$post_id = $parent_id;
		}

		$var = $_POST["acg_testimonial"];
		update_post_meta( $post_id, '_acg_testimonials_meta', $var );
	}
}

$mosaicTestimonials = new MosaicTestimonials();

function mosaicTestimonials() {
	global $mosaicTestimonials;

	return $mosaicTestimonials;
}

class acg_testimonials_widget extends WP_Widget {
	function __construct() {
		parent::__construct( 'acg_testimonials_widget', ' - Testimonials', [ 'description' => 'Display testimonials that rotate automatically' ] );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$param              = [];
		$param['post_type'] = 'testimonial';

		$title                   = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$number                  = $instance["number"] * 1;
		$number                  = ( $number ) ? $number : 4;
		$param['posts_per_page'] = $number;
		if ( (int) $instance["categoryid"] ) {
			$param['tax_query'] = [
				[
					'taxonomy' => 'testicat',
					'field'    => 'id',
					'terms'    => (int) $instance["categoryid"]
				]
			];
		}
		$param['orderby'] = $instance['orderby'];
		$param['order']   = $instance['order'];

		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		add_filter( 'excerpt_length', 'recent_posts_excerpt_length', 999 );

		remove_all_filters( 'posts_orderby' );
		$recentposts = new WP_Query( $param );

		$showfields = [ 'company', 'name', 'title', 'address', 'website' ];

		$delay = (int) $instance['delay'];
		if ( ! $delay ) {
			$delay = 5000;
		}
		$animate = $instance['animate'];
		$class   = $animate;
		if ( $class == 'both' ) {
			$class = 'animate animate-controls';
		}

		$class .= ( $instance['controlsmove'] ) ? ' controls-move' : '';
		$class = ( $instance['animate'] ) ? ' class="action-animate animate-' . $class . '"' : '';


		$recentposts->query( $param );

		if ( $recentposts->have_posts() ) :

			echo '<ul' . $class . ' data-delay="' . $delay . '">';
			while ( $recentposts->have_posts() ) : $recentposts->the_post();

				$link = '<a class="read-more" href="' . get_permalink() . '">';
				$meta = get_post_meta( get_the_ID(), '_acg_testimonials_meta', TRUE ); ?>
                <li><?php
					if ( $instance['imagesize'] && has_post_thumbnail( get_the_ID() ) ) {
						$image = get_the_post_thumbnail( get_the_ID(), $instance['imagesize'] );
						echo $image;
					}

					if ( $instance["showttitle"] ) {
						echo '<h3>';
						echo ( $instance["titlelink"] ) ? $link . get_the_title() . '</a>' : get_the_title();
						echo '</h3>';
					}
					$link    = ( $instance["readmore"] ) ? '<a class="read-more" href="' . get_permalink() . '">' . $instance["readmore"] . '</a>' : '';
					$viewall = '';
					if ( $instance["viewall"] ) {
						$viewallurl = ( $instance['viewallurl'] ) ? get_permalink( $instance['viewallurl'] ) : get_post_type_archive_link( 'testimonial' );
						$viewall    = '<a class="view-all" href="' . $viewallurl . '">' . $instance["viewall"] . '</a>';
					}

					if ( $instance['showtext'] ) {
						echo '<div class="content">';
						the_content();
						echo '</div>';
					}

					$meta_title = $company = $title = $name = '';
					$title_show = $instance['showtitle'];

					// Formats 1, 3, 4, 8 - company is own div
					if ( in_array( $title_show, [ 1, 3, 4, 8 ] ) && ! empty( $meta['company'] ) ) {
						$company = '<div class="meta company">' . $meta['company'] . '</div>';
						// Formats 5, 6, 7 - company inline
					} else if ( in_array( $title_show, [ 5, 6, 7 ] ) && ! empty( $meta['company'] ) ) {
						$company = '<span class="company">' . $meta['company'] . '</span>';
					}

					// Formats 2, 3, 4 - name in own div
					if ( in_array( $title_show, [ 2, 3, 4 ] ) && ! empty( $meta['name'] ) ) {
						$name = '<div class="meta name">' . $meta['name'] . '</div>';
						// Formats 5 - 9 - name inline
					} else if ( $title_show > 4 && ! empty( $meta['name'] ) ) {
						$name = '<span class="name">' . $meta['name'] . '</span>';
					}

					// Formats 4, 6 - title in own div
					if ( in_array( $title_show, [ 4, 6 ] ) && ! empty( $meta['title'] ) ) {
						$title = '<div class="meta title">' . $meta['title'] . '</div>';
						// Formats 5 - 9 - name inline
					} else if ( $title_show > 6 && ! empty( $meta['title'] ) ) {
						$title = '<span class="title">' . $meta['title'] . '</span>';
					}

					// Glue it up...
					$string = '';
					if ( $title_show ) {
						$string = '<div class="meta name_company_title">';
						if ( $title_show == 3 ) {
							$temp    = $name;
							$name    = $company;
							$company = $temp;
						} else if ( in_array( $title_show, [ 5, 6 ] ) && $name && $company ) {
							$name    .= ', ' . $company;
							$company = '';
						} else if ( in_array( $title_show, [ 7, 8, 9 ] ) && $name && $title ) {
							$name  .= ', ' . $title;
							$title = '';
						}
						if ( $title_show == 7 && $company ) {
							$name    .= ', ' . $company;
							$company = '';
						}
						$string .= $name . $title . $company;

						$string .= '</div>';
					}

					$meta['title'] = $string;

					foreach ( $showfields AS $field ) {
						if ( $instance[ 'show' . $field ] && ! empty( $meta[ $field ] ) ) {
							echo '<div class="meta ' . $field . '">' . $meta[ $field ] . '</div>' . PHP_EOL;
						}
					}
					echo $link;
					echo $viewall; ?>
                </li>

			<?php endwhile;
			echo '</ul>';

			if ( $instance['animate'] == 'controls' || $instance['animate'] == 'both' ) {
				echo '<ul class="testi_controls">';
				echo '<li class="control prev"><a class="prev" href="javascript:void(0);"><i class="fa fa-angle-left"></i>Prev</a></li>';
				if ( $instance['animate'] == 'both' ) {
					echo '<li class="pause" style="display:none;"><a class="pause" href="javascript:void(0);">Pause</a></li>';
				}
				echo '<li class="control next"><a class="next" href="javascript:void(0);"><i class="fa fa-angle-right"></i>Next</a></li>';
				echo '</ul>';
			}

		endif;
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance                  = $old_instance;
		$instance["showttitle"]    = ( isset( $new_instance["showttitle"] ) ) ? 1 : 0;
		$instance["showtext"]      = ( isset( $new_instance["showtext"] ) ) ? 1 : 0;
		$instance["showcompany"]   = ( isset( $new_instance["showcompany"] ) ) ? 1 : 0;
		$instance["showtitle"]     = $new_instance["showtitle"];
		$instance["showaddress"]   = ( isset( $new_instance["showaddress"] ) ) ? 1 : 0;
		$instance["showwebsite"]   = ( isset( $new_instance["showwebsite"] ) ) ? 1 : 0;
		$instance["titlelink"]     = ( isset( $new_instance["titlelink"] ) ) ? 1 : 0;
		$instance['title']         = strip_tags( $new_instance['title'] );
		$instance['number']        = strip_tags( $new_instance['number'] );
		$instance['readmore']      = $new_instance['readmore'];
		$instance['viewall']       = $new_instance['viewall'];
		$instance['viewallurl']    = $new_instance['viewallurl'];
		$instance['viewallpageid'] = $new_instance['viewallpageid'];
		$instance["categoryid"]    = $new_instance["categoryid"];
		$instance["orderby"]       = $new_instance["orderby"];
		$instance["order"]         = $new_instance["order"];
		$instance["animate"]       = $new_instance["animate"];
		$instance["controlsmove"]  = ( isset( $new_instance["controlsmove"] ) ) ? 1 : 0;
		$instance["imagesize"]     = $new_instance["imagesize"];
		$instance['delay']         = (int) $new_instance['delay'];

		return $instance;
	}

	function form( $instance ) {
		$defaults = [
			'title'        => '',
			'number'       => '4',
			'showttitle'   => 1,
			'showtext'     => 1,
			'showcompany'  => 1,
			'showtitle'    => 1,
			'showaddress'  => 1,
			'showwebsite'  => 1,
			'imagesize'    => '',
			'titlelink'    => 1,
			'readmore'     => 'Read More &raquo;',
			'viewall'      => 'View All',
			'viewallurl'   => '',
			'categoryid'   => '',
			'orderby'      => '',
			'order'        => 'DESC',
			'animate'      => TRUE,
			'controlsmove' => 0,
			'delay'        => 5000
		];

		$default = '';
		$title   = '';

		$instance   = wp_parse_args( (array) $instance, $default );
		$title      = strip_tags( $instance['title'] );
		$number     = strip_tags( $instance['number'] );
		$readmore   = strip_tags( $instance['readmore'] );
		$viewall    = strip_tags( $instance['viewall'] );
		$categoryid = $instance['categoryid'];
		$viewallurl = $instance['viewallurl'];

		$delay = $instance['delay'];
		?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/></p>
        <p><label for="<?php echo $this->get_field_id( 'categoryid' ); ?>"><?php _e( 'Category:' ); ?></label>
			<?php wp_dropdown_categories( [
					'taxonomy'        => 'testicat',
					'name'            => $this->get_field_name( 'categoryid' ),
					'selected'        => $categoryid,
					'hierarchical'    => 1,
					'show_option_all' => '- All Categories -'
				]
			);

			$opts = [
				''  => 'No',
				'1' => 'Company Only',
				'2' => 'Name Only',
				'3' => 'Company &amp; Name (on Separate lines)',
				'4' => 'All Separate Lines',
				'5' => 'Name, Company',
				'6' => 'Name, Company (with Title on Separate line)',
				'7' => 'Name, Title, Company',
				'8' => 'Name, Title, (with Company on Separate lines)',
				'9' => 'Name, Title'
			];

			$select = '<select style="max-width:300px;" name="' . $this->get_field_name( 'showtitle' ) . '">';
			foreach ( $opts AS $v => $l ) {
				$select .= '<option value="' . $v . '"';
				$select .= ( $v == $instance['showtitle'] ) ? ' selected' : '';
				$select .= '>' . $l . '</option>';
			}
			$select .= '</select>';

			$opts = [
				'ID'         => 'ID',
				'title'      => 'Title',
				'date'       => 'Date',
				'modified'   => 'Modified Date',
				'rand'       => 'Random',
				'menu_order' => 'Page Order',
			];

			$obyselect = '<select name="' . $this->get_field_name( 'orderby' ) . '">';
			foreach ( $opts AS $v => $l ) {
				$obyselect .= '<option value="' . $v . '"';
				$obyselect .= ( $v == $instance['orderby'] ) ? ' selected' : '';
				$obyselect .= '>' . $l . '</option>';
			}
			$obyselect .= '</select>';

			$opts = [
				'DESC' => 'Descending (High to Low)',
				'ASC'  => 'Ascending (Low to High)',
			];

			$oselect = '<select name="' . $this->get_field_name( 'order' ) . '">';
			foreach ( $opts AS $v => $l ) {
				$oselect .= '<option value="' . $v . '"';
				$oselect .= ( $v == $instance['order'] ) ? ' selected' : '';
				$oselect .= '>' . $l . '</option>';
			}
			$oselect .= '</select>';

			$opts = [
				''         => 'No Animation',
				'animate'  => 'Automatic (without Controls)',
				'both'     => 'Animate (with Controls)',
				'controls' => 'With Controls Only'
			];

			$aselect = '<select name="' . $this->get_field_name( 'animate' ) . '">';
			foreach ( $opts AS $v => $l ) {
				$aselect .= '<option value="' . $v . '"';
				$aselect .= ( $v == $instance['animate'] ) ? ' selected' : '';
				$aselect .= '>' . $l . '</option>';
			}
			$aselect .= '</select>';

			$opts = [
				''          => 'Do not show',
				'thumbnail' => 'Thumbnail',
				'medium'    => 'Medium',
				'large'     => 'Large',
				'full'      => 'Full'
			];

			$sizeselect = '<select name="' . $this->get_field_name( 'imagesize' ) . '">';
			foreach ( $opts AS $v => $l ) {
				$sizeselect .= '<option value="' . $v . '"';
				$sizeselect .= ( $v == $instance['imagesize'] ) ? ' selected' : '';
				$sizeselect .= '>' . $l . '</option>';
			}
			$sizeselect .= '</select>';

			$viewallurllist = wp_dropdown_pages( 'echo=0&name=' . $this->get_field_name( 'viewallurl' ) . '&selected=' . $viewallurl . '&show_option_none= - Testimonials Listing - ' );


			?></p>
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of Testimonials to Include:' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'number' ); ?>"
                   name="<?php echo $this->get_field_name( 'number' ); ?>" type="text"
                   value="<?php echo esc_attr( $number ); ?>" size="3"/></p>
        <p><label for="<?php echo $this->get_field_name( "imagesize" ); ?>">Show
                Image: <?php echo $sizeselect; ?></label></p>
		<?php $checked = ( $instance["showttitle"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "showttitle" ); ?>">Show Testimonial Title: <input
                        type="checkbox" id="<?php echo $this->get_field_id( "showttitle" ); ?>"
                        name="<?php echo $this->get_field_name( "showttitle" ); ?>"
                        value="1"<?php echo $checked; ?> /></label>
        </p>
		<?php $checked = ( $instance["showtext"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "showtext" ); ?>">Show Testimonial Content: <input
                        type="checkbox" id="<?php echo $this->get_field_id( "showtext" ); ?>"
                        name="<?php echo $this->get_field_name( "showtext" ); ?>"
                        value="1"<?php echo $checked; ?> /></label></p>
        <p><label for="<?php echo $this->get_field_name( "showtitle" ); ?>">Show Company / Name /
                Title: <?php echo $select; ?></label></p>
		<?php $checked = ( $instance["showaddress"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "showaddress" ); ?>">Show Address: <input type="checkbox"
                                                                                                   id="<?php echo $this->get_field_id( "showaddress" ); ?>"
                                                                                                   name="<?php echo $this->get_field_name( "showaddress" ); ?>"
                                                                                                   value="1"<?php echo $checked; ?> /></label>
        </p>

		<?php $checked = ( $instance["showwebsite"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "showwebsite" ); ?>">Show Website: <input type="checkbox"
                                                                                                   id="<?php echo $this->get_field_id( "showwebsite" ); ?>"
                                                                                                   name="<?php echo $this->get_field_name( "showwebsite" ); ?>"
                                                                                                   value="1"<?php echo $checked; ?> /></label>
        </p>
		<?php $checked = ( $instance["titlelink"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "titlelink" ); ?>">Testimonial Title is Link: <input
                        type="checkbox" id="<?php echo $this->get_field_id( "titlelink" ); ?>"
                        name="<?php echo $this->get_field_name( "titlelink" ); ?>"
                        value="1"<?php echo $checked; ?> /></label></p>
        <p><label for="<?php echo $this->get_field_id( 'readmore' ); ?>"><?php _e( 'Read More Link:' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'readmore' ); ?>"
                   name="<?php echo $this->get_field_name( 'readmore' ); ?>" type="text"
                   value="<?php echo esc_attr( $readmore ); ?>"/></p>
        <p><label for="<?php echo $this->get_field_id( 'viewall' ); ?>"><?php _e( 'View All Link:' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'viewall' ); ?>"
                   name="<?php echo $this->get_field_name( 'viewall' ); ?>" type="text"
                   value="<?php echo esc_attr( $viewall ); ?>"/></p>
        <p>
            <label for="<?php echo $this->get_field_id( 'viewallurl' ); ?>"><?php _e( 'View All Links To:' ); ?><?php echo $viewallurllist; ?></label>
        </p>
        <p><label for="<?php echo $this->get_field_name( "orderby" ); ?>">Order By: <?php echo $obyselect; ?></label>
        </p>
        <p><label for="<?php echo $this->get_field_name( "order" ); ?>">Order: <?php echo $oselect; ?></label></p>
        <p><label for="<?php echo $this->get_field_name( "animate" ); ?>">Animate: <?php echo $aselect; ?></label></p>
		<?php $checked = ( $instance["controlsmove"] ) ? ' checked="checked"' : ''; ?>
        <p><label for="<?php echo $this->get_field_name( "controlsmove" ); ?>">Controls Flow Up/Down with Testimonial:
                <input type="checkbox" id="<?php echo $this->get_field_id( "controlsmove" ); ?>"
                       name="<?php echo $this->get_field_name( "controlsmove" ); ?>"
                       value="1"<?php echo $checked; ?> /></label>
        </p>
        <p><label for="<?php echo $this->get_field_name( "delay" ); ?>">Animation Delay: <input size="5"
                                                                                                id="<?php echo $this->get_field_id( 'delay' ); ?>"
                                                                                                name="<?php echo $this->get_field_name( 'delay' ); ?>"
                                                                                                value="<?php echo $delay; ?>"/>
                <small>milliseconds</small>
            </label></p>
		<?php
	}
}

// Add widget
register_widget( 'acg_testimonials_widget' );