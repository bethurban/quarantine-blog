<?php

use MosaicSections\CustomPostType\MosaicCustomPostType;

class MosaicThemeEvents extends MosaicCustomPostType {
	const POST_TYPE = 'event';
	const POST_SLUG = 'event';

	public function __construct() {
		parent::__construct( self::POST_TYPE );

		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
		add_action( 'wp_ajax_mosaic-upcoming-events', [ $this, 'ajax_upcoming_events' ] );
		add_action( 'wp_ajax_nopriv_mosaic-upcoming-events', [ $this, 'ajax_upcoming_events' ] );

		add_shortcode( 'mosaic_events_feed', [ $this, 'render_shortcode' ] );

		$this->register_sections( [
			[
				'name'           => 'event-grid',
				'button_text'    => 'Event Grid',
				'button_icon'    => 'fa-calendar',
				'admin_section'  => [ $this, 'admin_section' ],
				'render_section' => [ $this, 'render_section' ]
			],
		] );
	}

	/**
	 * AJAX action for rendering upcoming events.
	 * Called when a visitor clicks on the event location tags.
	 */
	public function ajax_upcoming_events() {
		$terms = [
			'eventlocation' => _::get( $_POST, 'eventlocation' ),
			'eventcategory' => _::get( $_POST, 'eventcategory' )
		];

		$number_of_events = _::get( $_POST, 'number_of_events' );
		$number_of_events = ( $number_of_events ) ? $number_of_events : 3;

		echo $this->render_events( $number_of_events, $terms );
		die();
	}

