<?php

/**
 * Class MegaMenuWalker
 *
 * This class is used to render the Mega Menu under the relevant / appropriate top-level menu items.
 * Concept:
 * 1. For non-mega-menu items, do nothing different.
 * 2. For mega-menu items, do NOT render the sub-items that may have been set up in Appearance=>Menus, but instead
 *    render the mega menu content.
 *
 * In order to accomplish this, the class sets up a "buffer" of the output, and "skips" sections
 * that should be omitted (per item 2 above).  It then appends in the mega-menu markup.
 *
 * Additionally, the class adds a css class of "mega-menu-container" to the parent LI, so that it is possible to
 * apply special styling to make it appear full width, to reduce the amount of javascript required for basic
 * styling.
 */
class MegaMenuWalker extends Walker_Nav_Menu {
	private $mega_menu = NULL;
	private $mega_menu_id = FALSE;
	private $in_mega_menu_sub = FALSE;
	private $output_buffer = '';

	function __construct() {
		if ( NULL != $this->mega_menu ) {
			return;
		}

		$this->mega_menu = get_option( 'mosaic_theme_mega_menu' );

		add_filter( 'nav_menu_css_class', [ $this, 'nav_menu_css_class' ], 10, 2 );
		add_filter( 'nav_menu_item_id', [ $this, 'li_buffer' ], 10, 4 );
		add_filter( 'nav_menu_item_args', [ $this, 'setup_mega_menu_flags' ], 10, 3 );
		add_filter( 'walker_nav_menu_start_el', [ $this, 'store_buffer' ] );
	}

	/**
	 * Hooked as early as possible when outputting a menu item.
	 * Determine if the given menu item has a mega menu.
	 * If so, set the class variable for use throughout other methods.
	 *
	 * @param stdClass $args
	 * @param stdClass $item
	 * @param int      $depth
	 *
	 * @return stdClass $args
	 */
	function setup_mega_menu_flags( $args, $item, $depth ) {
		if ( $this->mega_menu_id ) {
			return $args;
		}

		$this->mega_menu_id = $this->is_mega_menu( $item->ID );

		return $args;
	}

	/**
	 * Insert the "mega menu container" class into the <li> wrapper for a menu item
	 * that contains a mega menu.
	 *
	 * @param array    $classes
	 * @param stdClass $item
	 *
	 * @return array
	 */
	function nav_menu_css_class( $classes, $item ) {
		if ( $this->mega_menu_id == $item->ID ) {
			$classes[] = 'mega-menu-container';
		}

		return $classes;
	}

	/**
	 * Hooked into the `nav_menu_item_id` filter.
	 * Same code as that filter, with the exception that it exits / does not add to the menu if
	 * it's inside of a mega-menu.
	 *
	 * @param int      $id
	 * @param stdClass $item
	 * @param stdClass $args
	 * @param int      $depth
	 */
	function li_buffer( $id, $item, $args, $depth ) {
		$t = "\t";
		if ( $this->mega_menu_id && $item->ID != $this->mega_menu_id ) {
			return;
		}

		$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

		$classes   = empty( $item->classes ) ? [] : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$this->output_buffer .= $indent . '<li' . $id . $class_names . '>';
	}

	/**
	 * Hooked into the `walker_nav_menu_start_el` filter.
	 * Ensures current menu link is added to the item to the output buffer if / when appropriate.
	 *
	 * @param string $buffer
	 *
	 * @return string
	 */
	function store_buffer( $buffer ) {
		if ( $this->mega_menu_id ) {
			if ( ! $this->in_mega_menu_sub ) {
				$this->output_buffer .= $buffer;
			}

			$this->in_mega_menu_sub = TRUE;
		}

		return $buffer;
	}

