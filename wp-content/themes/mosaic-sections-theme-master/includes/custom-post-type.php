<?php

namespace MosaicSections\CustomPostType;

use \_;

require_once '_.php';

abstract class MosaicCustomPostType {
	protected $post_type_name;
	protected $use_section;
	protected $filter_prefix;
	protected $sections = [];

	public function __construct( $post_type_name ) {
		if ( $this->check_if_post_type_exists() ) {
			wp_die( "A custom post type with the name '{$this->post_type_name}' already exists" );

			return;
		}

		$this->post_type_name = $post_type_name;
		$this->filter_prefix  = 'mosaic_post_' . $this->post_type_name . '_';

		$this->add_actions();
		$this->add_filters();

		add_shortcode( "mosaic_{$this->post_type_name}_feed", [ $this, 'render_shortcode' ] );
	}

	function add_actions() {
		$actions = [
			'admin_enqueue_scripts',
			'admin_init',
			'admin_footer' => [ 9999, 1 ],
			'save_post',
			'init'         => [ 0, 1 ]
		];

		$this->add_hooks( $actions );

		add_action( 'load-themes.php', [ $this, 'flush_rewrite_rules' ] );
	}

	function add_filters() {
		add_filter( 'mosaic_register_sections', [ $this, 'register_section_filter' ] );
		add_filter( 'mosaic_admin_section', [ $this, 'mosaic_admin_section' ], 10, 2 );
	}

	function init() {
		$this->register_post_type();
		$this->register_taxonomy();
	}

	function register_post_type() {
		$post_type_name_capitalized = ucwords( str_replace( '-', ' ', $this->post_type_name ) );

		register_post_type( $this->post_type_name,
			[
				'labels'          => [
					'name'               => __( $post_type_name_capitalized, 'mosaic' ),
					'singular_name'      => __( $post_type_name_capitalized, 'mosaic' ),
					'add_new'            => __( 'Add New', 'mosaic' ),
					'add_new_item'       => __( 'Add ' . $post_type_name_capitalized, 'mosaic' ),
					'new_item'           => __( 'Add ' . $post_type_name_capitalized, 'mosaic' ),
					'view_item'          => __( 'View ' . $post_type_name_capitalized, 'mosaic' ),
					'search_items'       => __( 'Search ' . $post_type_name_capitalized, 'mosaic' ),
					'edit_item'          => __( 'Edit ' . $post_type_name_capitalized, 'mosaic' ),
					'all_items'          => __( 'All ' . $post_type_name_capitalized, 'mosaic' ),
					'not_found'          => __( 'No ' . $post_type_name_capitalized . ' found', 'mosaic' ),
					'not_found_in_trash' => __( 'No ' . $post_type_name_capitalized . ' found in Trash', 'mosaic' )
				],
				'public'          => TRUE,
				'show_ui'         => TRUE,
				'capability_type' => 'post',
				'hierarchical'    => FALSE,
				'rewrite'         => [ 'slug' => $post_type_name_capitalized, 'with_front' => FALSE ],
				'query_var'       => TRUE,
				'supports'        => [ 'title', 'revisions', 'thumbnail', 'editor' ],
				'menu_position'   => 10,
				'has_archive'     => TRUE
			]
		);
	}

	/**
	 * // TODO: Cale - what was your vision for supporting multiple 'post_types'?
	 * Register a custom hierarchical (category-like) taxonomy.
	 *
	 * @param string       $key
	 * @param array|string $post_types
	 * @param string       $singular
	 * @param string       $plural
	 * @param string       $slug
	 */
	final function register_cat( $key, $post_types, $singular, $plural, $slug ) {
		$this->register_tax( $key, $post_types, $singular, $plural, $slug, TRUE );
	}

	/**
	 * Register a custom flat (tag-like) taxonomy.
	 *
	 * @param string       $key
	 * @param array|string $post_types
	 * @param string       $singular
	 * @param string       $plural
	 * @param string       $slug
	 */
	final function register_tag( $key, $post_types, $singular, $plural, $slug ) {
		$this->register_tax( $key, $post_types, $singular, $plural, $slug, FALSE );
	}