	function admin_enqueue_scripts() {
		if ( ! $this->is_edit_custom_post_screen() ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-easing', get_template_directory_uri() . '/js/jquery.easing.1.2.js' );
		wp_enqueue_script( 'jquery-timepicker', get_template_directory_uri() . '/js/jquery.timepicker.js' );
		wp_enqueue_script( 'bootstrap-datepicker', get_template_directory_uri() . '/js/bootstrap-datepicker.js' );
		wp_enqueue_script( 'datepair', get_template_directory_uri() . '/js/Datepair.js' );
		wp_enqueue_script( 'jquery-datepair', get_template_directory_uri() . '/js/jquery.datepair.js' );
		wp_enqueue_style( 'bootsrap-datepicker-css', get_template_directory_uri() . '/css/bootstrap-datepicker.css' );
		wp_enqueue_style( 'jquery-timepicker-css', get_template_directory_uri() . '/css/jquery.timepicker.css' );
	}

	/**
	 * WP admin_init action.
	 *
	 * Adds the metabox to the "Event" custom post type.
	 */
	function admin_init() {
		add_meta_box( 'mosaic_events', __( 'Event Info', 'mosaic' ), [
			$this,
			'meta_box'
		], self::POST_TYPE, 'normal', 'core' );
	}

	/**
	 * Called by init action.
	 * Registers the Event custom post type.
	 */
	function register_post_type() {
		register_post_type( $this->post_type_name,
			[
				'labels'          => [
					'name'               => __( 'Events', 'mosaic' ),
					'singular_name'      => __( 'Event', 'mosaic' ),
					'add_new'            => __( 'Add New', 'mosaic' ),
					'add_new_item'       => __( 'Add Event', 'mosaic' ),
					'new_item'           => __( 'Add Event', 'mosaic' ),
					'view_item'          => __( 'View Event', 'mosaic' ),
					'search_items'       => __( 'Search Events', 'mosaic' ),
					'edit_item'          => __( 'Edit Event', 'mosaic' ),
					'all_items'          => __( 'All Events', 'mosaic' ),
					'not_found'          => __( 'No Events found', 'mosaic' ),
					'not_found_in_trash' => __( 'No Events found in Trash', 'mosaic' )
				],
				'taxonomies'      => [ 'eventcategory', 'eventlocation' ],
				'public'          => TRUE,
				'show_ui'         => TRUE,
				'capability_type' => 'post',
				'hierarchical'    => FALSE,
				'rewrite'         => [ 'slug' => self::POST_SLUG, 'with_front' => FALSE ],
				'query_var'       => TRUE,
				'supports'        => [ 'title', 'revisions', 'thumbnail', 'editor' ],
				'menu_position'   => 10,
				'menu_icon'       => 'dashicons-calendar-alt',
				'has_archive'     => TRUE
			]
		);
	}

	/**
	 * Called by the init action.
	 * Registers the taxonomies for the Event custom post type.
	 */
	function register_taxonomy() {
		$category_label        = $this->get_event_category_label();
		$category_label_plural = $this->get_event_category_label( TRUE );
		$location_label        = $this->get_event_location_label();
		$location_label_plural = $this->get_event_location_label( TRUE );

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = [
			'name'              => $category_label_plural,
			'singular_name'     => $category_label,
			'search_items'      => "Search {$category_label_plural}",
			'all_items'         => __( "All {$category_label_plural}", 'mosaic' ),
			'parent_item'       => __( "Parent {$category_label}", 'mosaic' ),
			'parent_item_colon' => __( "Parent {$category_label}:", 'mosaic' ),
			'edit_item'         => __( "Edit {$category_label}", 'mosaic' ),
			'update_item'       => __( "Update {$category_label}", 'mosaic' ),
			'add_new_item'      => __( "Add New {$category_label}", 'mosaic' ),
			'new_item_name'     => __( "New {$category_label} Name", 'mosaic' ),
			'menu_name'         => __( "Event {$category_label_plural}", 'mosaic' )
		];

		register_taxonomy( 'eventcategory', self::POST_TYPE, [
			'hierarchical' => TRUE,
			'labels'       => $labels,
			'query_var'    => TRUE,
			'rewrite'      => [ 'slug' => 'eventcat' ]
		] );

		$labels = [
			'name'              => $location_label_plural,
			'singular_name'     => $location_label,
			'search_items'      => "Search {$location_label_plural}",
			'all_items'         => __( "All {$location_label_plural}", 'mosaic' ),
			'parent_item'       => __( "Parent {$location_label}", 'mosaic' ),
			'parent_item_colon' => __( "Parent {$location_label}:", 'mosaic' ),
			'edit_item'         => __( "Edit {$location_label}", 'mosaic' ),
			'update_item'       => __( "Update {$location_label}", 'mosaic' ),
			'add_new_item'      => __( "Add New {$location_label}", 'mosaic' ),
			'new_item_name'     => __( "New {$location_label} Name", 'mosaic' ),
			'menu_name'         => __( "Event {$location_label_plural}", 'mosaic' )
		];

		register_taxonomy( 'eventlocation', self::POST_TYPE, [
			'hierarchical' => TRUE,
			'labels'       => $labels,
			'query_var'    => TRUE,
			'rewrite'      => [ 'slug' => 'location' ]
		] );
	}

	/**
	 * Renders the "Events" shortcode.
	 *
	 * @param array $shortcode_args
	 *
	 * @return string
	 */
	function render_shortcode( $shortcode_args ) {
		$default = [
			'count' => 3,
			'class' => ''
		];

		$shortcode_args = shortcode_atts( $default, $shortcode_args );

		$number_of_posts = $shortcode_args['count'];
		$custom_classes  = $shortcode_args['class'];
		$events          = $this->render_events( $number_of_posts );

		$content = '';
		$content .= '<div class="' . $custom_classes . '">';
		$content .= $events;
		$content .= '</div>';

		return $content;
	}

	/**
	 * Render the "Section" interface within the page editor if the page
	 * is set to be a "Section" template.
	 *
	 * @param array                       $data
	 * @param MosaicHomeTemplateInterface $admin
	 */
	function admin_section( $data, $admin ) {
		$category_title    = mosaicEvents()->get_event_category_label();
		$location_title    = mosaicEvents()->get_event_location_label();
		$section_id        = _::get( $data, 'section_id' );
		$number_of_events  = _::get( $data, 'number_of_events' );
		$selected_location = _::get( $data, 'eventlocation' );
		$selected_category = _::get( $data, 'eventcategory' );
		$title             = _::get( $data, 'event_grid_title' );
		$sub_headline      = _::get( $data, 'event_grid_subheadline' );

		echo '<p><label>Event Grid Title</label></p>';
		echo '<p><input type="text" class="widefat is-title" placeholder="Event Grid Title" name="section[' . $section_id . '][event_grid_title]" value="' . esc_attr( $title ) . '"/></p>';
		echo '<p><label>Event Grid Subheadline</label></p>';
		echo '<p><input type="text" class="widefat" placeholder="Event Grid SubHeadline" name="section[' . $section_id . '][event_grid_subheadline]" value="' . esc_attr( $sub_headline ) . '"/></p>';
		echo '<p><label>Number of Events </label>';

		$name = 'section[' . $section_id . '][number_of_events]';

		echo $this->create_dropdown( $name, [
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6'
		], $number_of_events, 'Select...' );

		echo '</p>';

		echo $this->taxonomy_dropdown( 'section[' . $section_id . '][eventlocation]', $location_title, 'eventlocation', $selected_location );
		echo $this->taxonomy_dropdown( 'section[' . $section_id . '][eventcategory]', $category_title, 'eventcategory', $selected_category );

		$admin->generate_button_input( $data, 'button_text' );
	}

	/**
	 * Utility for creating dropdowns from an array of options.
	 * TODO: This should be in a more abstracted / available location? Do we have a "utility class" or similar?
	 *
	 * @param string $name
	 * @param array  $array
	 * @param mixed  $selected_value
	 * @param string $select_text
	 *
	 * @return string
	 */
	private function create_dropdown( $name, $array, $selected_value, $select_text = 'Please Select...' ) {
		$select = '<select name="' . $name . '">';

		if ( $select_text ) {
			$select .= '<option value="">' . $select_text . '</option>';
		}

		foreach ( $array AS $value => $label ) {
			$select .= '<option value="' . $value . '"';
			$select .= ( $selected_value == $value ) ? ' selected' : '';
			$select .= '>' . $label . '</option>';
		}

		$select .= '</select>';

		return $select;
	}

	/**
	 * Render a taxonomy dropdown
	 *
	 * @param {string} $name
	 * @param {string} $label
	 * @param {string} $taxonomy
	 * @param {string} $selected
	 *
	 * @return string
	 */
	public function taxonomy_dropdown( $name, $label, $taxonomy, $selected ) {
		$content = '<p><label>' . $label . ' </label>';

		$terms = get_terms( [
			'taxonomy' => $taxonomy
		] );

		$array = [];

		foreach ( $terms as $term ) {
			$array[ $term->term_id ] = $term->name;
		}

		$content .= $this->create_dropdown( $name, $array, $selected, 'Display All...' );
		$content .= '</p>';

		return $content;
	}

	/**
	 * Utility function to render X number of events, based on the passed-in terms array
	 *
	 * @param int   $number_of_events
	 * @param array $terms
	 *
	 * @return string
	 */
	function render_events( $number_of_events = 3, $terms = [] ) {
		$upcoming_events = $this->load_events( $number_of_events, $terms );
		$events          = '';
		$event_counter   = 0;

		foreach ( $upcoming_events->posts as $event ) {
			if ( $event_counter ++ < $number_of_events ) {
				$events .= $this->generate_event( $event );
			}
		}

		return $events;
	}

	/**
	 * Renders the front-end view of the "Events" section.
	 *
	 * @param array                    $data
	 * @param MosaicHomeTemplateRender $render
	 */
	function render_section( $data, $render ) {
		$headline         = _::get( $data, 'headline' );
		$sub_headline     = _::get( $data, 'subheadline' );
		$number_of_events = _::get( $data, 'number_of_events' );

		$terms = [
			'eventlocation' => _::get( $data, 'eventlocation' ),
			'eventcategory' => _::get( $data, 'eventcategory' )
		];

		$locations = get_terms( [ 'taxonomy' => 'eventlocation' ] );

		$render->open_sub_content_div( $data, 'event-grid-wrapper sub-contents' );
		echo '<div class="event-grid-title center">';
		echo '<h2 class="section-headline">' . $headline . '</h2>';
		echo '</div>';

		if ( ! $terms['eventlocation'] ) {
			if ( ! empty( $locations ) ) {

				$existing_locations = [];
				$existing           = $this->load_events( - 1, $terms );

				foreach ( $existing->posts AS $post ) {
					$locs = wp_get_post_terms( $post->ID, 'eventlocation' );

					foreach ( $locs AS $loc ) {
						$existing_locations[ $loc->term_id ] = $loc->term_id;
					}
				}

				echo '<div class="locations">';
				echo '<div class="location">';
				echo '<a class="upcoming-events active" href="javascript:void(0);" data-location="all" data-category="' . $terms['eventcategory'] . '" data-number-of-events="' . $number_of_events . '">';
				echo 'View All';
				echo '</a>';
				echo '</div>';

				foreach ( $locations as $event_location ) {
					echo '<div class="location">';

					if ( array_key_exists( $event_location->term_id, $existing_locations ) ) {
						echo '<a class="upcoming-events" href="javascript:void(0);" data-location="' . $event_location->term_id . '" data-category="' . $terms['eventcategory'] . '" data-number-of-events="' . $number_of_events . '">';
						$tag = 'a';
					} else {
						echo '<span class="upcoming-events">';
						$tag = 'span';
					}

					echo $event_location->name;
					echo "</{$tag}>";
					echo '</div>';
				}

				echo '</div>';
			}
		}

		$number_of_events = ( $number_of_events ) ? $number_of_events : 3;

		echo '<div class="events" id="upcoming-events">';
		echo $this->render_events( $number_of_events, $terms );
		echo '</div>';

		echo '<p class="subheadline center">' . $sub_headline . '</p>';

		echo '<div class="event-button">';
		// Change to match what function looks for
		$render->generate_button_area( $data );
		echo '</div>';

		$render->close_sub_content_div( $data );
	}

	public function generate_event( $event ) {
		$start_date = get_post_meta( $event->ID, '_mosaic_events_start_date', TRUE );

		if ( $start_date ) {
			$start_date = date( 'm/d/Y', $start_date );
		}

		$venue_name = get_post_meta( $event->ID, '_mosaic_events_venue_name', TRUE );
		$address    = get_post_meta( $event->ID, '_mosaic_events_address', TRUE );
		$city       = get_post_meta( $event->ID, '_mosaic_events_city', TRUE );
		$state      = get_post_meta( $event->ID, '_mosaic_events_state', TRUE );

		$event_grid = '';
		$event_grid .= '<div class="event-wrapper">';
		$event_grid .= '<a href="' . get_permalink( $event->ID ) . '">';
		$event_grid .= '<div class="event">';
		$event_grid .= '<div class="event-title">';
		$event_grid .= '<h2>' . $event->post_title . '</h2>';
		$event_grid .= '</div>';
		$event_grid .= '<div class="event-date">';
		$event_grid .= '<p>' . $start_date . '</p>';
		$event_grid .= '</div>';
		$event_grid .= '<div class="event-location">';

		if ( $venue_name ) {
			$event_grid .= '<div class="venue">';
			$event_grid .= '<p>' . $venue_name . '</p>';
			$event_grid .= '</div>';
		}

		if ( $address ) {
			$event_grid .= '<p>' . $address;

			if ( $city ) {
				$event_grid .= ', ' . $city;
			}

			$event_grid .= '</p>';
		}

		if ( $state ) {
			$event_grid .= '<p>' . $state . '</p>';
		}

		$event_grid .= '</div>';
		$event_grid .= '<div class="event-link">';
		$event_grid .= 'Learn More';
		$event_grid .= '</div>';
		$event_grid .= '</div>';
		$event_grid .= '</a>';
		$event_grid .= '</div>';

		return $event_grid;
	}

	function load_events( $count, $terms = [] ) {
		$args = [
			'post_type'      => self::POST_TYPE,
			'meta_query'     => [
				[
					'key'     => '_mosaic_events_start_date',
					'value'   => current_time( 'timestamp' ),
					'compare' => '>'
				]
			],
			'posts_per_page' => $count
		];

		if ( ! empty( $terms ) ) {
			$args['tax_query'] = [];

			foreach ( $terms AS $taxonomy => $term_id ) {
				if ( (int) $term_id ) {
					$args['tax_query'][] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_id
					];
				}
			}
		}

		return new WP_Query( $args );
	}

