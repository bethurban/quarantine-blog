<?php

use MosaicSections\CustomPostType\MosaicCustomPostType;

class MosaicThemeTeam extends MosaicCustomPostType {

	const POST_TYPE = 'team';

	public function __construct() {
		// Enqueue admin scripts
		parent::__construct( self::POST_TYPE );

		$this->register_sections( [
			[
				'name'           => 'team-grid',
				'button_text'    => 'Team Grid',
				'button_icon'    => 'fa-users',
				'admin_section'  => [ $this, 'admin_section' ],
				'render_section' => [ $this, 'render_section' ]
			]
		] );
	}

	function admin_enqueue_scripts() {
		if ( ! $this->is_edit_custom_post_screen() ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-easing', get_template_directory_uri() . '/js/jquery.easing.1.2.js' );
	}

	/**
	 * WP admin_init action.
	 *
	 * Adds the metabox to the "Team Member" custom post type.
	 */
	function admin_init() {
		add_meta_box( 'mosaic_team', __( 'Team Member Info', 'mosaic' ), [
			$this,
			'meta_box'
		], 'team', 'normal', 'core' );
	}

	/**
	 * Called by init action.
	 * Registers the Team Member custom post type.
	 */
	function register_post_type() {
		register_post_type( self::POST_TYPE,
			[
				'labels'          => [
					'name'               => __( 'Team', 'mosaic' ),
					'singular_name'      => __( 'Team Member', 'mosaic' ),
					'add_new'            => __( 'Add New Team Member', 'mosaic' ),
					'add_new_item'       => __( 'Add Team Member', 'mosaic' ),
					'new_item'           => __( 'Add Team Member', 'mosaic' ),
					'view_item'          => __( 'View Team Member', 'mosaic' ),
					'search_items'       => __( 'Search Team Members', 'mosaic' ),
					'edit_item'          => __( 'Edit Team Member', 'mosaic' ),
					'all_items'          => __( 'All Team Members', 'mosaic' ),
					'not_found'          => __( 'No Team Members found', 'mosaic' ),
					'not_found_in_trash' => __( 'No Team Members found in Trash', 'mosaic' )
				],
				'taxonomies'      => [ 'teamcategory' ],
				'public'          => TRUE,
				'show_ui'         => TRUE,
				'capability_type' => 'post',
				'hierarchical'    => FALSE,
				'rewrite'         => [ 'slug' => 'team', 'with_front' => FALSE ],
				'query_var'       => TRUE,
				'supports'        => [ 'title', 'revisions', 'thumbnail', 'editor' ],
				'menu_position'   => 10,
				'menu_icon'       => 'dashicons-businessman',
				'has_archive'     => TRUE
			]
		);
	}

	/**
	 * Called by the init action.
	 * Registers the taxonomies for the Team Member custom post type.
	 */
	function register_taxonomy() {
		$category_label        = $this->get_team_category_label();
		$category_label_plural = $this->get_team_category_label( TRUE );

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
			'menu_name'         => __( "Team {$category_label_plural}", 'mosaic' )
		];

		register_taxonomy( 'teamcategory', 'team', [
			'hierarchical' => TRUE,
			'labels'       => $labels,
			'query_var'    => TRUE,
			'rewrite'      => [ 'slug' => 'teamcat' ]
		] );
	}

	function get_team_category_label( $plural = FALSE ) {
		return $this->get_team_tax_label( 'category', $plural );
	}

	function get_team_tax_label( $type, $plural = FALSE ) {
		$plural   = ( $plural ) ? '_plural' : '';
		$defaults = [
			'category'        => 'Category',
			'category_plural' => 'Categories',
		];

		$default = $defaults[ $type . $plural ];

		return MosaicTheme::get_option( "team_{$type}_label{$plural}", $default );
	}