	/**
	 * Starts the list before the elements are added.
	 *
	 * @since 3.0.0
	 *
	 * @see   Walker::start_lvl()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = [] ) {
		if ( $this->mega_menu_id ) {
			return;
		}

		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}

		$indent              = str_repeat( $t, $depth );
		$output              .= "{$n}{$indent}<ul class=\"sub-menu\">{$n}";
		$this->output_buffer = $output;
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @since 3.0.0
	 *
	 * @see   Walker::end_lvl()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An object of wp_nav_menu() arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = [] ) {
		if ( $this->mega_menu_id ) {
			return;
		}

		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}

		$indent              = str_repeat( $t, $depth );
		$output              .= "$indent</ul>{$n}";
		$this->output_buffer = $output;
	}


	/**
	 * Ends the element output, if needed.
	 *
	 * @since 3.0.0
	 *
	 * @see   Walker::end_el()
	 *
	 * @param string  $output Passed by reference. Used to append additional content.
	 * @param WP_Post $item   Page data object. Not used.
	 * @param int     $depth  Depth of page. Not Used.
	 * @param array   $args   An object of wp_nav_menu() arguments.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = [] ) {
		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}

		if ( ! $this->mega_menu_id ) {
			$output              .= "</li>{$n}";
			$this->output_buffer = $output;

			return;
		}

		if ( $item->ID != $this->mega_menu_id ) {
			return;
		}

		$output              = $this->output_buffer;
		$output              .= $this->render_mega_menu();
		$this->output_buffer = $output;

		$this->mega_menu_id     = FALSE;
		$this->in_mega_menu_sub = FALSE;

	}

	private function is_mega_menu( $ID ) {
		$mega_menu = ( empty( $this->mega_menu[ $ID ] ) ) ? FALSE : $ID;
		if ( $mega_menu ) {
			$mega_menu = ( empty( $this->mega_menu[ $mega_menu ]['menus'] ) ) ? FALSE : $mega_menu;
		}

		return $mega_menu;
	}

	private function render_mega_menu() {
		if ( ! $this->mega_menu_id ) {
			return '';
		}

		$menu_data = $this->mega_menu[ $this->mega_menu_id ];
		$menus     = $menu_data['menus'];

		if ( ! $menus ) {
			return '';
		}

		$menu_count = count( $menus );
		$background = $menu_data['background'];
		$color      = $menu_data['color'];
		$opacity    = $menu_data['opacity'];
		$opacity    /= 100;

		if ( ! $opacity ) {
			$opacity = 1;
		}


		ob_start();
		echo '<div id="mega-menu-' . $this->mega_menu_id . '" class="mega-menu ' . $color . '" data-menu-id="' . $this->mega_menu_id . '">';
		do_action( 'mosaic_mega_menu_before_liner', $menu_data );
		echo '<div class="mega-menu-liner">';
		foreach ( $menus AS $menu ) {
			if ( ! $menu['menu_id'] ) {
				continue;
			}

			echo '<div class="mega-sub-menu count-' . $menu_count . ' ' . ( ( isset( $menu['classes'] ) ) ? $menu['classes'] : '' ) . '">';
			echo '<div class="mega-sub-menu-container">';

			if ( $menu['menu_id'] ) {
				if ( $menu['image'] && 'undefined' !== $menu['image'] ) {
					echo '<div class="mega-sub-menu-image"><img class="' . $menu['menu_id'] . '" src="' . $menu['image'] . '"></div>';
				}

				if ( $menu['heading'] ) {
					echo '<p class="menu-title">' . $menu['heading'] . '</p>';
				}
			}

			wp_nav_menu( [ 'menu' => $menu['menu_id'], 'container' => '', 'item_spacing' => 'discard' ] );

			echo '</div>';
			echo '</div>';
		}

		do_action( 'mosaic_mega_menu_before_bg', $menu_data );
		echo apply_filters( 'mosaic_mega_menu_bg', '<div class="mega-menu-bg bg-' . $background . '" style="opacity: ' . $opacity . ';"></div>', "mega-menu-bg bg-{$background}", $menu_data );
		do_action( 'mosaic_mega_menu_after_bg', $menu_data );
		echo '</div>';
		do_action( 'mosaic_mega_menu_after_liner', $menu_data );
		echo '</div>';

		return ob_get_clean();
	}
}