	function get_event_category_label( $plural = FALSE ) {
		return $this->get_event_tax_label( 'category', $plural );
	}

	function get_event_location_label( $plural = FALSE ) {
		return $this->get_event_tax_label( 'location', $plural );
	}

	function get_event_tax_label( $type, $plural = FALSE ) {
		$plural   = ( $plural ) ? '_plural' : '';
		$defaults = [
			'category'        => 'Category',
			'category_plural' => 'Categories',
			'location'        => 'Location',
			'location_plural' => 'Locations'
		];

		$default = $defaults[ $type . $plural ];

		return MosaicTheme::get_option( "event_{$type}_label{$plural}", $default );
	}

	/**
	 * Display the metabox when editing an event.
	 */
	function meta_box() {
		global $post;
		// Load the option values from the db
		$start_date = get_post_meta( $post->ID, '_mosaic_events_start_date', TRUE );
		$end_date   = get_post_meta( $post->ID, '_mosaic_events_end_date', TRUE );
		$start_time = get_post_meta( $post->ID, '_mosaic_events_start_time', TRUE );
		$end_time   = get_post_meta( $post->ID, '_mosaic_events_end_time', TRUE );
		$venue_name = get_post_meta( $post->ID, '_mosaic_events_venue_name', TRUE );
		$address    = get_post_meta( $post->ID, '_mosaic_events_address', TRUE );
		$city       = get_post_meta( $post->ID, '_mosaic_events_city', TRUE );
		$state      = get_post_meta( $post->ID, '_mosaic_events_state', TRUE );
		$zip_code   = get_post_meta( $post->ID, '_mosaic_events_zip_code', TRUE );

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'events_nonce' );
		echo '<p><label>Event Date / Time: </label></p>';
		echo '<p id="mosaic-event-run-time">';