	/**
	 * Register a custom taxonomy.
	 *
	 * @param string       $key
	 * @param array|string $post_types
	 * @param string       $singular
	 * @param string       $plural
	 * @param string       $slug
	 * @param bool         $hierarchical
	 */
	final function register_tax( $key, $post_types, $singular, $plural, $slug, $hierarchical ) {
		$labels = [
			'name'              => $plural,
			'singular_name'     => $singular,
			'search_items'      => "Search {$plural}",
			'all_items'         => __( "All {$plural}", 'mosaic' ),
			'parent_item'       => __( "Parent {$singular}", 'mosaic' ),
			'parent_item_colon' => __( "Parent {$singular}:", 'mosaic' ),
			'edit_item'         => __( "Edit {$singular}", 'mosaic' ),
			'update_item'       => __( "Update {$singular}", 'mosaic' ),
			'add_new_item'      => __( "Add New {$singular}", 'mosaic' ),
			'new_item_name'     => __( "New {$singular} Name", 'mosaic' ),
			'menu_name'         => __( "{$plural}", 'mosaic' )
		];

		register_taxonomy( $key, $post_types, [
			'hierarchical' => $hierarchical,
			'labels'       => $labels,
			'query_var'    => TRUE,
			'rewrite'      => [ 'slug' => $slug ]
		] );
	}

	final function register_sections( $sections ) {
		if ( empty( $sections ) ) {
			return;
		}

		$sections = array_filter( $sections, function ( $section ) {
			return ( _::has( $section, 'admin_section' ) && _::has( $section, 'render_section' ) );
		} );

		foreach ( $sections as $section ) {
			$this->sections[] = $section;
		}
	}

	final function register_section_filter( $sections ) {
		if ( ! is_array( $sections ) ) {
			return $sections;
		}

		return array_merge( $sections, $this->sections );
	}

	function register_taxonomy() {
	}

	function admin_enqueue_scripts() {
	}

	function admin_init() {
	}

	function admin_footer() {
	}

	function render_shortcode( $shortcode_args ) {
	}

	/**
	 * Filters admin section data array
	 *
	 * @param array  $data
	 * @param string $type
	 *
	 * @return array
	 */
	function mosaic_admin_section( $data, $type ) {
		foreach ( $this->sections as $custom_section ) {
			if ( $custom_section['name'] === $type ) {
				$data['title'] = $custom_section['button_text'];
			}
		}

		return $data;
	}

	/**
	 * Default callback function for section's admin interface
	 * NOTE: all render callbacks will be passed the two arguments below
	 *
	 * @param array                        $section
	 * @param \MosaicHomeTemplateInterface $adminClass
	 */
	function admin_section( $section, $adminClass ) {
	}

	/**
	 * Default callback function for section's frontend render
	 * NOTE: all render callbacks will be passed the two arguments below
	 *
	 * @param array                     $section
	 * @param \MosaicHomeTemplateRender $renderClass
	 */
	function render_section( $section, $renderClass ) {
	}

	function save_post( $post_id ) {
	}

	final function flush_rewrite_rules() {
		global $pagenow, $wp_rewrite;

		if ( 'themes.php' == $pagenow && isset( $_GET['activated'] ) ) {
			$wp_rewrite->flush_rules();
		}
	}

	/**
	 * Ensures that the new post type we're trying to register does not already exist
	 *
	 * @return bool
	 */
	final function check_if_post_type_exists() {
		$registered_custom_post_types = get_post_types( [ '_builtin' => FALSE ] );

		if ( ! $registered_custom_post_types ) {
			return FALSE;
		}

		foreach ( $registered_custom_post_types as $post_type ) {
			if ( $this->post_type_name === $post_type ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	final function is_edit_custom_post_screen() {
		$screen = get_current_screen();

		return ( $screen && $this->post_type_name == $screen->post_type );
	}

	/**
	 * Programmatically add WP 'action's or 'filter's
	 *
	 * @param array  $hooks
	 * @param string $type
	 */
	final function add_hooks( $hooks = [], $type = 'action' ) {
		if ( empty ( $hooks ) || ! is_array( $hooks ) ) {
			return;
		}

		if ( $type !== 'action' ) {
			$type = 'filter';
		}

		foreach ( $hooks AS $hook => $args ) {
			$default_hook_args = [ 10, 1 ];

			// Check if hook has set 'priority' and 'parameters'
			if ( is_int( $hook ) && is_string( $args ) ) {
				$hook = $args;
				$args = $default_hook_args;
			}

			// PHP only support naming functions with `_` NOT `-`
			$hook_callback_name = str_replace( '-', '_', $hook );

			// Skip array entry if 'hook' does not have a respective valid callback function
			// Callback function name MUST be the same as the name of the 'hook'
			if ( ! method_exists( $this, $hook_callback_name ) ) {
				continue;
			}

			$hook_callback = [ $this, $hook_callback_name ];
			$priority      = $args[0];
			$parameters    = $args[1];

			call_user_func( "add_{$type}", $hook, $hook_callback, $priority, $parameters );
		}
	}
}

do_action( 'mosaic_custom_post_type_ready' );
