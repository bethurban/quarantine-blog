<?php

/**
 * Class MosaicHomeTemplateRender
 */
class MosaicHomeTemplateRender {

	const VERSION = '1.0.0';

	/**
	 * Store the ID of the post being viewed.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Store a counter of section(s) being rendered.
	 * Allows do_action hooks to be able to find a section (first, second, third)
	 *
	 * @var int
	 */
	private $section_index = 0;

	private $templates = [
		'page-home.php',
		'page-home-with-sidebar.php'
	];

	/**
	 * Store the data for the section(s) defined for the page.
	 *
	 * @var array
	 */
	private $sections = [];

	private $registered_custom_sections = [];

	/**
	 * User Roles than can edit, view, delete posts/pages
	 * AND users that are able to view frontend "edit section" UI
	 *
	 * @var array
	 */
	private $allowed_user_roles = [
		'administrator',
		'editor',
	];

	/**
	 * MosaicHomeTemplateRender constructor.
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'wp' ] );

		// Hotfix: Fix page 'jumps' for Gravity Forms on AJAX calls
		if ( class_exists( 'GFCommon' ) ) {
			add_filter( 'gform_confirmation_anchor', '__return_true' );
		}
	}

	/**
	 * Set the post ID class variable.
	 *
	 * @param int $post_id
	 */
	public function set_post_id( $post_id ) {
		if ( $post_id ) {
			if ( $this->post_id != $post_id ) {
				$this->sections = (array) get_post_meta( $post_id, '_mosaic_home_sections', TRUE );
			}

			$this->post_id = $post_id;
		}
	}

	/**
	 * WP wp action.
	 * Sets up the relevant actions and data.
	 * Wired into the wp action so that the queried $post is available.
	 */
	public function wp() {
		if ( is_admin() ) {
			return;
		}

		$post = get_queried_object();

		if ( empty( $post->post_type ) || empty( $post->ID ) ) {
			return;
		}

		if ( 'page' !== $post->post_type ) {
			return;
		}

		if ( ! in_array( get_post_meta( $post->ID, '_wp_page_template', TRUE ), $this->templates ) ) {
			return;
		}

		$this->set_post_id( $post->ID );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		$this->registered_custom_sections = apply_filters( 'mosaic_register_sections', [] );
	}

	/**
	 * WP enqueue_scripts action.
	 * Enqueues the relevant sections scripts and styles.
	 * Attempts to be minimalistic - only enqueue bxslider when a section that requires it is included in the page.
	 * Should only be called when the page being viewed is a "Sections" page.
	 */
	public function enqueue_scripts() {
		$scripts = [
			'bucket-carousel' => 'jquery.bxslider.min.js',
			'content-slider'  => 'jquery.bxslider.min.js'
		];

		$styles = [
			'bucket-carousel' => 'jquery.bxslider.css',
			'content-slider'  => 'jquery.bxslider.css',
		];

		$sections = wp_list_pluck( $this->sections, 'type' );

		wp_register_script( 'jquery-image-comparison', TEMPLATE_URL . '/js/jquery.image.comparison.slider.js', [ 'jquery' ], self::VERSION, TRUE );
		// this script is necessary to handle mobile jquery events
		wp_register_script( 'jquery-image-comparison-mobile', TEMPLATE_URL . '/js/jquery.image.comparison.mobile.custom.min.js', [ 'jquery' ], self::VERSION, TRUE );
		wp_register_script( 'jquery-image-comparison', TEMPLATE_URL . '/js/jquery.image.comparison.mobile.custom.min.js', [ 'jquery' ], self::VERSION, TRUE );
		wp_register_style( 'jquery-image-comparison', TEMPLATE_URL . '/css/jquery.image.comparison.slider.css' );
		wp_register_script( 'mosaic-home-template', TEMPLATE_URL . '/js/home.template.jquery.js', [ 'jquery' ], self::VERSION, TRUE );
		wp_localize_script( 'mosaic-home-template', 'mosaicData', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ] );
		wp_enqueue_script( 'mosaic-home-template' );
		wp_enqueue_script( 'jquery-image-comparison' );
		wp_enqueue_script( 'jquery-image-comparison-mobile' );
		wp_enqueue_style( 'jquery-image-comparison' );

		foreach ( $scripts AS $type => $script ) {
			if ( in_array( $type, $sections ) ) {
				wp_enqueue_script( $script, TEMPLATE_URL . '/js/' . $script, [ 'jquery' ], self::VERSION, TRUE );
			}
		}