		if ( $start_date ) {
			$start_date = date( 'm/d/Y', $start_date );
		}
		if ( $end_date ) {
			$end_date = date( 'm/d/Y', $end_date );
		}

		echo '<input type="text" size="10" class="date start" placeholder="Start Date" name="_mosaic_events_start_date" value="' . $start_date . '"/>';
		echo '<input type="text" size="10" class="time start" placeholder="Start Time" name="_mosaic_events_start_time" value="' . $start_time . '"/> to ';
		echo '<input type="text" size="10" class="date end" placeholder="End Date" name="_mosaic_events_end_date" value="' . $end_date . '"/>';
		echo '<input type="text" size="10" class="time end" placeholder="End Time" name="_mosaic_events_end_time" value="' . $end_time . '"/>';
		echo '</p>';
		echo '<p><label for="_mosaic_events_venue_name">Venue Name:</label></p>';
		echo '<p><input type="text" name="_mosaic_events_venue_name" value="' . $venue_name . '" /></p>';
		echo '<p><label for="_mosaic_events_address">Address:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_events_address" value="' . $address . '" /></p>';
		echo '<p><label for="_mosaic_events_city">City:</label></p>';
		echo '<p><input type="text" name="_mosaic_events_city" value="' . $city . '" /></p>';
		echo '<p><label for="_mosaic_eventS_state">State:</label></p>';
		echo '<p><input type="text" name="_mosaic_events_state" value="' . $state . '" /></p>';
		echo '<p><label for="_mosaic_events_zip_code">Zip Code:</label></p>';
		echo '<p><input type="text" name="_mosaic_events_zip_code" value="' . $zip_code . '" /></p>';
	}

	/**
	 * Output scripts to bind the date / time pickers in the footer.
	 */
	function admin_footer() {
		if ( ! $this->is_edit_custom_post_screen() ) {
			return;
		}

		wp_enqueue_script( 'eventDatePicker', get_stylesheet_directory_uri() . '/js/eventDatePicker.jquery.js' );
	}

	/**
	 * Save the Event metadata when the post is saved.
	 *
	 * @param int $post_id
	 */
	function save_post( $post_id ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST['events_nonce'] ) || ! wp_verify_nonce( $_POST['events_nonce'], plugin_basename( __FILE__ ) ) ) {
			return;
		}

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
		// to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( self::POST_TYPE != $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		//make sure we have the published post ID and not a revision
		if ( $parent_id = wp_is_post_revision( $post_id ) ) {
			$post_id = $parent_id;
		}

		$start_date = $_POST['_mosaic_events_start_date'];
		$end_date   = $_POST['_mosaic_events_end_date'];
		$start_time = $_POST['_mosaic_events_start_time'];
		$end_time   = $_POST['_mosaic_events_end_time'];
		$venue_name = $_POST['_mosaic_events_venue_name'];
		$address    = $_POST['_mosaic_events_address'];
		$city       = $_POST['_mosaic_events_city'];
		$state      = $_POST['_mosaic_events_state'];
		$zip_code   = $_POST['_mosaic_events_zip_code'];

		if ( $start_date ) {
			$start_date = strtotime( $start_date );
		}
		if ( $end_date ) {
			$end_date = strtotime( $end_date );
		}

		update_post_meta( $post_id, '_mosaic_events_start_date', $start_date );
		update_post_meta( $post_id, '_mosaic_events_end_date', $end_date );
		update_post_meta( $post_id, '_mosaic_events_start_time', $start_time );
		update_post_meta( $post_id, '_mosaic_events_end_time', $end_time );
		update_post_meta( $post_id, '_mosaic_events_venue_name', $venue_name );
		update_post_meta( $post_id, '_mosaic_events_address', $address );
		update_post_meta( $post_id, '_mosaic_events_city', $city );
		update_post_meta( $post_id, '_mosaic_events_state', $state );
		update_post_meta( $post_id, '_mosaic_events_zip_code', $zip_code );
	}

	/**
	 * In order to make the events and events archive listings show in the right order,
	 * and only show the future-dated events, this function watches if the query is for
	 * events, and if so, adds some arguments.
	 *
	 * SAMPLE: http://localhost/wp_dr/event/?events_filter_start_date=05/01/2017&events_filter_end_date=5/1/2017
	 *
	 * @param $query
	 */
	function pre_get_posts( $query ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$args = $query->query_vars;

		if ( ! $args ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			if ( empty( $args['post_type'] ) || self::POST_TYPE != $args['post_type'] ) {
				return;
			}
		}


		// Sometimes we have to set this.  Odd.
		if ( empty( $args['post_type'] ) ) {
			if ( is_tax( 'eventcategory' ) || is_tax( 'eventlocation' ) ) {
				$query->set( 'post_type', self::POST_TYPE );
				$args = $query->query_vars;
			}
		}

		if ( ! empty( $args['post_type'] ) && self::POST_TYPE == $args['post_type'] ) {
			$query->set( 'meta_key', '_mosaic_events_start_date' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );

			$meta_query = $query->get( 'meta_query' );

			if ( empty( $meta_query ) ) {
				$meta_query = [];
			}

			// Handle the "event date filter" settings. (start range)
			if ( ! empty( $_REQUEST['events_filter_start_date'] ) ) {
				$meta_query[] = [
					'key'     => '_mosaic_events_start_date',
					'value'   => strtotime( 'midnight', strtotime( $_REQUEST['events_filter_start_date'] ) ),
					'compare' => '>='
				];
			}

			// Handle the "event date filter" settings. (end range)
			if ( ! empty( $_REQUEST['events_filter_end_date'] ) ) {
				$meta_query[] = [
					'key'     => '_mosaic_events_start_date',
					'value'   => strtotime( 'tomorrow', strtotime( $_REQUEST['events_filter_end_date'] ) ),
					'compare' => '<'
				];
			}

			// Check the meta query to see if there are already any start / end date filters
			$existing = FALSE;
			if ( is_array( $meta_query ) ) {
				$existing = array_filter( $meta_query, function ( $q ) {
					return ( '_mosaic_events_start_date' == $q['key'] );
				} );
			}

			// IF and ONLY IF there's no existing meta query filters, THEN we set the default of "today or later"
			if ( ! $existing ) {
				$today = strtotime( 'midnight', current_time( 'timestamp' ) );

				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => '_mosaic_events_start_date',
						'value'   => $today,
						'compare' => '>='
					],
					[
						'key'     => '_mosaic_events_end_date',
						'value'   => $today,
						'compare' => '>='
					]
				];
			}

			$query->set( 'meta_query', $meta_query );

			$tax_query = [];

			if ( ! empty( $_REQUEST['events_filter_eventcategory'] ) ) {
				$terms       = $_REQUEST['events_filter_eventcategory'];
				$terms       = array_keys( $terms );
				$tax_query[] = [
					'taxonomy' => 'eventcategory',
					'field'    => 'id',
					'terms'    => $terms,
					'operator' => 'IN'
				];
			}

			if ( ! empty( $_REQUEST['events_filter_eventlocation'] ) ) {
				$terms       = $_REQUEST['events_filter_eventlocation'];
				$terms       = array_keys( $terms );
				$tax_query[] = [
					'taxonomy' => 'eventlocation',
					'field'    => 'id',
					'terms'    => $terms,
					'operator' => 'IN'
				];
			}

			if ( ! empty( $tax_query ) ) {
				$query->set( 'tax_query', $tax_query );
			}
		}
	}
}