	/**
	 * Display the metabox when editing an team member.
	 */
	function meta_box() {
		global $post;

		// Load the option values from the db
		$position    = get_post_meta( $post->ID, '_mosaic_team_position', TRUE );
		$short_bio   = get_post_meta( $post->ID, '_mosaic_team_short_bio', TRUE );
		$facebook    = get_post_meta( $post->ID, '_mosaic_team_facebook', TRUE );
		$linkedin    = get_post_meta( $post->ID, '_mosaic_team_linkedin', TRUE );
		$twitter     = get_post_meta( $post->ID, '_mosaic_team_twitter', TRUE );
		$google_plus = get_post_meta( $post->ID, '_mosaic_team_google_plus', TRUE );
		$link_text   = get_post_meta( $post->ID, '_mosaic_team_external_link_text', TRUE );
		$link_url    = get_post_meta( $post->ID, '_mosaic_team_external_link_url', TRUE );

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'team_nonce' );
		echo '<p><label for="_mosaic_team_position">Position:</label></p>';
		echo '<p><input type="text" class="widefat" name="_mosaic_team_position" value="' . $position . '" /></p>';
		echo '<p><label for="_mosaic_team_short_bio">Short Bio:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_team_short_bio" value="' . $short_bio . '" /></p>';
		echo '<p><label for="_mosaic_team_facebook">Facebook:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_team_facebook" value="' . $facebook . '" /></p>';
		echo '<p><label for="_mosaic_team_linkedin">LinkedIn:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_team_linkedin" value="' . $linkedin . '" /></p>';
		echo '<p><label for="_mosaic_team_twitter">Twitter:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_team_twitter" value="' . $twitter . '" /></p>';
		echo '<p><label for="_mosaic_team_google_plus">Google Plus:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_team_google_plus" value="' . $google_plus . '" /></p>';
		echo '<p><label for="_mosaic_team_external_link_text">External Link Text:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_team_external_link_text" value="' . $link_text . '" /></p>';
		echo '<p><label for="_mosaic_team_external_link_url">External Link Url:</label></p>';
		echo '<p><input class="widefat" type="text" name="_mosaic_team_external_link_url" value="' . $link_url . '" /></p>';
	}

	/**
	 * Save the Team Member metadata when the post is saved.
	 *
	 * @param int $post_id
	 */
	function save_post( $post_id ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST['team_nonce'] ) || ! wp_verify_nonce( $_POST['team_nonce'], plugin_basename( __FILE__ ) ) ) {
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

		$position    = $_POST['_mosaic_team_position'];
		$short_bio   = $_POST['_mosaic_team_short_bio'];
		$facebook    = $_POST['_mosaic_team_facebook'];
		$linkedin    = $_POST['_mosaic_team_linkedin'];
		$twitter     = $_POST['_mosaic_team_twitter'];
		$google_plus = $_POST['_mosaic_team_google_plus'];
		$link_text   = $_POST['_mosaic_team_external_link_text'];
		$link_url    = $_POST['_mosaic_team_external_link_url'];

		update_post_meta( $post_id, '_mosaic_team_position', $position );
		update_post_meta( $post_id, '_mosaic_team_short_bio', $short_bio );
		update_post_meta( $post_id, '_mosaic_team_facebook', $facebook );
		update_post_meta( $post_id, '_mosaic_team_linkedin', $linkedin );
		update_post_meta( $post_id, '_mosaic_team_twitter', $twitter );
		update_post_meta( $post_id, '_mosaic_team_google_plus', $google_plus );
		update_post_meta( $post_id, '_mosaic_team_external_link_text', $link_text );
		update_post_meta( $post_id, '_mosaic_team_external_link_url', $link_url );
	}

	public function admin_section( $data, $admin ) {
		$category_title    = mosaicTeam()->get_team_category_label();
		$section_id        = _::get( $data, 'section_id' );
		$title             = _::get( $data, 'team_grid_title' );
		$sub_headline      = _::get( $data, 'team_grid_subheadline' );
		$number_of_team    = _::get( $data, 'number_of_team' );
		$selected_category = _::get( $data, 'teamcategory' );

		echo '<p><label>Team Grid Title</label></p>';
		echo '<input type="text" class="widefat is-title" placeholder="Team Grid Title" name="section[' . $section_id . '][team_grid_title]" value="' . esc_attr( $title ) . '"/>';
		echo '<p><label>Event Grid Subheadline</label></p>';
		echo '<input type="text" class="widefat" placeholder="Team Grid SubHeadline" name="section[' . $section_id . '][team_grid_subheadline]" value="' . esc_attr( $sub_headline ) . '"/>';
		echo '<p><label>Number of Team Members</label>';

		$name = 'section[' . $section_id . '][number_of_team]';

		echo $admin->create_dropdown( $name, [
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6'
		], $number_of_team, 'Select...' );

		echo '</p>';

		$admin->taxonomy_dropdown( 'section[' . $section_id . '][teamcategory]', $category_title, 'teamcategory', $selected_category );

		$admin->generate_button_input( $data, 'team_grid_button_text' );
	}


	public function render_section( $data, $render ) {
		// Output data if and only if team grid section is being used
		wp_localize_script( 'mosaic-home-template', 'mosaicTeamData', $this->sanitize_team_data() );
		wp_enqueue_script( 'mosaic-home-template' );

		$title          = _::get( $data, 'team_grid_title' );
		$sub_headline   = _::get( $data, 'team_grid_subheadline' );
		$number_of_team = _::get( $data, 'number_of_team' );

		$terms = [
			'teamcategory' => _::get( $data, 'teamcategory' )
		];

		$render->open_sub_content_div( $data, 'team-grid-wrapper sub-contents' );

		echo '<div class="team-grid-title center">';
		echo '<h2 class="section-headline">' . $title . '</h2>';
		echo '</div>';

		echo '<div class="team-grid-sub-headline center">';
		echo '<p>' . $sub_headline . '</p>';
		echo '</div>';

		echo '<div class="team">';

		$number_of_team = $number_of_team ? $number_of_team : 3;
		$team_members   = $this->load_team_members( $number_of_team, $terms );

		foreach ( $team_members->posts as $member ) {
			echo $this->generate_team_member( $member );
		}

		echo '</div>';

		echo '<div class="team-button center">';
		// Change to match what function looks for
		$data['button_text'] = _::get( $data, 'team_grid_button_text' );

		$render->generate_button_area( $data );
		echo '</div>';

		$render->close_sub_content_div( $data );
	}

	/**
	 * Render a single team member
	 *
	 * @param WP_Post $team_member
	 *
	 * @return string
	 */
	public function generate_team_member( $team_member ) {
		$meta_data = $this->retrieve_team_member_meta_data( $team_member->ID );

		$member_image = wp_get_attachment_url( get_post_thumbnail_id( $team_member->ID ) );

		$member = '';

		$member .= '<div class="team-member" data-id="' . $team_member->ID . '">';

		if ( $member_image ) {
			$member .= '<div class="image">';
			$member .= '<img src="' . $member_image . '">';
			$member .= '</div>';
		}

		$member .= '<div class="member-name"><h3>' . $team_member->post_title . '</h3></div>';
		$member .= '<div class="position">' . $meta_data['position'] . '</div>';
		$member .= '<div class="short-bio">';
		$member .= $meta_data['short_bio'];
		$member .= '</div>';
		$member .= '</div>';

		return $member;
	}

	/**
	 * Return a sanitize array of team data
	 *
	 * @return array - [
	 *                  'title' => post title,
	 *                  'bio' => post content,
	 *                  'meta_data' => [
	 *                      'position => member position,
	 *                      'short_bio' => member short bio,
	 *                      'facebook' => facebook link,
	 *                      'linkedin' => linkedin link,
	 *                      'twitter' => twitter link,
	 *                      'google_plus' => google_plus link,
	 *                  ]
	 *               ]
	 */
	public function sanitize_team_data() {
		$team_data = $this->load_team_members();
		$members   = [];

		foreach ( $team_data->posts as $team_member ) {
			$members[ $team_member->ID ] = [
				'title'     => $team_member->post_title,
				'bio'       => apply_filters( 'the_content', $team_member->post_content ),
				'image'     => wp_get_attachment_url( get_post_thumbnail_id( $team_member->ID ) ),
				'meta_data' => $this->retrieve_team_member_meta_data( $team_member->ID )
			];
		}

		return $members;
	}

	/**
	 * Queries the team members, and return the results
	 *
	 * @param int   $number_of_team
	 * @param array $terms
	 *
	 * @return WP_Query
	 */
	public function load_team_members( $number_of_team = -1, $terms = [] ) {
		if ( ! $number_of_team ) {
			$number_of_team = -1;
		}

		$args = [
			'post_type'      => 'team',
			'posts_per_page' => $number_of_team
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

	/**
	 * Retrieve meta_data for team member
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public function retrieve_team_member_meta_data( $id ) {
		$meta_data = [
			'position'           => '',
			'short_bio'          => '',
			'linkedin'           => '',
			'twitter'            => '',
			'facebook'           => '',
			'google_plus'        => '',
			'external_link_text' => '',
			'external_link_url'  => ''
		];

		foreach ( $meta_data as $key => $field ) {
			$meta_data[ $key ] = get_post_meta( $id, "_mosaic_team_{$key}", TRUE );
		}

		return $meta_data;
	}
}

$mosaicTeam = new MosaicThemeTeam();

function mosaicTeam() {
	global $mosaicTeam;

	if ( empty( $mosaicTeam ) ) {
		$mosaicTeam = new MosaicThemeTeam();
	}

	return $mosaicTeam;
}