		foreach ( $styles AS $type => $style ) {
			if ( in_array( $type, $sections ) ) {
				wp_enqueue_style( $style, TEMPLATE_URL . '/css/' . $style );
			}
		}
	}

	/**
	 * Draw all the sections for the front-end.
	 *
	 * @param NULL|int $post_id
	 *
	 */
	public function render_sections( $post_id = NULL ) {
		$this->set_post_id( $post_id );
		$effects = ( (int) get_post_meta( $this->post_id, '_page_loading_effects', TRUE ) ) ? 'effect-fade-in' : '';

		$effects_class = ( $effects ) ? 'effect-fade-in' : $effects = '';

		echo '<div id="mosaic-home-sections" class="' . $effects_class . '">';

		foreach ( $this->sections AS $index => $section ) {
			$section['id'] = $index;
			$this->render_section( $section );
		}

		echo '</div>';
	}

	/**
	 * Switch controller for drawing each type of section.
	 *
	 * @param array $section
	 *
	 */
	public function render_section( $section ) {
		$section = apply_filters( 'mosaic_render_section', $section );

		$type = _::get( $section, 'type' );

		$classes   = [];
		$classes[] = _::get( $section, 'color' );
		$classes[] = _::get( $section, 'additional_classes' );
		$anchor    = _::get( $section, 'anchor_link' );

		$background      = _::get( $section, 'background_image' );
		$parallax_option = _::get( $section, 'parallax_background' );

		$data_attr = _::get( $section, 'data_attr' );

		$data = '';

		if ( $data_attr ) {
			if ( is_array( $data_attr ) ) {
				foreach ( $data_attr AS $key => $value ) {
					$data .= ( FALSE === stripos( $key, 'data-' ) ) ? 'data-' : '';
					$data .= $key . '="' . $value . '"';
				}
			} else {
				$data = $data_attr;
			}

			$data = ' ' . $data;
		}

		$additional_styles = '';
		$anchor_link       = '';

		if ( (int) $parallax_option ) {
			$classes[] = 'parallax';
		}

		if ( ! empty( $background ) ) {
			$background = ' style="background: white url(' . $background . ') center center / cover no-repeat; ' . $additional_styles . '"';
		}

		if ( $anchor ) {
			$anchor_link = 'id="' . $anchor . '"';
		}

		if ( _::get( $section, 'overlay_color' ) ) {
			$classes[] = 'has-overlay';
		}

		$classes = implode( ' ', $classes );

		// this is late, to ensure all necessary processing is done before skipping rendering - but skips BEFORE any output or do_actions...
		if ( _::get( $section, 'hide', FALSE ) ) {
			return;
		}

		echo '<div class="section-wrapper">';

		do_action( 'mosaic_section_wrapper_top', $section, $this->section_index );

		echo '<section class="mosaic-section section-' . $type . ' ' . $classes . '"' . $background . ' ' . $anchor_link . '' . $data . '>';

		$this->section_overlay( $section );
		$this->edit_section_link( $section['id'] );

		do_action( 'mosaic_section_section_top', $section, $this->section_index );

		switch ( $type ) {
			case "content-area":
				$this->render_content_area( $section );
				break;
			case "cta":
				$this->render_cta( $section );
				break;
			case "banner":
				$this->render_banner( $section );
				break;
			case "content-slider":
				$this->render_content_slider( $section );
				break;
			case "bucket-grid":
			case "bucket-carousel":
			case "bucket-panels":
			case "bucket-stats":
			case "bucket-overlay":
				$this->render_featured_buckets( $section );
				break;
			case "product-highlight":
				$this->render_product_highlight( $section );
				break;
			case "contact-form":
				$this->render_contact_form( $section );
				break;
			case "checklist":
				$this->render_checklist( $section );
				break;
			case "donate-callout":
				$this->render_donate_meta_box( $section );
				break;
			case "video-grid":
				$this->render_video_grid( $section );
				break;
			case "image-grid":
				$this->render_image_grid( $section );
				break;
			case "image-list":
				$this->render_image_list( $section );
				break;
			case "split":
				$this->render_split_grid( $section );
				break;
			case "image-comparison":
				$this->render_image_comparison( $section );
				break;
			case "video-hero":
				$this->render_video_hero( $section );
				break;
			case "accordions":
				$this->render_accordions( $section );
				break;
			case "recent-posts":
				$this->render_recent_posts( $section );
				break;
			default:
				$this->render_custom_section( $type, $section );
				break;
		}

		do_action( 'mosaic_section_section_bottom', $section, $this->section_index );
		echo '</section>';
		do_action( 'mosaic_section_wrapper_bottom', $section, $this->section_index );
		echo '</div>';
	}

	/**
	 * Render edit section link
	 *
	 * @param $section_id
	 */
	public function edit_section_link( $section_id ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! $this->is_user_allowed() ) {
			return;
		}

		//TODO: Fix broken $edit_post_link for users with "author" role
		$edit_post_Link = get_edit_post_link( $this->post_id );
		$section_anchor = $edit_post_Link . '#mosaic-section-' . $section_id;

		echo '<div class="edit-section"></div>';
		echo '<a href="' . $section_anchor . '" target="_blank" class="edit-section-link" name="Edit Section"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style=""><path d="M24 13.616v-3.232c-1.651-.587-2.694-.752-3.219-2.019v-.001c-.527-1.271.1-2.134.847-3.707l-2.285-2.285c-1.561.742-2.433 1.375-3.707.847h-.001c-1.269-.526-1.435-1.576-2.019-3.219h-3.232c-.582 1.635-.749 2.692-2.019 3.219h-.001c-1.271.528-2.132-.098-3.707-.847l-2.285 2.285c.745 1.568 1.375 2.434.847 3.707-.527 1.271-1.584 1.438-3.219 2.02v3.232c1.632.58 2.692.749 3.219 2.019.53 1.282-.114 2.166-.847 3.707l2.285 2.286c1.562-.743 2.434-1.375 3.707-.847h.001c1.27.526 1.436 1.579 2.019 3.219h3.232c.582-1.636.75-2.69 2.027-3.222h.001c1.262-.524 2.12.101 3.698.851l2.285-2.286c-.744-1.563-1.375-2.433-.848-3.706.527-1.271 1.588-1.44 3.221-2.021zm-12 2.384c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4z"/></svg></a>';
	}

	/**
	 * Check if current user's role(s) are allowed to view frontend sections "admin" UI
	 *
	 * @param WP_User|int|null $user
	 *
	 * @return bool
	 */
	public function is_user_allowed( $user = NULL ) {
		if ( empty( $user ) ) {
			$user = $this->get_current_user();

			if ( ! $user ) {
				return FALSE;
			}
		}

		if ( is_int( $user ) ) {
			$user_id = $user;

			// FALSE if user does not exist
			$user = get_user_by( 'id', $user_id );

			if ( ! $user ) {
				echo "User with ID: ${$user_id} does not exist!";

				return FALSE;
			}
		}

		/**
		 * @var array
		 */
		$user_roles_array = $user->roles;

		foreach ( $user_roles_array as $role ) {
			// If any of the user's role is a match then user's role is supported
			if ( in_array( $role, $this->allowed_user_roles ) ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Utility wrapper function for retrieving current logged in user
	 *
	 * @return bool|WP_User
	 */
	public function get_current_user() {
		$user = wp_get_current_user();

		/**
		 * wp_get_current_user() sets ID to 0 if no user is logged in
		 * @see https://codex.wordpress.org/Function_Reference/wp_get_current_user#Return_Values
		 */
		if ( ! $user->ID ) {
			echo 'No user is currently logged in.';

			return FALSE;
		}

		return $user;
	}

	/**
	 * Check if user is post's author
	 *
	 * @param WP_Post|int|null $post
	 * @param WP_User|int|null $user
	 *
	 * @return bool
	 */
	public function is_post_author( $post = NULL, $user = NULL ) {
		if ( empty( $post ) ) {
			global $post;
		}

		if ( empty( $user ) ) {
			$user = $this->get_current_user();

			if ( ! $user ) {
				return FALSE;
			}
		}

		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( is_int( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		if ( $user->ID == $post->post_author ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Render Section overlay
	 *
	 * @param array $data
	 */
	public function section_overlay( $data ) {
		$color   = _::get( $data, 'overlay_color' );
		$opacity = _::get( $data, 'overlay_opacity' );

		if ( ! $color || ! isset( $color ) ) {
			return;
		}

		$color = ( strpos( $color, '#' ) === 0 ) ? substr( $color, 1 ) : $color;

		if ( ! $this->validate_hex_color_value( $color ) ) {
			$color = '000';
		}

		if ( ! $opacity || 0 > $opacity ) {
			$opacity = 0;
		}

		if ( 100 < $opacity ) {
			$opacity = 100;
		}

		echo '<div class="section-overlay" style="background: #' . $color . '; opacity: ' . ( $opacity / 100 ) . '"></div>';
	}

	/**
	 * Abstraction of the opening div tag for the "sub-contents" div containers.
	 * Allows adding do_action(s) to the top and bottom of the "sub-contents" divs.
	 *
	 * @param array  $data
	 * @param string $class
	 * @param bool   $return
	 *
	 * @return string
	 */
	public function open_sub_content_div( $data, $class, $return = FALSE ) {
		if ( $return ) {
			ob_start();
		}

		echo '<div class="' . $class . '">';
		do_action( 'mosaic_section_sub_content_top', $data, $this->section_index );

		if ( $return ) {
			return ob_get_clean();
		}
	}

	/**
	 * Abstraction of the closing div tag for the "sub-contents" div containers.
	 * Allows adding do_action(s) to the top and bottom of the "sub-contents" divs.
	 *
	 * @param array $data
	 * @param bool  $return
	 *
	 * @return string
	 */
	public function close_sub_content_div( $data, $return = FALSE ) {
		if ( $return ) {
			ob_start();
		}

		do_action( 'mosaic_section_sub_content_bottom', $data, $this->section_index );
		echo '</div>';

		if ( $return ) {
			return ob_get_clean();
		}
	}

	/**
	 * Checks if a string is a valid hex color value
	 *
	 * @param string $color
	 *
	 * @return bool|false|int
	 */
	public function validate_hex_color_value( $color ) {
		if ( ! ( strlen( $color ) === 3 || strlen( $color ) == 6 ) ) {
			return FALSE;
		}

		$pattern = '/([a-f0-9]{3}){1,2}\b/mi';

		return ( preg_match( $pattern, $color ) );
	}

	/**
	 * Renders the "Content Area" Section interface (when editing a page in the dashboard)
	 *
	 * @param array $data
	 *
	 */
	public function render_content_area( $data ) {
		$content = _::get( $data, 'content' );

		$this->open_sub_content_div( $data, 'content-wrapper sub-contents' );

		echo apply_filters( 'the_content', $content );

		$this->close_sub_content_div( $data );
	}

	/**
	 * Renders the "CTA" Section interface (when editing a page in the dashboard).
	 *
	 * @param array $data
	 *
	 */
	public function render_cta( $data ) {
		$content = _::get( $data, 'content' );

		$this->open_sub_content_div( $data, 'cta-wrapper sub-contents center' );

		echo '<div class="cta-headline ">';
		echo apply_filters( 'the_content', $content );
		echo '</div>';

		$this->generate_button_area( $data );
		$this->close_sub_content_div( $data );
	}

	/**
	 * Renders the "Hero Banner" Section interface (when editing a page in the dashboard).
	 *
	 * @param array $data
	 *
	 */
	public function render_banner( $data ) {
		$image          = _::get( $data, 'image' );
		$content        = _::get( $data, 'content' );
		$text_alignment = _::get( $data, 'text_alignment', 'middle' );
		$text_placement = _::get( $data, 'text_placement' );

		$image_block = '<div class="banner-image slide-up">';

		if ( $image ) {
			$image_block .= '<img src="' . $image . '"/>';
		}

		$image_block .= '</div>';

		if ( 'center' == $text_placement ) {
			$text_alignment .= ' full-width';
		}

		$text_block = '';
		$text_block .= '<div class="banner-content-wrapper">';
		$text_block .= '<div class="banner-content">'; // ' . $align . '">';
		$text_block .= apply_filters( 'the_content', $content );
		$text_block .= '</div>';
		$text_block .= $this->generate_button_area( $data, 'center', FALSE );
		$text_block .= '</div>';

		$text_placement = ( $text_placement ) ? $text_placement : 'left';

		$this->open_sub_content_div( $data, 'banner-wrapper sub-contents image-' . $text_placement . ' align-' . $text_alignment );

		if ( 'right' == $text_placement ) {
			echo $image_block;
			echo $text_block;
		} else if ( 'left' == $text_placement ) {
			echo $text_block;
			echo $image_block;
		} else {
			echo $text_block;
		}

		$this->close_sub_content_div( $data );
	}

	/**
	 * Renders the "Content Slider" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data
	 *
	 */
	public function render_content_slider( $data ) {
		$title = _::get( $data, 'headline' );

		$this->open_sub_content_div( $data, 'sub-contents' );

		echo '<h2 class="sliders-headline section-headline center">' . $title . '</h2>';
		echo '<div class="content-slider">';

		$content_sliders = _::get( $data, 'content_sliders' );

		foreach ( $content_sliders as $slider ) {
			$this->generate_content_slider( $slider );
		}

		echo '</div>';
		$this->close_sub_content_div( $data );
	}

	/**
	 * Renders the "Featured Buckets" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data
	 *
	 */
	public function render_featured_buckets( $data ) {
		$section_id                 = _::get( $data, 'id' );
		$section_additional_classes = _::get( $data, 'additional_classes' );
		$title                      = _::get( $data, 'buckets_title' );
		$buckets                    = _::get( $data, 'buckets' );
		$buckets_text               = _::get( $data, 'buckets_text' );
		$subheadline                = _::get( $data, 'buckets_subheadline' );

		if ( empty( $buckets ) ) {
			$buckets = [ [] ];
		}

		$this->open_sub_content_div( $data, 'bucket-wrapper sub-contents' );

		if ( $title ) {
			echo '<h2 class="buckets-headline section-headline center">' . $title . '</h2>';
		}

		if ( $subheadline ) {
			echo '<div class="buckets-subheadline center">' . $subheadline . '</div>';
		}

		echo '<div class="buckets">';

		foreach ( $buckets AS $bucket_data ) {
			$bucket_data['type']                       = _::get( $data, 'type' );
			$bucket_data['section_id']                 = $section_id;
			$bucket_data['section_additional_classes'] = $section_additional_classes;

			$this->generate_bucket( $bucket_data );
		}

		echo '</div>';

		if ( $buckets_text ) {
			echo '<div class="buckets-text">';
			echo apply_filters( 'the_content', $buckets_text );
			echo '</div>';
		}

		$this->close_sub_content_div( $data );
	}

	/**
	 * Renders the "Product Highlight" Section
	 *
	 * @param $data
	 *
	 */
	public function render_product_highlight( $data ) {
		$highlights           = _::get( $data, 'highlights' );
		$image                = _::get( $data, 'image' );
		$highlights_placement = _::get( $data, 'highlights_placement' );
		$title                = _::get( $data, 'product_highlight_title' );
		$sub_title            = _::get( $data, 'product_highlight_sub_title' );

		$this->open_sub_content_div( $data, 'product-highlight sub-contents' );

		echo '<div class="product-highlight-info center">';
		echo '<div class="product-highlight-title"><h2 class="section-headline">' . $title . '</h2></div>';
		echo '<div class="product-highlight-subtitle"><p>' . $sub_title . '</p></div>';
		echo '</div>';

		$image_section    = '';
		$left_highlights  = [];
		$right_highlights = [];
		$size             = 'one-half';

		if ( 'left' == $highlights_placement ) {
			$left_highlights = $highlights;
		} else if ( 'right' == $highlights_placement ) {
			$right_highlights = $highlights;
		} else {
			$size             = 'one-third';
			$half             = ceil( count( $highlights ) / 2 );
			$left_highlights  = array_slice( $highlights, 0, $half );
			$right_highlights = array_slice( $highlights, $half );
		}

		$image_section .= '<div class="highlight-image ' . $size . '">';
		$image_section .= '<img src="' . $image . '">';
		$image_section .= '</div>';

		$left  = $this->generate_highlight_column( $left_highlights, "{$size} highlights-left" );
		$right = $this->generate_highlight_column( $right_highlights, "{$size} highlights-right" );

		echo $left . $image_section . $right;

		$this->generate_button_area( $data, 'center' );

		$this->close_sub_content_div( $data );
	}

	/**
	 * Renders the "Contact Form" Section
	 *
	 * @param $data
	 *
	 */
	public function render_contact_form( $data ) {
		$form_id = _::get( $data, 'form_option' );

		$this->open_sub_content_div( $data, 'contact-form-wrapper sub-contents' );

		if ( $form_id ) {
			echo do_shortcode( '[form-builder id="' . $form_id . '"]' );
		}

		$this->close_sub_content_div( $data );
	}

	/**
	 * Generate the full "Checklist" section
	 *
	 * @param $data
	 */
	public function render_checklist( $data ) {
		$image     = _::get( $data, 'image' );
		$title     = _::get( $data, 'checklist_title' );
		$sub_title = _::get( $data, 'checklist_sub_title' );

		$checklist_items = _::get( $data, 'checklist_items' );

		$checklist = '';

		$checklist .= $this->open_sub_content_div( $data, 'checklist sub-contents', TRUE );

		$checklist .= '<div class="checklist-wrapper">';
		$checklist .= '<div class="checklist-info center">';
		$checklist .= '<div class="checklist-title">';
		$checklist .= '<h2 class="section-headline">' . $title . '</h2>';
		$checklist .= '</div>';
		$checklist .= '<div class="checklist-sub-title">';
		$checklist .= '<p>' . $sub_title . '</p>';
		$checklist .= '</div>';
		$checklist .= '</div>';
		$checklist .= '<div class="checklist-items">';

		foreach ( $checklist_items as $items ) {
			$checklist .= $this->generate_checklist_item( $items );
		}

		$checklist .= '</div>';

		if ( $image ) {
			$checklist .= '<div class="checklist-image">';
			$checklist .= '<img src="' . $image . '" >';
			$checklist .= '</div>';
		}

		$checklist .= '</div>';
		$checklist .= $this->close_sub_content_div( $data, TRUE );
		echo $checklist;
	}

	/**
	 * Generate the full "Donate Callout" section
	 *
	 * @param $data
	 *
	 */
	public function render_donate_meta_box( $data ) {
		$content = _::get( $data, 'content' );

		$this->open_sub_content_div( $data, 'donate-wrapper sub-contents' );

		echo '<div class="donate-text valign">';
		echo apply_filters( 'the_content', $content );
		echo '</div>';

		echo '<div class="donate-button valign">';
		$this->generate_button_area( $data );
		echo '</div>';

		$this->close_sub_content_div( $data );
	}

	/**
	 * Generate the full "Video Grid" section
	 *
	 * @param $data
	 *
	 */
	public function render_video_grid( $data ) {
		$videos = _::get( $data, 'video_items' );
		$title  = _::get( $data, 'video_grid_title' );

		$video_grid = '';
		$video_grid .= $this->open_sub_content_div( $data, 'video-grid-wrapper sub-contents', TRUE );
		$video_grid .= '<div class="video-grid-title center">';
		$video_grid .= '<h2 class="section-headline">' . $title . '</h2>';
		$video_grid .= '</div>';
		$video_grid .= '<div class="videos">';

		foreach ( $videos as $video_item ) {
			$video_grid .= $this->generate_video( $video_item );
		}

		$video_grid .= '</div>';

		$video_grid .= $this->close_sub_content_div( $data, TRUE );

		echo $video_grid;
	}

	/**
	 * Generates the full "Image Grid" section
	 *
	 * @param $data
	 *
	 */
	public function render_image_grid( $data ) {
		$images = _::get( $data, 'image_items' );
		$title  = _::get( $data, 'image_grid_title' );

		$image_grid = '';
		$image_grid .= $this->open_sub_content_div( $data, 'image-grid-wrapper sub-contents', TRUE );
		$image_grid .= '<div class="image-grid-title center">';
		$image_grid .= '<h2 class="section-headline">' . $title . '</h2>';
		$image_grid .= '</div>';
		$image_grid .= '<div class="images">';

		foreach ( $images as $image_item ) {
			$image_grid .= $this->generate_image( $image_item );
		}

		$image_grid .= '</div>';
		$image_grid .= $this->close_sub_content_div( $data, TRUE );

		echo $image_grid;
	}

	/**
	 * Generates the full "Image List" section
	 *
	 * @param $data
	 */
	public function render_image_list( $data ) {
		$title  = _::get( $data, 'image_list_headline' );
		$images = _::get( $data, 'image_list_items' );

		$image_list = '';
		$image_list .= $this->open_sub_content_div( $data, 'image-list-wrapper sub-contents', TRUE );
		$image_list .= '<div class="image-list-title center">';
		$image_list .= '<h2 class="section-headline">' . $title . '</h2>';
		$image_list .= '</div>';
		$image_list .= '<div class="image-list-items">';

		foreach ( $images as $image_list_item ) {
			$image_list .= $this->generate_image_list_item( $image_list_item );
		}

		$image_list .= $this->close_sub_content_div( $data, TRUE );

		echo $image_list;
	}

	/**
	 * Generates Split Grid
	 *
	 * @param $data
	 */
	public function render_split_grid( $data ) {
		$left           = _::get( $data, 'left' );
		$right          = _::get( $data, 'right' );
		$split_headline = _::get( $data, 'split_grid_headline' );


		$this->open_sub_content_div( $data, 'split-container sub-contents' );

		if ( $split_headline ) {
			echo '<h2 class="split-grid-headline section-headline center">' . $split_headline . '</h2>';
		}

		echo '<div class="split left ' . $left['color'] . '">';
		echo '<div class="content">';
		echo apply_filters( 'the_content', $left['content'] );
		echo '</div>';
		echo '</div>';
		echo '<div class="split right ' . $right['color'] . '">';
		echo '<div class="content">';
		echo apply_filters( 'the_content', $right['content'] );
		echo '</div>';
		echo '</div>';
		$this->close_sub_content_div( $data );
	}


	/**
	 * Generates Image Comparison
	 *
	 * @param $data
	 */
	public function render_image_comparison( $data ) {
		$top_image    = _::get( $data, 'top_image' );
		$bottom_image = _::get( $data, 'bottom_image' );
		$headline     = _::get( $data, 'image_comparison_headline' );
		$this->open_sub_content_div( $data, 'image-comparison-wrapper sub-contents' );

		if ( $headline ) {
			echo '<h2 class="section-headline center">' . $headline . '</h2>';
		}

		echo '<figure class="cd-image-container">';
		echo '<img src="' . $bottom_image . '" alt="Original Image">';
		echo '<span class="cd-image-label" data-type="original">Original</span>';

		echo '<div class="cd-resize-img"> <!-- the resizable image on top -->';
		echo '<img src="' . $top_image . '" alt="Modified Image">';
		echo '<span class="cd-image-label" data-type="modified">Modified</span>';
		echo '</div>';

		echo '<span class="cd-handle"></span>';
		echo '</figure>';

		$this->close_sub_content_div( $data );
	}

	public function render_video_hero( $data ) {
		$content           = _::get( $data, 'content' );
		$video_backgrounds = _::get( $data, 'video_background' );

		if ( $video_backgrounds ) {
			echo '<video class="video-background" autoplay muted loop>';

			foreach ( $video_backgrounds as $key => $value ) {
				if ( $value ) {
					echo '<source src="' . $value . '" type="video/' . $key . '">';
				}
			}

			echo '</video>';
		}

		$this->open_sub_content_div( $data, 'sub-contents' );
		echo apply_filters( 'the_content', $content );
		$this->close_sub_content_div( $data );
	}

	public function render_accordions( $data ) {
		$headline   = apply_filters( 'mosaic_accordion_headline', _::get( $data, 'accordions_headline' ), $data );
		$accordions = _::get( $data, 'accordions' );

		$this->open_sub_content_div( $data, 'sub-contents' );

		echo '<div class="headline center"><h2 class="section-headline">' . $headline . '</h2></div>';

		foreach ( $accordions as $accordion ) {
			$this->generate_accordion( $accordion );
		}

		$this->close_sub_content_div( $data );
	}

	public function render_recent_posts( $data ) {
		$headline     = apply_filters( 'mosaic_recent_post_headline', _::get( $data, 'posts_headline' ), $data );
		$which_posts  = _::get( $data, 'which_posts' );
		$post_classes = _::get( $data, [ 'post', 'additional_classes' ], [] );
		if ( is_array( $post_classes ) ) {
			$post_classes = trim( implode( ' ', $post_classes ) );
		}

		$this->open_sub_content_div( $data, 'sub-contents' );
		if ( $headline ) {
			echo '<div class="headline center"><h2 class="section-headline">' . $headline . '</h2></div>';
		}

		$posts = [];
		if ( 'manual' == $which_posts ) {
			$ids = _::get( $data, 'post_ids' );
			$ids = explode( ',', $ids );
			$ids = array_filter( $ids );
			foreach ( $ids AS $id ) {
				$posts[] = get_post( $id );
			}
		} else {
			$category_id = _::get( $data, 'category_id' );
			$order       = ( 'asc' == _::get( $data, 'sort_posts', 'desc' ) ) ? 'ASC' : 'DESC';

			$args = [
				'posts_per_page' => _::get( $data, 'number_posts', 3 ),
				'order'          => $order
			];

			if ( $category_id ) {
				$args['cat'] = $category_id;
			}

			$posts = get_posts( $args );
		}

		if ( $posts ) {
			foreach ( $posts AS $post ) {
				// div
				// image (if there is one)
				// small overlay with cat / tag
				// title (h3?)
				// excerpt
				// read more
				$thumbnail = get_the_post_thumbnail( $post->ID );
				$tax_shown = FALSE;

				$categories = wp_get_post_categories( $post->ID, [ 'fields' => 'all' ] );
				$tags       = wp_get_post_tags( $post->ID, [ 'fields' => 'all' ] );


				$limit     = apply_filters( 'mosaic_recent_post_tax_limit', 3 );
				$tax_count = 0;
				$taxes     = [];
				foreach ( $categories AS $category ) {
					if ( ++$tax_count > $limit ) {
						continue;
					}

					$taxes[] = '<a class="tax-link tax-category" href="' . get_category_link( $category->term_id ) . '">' . $category->name . '</a>';
				}

				foreach ( $tags AS $tag ) {
					if ( ++$tax_count > $limit ) {
						continue;
					}

					$taxes[] = '<a class="tax-link tax-tag" href="' . get_tag_link( $tag->term_id ) . '">' . $tag->name . '</a>';
				}

				$taxes = implode( apply_filters( 'mosaic_tax_sep', '<span class="tax-sep">/</span>' ), $taxes );
				$taxes = '<div class="recent-post-taxonomy">' . $taxes . '</div>';

				if ( ! $thumbnail ) {
					$post_classes .= ' no-thumbnail';
				}

				echo '<div class="recent-post ' . $post_classes . '">';

				if ( $thumbnail ) {
					echo '<div class="thumbnail">';
					echo $thumbnail;
					echo $taxes;
					$tax_shown = TRUE;
					echo '</div>';
				}

				// TODO: improve control over order here...
				if ( $taxes && ! $tax_shown ) {
					echo $taxes;
				}

				echo '<div class="post-text">';
				echo apply_filters( 'mosaic_recent_post_title', '<h3 class="recent-post-title">' . $post->post_title . '</h3>', $post );

				echo '<div class="post-content">';
				$excerpt = get_the_excerpt( $post->ID );
				if ( ! trim( $excerpt ) ) {
					$excerpt = wp_trim_words( apply_filters( 'the_content', $post->post_content ) );
				}

				echo $excerpt;
				echo '</div>';

				$read_more = apply_filters( 'mosaic_recent_post_read_more', 'READ MORE' );
				echo '<div class="recent-post-read-more-wrap"><a class="read-more recent-post-read-more" href="' . get_permalink( $post->ID ) . '">' . $read_more . '</a></div>';

				echo '</div>';
				echo '</div>';
			}
		}

		$this->close_sub_content_div( $data );
	}

	/**
	 * Render section by using registered section's callback
	 *
	 * @param string $type
	 * @param array  $data
	 */
	function render_custom_section( $type, $data ) {
		$section  = _::find( $this->registered_custom_sections, [ 'name' => $type ] );
		$callback = $section['render_section'];

		if ( empty( $callback ) ) {
			return;
		}

		if ( ! is_callable( $callback ) ) {
			return;
		}

		// Run callback. Pass section data and instance of this class
		call_user_func( $callback, $data, $this );
	}

	public function generate_accordion( $data ) {
		$accordion_headline = _::get( $data, 'accordion_headline' );
		$accordion_body     = _::get( $data, 'accordion_body' );
		$view               = _::get( $data, 'accordion_view' );
		$color              = _::get( $data, 'color' );

		$open   = '';
		$active = '';

		if ( 'open' == $view ) {
			$open   = $view;
			$active = 'active';
		}

		echo '<div class="accordion ' . $open . ' ' . $color . '">';
		echo '<div class="accordion-headline ' . $active . '"><h4><span>' . $accordion_headline . '</span></h4></div>';
		echo '<div class="accordion-body">' . apply_filters( 'the_content', $accordion_body ) . '</div>';
		echo '</div>';
	}

	/**
	 * Generate a single bucket
	 *
	 * @param $data
	 *
	 */
	public function generate_bucket( $data ) {
		$bucket_headline    = _::get( $data, 'bucket_headline' );
		$bucket_description = _::get( $data, 'bucket_description' );
		$bucket_image       = _::get( $data, 'image' );
		$type               = _::get( $data, 'type' );
		$classes            = _::get( $data, 'additional_classes' );
		$color              = _::get( $data, 'color' );

		if ( "bucket-panels" == $type ) {
			echo '<div class="bucket bucket-panel ' . $color . ' ' . $classes . '">';
			echo '<div class="bucket-info center">';
			echo '<div class="bucket-panel-title"><h3>' . $bucket_headline . '</h3></div>';
			echo '<div class="bucket-panel-text">';
			echo '<p>' . $bucket_description . "</p>";
			$this->generate_button_area( $data );
			echo '</div>';
			echo '</div>';
			do_action( "mosaic_{$type}_end", $data );
			echo '</div>';
		} elseif ( "bucket-stats" == $type ) {
			$bucket_stat               = _::get( $data, 'bucket_stat' );
			$bucket_secondary_headline = _::get( $data, 'bucket_secondary_headline' );
			echo '<div class="bucket bucket-stats ' . $color . ' ' . $classes . '">';
			echo '<div class="bucket-info center">';
			echo '<div class="bucket-stat"><h2>' . $bucket_stat . '</h2></div>';
			echo '<div class="bucket-secondary-headline"><h2>' . $bucket_secondary_headline . '</h2></div>';
			echo '</div>';
			echo '<div class="bucket-panel-text center">';
			echo '<p>' . $bucket_description . '</p>';
			echo '</div>';
			do_action( "mosaic_{$type}_end", $data );
			echo '</div>';
		} elseif ( 'bucket-overlay' == $type ) {
			$icon              = _::get( $data, 'icon' );
			$alt_icon          = _::get( $data, 'icon_alternate' );
			$button_url        = _::get( $data, 'button_url' );
			$button_new_window = _::get( $data, 'button_new_window' );
			$background_image  = $bucket_image;
			$button_text       = _::get( $data, 'button_text' );

			echo '<div class="bucket ' . $type . ' ' . $color . ' ' . $classes . '">';

			if ( $button_url ) {
				$target = ( $button_new_window ) ? 'target="_blank"' : '';
				echo '<a href="' . $button_url . '" ' . $target . '>';
			}

			echo '<div class="wrapper">';

			if ( $background_image ) {
				echo '<div class="bucket-background" style="background: url(' . $background_image . ') center center / cover no-repeat;" data-image="' . $background_image . '">';
				echo '</div>';
			}

			if ( $icon || $alt_icon ) {
				echo '<div class="bucket-icon">';
				echo $alt_icon ? '<img src="' . $alt_icon . '" class="icon alt" alt="Bucket Alternate Icon"/>' : '';
				echo $icon ? '<img src="' . $icon . '" class="icon" alt="Bucket Icon"/>' : '';
				echo '</div>';
			}

			echo '<div class="bucket-info">';
			echo '<div class="bucket-title"><h3>' . $bucket_headline . '</h3></div>';
			echo '<div class="bucket-text">' . apply_filters( 'the_content', $bucket_description ) . '</div>';

			if ( $button_text ) {
				echo '<div class="button-area">';
				echo '<span class="button-outline"><span>' . $button_text . '</span></span>';
				echo '</div>';
			}

			echo '</div>';
			echo '</div>';

			do_action( "mosaic_{$type}_end", $data );

			if ( $button_url ) {
				echo '</a>';
			}

			echo '</div>';
		} else {
			$icon              = _::get( $data, 'icon' );
			$button_url        = _::get( $data, 'button_url' );
			$button_new_window = _::get( $data, 'button_new_window' );
			$image_caption     = _::get( $data, 'image_caption' );

			if ( "bucket-carousel" == $type ) {
				$type         = 'bucket_carousel';
				$bucket_class = $type;
			} else {
				$type         = 'bucket_grid';
				$bucket_class = 'bucket-grid ' . $color;
			}

			echo '<div class="bucket ' . $bucket_class . ' ' . $classes . '">';

			if ( $button_url ) {
				$target = ( $button_new_window ) ? 'target="_blank"' : '';
				echo '<a href="' . $button_url . '" ' . $target . '>';
			}

			echo '<div class="wrapper">';

			if ( $bucket_image ) {
				echo '<div class="bucket-image">';
				echo '<img src="' . $bucket_image . '" >';

				if ( $image_caption ) {
					echo '<div class="bucket-image-caption">' . apply_filters( 'the_content', $image_caption ) . '</div>';
				}

				echo '</div>';
			}

			echo '<div class="bucket-info center">';

			if( $bucket_headline ) {
				echo '<div class="bucket-title"><h3>' . $bucket_headline . '</h3></div>';
			}

			echo '<div class="bucket-text">';
			echo '<div class="text-wrapper">' . apply_filters( 'the_content', $bucket_description ) . '</div>';

			if ( $icon ) {
				echo '<div class="icon">';
				echo '<img src="' . $icon . '"/>';
				echo '</div>';
			}

			echo '</div>';
			echo '</div>';
			do_action( "mosaic_{$type}_end", $data );
			echo '</div>';

			if ( $button_url ) {
				echo '</a>';
			}

			echo '</div>';
		}
	}

	/**
	 * Generate a single content slider
	 *
	 * @param $data
	 *
	 */
	public function generate_content_slider( $data ) {
		$content_slider_headline = _::get( $data, 'content_slider_headline' );
		$content_slider_body     = _::get( $data, 'content_slider_body' );
		$classes                 = _::get( $data, 'additional_classes' );
		$image                   = _::get( $data, 'image' );


		echo '<div class="content-slide center ' . $classes . '">';
		echo '<div class="wrapper">';
		echo '<h2>' . $content_slider_headline . '</h2>';

		if ( $image ) {
			echo '<div class="image-wrapper">';
			echo '<img src="' . $image . '">';
			echo '</div>';
		}

		echo '<div class="content-wrapper">';
		echo '<p>' . $content_slider_body . '</p>';
		echo '</div>';
		echo '</div>';

		$this->generate_button_area( $data );

		echo '</div>';
	}

	/**
	 * Draws a single column of highlights.
	 *
	 * @param array  $highlights
	 * @param string $class
	 *
	 * @return string
	 */
	public function generate_highlight_column( $highlights, $class = '' ) {
		if ( empty( $highlights ) ) {
			return '';
		}

		$column = '<div class="highlights ' . $class . '">';

		foreach ( $highlights as $highlight ) {
			$column .= $this->generate_highlight( $highlight );
		}

		$column .= '</div>';

		return $column;
	}

	/**
	 * Generate a single highlight item
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function generate_highlight( $data ) {
		$highlight_headline = _::get( $data, 'highlight_headline' );
		$highlight_text     = _::get( $data, 'highlight_text' );
		$color              = _::get( $data, 'color' );
		$classes            = _::get( $data, 'additional_classes' );

		$highlight = '';
		$highlight .= '<div class="highlight ' . $color . ' ' . $classes . '">';
		$highlight .= '<h3 class="highlight-headline">';
		$highlight .= $highlight_headline;
		$highlight .= '</h3>';
		$highlight .= '<div class="highlight-text">';
		$highlight .= apply_filters( 'the_content', $highlight_text );
		$highlight .= '</div>';
		$highlight .= '</div>';

		return $highlight;
	}

	/**
	 * Generate a single checklist item
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function generate_checklist_item( $data ) {
		$checklist_item_headline = _::get( $data, 'checklist_item_headline' );
		$checklist_item_text     = _::get( $data, 'checklist_item_text' );
		$color                   = _::get( $data, 'color' );
		$classes                 = _::get( $data, 'additional_classes' );

		$checklist_item = '';

		$checklist_item .= '<div class="checklist-item-wrapper ' . $color . ' ' . $classes . '">';
		$checklist_item .= '<i class="checklist-icon fa fa-check-circle-o" aria-hidden="true"></i>';
		$checklist_item .= '<h3 class="checklist-item-headline">';
		$checklist_item .= $checklist_item_headline;
		$checklist_item .= '</h3>';
		$checklist_item .= '<div class="checklist-item-text">';
		$checklist_item .= $checklist_item_text;
		$checklist_item .= '</div>';
		$checklist_item .= '</div>';

		return $checklist_item;
	}

	/**
	 * Generate a single video grid item
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function generate_video( $data ) {
		$video_url = _::get( $data, 'video_url' );
		$classes   = _::get( $data, 'additional_classes' );
		$video     = '';

		$video .= '<div class="video ' . $classes . '">';
		$video .= wp_oembed_get( $video_url, [ 'height' => 400 ] );
		$video .= '</div>';

		return $video;
	}

	/**
	 * Generate a single image grid item
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function generate_image( $data ) {
		$image_url = _::get( $data, 'button_url' );
		$image     = _::get( $data, 'image' );
		$classes   = _::get( $data, 'additional_classes' );
		$caption   = _::get( $data, 'caption' );

		$image_item = '';

		$image_item    .= '<div class="image-grid-item ' . $classes . '">';
		$image_caption = '';

		if ( $caption ) {
			$image_caption = '<div class="image-caption">';
			$image_caption .= $caption;
			$image_caption .= '</div>';
		}

		if ( $image_url ) {
			$image_item .= '<a href="' . $image_url . '" target="_blank">';
			$image_item .= '<img class="" src="' . $image . '"/>';
			$image_item .= '</a>';
			$image_item .= apply_filters( 'the_content', $image_caption );
		} else {
			$image_item .= '<img class="" src="' . $image . '">';
			$image_item .= apply_filters( 'the_content', $image_caption );
		}

		$image_item .= '</div>';

		return $image_item;
	}

	/**
	 * Generate a single "Image List" panel.
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function generate_image_list_item( $data ) {
		$title                      = _::get( $data, 'image_list_item_headline' );
		$subheadline                = _::get( $data, 'image_list_item_subheadline' );
		$image_placement            = _::get( $data, 'image_item_placement' );
		$image_url                  = _::get( $data, 'image' );
		$image_list_item_text       = _::get( $data, 'image_list_item_text' );
		$text_alignment             = _::get( $data, 'text_alignment', 'middle' );
		$image_list_item_after_text = _::get( $data, 'image_list_item_after_text' );
		$classes                    = _::get( $data, 'additional_classes' );

		$image_after_text = '';
		$image            = '<div class="image">';
		$image            .= '<img src="' . $image_url . '"/>';
		$image            .= '</div>';

		$image_text = '<div class="image-info">';
		$image_text .= '<div class="image_headline">';
		$image_text .= '<h3>' . $title . '</h3>';
		$image_text .= '</div>';
		if ( $subheadline ) {
			$image_text .= '<div class="image_subheadline"><h4>' . $subheadline . '</h4></div>';
		}
		$image_text .= '<div class="image-text">';
		$image_text .= '<p>' . $image_list_item_text . '</p>';
		$image_text .= '</div>';
		$image_text .= '</div>';

		if ( $image_list_item_after_text ) {
			$image_after_text = '<div class="image-after-text">' . apply_filters( 'the_content', $image_list_item_after_text ) . '</div>';
		}

		$image_list_item = '<div class="image-list-item align-' . $text_alignment . ' ' . $classes . '">';

		if ( (int) $image_placement ) {
			$image_list_item .= '<div class="image-list-item-container"><div class="image-list-item-wrapper">' . apply_filters( 'the_content', $image_text );
			$image_list_item .= $image . '</div>';
			$image_list_item .= $image_after_text . '</div>';
		} else {
			$image_list_item .= '<div class="image-list-item-container"> <div class="image-list-item-wrapper">' . $image;
			$image_list_item .= apply_filters( 'the_content', $image_text ) . '</div>';
			$image_list_item .= $image_after_text . '</div>';
		}

		$image_list_item .= '</div>';

		return $image_list_item;
	}

	/**
	 * Render a single button section area.
	 *
	 * @param array  $data - array which should contain the button text / url as keys / values
	 * @param string $class
	 * @param bool   $echo
	 *
	 * @return string|NULL
	 */
	public function generate_button_area( $data, $class = '', $echo = TRUE ) {
		$button_url    = _::get( $data, 'button_url' );
		$button_text   = _::get( $data, 'button_text' );
		$is_new_window = _::get( $data, 'button_new_window', FALSE );
		$button        = '';

		if ( $button_url ) {
			$class  = ( $class ) ? " {$class}" : '';
			$button = '<div class="button-area' . $class . '">';

			$button_text = '<span>' . $button_text . '</span>';
			$button      .= $this->wrap_content_in_link( $button_text, $button_url, $is_new_window, 'button-outline' );

			$button .= '</div>';
		}

		if ( ! $echo ) {
			return $button;
		}

		echo $button;
	}

	/**
	 * Wrap content string in an anchor link
	 *
	 * @param string      $content
	 * @param string      $url
	 * @param bool|string $new_window
	 * @param string      $class
	 *
	 * @return string
	 */
	public function wrap_content_in_link( $content, $url = '', $new_window = FALSE, $class = '' ) {

		if ( empty( $url ) ) {
			return $content;
		}

		$target = '';

		if ( $new_window ) {
			$target = 'target="_blank"';
		}

		if ( ! empty( $class ) ) {
			$class = 'class="' . $class . '"';
		}

		return '<a href="' . $url . '" ' . $class . ' ' . $target . '>' . $content . '</a>';
	}
}