$mosaicEvents = new MosaicThemeEvents();

function mosaicEvents() {
	global $mosaicEvents;

	if ( empty( $mosaicEvents ) ) {
		$mosaicEvents = new MosaicThemeEvents();
	}

	return $mosaicEvents;
}

function mosaicEventStartDate( $post_id, $format = 'M j' ) {
	return mosaicEventDate( 'start', $post_id, $format );
}

function mosaicEventEndDate( $post_id, $format = 'M j' ) {
	return mosaicEventDate( 'end', $post_id, $format );
}

function mosaicEventDate( $which, $post_id, $format ) {
	$key = ( FALSE !== stripos( $which, 'start' ) ) ? 'start' : 'end';

	$date = mosaicEventMeta( $post_id, "{$key}_date" );
	if ( ! $date ) {
		return '';
	}

	return date( $format, $date );
}

function mosaicEventAddress( $post_id ) {
	$address = mosaicEventMeta( $post_id, 'address' );
	$city    = mosaicEventMeta( $post_id, 'city' );
	$state   = mosaicEventMeta( $post_id, 'state' );

	if ( $address && $city ) {
		$address .= ', ';
	}

	$address .= $city;

	if ( $city && $state ) {
		$address .= ', ';
	}

	$address .= $state;

	return trim( $address );
}

