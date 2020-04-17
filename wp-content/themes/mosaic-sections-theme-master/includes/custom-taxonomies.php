<?php

class MosaicCustomTaxonomies {

	/**
	 * @var string
	 */
	private $render_type;

	/**
	 * Array of ALL registered custom taxonomies
	 *
	 * @var array
	 */
	private $custom_taxonomies = [];

	/**
	 * Array of the WP and Mosaic Theme "filters" that should be leveraged.
	 * Filters defined here are automatically "hooked", and run the class
	 * method by the same name as the filter.
	 *
	 * To control priority or number of arguments, assign the array element
	 * an optional array.  eg:
	 * [
	 *  'mosaic_additional_scss',
	 *  'the_title'            => [10, 2], // priority 10, 2 arguments
	 *  'mosaic_render_section'
	 * ]
	 *
	 * @var array
	 */
	private $filters = [
		'mosaic_post_get_taxonomies' => [ 10, 3 ]
	];

	/**
	 * Array of the WP and Mosaic Theme "actions" that should be leveraged.
	 * Actions defined here are automatically "hooked", and run the class
	 * method by the same name as the filter.
	 *
	 * To control priority or number of arguments, assign the array element
	 * an optional array.  eg:
	 * [
	 *  'wp_enqueue_scripts',
	 *  'mosaic_section_section_top' => [10, 2], // priority 10, 2 arguments
	 *  'wp_footer'
	 * ]
	 *
	 * @var array
	 */
	private $actions = [
		'mosaic_post_render_taxonomies' => [ 10, 3 ]
	];

	public function __construct() {
		$this->add_hooks( $this->filters, 'filter' );
		$this->add_hooks( $this->actions );
	}

	/**
	 * Retrieve ALL custom taxonomies types for a given post
	 * TODO: Decide if 'Source' taxonomy needs to be included in render
	 *
	 * @param array   $taxonomies
	 * @param WP_Post $post
	 * @param string  $render_type
	 *
	 * @return array
	 */
	public function mosaic_post_get_taxonomies( $taxonomies, $post, $render_type ) {
		$this->custom_taxonomies = get_taxonomies( [ '_builtin' => FALSE ] );
		$registered_taxonomies   = get_object_taxonomies( $post );

		return array_intersect( $registered_taxonomies, $this->custom_taxonomies );
	}

	/**
	 * Render ALL registered taxonomies of provided post
	 *
	 * @param array   $taxonomies
	 * @param WP_Post $post
	 * @param string  $render_type
	 */
	public function mosaic_post_render_taxonomies( $taxonomies, $post, $render_type ) {
		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy ) {
			$this->render_taxonomy_widget( $taxonomy, $post );
		}
	}

	/**
	 * Render taxonomy links widget
	 *
	 * @param string           $taxonomy
	 * @param null|int|WP_Post $post
	 */
	public function render_taxonomy_widget( $taxonomy, $post = NULL ) {
		$taxonomy_temp        = get_taxonomy( $taxonomy );
		$taxonomy_plural_name = _::get( $taxonomy_temp, [ 'labels', 'name' ] );

		/**
		 * @var WP_Term[]
		 */
		$terms = get_terms( $taxonomy );

		if ( ! empty( $post ) ) {
			if ( ! $this->has_tax( $post, $taxonomy ) ) {
				return;
			}

			$terms = get_the_terms( $post, $taxonomy );
		}

		echo '<div class="taxonomy custom-taxonomies taxonomy-' . $taxonomy . '">';
		echo '<span class="taxonomy-label"><h3>' . $taxonomy_plural_name . '</h3></span>';

		foreach ( $terms as $term ) {
			echo '<a href="' . get_term_link( $term, $taxonomy ) . '" rel="' . $taxonomy . ' ' . $term->slug . '">' . $term->name . '</a>';
		}

		echo '</div>';
	}

	/**
	 * Check if post has attached or is attached to a taxonomy
	 *
	 * @param int|WP_Post $post
	 * @param string      $taxonomy
	 *
	 * @return bool
	 */
	public function has_tax( $post, $taxonomy ) {
		$terms = get_the_terms( $post, $taxonomy );

		return ! ( empty( $terms ) || _::has( $terms, 'errors' ) );
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

			// Skip array entry if 'hook' does not have a respective valid callback function
			// Callback function name MUST be the same as the name of the 'hook'
			if ( ! method_exists( $this, $hook ) ) {
				continue;
			}

			$hook_callback = [ $this, $hook ];
			$priority      = $args[0];
			$parameters    = $args[1];

			call_user_func( "add_{$type}", $hook, $hook_callback, $priority, $parameters );
		}
	}
}

new MosaicCustomTaxonomies();