function mosaicEventMeta( $post_id, $key ) {
	if ( FALSE === stripos( $key, '_mosaic_events_' ) ) {
		$key = "_mosaic_events_{$key}";
	}

	return trim( get_post_meta( $post_id, $key, TRUE ) );
}

function mosaicEventGetDate( $which, $format = 'm/d/Y' ) {
	$key = ( FALSE !== stripos( $which, 'end' ) ) ? 'events_filter_end_date' : 'events_filter_start_date';
	if ( empty( $_REQUEST[ $key ] ) ) {
		return '';
	}

	$date = $_REQUEST[ $key ];
	if ( ! is_numeric( $date ) ) {
		$date = strtotime( $date );
	}

	return date( $format, $date );
}

class EventsFilterWidget extends WP_Widget {
	public function __construct() {
		parent::__construct( 'EventsFilterWidget', 'Events Filter', [ 'description' => 'Provides Filtering for Events' ] );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}

		echo '<form method="post" action="' . get_permalink( get_queried_object() ) . '">';

		if ( ! empty( $instance['show_date'] ) ) {
			$start_date = mosaicEventGetDate( 'start' );
			$end_date   = mosaicEventGetDate( 'end' );
			echo '<div class="event_date_filter" id="mosaic-event-run-time">';
			echo '<h3 class="widget_event_date_title">Between Dates</h3>';
			echo '<span class="event_date_start_date">';
			echo '<input name="events_filter_start_date" class="date start" value="' . $start_date . '" placeholder="Start Date">';
			echo '</span>';

			echo '<span class="event_date_end_date">';
			echo '<input name="events_filter_end_date" class="date end" value="' . $end_date . '" placeholder="End Date">';
			echo '</div>';

			define( 'EVENTS_WIDGET_DISPLAYED', 'Events Widget Displayed' );
		}

		if ( ! empty( $instance['show_locations'] ) ) {
			echo '<h3 class="widget_event_location_title">' . mosaicEvents()->get_event_location_label() . '</h3>';
			$term_args = [ 'taxonomy' => 'eventlocation' ];
			$locations = get_terms( $term_args );
			$this->output_event_terms( $locations );
		}


		if ( ! empty( $instance['show_categories'] ) ) {
			echo '<h3 class="widget_event_categories_title">' . mosaicEvents()->get_event_category_label() . '</h3>';
			$term_args  = [ 'taxonomy' => 'eventcategory' ];
			$categories = get_terms( $term_args );
			$this->output_event_terms( $categories );
		}

		echo '<div class="widget_event_filter_submit"><input type="submit" name="filter" value="Filter Events" /></div>';
		echo '</form>';

		echo $args['after_widget'];
	}

	private function output_event_terms( $terms ) {
		$term     = reset( $terms );
		$taxonomy = $term->taxonomy;
		$existing = ( ! empty( $_REQUEST["events_filter_{$taxonomy}"] ) ) ? $_REQUEST["events_filter_{$taxonomy}"] : [];
		$existing = array_keys( $existing );
		echo '<ul>';
		foreach ( $terms AS $term ) {
			echo '<li>';
			$checked = ( in_array( $term->term_taxonomy_id, $existing ) ) ? ' checked' : '';
			echo '<input type="checkbox" id="' . $term->taxonomy . '-' . $term->term_taxonomy_id . '" name="events_filter_' . $term->taxonomy . '[' . $term->term_taxonomy_id . ']"' . $checked . '>';
			echo '<label for="' . $term->taxonomy . '-' . $term->term_taxonomy_id . '">' . $term->name . '</label>';
			echo '</li>';
		}
		echo '</ul>';

	}

	public function update( $new, $old ) {
		$simple = [
			'title',
			'class'
		];

		$checkboxes = [
			'show_date',
			'show_categories',
			'show_locations'
		];

		foreach ( $simple AS $key ) {
			$return[ $key ] = $new[ $key ];
		}

		foreach ( $checkboxes AS $key ) {
			$return[ $key ] = isset( $new[ $key ] ) ? TRUE : FALSE;
		}

		return $return;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance,
			[
				'title'           => '',
				'class'           => '',
				'show_date'       => TRUE,
				'show_categories' => TRUE,
				'show_locations'  => TRUE
			]
		);

		$title = strip_tags( $instance['title'] );
		$class = strip_tags( $instance['class'] );

		$category_label = mosaicEvents()->get_event_category_label( TRUE );
		$location_label = mosaicEvents()->get_event_location_label( TRUE );
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
        <p><input id="<?php echo $this->get_field_id( 'show_date' ); ?>"
                  name="<?php echo $this->get_field_name( 'show_date' ); ?>"
                  type="checkbox" <?php checked( $instance['show_date'], TRUE, TRUE ); ?> />&nbsp;<label
                    for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show Date Filters' ); ?></label>
        </p>
        <p><input id="<?php echo $this->get_field_id( 'show_categories' ); ?>"
                  name="<?php echo $this->get_field_name( 'show_categories' ); ?>"
                  type="checkbox" <?php checked( $instance['show_categories'], TRUE, TRUE ); ?> />&nbsp;<label
                    for="<?php echo $this->get_field_id( 'show_categories' ); ?>"><?php echo sprintf( __( 'Show %s Filters' ), $category_label ); ?></label>
        </p>
        <p><input id="<?php echo $this->get_field_id( 'show_locations' ); ?>"
                  name="<?php echo $this->get_field_name( 'show_locations' ); ?>"
                  type="checkbox" <?php checked( $instance['show_locations'], TRUE, TRUE ); ?> />&nbsp;<label
                    for="<?php echo $this->get_field_id( 'show_locations' ); ?>"><?php echo sprintf( __( 'Show %s Filters' ), $location_label ); ?></label>
        </p>
		<?php
	}
}

/**
 * Add event-specific sidebars to the set of sidebars.
 *
 * @param array $sidebars
 *
 * @return mixed
 */
function mosaic_event_sidebars( $sidebars ) {
	$sidebars["event_sidebar"]          = "Events";
	$sidebars["event_taxonomy_sidebar"] = "Events Archives";

	return $sidebars;
}

add_filter( 'mosaic_sidebars_array', 'mosaic_event_sidebars' );
register_widget( 'EventsFilterWidget' );

