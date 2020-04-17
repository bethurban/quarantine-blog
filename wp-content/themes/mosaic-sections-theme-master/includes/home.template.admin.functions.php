<?php

use Leafo\ScssPhp\Compiler;

/**
 * Class MosaicHomeTemplate
 * Provides functionality for custom "Home Page" sections.
 * Provides both the admin interface as well as the functionality
 * for rendering the content on the home page template.
 */
class MosaicHomeTemplateInterface {
	/**
	 * @var array
	 */
	private $editors;

	/**
	 * @var array
	 */
	private $color_schemes;

	/**
	 * @var array
	 */
	private $colors;

	/**
	 * @var array
	 */
	private $navigation_color_scheme;

	/**
	 * @var array
	 */
	private $footer_color_scheme;

	/**
	 * @var int
	 */
	private $checkbox_index = 0;

	/**
	 * @var string
	 */
	private static $message;

	/**
	 * @var string
	 */
	private static $success;

	/**
	 * @var array
	 */
	private static $templates = [
		'page-home.php',
		'page-home-with-sidebar.php'
	];

	/**
	 * @var array
	 *
	 * Custom sections that are structured:
	 * [
	 *     [ 'name'        => 'custom_section_name',
	 *       'button_icon' => 'fa-quote',
	 *       'button_text' => 'Custom Section Name'
	 *     ]
	 * ];
	 */
	private $registered_custom_sections = [];

	/**
	 * @var MosaicHomeTemplateInterface
	 */
	private static $instance;

	const COLORS_SETTING = 'mosaic_theme_colors';
	const COLORS_SCHEMES_SETTING = 'mosaic_theme_color_schemes';
	const COLORS_SCHEMES_HEADER_SETTING = 'mosaic_theme_navigation_color_scheme';
	const COLORS_SCHEMES_FOOTER_SETTING = 'mosaic_theme_footer_color_scheme';
	const MENU_SETTINGS_GROUP = 'mosaic_theme_mega_menu_settings';
	const MENU_SETTINGS = 'mosaic_theme_mega_menu';

	/**
	 * MosaicHomeTemplate constructor.
	 * Hook into all of the relevant actions in order to provide the "Sections"
	 * interface when editing the "Home Page" of the site.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'acg_admin_submenu', [ $this, 'admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ], -9999, 2 );
		add_action( 'admin_footer', [ $this, 'admin_footer' ] );
		add_action( 'wp_ajax_mosaic-template-ajax', [ $this, 'ajax_controller' ] );
		add_action( 'wp_ajax_mosaic-media-upload', [ $this, 'ajax_media_upload' ] );
		// TODO: Consolidate library ajax calls into a single Library AJAX handler
		add_action( 'wp_ajax_mosaic-save-library', [ $this, 'ajax_save_to_library' ] );
		add_action( 'wp_ajax_mosaic-load-library', [ $this, 'ajax_load_library' ] );
		add_action( 'wp_ajax_mosaic-delete-library', [ $this, 'ajax_delete_library' ] );
		add_action( 'save_post', [ $this, 'save_post_data' ], 10, 3 );
		add_action( 'dbx_post_sidebar', [ $this, 'render_fixed_navbar' ] );

		if ( is_admin_bar_showing() || defined( 'DOING_AJAX' ) ) {
			add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 999 );
			add_action( 'wp_footer', [ $this, 'wp_footer' ] );
			add_action( 'admin_footer', [ $this, 'wp_footer' ] );
			add_action( 'wp_ajax_rebuild_scss', [ $this, 'ajax_rebuild_scss' ] );
			add_action( 'wp_ajax_nopriv_rebuild_scss', [ $this, 'ajax_rebuild_scss' ] );
		}

		add_filter( 'use_block_editor_for_post_type', [ $this, 'use_block_editor_for_post_type' ] );

		$this->load_colors();

		self::$instance = $this;
	}

	/**
	 * Used to override the WP block editor if the page is set to use the "Sections" template.
	 * Required as of WP v5.0
	 *
	 * @param bool $use
	 *
	 * @return bool
	 */
	public function use_block_editor_for_post_type( $use ) {
		return ( ( $this->is_page_using_home_template() ) ? FALSE : $use );
	}

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			var_dump( "HAD TO CONSTRUCT<br>WHY, YODA?" );
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function admin_init() {
		register_setting( self::MENU_SETTINGS_GROUP, self::MENU_SETTINGS, [
			__CLASS__,
			'process_menu_save'
		] );

		$this->registered_custom_sections = apply_filters( 'mosaic_register_sections', [] );
	}

	/**
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
		$args = [
			'id'    => 'mosaic-compile-scss',
			'title' => 'Rebuild CSS',
			'href'  => '#',
			'meta'  => [ 'class' => 'mosaic-compile-scss' ]
		];

		$wp_admin_bar->add_menu( $args );
	}

	public function render_fixed_navbar() {
		if ( ! $this->is_page_using_home_template() ) {
			return;
		}

		echo '<div id="mosaic-admin-nav">';
		echo '<div class="left">';
		echo '<a class="button button-default collapse-sections-all"><i class="fa fa-compress"></i><span>Collapse</span> All Sections</a>';
		echo '</div>';
		echo '<div class="center">';
		echo '<a class="button button-default section-chooser">Add Section</a>';
		echo '<a class="button button-default library-chooser">Library</a>';
		echo '</div>';
		echo '<div class="center">';
		echo '<label>Jump To</label>';
		echo '<select id="mosaic-section-jump"></select>';
		echo '</div>';
		echo '<div class="right">';
		echo '<input name="save" type="submit" class="button button-primary button-large" id="publish" value="Update">';
		echo '</div>';
		echo '</div>';
	}

	public function wp_footer() {
		if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
			return;
		}

		wp_print_scripts( 'jquery' );
		?>
        <style>
            #wpadminbar #wp-admin-bar-mosaic-compile-scss .ab-item::before {
                content: "\f308";
            }

            #wpadminbar #wp-admin-bar-mosaic-compile-scss.busy .ab-item::before {
                content: "\f463";
                animation: dashicons-spin 1s infinite linear;
            }

            @media only screen and (max-width: 782px) {
                #wpadminbar #wp-admin-bar-mosaic-compile-scss {
                    display: block;
                }

                #wpadminbar #wp-admin-bar-mosaic-compile-scss .ab-item {
                    text-indent: 100%;
                    white-space: nowrap;
                    overflow: hidden;
                    width: 52px;
                    padding: 0;
                    color: #a0a5aa;
                    position: relative;
                }

                #wpadminbar #wp-admin-bar-mosaic-compile-scss .ab-item::before {
                    display: block;
                    text-indent: 0;
                    font: 400 32px/1 dashicons;
                    speak: none;
                    top: 7px;
                    width: 52px;
                    text-align: center;
                }
            }

            @keyframes dashicons-spin {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }
        </style>

		<?php
	}

	public function ajax_rebuild_scss() {
		$result  = self::process_sass( TRUE );
		$success = ( ! empty( $result ) );
		echo json_encode( [ 'success' => $success ] );
		wp_die();
	}

	/**
	 * Attempt to load the scripts on ONLY the desired page.
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {
		$this->enqueue_scripts_edit_page( $hook );
		$this->enqueue_scripts_mega_menu( $hook );
	}

	/**
	 * Load the scripts for the "Edit Page" interface.
	 * Should only enqueue scripts if it is in fact the right page, and the page has the "Home Page" template selected
	 *
	 * @param $hook
	 */
	private function enqueue_scripts_edit_page( $hook ) {
		if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
			return;
		}

		if ( ! $this->is_page_using_home_template() ) {
			return;
		}

		$posts = $this->get_posts();

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'mosaic-utilities', get_stylesheet_directory_uri() . '/js/utilities.admin.jquery.js' );
		wp_register_script( 'mosaic-home-template', get_stylesheet_directory_uri() . '/js/home.template.admin.jquery.js', [
			'jquery',
			'mosaic-utilities'
		] );
		wp_localize_script( 'mosaic-home-template', 'MosaicHome', [
			'colorSchemes' => $this->color_schemes,
			'posts'        => $posts
		] );
		wp_enqueue_script( 'mosaic-home-template' );
		wp_enqueue_style( 'mosaic-font-awesome', get_stylesheet_directory_uri() . '/font-awesome/css/font-awesome.min.css">' );
		wp_enqueue_style( 'mosaic-home-template', get_stylesheet_directory_uri() . '/css/admin-home-template.css' );
	}

	/**
	 * Load the scripts for the "Mega Menu" interface.
	 * Should only enqueue scripts if it is fact the "Mega Menu" screen.
	 *
	 * @param $hook
	 */
	private function enqueue_scripts_mega_menu( $hook ) {
		if ( 'mosaic-sections-theme_page_mosaic_manage_menu' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'mosaic-utilities', get_stylesheet_directory_uri() . '/js/utilities.admin.jquery.js' );
		wp_enqueue_script( 'mosaic-mega-menu', get_stylesheet_directory_uri() . '/js/mega-menu.admin.jquery.js', [
			'jquery',
			'jquery-ui-sortable',
			'mosaic-utilities'
		] );

		wp_enqueue_style( 'mosaic-home-template', get_stylesheet_directory_uri() . '/css/admin-home-template.css' );
	}

	public function admin_menu( $slug ) {
		add_submenu_page( $slug, 'Mosaic Sections Colors', 'Color Schemes', 'manage_options', 'mosaic_manage_colors', [
			$this,
			'color_settings'
		] );

		add_submenu_page( $slug, 'Mega Menu Settings', 'Mega Menu', 'manage_options', 'mosaic_manage_menu', [
			$this,
			'manage_mega_menu'
		] );
	}

	/**
	 * Add the meta-boxes to the "Edit Page" interface.
	 *
	 * @param string $post_type
	 * @param object $post
	 */
	public function add_meta_box( $post_type, $post ) {
		if ( ! $this->is_page_using_home_template() ) {
			return;
		}

		add_meta_box( 'mosaic-home-template-controls', 'Choose Section', [
			$this,
			'render_meta_box_controls'
		], 'page', 'side', 'high' );

		add_meta_box( 'mosaic-home-parallax-option', 'Page Loading Effects', [
			$this,
			'render_page_loading_effects_meta_box'
		], 'page', 'side', 'high' );
		add_meta_box( 'mosaic-home-template-sections', 'Home Page Sections', [
			$this,
			'render_meta_box_sections'
		], 'page', 'normal', 'high' );

		// NOTE: "Background Images" rendered in functions file, used on all pages
	}

	/**
	 * Utility to check if is a "page".
	 * Abstracted in case we want to refactor later, can do in one place.
	 *
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function is_page( $post_type ) {
		return ( 'page' == $post_type );
	}

	/**
	 * Determines if the current page in the editor is a page, and if it's using the "sections" template.
	 *
	 * @return bool
	 */
	private function is_page_using_home_template() {
		global $post;

		if ( ! $post ) {
			return FALSE;
		}

		if ( ! $this->is_page( $post->post_type ) ) {
			return FALSE;
		}

		if ( ! $this->is_home_template( $post ) ) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Utility to check if it the page is using the "home template".
	 * Abstracted in case we want to refactor later, can do it in one place.
	 *
	 * @param object $post
	 *
	 * @return bool
	 */
	public static function is_home_template( $post ) {
		// Check if it's the right page template
		if ( ! is_object( $post ) ) {
			return FALSE;
		}

		return ( in_array( get_post_meta( $post->ID, '_wp_page_template', TRUE ), self::$templates ) );
	}

	/**
	 * Generate the Meta Box for Page Loading Effects dropdown
	 *
	 * @param $post
	 */
	public function render_page_loading_effects_meta_box( $post ) {
		$parallax_option = get_post_meta( $post->ID, '_page_loading_effects', TRUE );
		$name            = '_page_loading_effects';
		$opts            = [
			'0' => 'No',
			'1' => 'Yes'
		];

		echo $this->create_dropdown( $name, $opts, $parallax_option );
	}

	/**
	 * Generate the Meta Box with the "Controls" for the section interface
	 */
	public function render_meta_box_controls() { ?>
        <div id="add-section">
            <a href="javascript:void(0);" class="button button-default section-chooser">Add Section</a>
        </div>
		<?php
	}

	public function admin_footer() {
		global $post;

		if ( 'post' != get_current_screen()->base || 'page' != get_current_screen()->id ) {
			return;
		}

		$type = get_current_screen()->post_type;

		if ( $type == '' && ! isset( $post ) ) {
			return;
		}

		if ( ! $this->is_home_template( $post ) ) {
			return;
		}

		?>
        <div id="section-chooser-lightbox" class="chooser-lightbox">
            <div class="section-list">
                <div class="controls">
                    <input type="text" class="search section-search" placeholder="Search Sections">
                    <a href="javascript:void(0);" class="lightbox-close">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
                <a href="javascript:void(0);" class="item add-section" data-section="content-area"><span
                            class="fa fa-align-left"></span>Content Area</a>
                <a href="javascript:void(0);" class="item add-section" data-section="content-slider"><span
                            class="fa fa-film fa-rotate-90"></span>Content
                    Slider</a>
                <a href="javascript:void(0);" class="item add-section" data-section="bucket-grid"><span
                            class="fa fa-th"></span>Featured Bucket
                    Grid</a>
                <a href="javascript:void(0);" class="item add-section" data-section="bucket-carousel"><span
                            class="fa fa-th-large"></span>Featured Bucket
                    Carousel</a>
                <a href="javascript:void(0);" class="item add-section" data-section="bucket-panels"><span
                            class="fa fa-clone"></span>Featured Bucket
                    Panels</a>
                <a href="javascript:void(0);" class="item add-section" data-section="bucket-stats"><span
                            class="fa fa-bar-chart"></span>Featured Bucket
                    Stats</a>
                <a href="javascript:void(0);" class="item add-section" data-section="bucket-overlay"><span
                            class="fa fa-th"></span>Featured Bucket
                    Overlay</a>
                <a href="javascript:void(0);" class="item add-section" data-section="product-highlight"><span
                            class="fa fa-bolt"></span>Product
                    Highlight</a>
                <a href="javascript:void(0);" class="item add-section" data-section="cta"><span
                            class="fa fa-phone"></span>Call-to-Action</a>
                <a href="javascript:void(0);" class="item add-section" data-section="donate-callout"><span
                            class="fa fa-money"></span>Callout</a>
                <a href="javascript:void(0);" class="item add-section" data-section="video-grid"><span
                            class="fa fa-video-camera"></span>Video Grid</a>
                <a href="javascript:void(0);" class="item add-section" data-section="image-grid"><span
                            class="fa fa-picture-o"></span>Image Grid</a>
                <a href="javascript:void(0);" class="item add-section" data-section="checklist"><span
                            class="fa fa-list-ul"></span>Checklist</a>
                <a href="javascript:void(0);" class="item add-section" data-section="split"><span
                            class="fa fa-columns"></span>Split Grid</a>
                <a href="javascript:void(0);" class="item add-section" data-section="banner"><span
                            class="fa fa-map-o"></span>Hero Banner</a>
                <a href="javascript:void(0);" class="item add-section" data-section="image-list"><span
                            class="fa fa-th-list"></span>Image List</a>
                <a href="javascript:void(0);" class="item add-section" data-section="image-comparison"><span
                            class="fa fa-picture-o"></span>Image Comparison</a>
                <a href="javascript:void(0);" class="item add-section" data-section="contact-form"><span
                            class="fa fa-user"></span>Contact Form</a>
                <a href="javascript:void(0);" class="item add-section" data-section="video-hero"><span
                            class="fa fa-camera"></span>Video Hero</a>
                <a href="javascript:void(0);" class="item add-section" data-section="accordions"><span
                            class="fa fa-bars"></span>Accordions</a>
                <a href="javascript:void(0);" class="item add-section" data-section="recent-posts"><span
                            class="fa fa-newspaper-o"></span>Recent Posts</a>
				<?php $this->render_custom_section_buttons(); ?>
            </div>
        </div>
		<?php
		$classes = $this->utility_classes();
		?>
        <div id="section-class-chooser-lightbox" class="chooser-lightbox">
            <div class="section-list class-list">
                <div class="controls">
                    <input type="text" class="search class-search" placeholder="Search Classes">
                    <a href="javascript:void(0);" class="lightbox-close">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
				<?php
				foreach ( $classes AS $class => $description ) {
					echo '<a href="javascript:void(0);" class="item add-class" data-class="' . $class . '">' . $class . '<span class="description">' . $description . '</span></a>';
				}
				?>
            </div>
        </div>
        <div id="section-library-save-lightbox" class="chooser-lightbox">
            <div class="section-list library-save">
                <div class="controls">
                    <a href="javascript:void(0);" class="lightbox-close">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
                <div>
                    <span class="section-title"></span>
                </div>
                <div class="section-name">
                    <label>Name</label>
                    <input type="text" name="section_name" value="">
                </div>
                <div class="submit"><a class="button button-primary save-library" href="javascript:void(0);">Save to
                        Library</a></div>
                <h3>Existing Sections</h3>
                <div class="library"></div>
            </div>
        </div>
        <div id="section-library-lightbox" class="chooser-lightbox">
            <div class="section-list library-chooser">
                <div class="controls">
                    <input type="text" class="search section-search" placeholder="Search Sections">
                    <a href="javascript:void(0);" class="lightbox-close">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
                <h3>Sections Available</h3>
                <div class="library"></div>
            </div>
        </div>
		<?php

		require_once ABSPATH . 'wp-includes/class-wp-editor.php';
		_WP_Editors::wp_link_dialog();
	}

	public function get_posts( $type = 'post' ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='{$type}' AND post_status='publish'" );
	}

	public function render_custom_section_buttons() {
		foreach ( $this->registered_custom_sections as $section ) {
			echo '<a href="javascript:void(0);" class="item add-section" data-section="' . $section['name'] . '"><span
                    class="fa ' . $section['button_icon'] . '"></span>' . $section['button_text'] . '</a>';
		}
	}

	/**
	 * Wrapper around "Get" meta box sections.
	 * Used for initial load (via PHP).
	 *
	 * @param object $post
	 */
	public function render_meta_box_sections( $post ) {
		echo '<div id="mosaic-home-sections">';

		$existing = get_post_meta( $post->ID, '_mosaic_home_sections', TRUE );

		$section_id = 0;

		if ( $existing ) {
			foreach ( $existing AS $section ) {
				$type                  = $section['type'];
				$section['section_id'] = $section_id++;
				$this->admin_render_section( $type, $section );
			}
		}

		echo '</div>';
	}

	/**
	 * Admin "Edit Page" Interface Controller called by AJAX calls
	 * Renders the appropriate section and returns the response as JSON.
	 */
	public function ajax_controller() {
		$section    = _::get( $_POST, 'section' );
		$section_id = _::get( $_POST, 'section_id' );
		$child_id   = _::get( $_POST, 'child_id' );
		$sub_type   = _::get( $_POST, 'sub_type' );
		$data       = _::get( $_POST, [ 'section_data', 'data' ] );

		if ( empty( $data ) ) {
			$data = [];
		}

		// data sent via AJAX request is JSON-encoded, which is adding slashes to quotes, etc.
		$data = stripslashes_deep( $data );

		if ( ! $sub_type ) {
			$data['section_id'] = $section_id;
			$response           = $this->admin_get_section( $section, $data );
		} else if ( FALSE !== stripos( $sub_type, 'bucket' ) ) {
			$data['section_id'] = $section_id;
			$data['bucket_id']  = $child_id;
			$data['type']       = $sub_type;
			$response           = $this->get_bucket( $data );
		} else if ( 'content-slider' == $sub_type ) {
			$data['section_id']        = $section_id;
			$data['content_slider_id'] = $child_id;
			$response                  = $this->get_content_slider( $data );
		} else if ( 'product-highlight' == $sub_type ) {
			$data['section_id']   = $section_id;
			$data['highlight_id'] = $child_id;
			$response             = $this->get_highlight( $data );
		} else if ( FALSE !== stripos( $sub_type, 'checklist' ) ) {
			$data['section_id']        = $section_id;
			$data['checklist_item_id'] = $child_id;
			$response                  = $this->get_checklist_item( $data );
		} else if ( FALSE !== stripos( $sub_type, 'video' ) ) {
			$data['section_id'] = $section_id;
			$data['video_id']   = $child_id;
			$response           = $this->get_video_item( $data );
		} else if ( 'image-grid' == $sub_type ) {
			$data['section_id'] = $section_id;
			$data['image_id']   = $child_id;
			$response           = $this->get_image_item( $data );
		} else if ( 'image-list' == $sub_type ) {
			$data['section_id']         = $section_id;
			$data['image_list_item_id'] = $child_id;
			$response                   = $this->get_image_list_item( $data );
		} else if ( 'accordions' == $sub_type ) {
			$data['section_id']   = $section_id;
			$data['accordion_id'] = $child_id;
			$response             = $this->get_accordion_item( $data );
		} else {
			echo 'What the?';
			die();
		}

		wp_send_json( $response );
	}

	/**
	 * Utility wrapper around "get_section".
	 * This function actually renders the section.
	 * Used on initial load of the "Edit Page" screen.
	 *
	 * @param string $type
	 * @param array  $data
	 */
	public function admin_render_section( $type, $data ) {
		$response = $this->admin_get_section( $type, $data );

		$section_id = _::get( $data, 'section_id' );
		$hidden     = _::get( $data, 'hide', FALSE );
		$hidden     = ( $hidden ) ? ' checked' : '';

		echo '<div class="section" data-section-type="' . $type . '" id="mosaic-section-' . $data['section_id'] . '">';
		echo '<div class="section-title">' . $response['title'];
		echo '<span class="content-title"></span>';
		echo '<a class="delete-section"><span class="dashicons dashicons-no"></span></a>';
		echo '<a class="collapse-section" title="Collapse Section"><span class="fa fa-compress"></span></a>';
		echo '<a class="save-section"><span class="fa fa-bookmark-o"></span></a>';
		echo '<label class="hide-section">Hide <input type="checkbox" name="section[' . $section_id . '][hide]"' . $hidden . '></label>';
		echo '</div>';
		echo '<div class="section-body">' . $response['html'] . '</div>';
		echo '</div>';
	}

	/**
	 * Workhorse of the admin interface "section" functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param string $type
	 * @param array  $data
	 *
	 * @return array
	 */
	public function admin_get_section( $type, $data = [] ) {
		ob_start();

		$data = apply_filters( 'mosaic_admin_section', $data, $type );

		// Ensure 'type' gets set for empty/new sections
		if ( empty( $data['type'] ) ) {
			$data['type'] = $type;
		}

		$this->editors = [];

		switch ( $type ) {
			case "content-area":
				$title             = 'Content Area';
				$editor_id         = $this->generate_editor_id();
				$wplink_id         = $this->generate_editor_id( 'wplink' );
				$data['editor_id'] = $editor_id;
				$data['wplink_id'] = $wplink_id;
				$this->render_content_area( $data );
				break;
			case "cta":
				$title             = 'Call to Action';
				$editor_id         = $this->generate_editor_id();
				$wplink_id         = $this->generate_editor_id( 'wplink' );
				$data['editor_id'] = $editor_id;
				$data['wplink_id'] = $wplink_id;
				$this->render_cta_meta_box( $data );
				break;
			case "banner":
				$title             = 'Hero Banner';
				$editor_id         = $this->generate_editor_id();
				$data['editor_id'] = $editor_id;
				$wplink_id         = $this->generate_editor_id( 'wplink' );
				$data['wplink_id'] = $wplink_id;
				$this->render_banner_meta_box( $data );
				break;
			case "content-slider":
				$title = 'Content Slider';
				$this->render_content_slider_meta_box( $data );
				break;
			case "bucket-grid":
				$title             = 'Featured Buckets - Grid';
				$editor_id         = $this->generate_editor_id();
				$data['editor_id'] = $editor_id;
				$this->render_featured_buckets_meta_box( $data );
				break;
			case "bucket-carousel":
				$title             = 'Featured Buckets - Carousel';
				$editor_id         = $this->generate_editor_id();
				$data['editor_id'] = $editor_id;
				$data['type']      = 'bucket-carousel';
				$this->render_featured_buckets_meta_box( $data );
				break;
			case "bucket-panels":
				$title             = 'Featured Buckets - Panels';
				$editor_id         = $this->generate_editor_id();
				$data['type']      = 'bucket-panels';
				$data['editor_id'] = $editor_id;
				$this->render_featured_buckets_meta_box( $data );
				break;
			case "bucket-stats":
				$title             = 'Featured Buckets - Stats';
				$editor_id         = $this->generate_editor_id();
				$data['type']      = 'bucket-stats';
				$data['editor_id'] = $editor_id;
				$this->render_featured_buckets_meta_box( $data );
				break;
			case "bucket-overlay":
				$title             = 'Featured Buckets - Overlay';
				$editor_id         = $this->generate_editor_id();
				$data['type']      = 'bucket-overlay';
				$data['editor_id'] = $editor_id;
				$this->render_featured_buckets_meta_box( $data );
				break;
			case "product-highlight":
				$title             = 'Product Highlight';
				$wplink_id         = $this->generate_editor_id( 'wplink' );
				$data['wplink_id'] = $wplink_id;
				$this->render_product_highlight_meta_box( $data );
				break;
			case "donate-callout":
				$title             = 'Callout';
				$wplink_id         = $this->generate_editor_id( 'wplink' );
				$data['wplink_id'] = $wplink_id;
				$editor_id         = $this->generate_editor_id();
				$data['editor_id'] = $editor_id;
				$this->render_callout_meta_box( $data );
				break;
			case "video-grid":
				$title = 'Video Grid';
				$this->render_video_grid_meta_box( $data );
				break;
			case "image-grid":
				$title = 'Image Grid';
				$this->render_image_grid_meta_box( $data );
				break;
			case "contact-form":
				$title             = 'Contact Form';
				$editor_id         = $this->generate_editor_id();
				$data['editor_id'] = $editor_id;
				$this->render_contact_form_meta_box( $data );
				break;
			case "checklist":
				$title = 'Checklist';
				$this->render_checklist_meta_box( $data );
				break;
			case "image-list":
				$title = 'Image List';
				$this->render_image_list_meta_box( $data );
				break;
			case "split":
				$title                    = 'Split Grid';
				$data['type']             = $type;
				$data['editors']['left']  = $this->generate_editor_id();
				$data['editors']['right'] = $this->generate_editor_id();
				$this->render_split_grid_meta_box( $data );
				break;
			case "image-comparison":
				$title = 'Image comparison';
				$this->render_image_comparison_meta_box( $data );
				break;
			case "video-hero":
				$title             = 'Video Hero';
				$data['editor_id'] = $this->generate_editor_id();
				$this->render_video_hero_meta_box( $data );
				break;
			case "accordions":
				$title = 'Accordions';
				$this->render_accordion_meta_box( $data );
				break;
			case 'recent-posts':
				$title = 'Recent Posts';
				$this->render_recent_posts_meta_box( $data );
				break;
			default:
				$title = $data['title'];
				$this->render_custom_section( $type, $data );
				break;
		}

		$this->section_options( $data );
		$section_id = _::get( $data, 'section_id' );
		echo '<input type="hidden" name="section[' . $section_id . '][type]" value="' . $type . '">';
		$html = ob_get_clean();

		$response = [
			'type'    => $type,
			'title'   => $title,
			'html'    => $html,
			'editors' => apply_filters( 'mosaic_section_admin_editors', $this->editors, $data ),
		];

		return $response;
	}

	/**
	 * Renders admin interface of registered custom section of type => $type
	 *
	 * @param string $type
	 * @param array  $data
	 */
	function render_custom_section( $type, $data ) {
		$section  = _::find( $this->registered_custom_sections, [ 'name' => $type ] );
		$callback = $section['admin_section'];

		if ( empty( $callback ) ) {
			return;
		}

		if ( ! is_callable( $callback ) ) {
			return;
		}

		// Run callback. Pass section data and instance of this class
		call_user_func( $callback, $data, $this );
	}

	/**
	 * Workhorse of the admin interface "content-slider" functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	function get_content_slider( $data = [] ) {
		$data['wplink_id'] = $this->generate_editor_id( 'wplink' );
		ob_start();
		$this->generate_content_slider( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Workhorse of the admin interface "bucket" functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	function get_bucket( $data = [] ) {
		ob_start();
		$data['wplink_id'] = $this->generate_editor_id( 'wplink' );
		$this->generate_bucket( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Workhorse of the admin interface "highlight" functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	function get_highlight( $data = [] ) {
		ob_start();
		$this->generate_highlight( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Workhorse of the admin interface "checklist" functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	function get_checklist_item( $data = [] ) {
		ob_start();
		$this->generate_checklist_item( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Workhorse of the admin interface "video" functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	function get_video_item( $data = [] ) {
		ob_start();
		$this->generate_video( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Workhorse of the admin interface "image" functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 */
	function get_image_item( $data = [] ) {
		ob_start();
		$data['wplink_id'] = $this->generate_editor_id( 'wplink' );
		$this->generate_image( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Workhorse of the admin interface 'image-list' functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	function get_image_list_item( $data = [] ) {
		ob_start();
		$data['wplink_id'] = $this->generate_editor_id( 'wplink_id' );
		$this->generate_image_list_item( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Workhorse of the admin interface 'accordion' functionality.
	 * Sets up the HTML, title, and other relevant data for the section.
	 * Returns it as an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	function get_accordion_item( $data = [] ) {
		ob_start();
		$this->generate_accordion( $data );
		$html = ob_get_clean();

		$response = [
			'section_id' => _::get( $data, 'section_id' ),
			'html'       => $html
		];

		return $response;
	}

	/**
	 * Renders the "Content Area" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data     - [
	 *                  'section_id'  => id of the section (integer)
	 *                  'editor_id'   => id (string, see generate_editor_id) to use for content editor,
	 *                  'content'     => HTML content that goes into content editor,
	 *                  ]
	 *
	 */
	public function render_content_area( $data ) {
		$this->generate_editor( $data, 'is-title partial-title' );
	}

	/**
	 * Renders the "CTA" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data     - [
	 *                  'section_id'           => id of the section (integer)
	 *                  'editor_id'            => id (string, see generate_editor_id) to use for content editor,
	 *                  'content'              => HTML content that goes into content editor,
	 *                  'button_text'          => text that goes into the button text input,
	 *                  'button_url'           => URL that goes into the button url input,
	 *                  'button_new_window'    => On / Off - if button opens link in new window
	 *                  ]
	 */
	public function render_cta_meta_box( $data ) {
		$this->generate_editor( $data, 'is-title partial-title' );
		$this->generate_button_input( $data );
	}

	/**
	 * Renders the "Hero Banner" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data     - [
	 *                  'section_id'           => id of the section (integer)
	 *                  'editor_id'            => id (string, see generate_editor_id) to use for content editor,
	 *                  'content'              => HTML content that goes into content editor,
	 *                  'text_placement'       => 'Left', 'Right', 'Center',
	 *                  'text_alignment'       => 'Top', 'Bottom', 'Middle',
	 *                  'button_text'          => text that goes into the button text input,
	 *                  'button_url'           => URL that goes into the button url input,
	 *                  'button_new_window'    => On / Off - if button opens link in new window,
	 *                  ]
	 *
	 */
	public function render_banner_meta_box( $data ) {
		$image          = _::get( $data, 'image' );
		$section_id     = _::get( $data, 'section_id' );
		$text_alignment = _::get( $data, 'text_alignment' );
		$text_placement = _::get( $data, 'text_placement' );

		$this->generate_image_input( $image, $section_id );
		$this->generate_editor( $data, 'is-title partial-title' );

		echo '<div class="hero-banner-text-placement">';
		$name = 'section[' . $section_id . '][text_placement]';
		echo '<label>Content Horizontal Alignment: </label>';

		echo $this->create_dropdown( $name, [
			'left'   => 'Left',
			'right'  => 'Right',
			'center' => 'Center'
		], $text_placement );

		echo '</div>';

		echo '<div class="hero-banner-text-alignment">';
		$name = 'section[' . $section_id . '][text_alignment]';
		echo '<label>Content Vertical Alignment: </label>';

		echo $this->create_dropdown( $name, [
			'top'    => 'Top',
			'middle' => 'Middle',
			'bottom' => 'Bottom'
		], $text_alignment );

		echo '</div>';

		$this->generate_button_input( $data );
	}

	/**
	 * Renders the "Content Slider" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data     - [
	 *                  'section_id'      => id of the section (integer)
	 *                  'editor_id'       => id (string, see generate_editor_id) to use for content editor,
	 *                  'headline'        => HTML markup for the section headline
	 *                  'content_sliders' => an array of 'content' slides
	 *                  ]
	 *
	 */
	public function render_content_slider_meta_box( $data ) {
		$section_id      = _::get( $data, 'section_id' );
		$headline        = _::get( $data, 'headline' );
		$content_sliders = _::get( $data, 'content_sliders' );

		echo '<div class="content-sliders-wrapper">';
		echo '<p><label>Content Slider Headline</label>';
		echo '<input type="text" class="widefat is-title" placeholder="Content Slider Headline" name="section[' . $section_id . '][headline]" value="' . esc_attr( $headline ) . '"/></p>';
		echo '<div class="content-sliders sub-contents sortable">';

		if ( empty( $content_sliders ) ) {
			$content_sliders = [
				'content_slider_id' => 0,
				'section_id'        => $section_id,
			];

			$this->generate_content_slider( $content_sliders );
		} else {
			$index = 0;

			foreach ( $content_sliders AS $content_slide ) {
				$content_slide ['content_slider_id'] = $index++;
				$content_slide['section_id']         = $section_id;

				$this->generate_content_slider( $content_slide );
			}
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="content-slider">Add Slide</a></p>';
		echo '</div>';
	}

	/**
	 * Renders the "Featured Buckets" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data     - [
	 *                  'section_id'          => id of the section (integer),
	 *                  'editor_id'           => id (string, see generate_editor_id) to use for content editor,
	 *                  'buckets_title'       => text that goes into the buckets title input,
	 *                  'buckets_subheadline' => text that goes into the buckets subheadline input,
	 *                  'buckets'             => an array of containing 'bucket' elements and used to generate bucket
	 *                  sub-content,
	 *                  'buckets_text'        => text that goes into 'buckets text' textarea (accepts HTML markup),
	 *                  ]
	 *
	 */
	public function render_featured_buckets_meta_box( $data ) {
		$section_id   = _::get( $data, 'section_id' );
		$title        = _::get( $data, 'buckets_title' );
		$sub_headline = _::get( $data, 'buckets_subheadline' );
		$buckets      = _::get( $data, 'buckets' );
		$buckets_text = _::get( $data, 'buckets_text' );

		echo '<div class="bucket-wrapper">';
		echo '<div class="buckets sub-contents sortable">';

		echo '<p><label>Featured Bucket Headline</label>';
		echo '<input type="text" class="widefat is-title" placeholder="Feature Bucket Title" name="section[' . $section_id . '][buckets_title]" value="' . esc_attr( $title ) . '"/></p>';
		echo '<p><label>Featured Bucket Subheadline</label>';
		echo '<input type="text" class="widefat" placeholder="Feature Bucket Subheadline" name="section[' . $section_id . '][buckets_subheadline]" value="' . esc_attr( $sub_headline ) . '"/></p>';

		if ( empty( $buckets ) ) {
			$buckets = [ [] ];
		}

		$bucket_id = 0;

		foreach ( $buckets AS $bucket_data ) {
			$bucket_data['section_id'] = $section_id;
			$bucket_data['bucket_id']  = $bucket_id++;
			$bucket_data['type']       = _::get( $data, 'type' );
			$bucket_data['wplink_id']  = $this->generate_editor_id( 'wplink_id' );
			$this->generate_bucket( $bucket_data );
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="bucket">Add Bucket</a></p>';
		echo '<p><label>Featured Buckets Text</label>';
		echo '<textarea class="widefat" placeholder="Feature Bucket Text" name="section[' . $section_id . '][buckets_text]">' . esc_textarea( $buckets_text ) . '</textarea></p>';
		echo '</div>';
	}

	/**
	 * Renders the "Product Highlight" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data     - [
	 *                  'section_id'                   => id of the section (integer),
	 *                  'editor_id'                    => id (string, see generate_editor_id) to use for content editor,
	 *                  'product_highlight_title'      => text for section title (accepts HTML markup),
	 *                  'product_highlight_sub_title'  => text for section subtitle (accepts HTML markup),
	 *                  'image'                        => image input 'field' for choosing Product Highlight Image,
	 *                  'highlights_placement'         => 'Left', 'Right',  'Both',
	 *                  'highlights'                   => an array containing 'highlight' items
	 *                  ]
	 *
	 */
	public function render_product_highlight_meta_box( $data ) {
		$highlights           = _::get( $data, 'highlights' );
		$image                = _::get( $data, 'image' );
		$section_id           = _::get( $data, 'section_id' );
		$highlights_placement = _::get( $data, 'highlights_placement' );

		$title     = _::get( $data, 'product_highlight_title' );
		$sub_title = _::get( $data, 'product_highlight_sub_title' );

		echo '<div class="highlight-wrapper">';

		$this->generate_image_input( $image, $section_id );

		echo '<p><label>Product Highlight Title</label></p>';
		echo '<input type="text" class="widefat is-title" placeholder="Product Highlight Title" name="section[' . $section_id . '][product_highlight_title]" value="' . esc_attr( $title ) . '"/></p>';

		echo '<p><label>Product Highlight Subtitle</label></p>';

		echo '<input type="text" class="widefat" placeholder="Product Highlight Subtitle" name="section[' . $section_id . '][product_highlight_sub_title]" value="' . esc_attr( $sub_title ) . '"/></p>';
		echo '<div class="highlights sub-contents sortable">';
		echo '<p><label>Highlight Text Placement:</label> ';

		$name = 'section[' . $section_id . '][highlights_placement]';

		echo $this->create_dropdown( $name, [
			'left'  => 'Left',
			'right' => 'Right',
			'both'  => 'Both'
		], $highlights_placement );

		echo '</p>';

		if ( empty( $highlights ) ) {
			$highlights = [
				'highlight_id' => 0,
				'section_id'   => $section_id
			];

			$this->generate_highlight( $highlights );
		} else {
			$index = 0;

			foreach ( $highlights AS $highlights_data ) {
				$highlights_data['highlight_id'] = $index++;
				$highlights_data['section_id']   = $section_id;

				$this->generate_highlight( $highlights_data );
			}
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="highlight">Add Highlight</a></p>';
		echo '</div>';

		$this->generate_button_input( $data );
	}

	/**
	 * Renders the "Contact Form" Section interface (when editing a page in the dashboard).
	 *
	 * @param $data     - [
	 *                  'section_id'  => id of the section (integer)
	 *                  'form_option' => string of selected option,
	 *                  ]
	 *
	 */
	public function render_contact_form_meta_box( $data ) {
		$selected_option = _::get( $data, 'form_option' );
		$section_id      = _::get( $data, 'section_id' );
		$name            = 'section[' . $section_id . '][form_option]';

		if ( function_exists( 'ACGForms' ) ) {
			$forms        = ACGForms()->getAllForms();
			$form_entries = [];
			$id           = 0;

			foreach ( $forms as $form => $form_values ) {
				foreach ( $form_values as $key => $form_title ) {
					if ( $key == 'id' ) {
						$id = $form_title;
					}

					if ( $key == 'form_title' ) {
						$form_entries[ $id ] = $form_title;
					}
				}
			}

			echo '<p><label>Choose Contact Form:</label> ';
			echo $this->create_dropdown( $name, $form_entries, $selected_option );
		} else {
			echo '<div class="section-error">This section requires the ACG Form Builder plugin</div>';
		}
	}

	/**
	 * Renders the Checklist Meta Box
	 *
	 * @param $data     - [
	 *                  'section_id'            => id of the section (integer)
	 *                  'image'                 => image URL that used to generate image input box,
	 *                  'checklist_title'       => text that goes into the Checklist Title input,
	 *                  'checklist_sub_title'   => text that goes into the Checklist Subtitle input,
	 *                  'checklist_items'       => an array used to render sub-content items,
	 *                  ]
	 */
	public function render_checklist_meta_box( $data ) {
		$section_id = _::get( $data, 'section_id' );
		$image      = _::get( $data, 'image' );
		$title      = _::get( $data, 'checklist_title' );
		$sub_title  = _::get( $data, 'checklist_sub_title' );

		$checklist_items = _::get( $data, 'checklist_items' );

		echo '<div class="checklist-wrapper">';

		$this->generate_image_input( $image, $section_id );

		echo '<p><label>Checklist Title</label></p>';
		echo '<input type="text" class="widefat is-title" placeholder="Checklist Title" name="section[' . $section_id . '][checklist_title]" value="' . esc_attr( $title ) . '"/></p>';

		echo '<p><label>Checklist Subtitle</label></p>';
		echo '<input type="text" class="widefat" placeholder="Checklist Subtitle" name="section[' . $section_id . '][checklist_sub_title]" value="' . esc_attr( $sub_title ) . '"/></p>';

		echo '<div class="checklist-items sub-contents sortable">';

		if ( empty( $checklist_items ) ) {
			$checklist_items = [
				'checklist_item_id' => 0,
				'section_id'        => $section_id
			];

			$this->generate_checklist_item( $checklist_items );
		} else {
			$index = 0;

			foreach ( $checklist_items AS $checklist_item ) {
				$checklist_item ['checklist_item_id'] = $index++;
				$checklist_item['section_id']         = $section_id;

				$this->generate_checklist_item( $checklist_item );
			}
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="checklist">Add Checklist Item</a></p>';
		echo '</div>';
	}

	/**
	 * Render Callout Meta Box
	 *
	 * @param $data      - [
	 *                   'section_id'           => id of the section (integer)
	 *                   'editor_id'            => id (string, see generate_editor_id) to use for content editor,
	 *                   'content'              => HTML content that goes into content editor,
	 *                   'button_text'          => text that goes into the button text input,
	 *                   'button_url'           => URL that goes into the button url input,
	 *                   'button_new_window'    => On / Off - if button opens link in new window
	 *                   ]
	 */
	public function render_callout_meta_box( $data ) {
		$this->generate_editor( $data, 'is-title partial-title' );
		$this->generate_button_input( $data );
	}

	/**
	 * Renders Video Grid Meta Box
	 *
	 * @param $data       - [
	 *                    'section_id'        => id of the section (integer),
	 *                    'video_grid_title'  => HTML markup for the section's headline,
	 *                    'video_items'       => an array of 'video items'
	 *                    ]
	 */
	public function render_video_grid_meta_box( $data ) {
		$section_id = _::get( $data, 'section_id' );
		$title      = _::get( $data, 'video_grid_title' );
		$videos     = _::get( $data, 'video_items' );

		echo '<div class="video-wrapper">';
		echo '<p><label>Video Grid Title</label></p>';
		echo '<input type="text" class="widefat is-title" placeholder="Video Grid Title" name="section[' . $section_id . '][video_grid_title]" value="' . esc_attr( $title ) . '"/></p>';
		echo '<div class="videos sub-contents sortable">';

		if ( empty( $videos ) ) {
			$videos = [
				'video_id'   => 0,
				'section_id' => $section_id
			];

			$this->generate_video( $videos );
		} else {
			$index = 0;

			foreach ( $videos as $video_item ) {
				$video_item['video_id']   = $index++;
				$video_item['section_id'] = $section_id;

				$this->generate_video( $video_item );
			}
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="video">Add Video</a></p>';
		echo '</div>';
	}

	/**
	 * Render Image Grid Meta Meta Box
	 *
	 * @param $data       - [
	 *                    'section_id'        => id of the section (integer),
	 *                    'image_grid_title'  => text for the Image Grid Title input
	 *                    'image_items'       => an array of 'image items', used to render sub-content items,
	 *                    ]
	 *
	 */
	public function render_image_grid_meta_box( $data ) {
		$section_id = _::get( $data, 'section_id' );
		$title      = _::get( $data, 'image_grid_title' );
		$images     = _::get( $data, 'image_items' );

		echo '<div class="image-grid-wrapper">';
		echo '<p><label>Image Grid Title</label></p>';
		echo '<input type="text" class="widefat is-title" placeholder="Image Grid Title" name="section[' . $section_id . '][image_grid_title]" value="' . esc_attr( $title ) . '"/></p>';
		echo '<div class="image-grid sub-contents sortable">';

		if ( empty( $images ) ) {
			$images = [
				'image_id'   => 0,
				'section_id' => $section_id
			];

			$this->generate_image( $images );
		} else {
			$index = 0;

			foreach ( $images as $image ) {
				$image['image_id']   = $index++;
				$image['section_id'] = $section_id;

				$this->generate_image( $image );
			}
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="image-grid-item">Add Image</a></p>';
		echo '</div>';
	}

	/**
	 * Render a taxonomy dropdown
	 *
	 * @param {string} $name
	 * @param {string} $label
	 * @param {string} $taxonomy
	 * @param {string} $selected
	 */
	public function taxonomy_dropdown( $name, $label, $taxonomy, $selected ) {
		echo '<p><label>' . $label . ' </label>';

		$terms = get_terms( [
			'taxonomy' => $taxonomy
		] );

		$array = [];

		foreach ( $terms as $term ) {
			$array[ $term->term_id ] = $term->name;
		}

		echo $this->create_dropdown( $name, $array, $selected, 'Display All...' );
		echo '</p>';
	}

	/**
	 * Renders Image List Meta Box
	 *
	 * @param $data       - [
	 *                    'section_id'            => id of the section (integer),
	 *                    'image_list_headline'   => text that going into the headline input,
	 *                    'image_list_items'      =>
	 *                    ]
	 */
	public function render_image_list_meta_box( $data ) {
		$section_id          = _::get( $data, 'section_id' );
		$image_list_headline = _::get( $data, 'image_list_headline' );
		$image_list          = _::get( $data, 'image_list_items' );

		echo '<div class="image-list-wrapper">';
		echo '<p><label>Image List Headline</label>';
		echo '<input type="text" class="widefat is-title" placeholder="Image List Headline" name="section[' . $section_id . '][image_list_headline]" value="' . esc_attr( $image_list_headline ) . '"/></p>';
		echo '<div class="image-list-items sub-contents sortable">';

		$index = 0;

		if ( empty( $image_list ) ) {
			$image_list = [
				[
					'image_list_item_id' => 0,
					'section_id'         => $section_id
				]
			];
		}

		foreach ( $image_list AS $image_list_data ) {
			$image_list_data['image_list_item_id'] = $index++;
			$image_list_data['section_id']         = $section_id;

			$this->generate_image_list_item( $image_list_data );
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="image-list-item">Add Image List Item</a></p>';
		echo '</div>';
	}

	/**
	 * Renders Split Grid Meta Box
	 *
	 * @param $data       - [
	 *                    'section_id'            => id of section (integer),
	 *                    'split_grid_headline'   => text that goes into the headline input,
	 *                    'editors'               => hold data for both editors (array)
	 *                    ]
	 *
	 */
	public function render_split_grid_meta_box( $data ) {
		$section_id     = _::get( $data, 'section_id' );
		$split_headline = _::get( $data, 'split_grid_headline' );
		$editors        = _::get( $data, 'editors' );

		echo '<p><label>Split Grid Headline</label>';
		echo '<input type="text" class="widefat is-title" placeholder="Split Grid Headline" name="section[' . $section_id . '][split_grid_headline]" value="' . esc_attr( $split_headline ) . '"/></p>';

		foreach ( $editors as $side => $editor_id ) {
			echo '<div class="split-container">';
			echo '<p class="title">' . ucwords( $side ) . ' Column</p>';

			$data['editor_id'] = $editor_id;

			$this->generate_editor( $data );
			$this->color_scheme_chooser( 'section[' . $section_id . '][' . $side . '][color]', $data[ $side ], 'split-scheme-chooser' );

			echo '</div>';
		}
	}

	/**
	 * Renders Image Comparison Meta Box
	 *
	 * @param $data       - [
	 *                    'section_id'                  => id of section (integer),
	 *                    'image_comparision_headline'  => text that goes into the Image comparison headline input
	 *                    'top_image'                   => image URL used to generate 'Top Image' box,
	 *                    'bottom_image'                => image URL used to generate 'Bottom Image' box
	 *                    ]
	 */
	public function render_image_comparison_meta_box( $data ) {
		$section_id       = _::get( $data, 'section_id' );
		$section_headline = _::get( $data, 'image_comparison_headline' );
		$top_image        = _::get( $data, 'top_image' );
		$bottom_image     = _::get( $data, 'bottom_image' );

		echo '<p><label>Image Comparison Headline</label>';
		echo '<input type="text" class="widefat is-title" placeholder="Image Comparison Headline" name="section[' . $section_id . '][image_comparison_headline]" value="' . esc_attr( $section_headline ) . '"/></p>';

		echo '<p>Top Image</p>';
		$this->generate_image_input( $top_image, 'section[' . $section_id . '][top_image]', 'image_comparison' );

		echo '<p>Bottom Image</p>';
		$this->generate_image_input( $bottom_image, 'section[' . $section_id . '][bottom_image]', 'image_comparison' );
	}

	/**
	 * Renders Video Hero Meta Box
	 *
	 * @param $data       - [
	 *                    'section_id'        => id of section (integer),
	 *                    'video_backgrounds' => contains all video 'types': webm, mp4, ogg/ogv (array),
	 *                    ]
	 */
	public function render_video_hero_meta_box( $data ) {
		$section_id        = _::get( $data, 'section_id' );
		$video_backgrounds = _::get( $data, 'video_background' );
		$mp4               = _::get( $video_backgrounds, 'mp4' );
		$webm              = _::get( $video_backgrounds, 'webm' );
		$ogg               = _::get( $video_backgrounds, 'ogg' );

		$this->generate_editor( $data, 'is-title partial-title' );

		echo '<p><label>Mp4 Video </label><input type="text" value="' . esc_attr( $mp4 ) . '" class="video" name="section[' . $section_id . '][video_background][mp4]" placeholder="MP4 File" readonly/> ';
		echo '<a class="attach-video button button-default" data-type="mp4">Attach</a> <a class="clear-video button button-default">Clear</a></p>';
		echo '<p><label>WebM Video </label><input type="text" value="' . esc_attr( $webm ) . '" class="video" name="section[' . $section_id . '][video_background][webm]" placeholder="WebM File" readonly/> ';
		echo '<a class="attach-video button button-default" data-type="webm">Attach</a> <a class="clear-video button button-default">Clear</a></p>';
		echo '<p><label>Ogg Video </label><input type="text" value="' . esc_attr( $ogg ) . '" class="video" name="section[' . $section_id . '][video_background][ogg]" placeholder="Ogv/Ogg File" readonly/> ';
		echo '<a class="attach-video button button-default" data-type="ogg">Attach</a> <a class="clear-video button button-default">Clear</a></p>';
	}

	/**
	 * Renders Accordion Meta Box
	 *
	 * @param $data       - [
	 *                    'section_id'          => id of the section (integer),
	 *                    'accordions'          => all accordion items (array)
	 *                    'accordion_headline'  => text for the headline inpur
	 *                    ]
	 *
	 */
	public function render_accordion_meta_box( $data ) {
		$section_id          = _::get( $data, 'section_id' );
		$accordions          = _::get( $data, 'accordions' );
		$accordions_headline = _::get( $data, 'accordions_headline' );

		echo '<p><label>Accordions Headline</label>';
		echo '<input type="text" placeholder="Accordions Headline" class="widefat is-title" name="section[' . $section_id . '][accordions_headline]" value="' . esc_attr( $accordions_headline ) . '" ></p>';
		echo '<div class="sub-contents sortable">';

		$index = 0;
		if ( empty( $accordions ) ) {
			$accordions = [
				[
					'accordion'  => 0,
					'section_id' => $section_id
				]
			];
		}

		foreach ( $accordions AS $accordion ) {
			$accordion['accordion_id'] = $index++;
			$accordion['section_id']   = $section_id;

			$this->generate_accordion( $accordion );
		}

		echo '</div>';
		echo '<p><a href="javascript:void(0);" class="button add-sub-content" data-section="accordion-item">Add Accordion Item</a></p>';
	}

	/**
	 * Section to render admin interface for "Recent Posts" selection
	 *
	 * @param $data
	 */
	public function render_recent_posts_meta_box( $data ) {
		$section_id     = _::get( $data, 'section_id' );
		$which_posts    = _::get( $data, 'which_posts' );
		$sort_posts     = _::get( $data, 'sort_posts' );
		$number_posts   = _::get( $data, 'number_posts' );
		$category_id    = _::get( $data, 'category_id', 0 );
		$post_ids       = _::get( $data, 'post_ids', '' );
		$posts_headline = _::get( $data, 'posts_headline' );

		echo '<p><label>Posts Headline</label>';
		echo '<input type="text" placeholder="Posts Headline" class="widefat is-title" name="section[' . $section_id . '][posts_headline]" value="' . esc_attr( $posts_headline ) . '" ></p>';

		$options = [
			'newest' => 'Most Recent from All Posts',
			'manual' => 'Manually Choose'
		];

		echo '<p><label>Choose Posts</label></p>';
		echo $this->create_dropdown( 'section[' . $section_id . '][which_posts]', $options, $which_posts, '', 'recent-post-type' );

		echo '<div class="post-newest">';

		echo '<p><label>Number of Posts</label></p>';
		echo '<input name="section[' . $section_id . '][number_posts]" value="' . esc_attr( $number_posts ) . '">';
		echo '</p>';

		echo '<p><label>Post Category</label></p>';
		wp_dropdown_categories( [
			'name'            => 'section[' . $section_id . '][category_id]',
			'show_option_all' => '- All Categories -',
			'selected'        => $category_id,
			'heirarchical'    => 1,
			'depth'           => 3
		] );

		$options = [
			'desc' => 'Most Recent Posts First',
			'asc'  => 'Oldest Posts First',
		];

		echo '<p><label>Sort By</label></p>';
		echo $this->create_dropdown( 'section[' . $section_id . '][sort_posts]', $options, $sort_posts );

		echo '</div>';

		// javascript-driven drag-and-drop interface for choosing posts
		echo '<div class="post-chooser searchable" data-id="ID" data-display="post_title" data-search="post_title" data-source="posts"><h4>Choose Posts To Display</h4><p><label>Search Posts</label></p><input type="text" class="searchable-search widefat" placeholder="Search Posts">';
		echo '<div class="searchable-selected sortable sortable-list"></div>';
		echo '<div class="sortable-description">drag-and-drop items above to change the display order</div></div>';
		echo '<input type="hidden" class="searchable-ids widefat" name="section[' . $section_id . '][post_ids]" value="' . $post_ids . '">';

		$additional_classes         = _::get( $data, 'additional_classes' );
		$data['additional_classes'] = _::get( $data, [ 'post', 'additional_classes' ] );
		$this->generate_additional_classes( $data, '[post]', 'Additional Classes (applied to individual posts)' );
		echo '<hr>';
		$data['additional_classes'] = $additional_classes;
	}

	/**
	 * Renders all section 'options' such as 'background image,' 'color scheme chooser,' etc
	 *
	 * @param array $data
	 */
	private function section_options( $data ) {
		$section_id = _::get( $data, 'section_id' );

		do_action( 'mosaic_before_section_options', $data, $section_id );

		$this->color_scheme_chooser( 'section[' . $section_id . '][color]', $data );
		$this->background_chooser( 'section[' . $section_id . '][background]', $data );
		$this->parallax_background_chooser( $data );
		$this->generate_section_overlay( $data );
		$this->generate_additional_classes( $data );
		$this->generate_anchor_link( $data );

		do_action( 'mosaic_after_section_options', $data, $section_id );
	}

	/**
	 * Utility function to generate a bucket, based on the passed-in data.
	 *
	 * @param $data - must contain at least: [
	 *              'section_id' => (int),
	 *              'bucket_id'  => (int),
	 *              'bucket_description' => (string),
	 *              'bucket_headline' => (string),
	 *              'bucket_image' => (string)
	 *              ]
	 *
	 *              can optionally contain 'content' => (string)
	 */
	public function generate_bucket( $data ) {
		$section_id         = _::get( $data, 'section_id' );
		$bucket_id          = _::get( $data, 'bucket_id' );
		$bucket_headline    = _::get( $data, 'bucket_headline' );
		$bucket_description = _::get( $data, 'bucket_description' );
		$type               = _::get( $data, 'type' );

		if ( "bucket-panels" == $type ) {
			echo '<div class="bucket sub-content">';
			echo '<h5 class="bucket-title sub-content-title">';
			echo '<a class="delete-sub-content">X</a>';
			echo 'Bucket - Panel</h5>';
			echo '<p><label>Bucket Headline</label>';
			echo '<input type="text" class="widefat" placeholder="Bucket Headline" name="section[' . $section_id . '][buckets][' . $bucket_id . '][bucket_headline]" value="' . esc_attr( $bucket_headline ) . '"/></p>';
			echo '<p><label>Bucket Description</label>';
			echo '<textarea class="widefat" placeholder="Bucket Description" name="section[' . $section_id . '][buckets][' . $bucket_id . '][bucket_description]">' . esc_textarea( $bucket_description ) . '</textarea></p>';

			$this->generate_button_input( $data, [ 'buckets', $bucket_id, 'button_text' ] );
			// For panels, we need to let them choose the background color for the headline
			$this->color_highlight_chooser( 'section[' . $section_id . '][buckets][' . $bucket_id . '][color]', $data );
			$this->generate_additional_classes( $data, "[buckets][{$bucket_id}]" );

			echo '</div>';
		} else if ( "bucket-stats" == $type ) {
			$bucket_stat               = _::get( $data, 'bucket_stat' );
			$bucket_secondary_headline = _::get( $data, 'bucket_secondary_headline' );

			echo '<div class="bucket sub-content">';
			echo '<h5 class="bucket-title sub-content-title">';
			echo '<a class="delete-sub-content">X</a>';
			echo 'Bucket-Stats</h5>';
			echo '<p><label>Bucket Stat</abel>';
			echo '<input type="text" class="widefat" placeholder="Bucket Stat" name="section[' . $section_id . '][buckets][' . $bucket_id . '][bucket_stat]" value="' . esc_attr( $bucket_stat ) . '"/></p>';
			echo '<p><label>Bucket Secondary Healine</abel>';
			echo '<input type="text" class="widefat" placeholder="Bucket Stat" name="section[' . $section_id . '][buckets][' . $bucket_id . '][bucket_secondary_headline]" value="' . esc_attr( $bucket_secondary_headline ) . '"/></p>';
			echo '<p><label>Bucket Description</label>';
			echo '<textarea class="widefat" placeholder="Bucket Description" name="section[' . $section_id . '][buckets][' . $bucket_id . '][bucket_description]">' . esc_textarea( $bucket_description ) . '</textarea></p>';

			$this->color_highlight_chooser( 'section[' . $section_id . '][buckets][' . $bucket_id . '][color]', $data );
			$this->generate_additional_classes( $data, "[buckets][{$bucket_id}]" );

			echo '</div>';
		} else {
			$icon          = _::get( $data, 'icon' );
			$alt_icon      = _::get( $data, 'icon_alternate' );
			$bucket_image  = _::get( $data, 'image' );
			$image_caption = _::get( $data, 'image_caption' );

			$is_overlay_bucket = ( 'bucket-overlay' === $type );

			echo '<div class="bucket sub-content">';
			echo '<h5 class="bucket-title sub-content-title">';
			echo '<a class="delete-sub-content">X</a>';
			echo 'Bucket</h5>';

			if ( $is_overlay_bucket ) {
				echo '<label>Background Image:</label>';
			}

			$this->generate_image_input( $bucket_image, $section_id, 'buckets', $bucket_id );

			if ( ! $is_overlay_bucket ) {
				echo '<p><label>Image Caption</label>';
				echo '<textarea class="widefat" placeholder="Image Caption" name="section[' . $section_id . '][buckets][' . $bucket_id . '][image_caption]">' . esc_textarea( $image_caption ) . '</textarea></p>';
			}

			echo '<p><label>Bucket Headline</label>';
			echo '<input type="text" class="widefat" placeholder="Bucket Headline" name="section[' . $section_id . '][buckets][' . $bucket_id . '][bucket_headline]" value="' . esc_attr( $bucket_headline ) . '"/></p>';
			echo '<p><label>Bucket Description</label>';
			echo '<textarea class="widefat" placeholder="Bucket Description" name="section[' . $section_id . '][buckets][' . $bucket_id . '][bucket_description]">' . esc_textarea( $bucket_description ) . '</textarea></p>';

			if ( $is_overlay_bucket ) {
				$this->generate_button_input( $data, [ 'buckets', $bucket_id, 'button_text' ] );
			} else {
				$this->generate_link_input( 'Bucket URL', $data );
			}

			echo '<p><label>Icon Image: </label>';
			$this->generate_image_input( $icon, 'section[' . $section_id . '][buckets][' . $bucket_id . '][icon]', 'image_comparison' );
			echo '</p>';

			if ( $is_overlay_bucket ) {
				echo '<p><label>Alternative Icon Image:</label>';
				$this->generate_image_input( $alt_icon, 'section[' . $section_id . '][buckets][' . $bucket_id . '][icon_alternate]', 'image_comparison' );
				echo '</p>';
			}

			$this->color_highlight_chooser( 'section[' . $section_id . '][buckets][' . $bucket_id . '][color]', $data );
			$this->generate_additional_classes( $data, "[buckets][{$bucket_id}]" );

			echo '</div>';
		}
	}

	/**
	 * Utility function to generate a content slider, based on the passed-in data.
	 *
	 * @param $data - must contain at least: [
	 *              'section_id' => (int),
	 *              'content_slider_id'  => (int),
	 *              'content_slider_headline' => (string),
	 *              'content_slider_body' => (string)
	 *              ]
	 *
	 *              can optionally contain 'content' => (string)
	 */
	public function generate_content_slider( $data ) {
		$section_id              = _::get( $data, 'section_id' );
		$content_slider_id       = _::get( $data, 'content_slider_id' );
		$content_slider_headline = _::get( $data, 'content_slider_headline' );
		$content_slider_body     = _::get( $data, 'content_slider_body' );
		$image                   = _::get( $data, 'image' );
		$image_position          = _::get( $data, 'image_position' );

		echo '<div class= "content-slider sub-content">';
		echo '<h5 class="slider-title sub-content-title">';
		echo '<a class="delete-sub-content">X</a>';
		echo 'Slider</h5>';
		echo '<p><label>Slider Headline</label>';
		echo '<input type="text" class="widefat" placeholder="Content Slider Headline" name="section[' . $section_id . '][content_sliders][' . $content_slider_id . '][content_slider_headline]" value="' . esc_attr( $content_slider_headline ) . '"/></p>';

		$this->generate_image_input( $image, 'section[' . $section_id . '][content_sliders][' . $content_slider_id . '][image]', 'image_comparison' );

		$image_position_array = [
			'before' => 'Before Slider Body',
			'after'  => 'After Slider Body'
		];

		echo '<p><label>Image Position: </label>';
		echo $this->create_dropdown( 'section[' . $section_id . '][content_sliders][' . $content_slider_id . '][image_position]', $image_position_array, $image_position );
		echo '</p>';


		echo '<p><label>Slider Body</label>';
		echo '<textarea class="widefat" placeholder="Content Slider Body" name="section[' . $section_id . '][content_sliders][' . $content_slider_id . '][content_slider_body]">' . esc_textarea( $content_slider_body ) . '</textarea></p>';

		$this->generate_button_input( $data, [ 'content_sliders', $content_slider_id, 'button_text' ] );
		$this->generate_additional_classes( $data, "[content_sliders][{$content_slider_id}]" );

		echo '</div>';
	}

	/**
	 * Utility function to generate a highlight, based on the passed-in data.
	 *
	 * @param $data - must contain at least: [
	 *              'section_id' => (int),
	 *              'highlight_id'  => (int),
	 *              'highlight_headline' => (string),
	 *              'highlight_text' => (string)
	 *              ]
	 *
	 *              can optionally contain 'content' => (string)
	 */
	public function generate_highlight( $data ) {
		$section_id         = _::get( $data, 'section_id' );
		$highlight_id       = _::get( $data, 'highlight_id' );
		$highlight_headline = _::get( $data, 'highlight_headline' );
		$highlight_text     = _::get( $data, 'highlight_text' );

		echo '<div class="highlight sub-content">';
		echo '<h5 class="highlight-title sub-content-title">';
		echo '<a class="delete-sub-content">X</a>';
		echo 'Highlight</h5>';
		echo '<p><label>Highlight Headline</label>';
		echo '<input type="text" class="widefat" placeholder="Highlight Headline" name="section[' . $section_id . '][highlights][' . $highlight_id . '][highlight_headline]" value="' . $highlight_headline . '"/></p>';
		echo '<p><label>Highlight Text</label></p>';
		echo '<textarea class="widefat" placeholder="Highlight Text" name="section[' . $section_id . '][highlights][' . $highlight_id . '][highlight_text]">' . esc_textarea( $highlight_text ) . '</textarea></p>';

		$this->color_highlight_chooser( 'section[' . $section_id . '][highlights][' . $highlight_id . '][color]', $data );
		$this->generate_additional_classes( $data, "[highlights][{$highlight_id}]" );

		echo '</div>';
	}

	/**
	 * Utility function to generate a checklist item, based on the passed-in data.
	 *
	 * @param $data - must contain at least: [
	 *              'section_id' => (int),
	 *              'checklist_item_id'  => (int),
	 *              'checklist_item_headline' => (string),
	 *              'checklist_item_text' => (string)
	 *              ]
	 *
	 */
	public function generate_checklist_item( $data ) {
		$section_id              = _::get( $data, 'section_id' );
		$checklist_item_id       = _::get( $data, 'checklist_item_id' );
		$checklist_item_headline = _::get( $data, 'checklist_item_headline' );
		$checklist_item_text     = _::get( $data, 'checklist_item_text' );

		echo '<div class="checklist-item sub-content">';
		echo '<h5 class="checklist-item-title sub-content-title">';
		echo '<a class="delete-sub-content">X</a>';
		echo 'Checklist Item</h5>';
		echo '<p><label>Checklist Item Headline</label>';
		echo '<input type="text" class="widefat" placeholder="Checklist Item Headline" name="section[' . $section_id . '][checklist_items][' . $checklist_item_id . '][checklist_item_headline]" value="' . $checklist_item_headline . '"/></p>';
		echo '<p><label>Checklist Item Text</label></p>';
		echo '<textarea class="widefat" placeholder="Checklist Item Text" name="section[' . $section_id . '][checklist_items][' . $checklist_item_id . '][checklist_item_text]">' . esc_textarea( $checklist_item_text ) . '</textarea>';

		$this->color_highlight_chooser( 'section[' . $section_id . '][checklist_items][' . $checklist_item_id . '][color]', $data );
		$this->generate_additional_classes( $data, "[checklist_items][{$checklist_item_id}]" );

		echo '</div>';
	}

	/**
	 * Utility function to generate a video grid item, based on the passed-in data.
	 *
	 * @param $data - must contain at least: [
	 *              'section_id' => (int),
	 *              'video_id'  => (int),
	 *              'video_url' => (string),
	 *              ]
	 *
	 */
	public function generate_video( $data ) {
		$section_id    = _::get( $data, 'section_id' );
		$video_item_id = _::get( $data, 'video_id' );
		$video_url     = _::get( $data, 'video_url' );

		echo '<div class="video-item sub-content">';
		echo '<h5 class="Video-item-title sub-content-title">';
		echo '<a class="delete-sub-content">X</a>';
		echo 'Video Item</h5>';
		echo '<p><label>Video Url</label>';
		echo '<input type="text" class="widefat" placeholder="Video Item Url" name="section[' . $section_id . '][video_items][' . $video_item_id . '][video_url]" value="' . $video_url . '"/></p>';

		$this->generate_additional_classes( $data, "[video_items][{$video_item_id}]" );

		echo '</div>';
	}

	/**
	 * Utility function to generate a checklist item, based on the passed-in data.
	 *
	 * @param $data - must contain at least: [
	 *              'section_id' => (int),
	 *              'image_id'  => (int),
	 *              ]
	 *
	 */
	public function generate_image( $data ) {
		$section_id = _::get( $data, 'section_id' );
		$image_id   = _::get( $data, 'image_id' );
		$caption    = _::get( $data, 'caption' );
		$image      = _::get( $data, 'image' );

		echo '<div class="image-item sub-content">';
		echo '<h5 class="image-item-title sub-content-title">';
		echo '<a class="delete-sub-content">X</a>';
		echo 'Image</h5>';

		$this->generate_image_input( $image, $section_id, 'image_items', $image_id );
		$this->generate_link_input( 'Image Link Url', $data );

		echo '<p><label>Caption</label>';
		echo '<input type="text" class="widefat" placeholder="Image Caption" name="section[' . $section_id . '][image_items][' . $image_id . '][caption]" value="' . esc_attr( $caption ) . '"/></p>';

		$this->generate_additional_classes( $data, "[image_items][{$image_id}]" );

		echo '</div>';
	}

	/**
	 * For the "Image List" section, generate the individual "image" panel.
	 *
	 * @param $data
	 */
	public function generate_image_list_item( $data ) {
		$section_id                  = _::get( $data, 'section_id' );
		$image_list_item_id          = _::get( $data, 'image_list_item_id' );
		$image_list_item_headline    = _::get( $data, 'image_list_item_headline' );
		$image_list_item_subheadline = _::get( $data, 'image_list_item_subheadline' );
		$image_item_placement        = _::get( $data, 'image_item_placement' );
		$image_list_item_text        = _::get( $data, 'image_list_item_text' );
		$image_list_item_after_text  = _::get( $data, 'image_list_item_after_text' );
		$text_alignment              = _::get( $data, 'text_alignment' );
		$image                       = _::get( $data, 'image' );

		echo '<div class="image-list-item sub-content">';
		echo '<h5 class="image-list-item-title sub-content-title">';
		echo '<a class="delete-sub-content">X</a>';
		echo 'Image List Item</h5>';

		$this->generate_image_input( $image, $section_id, 'image_list_items', $image_list_item_id );

		echo '<p><label>Image Placement: </label>';
		$name = 'section[' . $section_id . '][image_list_items][' . $image_list_item_id . '][image_item_placement]';

		$dropdown = [
			'0' => 'Left',
			'1' => 'Right'
		];

		echo $this->create_dropdown( $name, $dropdown, $image_item_placement );
		echo '</p>';

		echo '<div class="hero-banner-text-alignment">';
		$name = 'section[' . $section_id . '][image_list_items][' . $image_list_item_id . '][text_alignment]';
		echo '<label>Content Vertical Alignment: </label>';

		echo $this->create_dropdown( $name, [
			'top'    => 'Top',
			'middle' => 'Middle',
			'bottom' => 'Bottom'
		], $text_alignment );

		echo '</div>';

		echo '<p><label>Image List Item Headline</label>';
		echo '<input type="text" class="widefat" name="section[' . $section_id . '][image_list_items][' . $image_list_item_id . '][image_list_item_headline]" value="' . esc_attr( $image_list_item_headline ) . '"/></p>';
		echo '<p><label>Image List Item Subheadline</label>';
		echo '<input type="text" class="widefat" name="section[' . $section_id . '][image_list_items][' . $image_list_item_id . '][image_list_item_subheadline]" value="' . esc_attr( $image_list_item_subheadline ) . '"/></p>';
		echo '<p><label>Image List Item Body</label>';
		echo '<textarea type="text" class="widefat" name="section[' . $section_id . '][image_list_items][' . $image_list_item_id . '][image_list_item_text]">' . esc_textarea( $image_list_item_text ) . '</textarea></p>';
		echo '<p><label>Image List Item After Text</label>';
		echo '<textarea type="text" class="widefat" name="section[' . $section_id . '][image_list_items][' . $image_list_item_id . '][image_list_item_after_text]">' . esc_textarea( $image_list_item_after_text ) . '</textarea></p>';

		$this->generate_additional_classes( $data, "[image_list_items][{$image_list_item_id}]" );

		echo '</div>';
	}

	public function generate_accordion( $data ) {
		$section_id         = _::get( $data, 'section_id' );
		$accordion_id       = _::get( $data, 'accordion_id' );
		$accordion_headline = _::get( $data, 'accordion_headline' );
		$accordion_body     = _::get( $data, 'accordion_body' );
		$view               = _::get( $data, 'accordion_view' );

		$view_array = [
			'closed' => 'Closed',
			'open'   => 'Open'
		];

		echo '<div class="sub-content">';
		echo '<h5 class="accordion-title sub-content-title">';
		echo '<a class="delete-sub-content">X</a>';
		echo 'Accordion Item</h5>';
		echo '<p><label>Headline</label>';
		echo '<input type="text" placeholder="Accordion Item Headline" class="widefat" name="section[' . $section_id . '][accordions][' . $accordion_id . '][accordion_headline]" value="' . esc_attr( $accordion_headline ) . '" /></p>';
		echo '<p><label>Body</label>';
		echo '<textarea type="text" rows="8"  placeholder="Accordion Item Body" class="widefat" name="section[' . $section_id . '][accordions][' . $accordion_id . '][accordion_body]">' . esc_attr( $accordion_body ) . '</textarea></p>';
		echo '<p><label></label>';
		echo '<label>Default Accordion View</label><br>';

		echo $this->create_dropdown( "section[{$section_id}][accordions][{$accordion_id}][accordion_view]", $view_array, $view, NULL );

		$this->color_highlight_chooser( 'section[' . $section_id . '][accordions][' . $accordion_id . '][color]', $data );
		$this->generate_additional_classes( $data, "[accordions][{$accordion_id}]" );

		echo '</div>';
	}

	public function background_chooser( $name, $data ) {
		$background_image = _::get( $data, 'background_image' );
		$section_id       = _::get( $data, 'section_id' );
		echo '<div class="background-chooser-wrapper">';
		echo '<label>Background Image</label>';

		$this->generate_image_input( $background_image, $section_id, 'background_image' );

		echo '</div>';
	}

	/**
	 * Utility function to generate a wp_editor area, based on the passed-in data.
	 *
	 * @param $data  - must contain at least: [
	 *               'section_id' => (int),
	 *               'editor_id'  => (string)
	 *               ]
	 *
	 *              can optionally contain 'content' => (string)
	 *
	 * @param $class - string of optional CSS classes
	 */
	public function generate_editor( $data, $class = '' ) {
		$section_id = _::get( $data, 'section_id' );
		$editor_id  = _::get( $data, 'editor_id' );
		$content    = _::get( $data, 'content' );

		$name = 'section[' . $section_id . '][content]';

		if ( 'split' == $data['type'] ) {
			foreach ( $data['editors'] as $side => $editor ) {
				if ( $editor == $editor_id ) {
					$name    = 'section[' . $section_id . '][' . $side . '][content]';
					$content = $data[ $side ]['content'];
				}
			}
		}

		wp_editor( stripslashes( $content ), $editor_id, [ 'textarea_name' => $name, 'editor_class' => $class ] );

		$this->editors[] = $editor_id;
	}

	/**
	 * Generate an image input markup.
	 *
	 * @param string      $image
	 * @param int         $section_id
	 * @param bool|string $type
	 * @param bool|int    $sub_section_id
	 */
	public function generate_image_input( $image, $section_id, $type = FALSE, $sub_section_id = FALSE ) {
		echo '<div class="image-container"><div class="image-wrapper">';

		if ( $image ) {
			echo '<img src="' . esc_attr( $image ) . '" />';
			echo '<a class="delete"><span class="dashicons dashicons-no"></span></a>';
		}

		if ( 'background_image' == $type ) {
			$name = 'section[' . $section_id . '][' . $type . ']';
			echo '</div><input type="hidden" name="' . $name . '" value="' . esc_attr( $image ) . '" /></div>';

			return;
		}

		if ( 'post_background_image' == $type || 'image_comparison' == $type ) {
			$name = $section_id;
			echo '</div><input type="hidden" name="' . $name . '" value="' . esc_attr( $image ) . '" /></div>';

			return;
		}

		$name = 'section[' . $section_id . ']';

		if ( $type ) {
			$name .= '[' . $type . ']';
		}

		if ( FALSE !== $sub_section_id ) {
			$name .= '[' . $sub_section_id . ']';
		}

		$name .= '[image]';

		echo '</div><input type="hidden" name="' . $name . '" value="' . esc_attr( $image ) . '" /></div>';
	}

	/**
	 * Renders the button input (button text, url, etc).
	 *
	 * @param              $data               - [
	 *                                         'section_id'           => id of the section (integer)
	 *                                         'editor_id'            => id (string, see generate_editor_id) to use for
	 *                                         content editor,
	 *                                         'content'              => HTML content that goes into content editor,
	 *                                         'button_text'          => text that goes into the button text input,
	 *                                         'button_url'           => URL that goes into the button url input
	 *                                         'button_new_window'    => On / Off - if button opens link in new window
	 *                                         ]
	 *
	 * @param string|array $name               - the name key(s) of the button
	 * @param bool         $include_new_window - whether to expose the "Open in New Window" checkbox or not
	 *
	 */
	public function generate_button_input( $data, $name = 'button_text', $include_new_window = TRUE ) {
		$section_id  = _::get( $data, 'section_id' );
		$button_text = _::get( $data, 'button_text' );

		if ( 'event_grid_button_text' == $name ) {
			$button_text = _::get( $data, 'event_grid_button_text' );
		}

		if ( 'team_grid_button_text' == $name ) {
			$button_text = _::get( $data, 'team_grid_button_text' );
		}

		if ( is_array( $name ) ) {
			$name = implode( '][', $name );
		}

		echo '<p><label>Button Text</label>';
		echo '<input type="text" class="widefat" placeholder="Button Text" name="section[' . $section_id . '][' . $name . ']" value="' . esc_attr( $button_text ) . '"/>';
		echo '</p>';

		$this->generate_link_input( 'Button URL', $data, $include_new_window );
	}

	/**
	 * Utility function to generate a URL input, with button to launch the
	 * WP link chooser interface.
	 *
	 * @param string $label
	 * @param array  $data               - must contain at least: [
	 *                                   'section_id' => (int),
	 *                                   'wplink_id'  => (string)
	 *                                   ]
	 *
	 *                    can optional contain 'button_url' => (string)
	 *
	 * @param bool   $include_new_window - whether to expose the "Open in New Window" checkbox or not
	 */
	public function generate_link_input( $label, $data, $include_new_window = TRUE ) {
		$section_id = _::get( $data, 'section_id' );
		$button_url = _::get( $data, 'button_url' );
		$wplink_id  = _::get( $data, 'wplink_id' );

		$name = $this->generate_input_name( $section_id, 'button_url', $data );

		if ( isset( $data['content_slider_id'] ) ) {
			$content_slider_id = _::get( $data, 'content_slider_id' );
			$name              = 'section[' . $section_id . '][content_sliders][' . $content_slider_id . '][button_url]';
		} elseif ( isset( $data['bucket_id'] ) ) {
			$bucket_id = _::get( $data, 'bucket_id' );
			$name      = 'section[' . $section_id . '][buckets][' . $bucket_id . '][button_url]';
		} elseif ( isset( $data['image_id'] ) ) {
			$image_id = _::get( $data, 'image_id' );
			$name     = 'section[' . $section_id . '][image_items][' . $image_id . '][button_url]';
		}

		echo '<p class="section-url-input"><label>' . $label . '</label>';
		echo '<input type="text" class="url-input" placeholder="Button URL" id="' . $wplink_id . '" name="' . $name . '" value="' . esc_attr( $button_url ) . '"/>
	        <a href="javascript:void(0);" class="button button-small section-wplink" data-wplink-id="' . $wplink_id . '"><span class="dashicons dashicons-admin-links"></span></a>';

		if ( $include_new_window ) {
			$for     = 'section_checkbox_' . $this->checkbox_index++;
			$checked = checked( _::get( $data, 'button_new_window', FALSE ), 'on', FALSE );
			$name    = $this->generate_input_name( $section_id, 'button_new_window', $data );

			echo '<p><input type="checkbox" ' . $checked . ' name="' . $name . '" id="' . $for . '"><label for="' . $for . '" class="checkbox-label">Open in New Window</label></p>';
		}
	}

	private function generate_input_name( $section_id, $key, $data ) {
		$name = "section[{$section_id}]";

		if ( isset( $data['content_slider_id'] ) ) {
			$content_slider_id = _::get( $data, 'content_slider_id' );
			$name              .= '[content_sliders][' . $content_slider_id . ']';
		} elseif ( isset( $data['bucket_id'] ) ) {
			$bucket_id = _::get( $data, 'bucket_id' );
			$name      .= '[buckets][' . $bucket_id . ']';
		} elseif ( isset( $data['image_id'] ) ) {
			$image_id = _::get( $data, 'image_id' );
			$name     .= '[image_items][' . $image_id . ']';
		}

		$name .= "[{$key}]";

		return $name;
	}

	/**
	 * Small utility function to generate (hopefully) unique editor IDs
	 * so that editors, when drawn, each have their own ID, and TinyMCE can
	 * hook into them properly.
	 *
	 * @param string $word - can pass in other prefixes, such as "wplink".  Default is "editor"
	 *
	 * @return string
	 */
	public function generate_editor_id( $word = 'editor' ) {
		return $word . '-' . substr( md5( rand() ), 0, 10 );
	}

	public function parallax_background_chooser( $data ) {
		$section_id = _::get( $data, 'section_id' );
		$parallax   = _::get( $data, 'parallax_background' );

		$name     = 'section[' . $section_id . '][parallax_background]';
		$dropdown = $this->create_dropdown( $name, [
			0 => 'No',
			1 => 'Yes'
		], $parallax );

		echo '<div class="parallax-chooser-wrapper">';
		echo '<label>Parallax Background</label>';
		echo $dropdown;
		echo '</div>';
	}

	public function generate_additional_classes( $data, $name = '', $title = 'Additional Classes' ) {
		$section_id = _::get( $data, 'section_id' );
		$input_name = 'section[' . $section_id . '][additional_classes]';
		$classes    = _::get( $data, 'additional_classes' );

		if ( $name ) {
			$input_name = 'section[' . $section_id . ']' . $name . '[additional_classes]';
		}

		echo '<p class="additional-classes"><label>' . $title . '</label>';
		echo '<input class="class-input" placeholder="Additional Classes" type="text" name="' . $input_name . '" value="' . esc_attr( $classes ) . '"/>';
		echo ' <a href="javascript:void(0);" class="button button-small"><span class="dashicons dashicons-book-alt"></span></a>';
		echo '</p>';
	}

	/**
	 * Create Anchor Link input field
	 *
	 * @param $data
	 */
	public function generate_anchor_link( $data ) {
		$section_id   = _::get( $data, 'section_id' );
		$input_name   = 'section[' . $section_id . ']' . '[anchor_link]';
		$anchor_links = _::get( $data, 'anchor_link' );

		echo '<p><label>Anchor Link</label>';
		echo '<input class="widefat" placeholder="Anchor Link" type="text" name="' . $input_name . '" value="' . esc_attr( $anchor_links ) . '"/></p>';
	}

	/**
	 * Create section overlay input fields
	 *
	 * @param $data
	 */
	public function generate_section_overlay( $data ) {
		$section_id = _::get( $data, 'section_id' );
		$color      = _::get( $data, 'overlay_color' );
		$opacity    = _::get( $data, 'overlay_opacity' );

		$name = 'section[' . $section_id . ']';

		echo '<p><label>Overlay Options:</label></p>';

		echo '<p><label>Color: </label>';
		echo '<input type="text" name="' . $name . '[overlay_color]' . '" value="' . $color . '" placeholder="#000"></p>';
		echo '<small class="description"><em>Enter a valid hex color value. For example: \'#fff\', \'#ffffff\', \'fff\', or \'ffffff\'. Defaults to black (\'#000\')</em></small>';

		echo '<p><label>   Opacity: </label>';
		echo '<input type="number" max="100" min="0" name="' . $name . '[overlay_opacity]' . '" value="' . $opacity . '" placeholder="0"></p>';
		echo '</p><small class="description"><em>Enter a valid number from 0 to 100</em></small>';
	}

	/**
	 * Called by "Save Post" hook.
	 *
	 * @param int    $post_id
	 * @param object $post
	 */
	public function save_post_data( $post_id, $post ) {
		if ( ! $this->is_page_using_home_template() ) {
			return;
		}

		$sections             = _::get( $_POST, 'section', [] );
		$page_loading_effects = _::get( $_POST, '_page_loading_effects' );
		$full_background      = _::get( $_POST, '_full_background_image' );
		$square_background    = _::get( $_POST, '_square_background_image' );

		// this ensures that the save is happening in the right context.
		// this key should ALWAYS exist on an actual "page save".
		// if this key is not present, then there's a strong chance that
		// this is a "quick edit" or some other save_post trigger that
		// will end up vaping the sections data.
		// but, just checking for sections being empty isn't valid either,
		// as it CAN be empty legitimately if someone wipes all sections from
		// a page and saves...
		if ( empty( $sections ) && ! array_key_exists( '_full_background_image', $_POST ) ) {
			return;
		}

		$save_sections = [];
		foreach ( $sections AS $id => $section ) {
			$save_sections[] = $section;
		}

		update_post_meta( $post->ID, '_mosaic_home_sections', $save_sections );
		update_post_meta( $post->ID, '_page_loading_effects', $page_loading_effects );
		update_post_meta( $post->ID, '_full_background_image', $full_background );
		update_post_meta( $post->ID, '_square_background_image', $square_background );
	}

	/**
	 * Create the "Color" settings interface (under "Settings" => "Color Schemes")
	 */
	public function color_settings() {
		if ( ! empty( $_POST['mosaic_scheme_submit'] ) && ! empty( $_POST['mosaic_theme_color_schemes'] ) ) {
			update_site_option( self::COLORS_SCHEMES_HEADER_SETTING, $_POST['mosaic_theme_navigation_color_scheme'] );
			update_site_option( self::COLORS_SCHEMES_FOOTER_SETTING, $_POST['mosaic_theme_footer_color_scheme'] );
			update_site_option( self::COLORS_SCHEMES_SETTING, $_POST['mosaic_theme_color_schemes'] );
			update_site_option( self::COLORS_SETTING, $_POST['mosaic_theme_colors'] );

			self::process_sass();
		}

		$this->load_colors( TRUE );

		echo '<div class="wrap">';
		echo '<h1>Mosaic Sections Home Page Colors</h1>';

		// Output the error(s), if any, when the SASS was compiled
		if ( self::$message ) {
			echo '<div class="notice notice-error"><p>' . self::$message . '</p></div>';
		}

		if ( self::$success ) {
			echo '<div class="notice notice-success"><p>' . self::$success . '</p></div>';
		}

		if ( is_multisite() && count( get_sites() ) > 1 ) {
			echo '<div class="notice notice-warning"><p><strong>IMPORTANT:</strong> These color schemes may be used across multiple sites in this multi-site network.  Be careful when changing colors!</p></div>';
		}

		echo '<div class="notice notice-warning"><p>Note: All colors should be entered in as hex.  Example: #11bbff - shorthand is acceptable as well, such as: #ccc</p></div>';

		$plugin_styles = plugin_dir_path( __FILE__ );
		$plugin_styles = str_replace( '/includes/', '/', $plugin_styles );
		$plugin_css    = $plugin_styles . 'style-theme-core.css';

		if ( ! is_writable( $plugin_css ) ) {
			echo '<div class="error"><p><strong>WARNING! The style file is NOT writeable.<br>Changes you make here will NOT be honored on the display of your site.</strong></p></div>';
		}

		echo '<form class="mosaic-admin-colors" method="post" action="admin.php?page=mosaic_manage_colors">';


		echo '<table class="form-table">';

		echo '<tr><th><h2>Header & Footer</h2></th><th>Color</th><th>Background Color</th><th>Link Color</th><th>Link Hover Color</th></tr>';

		echo '<tr><th>Header Color Scheme</th>';
		echo '<td><input class="color" name="mosaic_theme_navigation_color_scheme[color]" value="' . $this->navigation_color_scheme['color'] . '"></td>';
		echo '<td><input class="background" name="mosaic_theme_navigation_color_scheme[background]" value="' . $this->navigation_color_scheme['background'] . '"></td>';
		echo '<td><input class="color link" name="mosaic_theme_navigation_color_scheme[link-color]" value="' . $this->navigation_color_scheme['link-color'] . '"></td>';
		echo '<td><input class="color link" name="mosaic_theme_navigation_color_scheme[link-hover-color]" value="' . $this->navigation_color_scheme['link-hover-color'] . '"></td>';
		echo '</tr>';

		echo '<tr><th>Footer Color Scheme</th>';
		echo '<td><input class="color" name="mosaic_theme_footer_color_scheme[color]" value="' . $this->footer_color_scheme['color'] . '"></td>';
		echo '<td><input class="background" name="mosaic_theme_footer_color_scheme[background]" value="' . $this->footer_color_scheme['background'] . '"></td>';
		echo '<td><input class="color link" name="mosaic_theme_footer_color_scheme[link-color]" value="' . $this->footer_color_scheme['link-color'] . '"></td>';
		echo '<td><input class="color link" name="mosaic_theme_footer_color_scheme[link-hover-color]" value="' . $this->footer_color_scheme['link-hover-color'] . '"></td>';
		echo '</tr>';
		echo '<tr><th><h2>Colors Schemes</h2></th><th>Name</th><th>Color</th><th>Background Color</th></tr>';
		foreach ( $this->color_schemes AS $key => $data ) {
			$ord = ucwords( str_ireplace( 'color-', '', $key ) );
			echo '<tr><th>Color Scheme ' . $ord . ':<p class="tip">CSS Class: .' . $key . '</p></th>';
			echo '<td><input name="mosaic_theme_color_schemes[' . $key . '][name]" value="' . $data['name'] . '" /></td>';
			echo '<td><input class="color" name="mosaic_theme_color_schemes[' . $key . '][color]" value="' . $data['color'] . '" /></td>';
			echo '<td><input class="background" name="mosaic_theme_color_schemes[' . $key . '][background]" value="' . $data['background'] . '" /></td>';
			echo '</tr>';
		}

		echo '<tr><th><h2>Highlight Colors</h2></th><th>Name</th><th colspan="2">Color</th></tr>';
		$counter = 1;
		foreach ( $this->colors AS $key => $data ) {
			$ord = $this->get_number_word( $counter++ );
			echo '<tr><th>Highlight Color ' . $ord . ':<p class="tip">CSS Class: .' . $key . '</p></th>';
			echo '<td><input name="mosaic_theme_colors[' . $key . '][name]" value="' . $data['name'] . '" /></td>';
			echo '<td><input class="color" name="mosaic_theme_colors[' . $key . '][color]" value="' . $data['color'] . '" /></td>';
			echo '<td><span class="background" style="background: black; display: inline-block; width: 90px; height: 25px; font-weight: bold; line-height: 25px; padding: 2px 10px; text-align: center;">ON BLACK</span></td>';
			echo '</tr>';
		}

		echo '<tr><th><input type="submit" name="mosaic_scheme_submit" class="button button-primary" value="Save Colors"></th><td colspan="3"></td></tr>';
		echo '</table>';
		echo '</form>';
		?>
        <style>.mosaic-theme-admin-colors .form-table th {
                vertical-align: bottom;
            }

            #section-chooser-lightbox {
                display: none;
            }

            .mosaic-theme-admin-colors .form-table th h2 {
                margin-bottom: 0;
            }

            .tip {
                margin: 0;
                padding: 0;
                color: #888;
                font-size: .7rem;
                font-weight: 300;
                font-style: italic;
                text-shadow: 1px 1px 1px white, -1px -1px 1px white;
            }

            .mosaic-admin-colors h2 {
                padding: 0;
                margin: 0;
            }
        </style>
        <script>
          jQuery( function ( $ ) {
            setStyle();
            $( 'input.background' ).on( 'keyup blur', function () {
              changeBackgrounds( $( this ) );
            } ).each( function () {
              changeBackgrounds( $( this ) );
            } );

            $( 'input.color' ).on( 'keyup blur', function () {
              changeColors( $( this ) );
            } ).each( function () {
              changeColors( $( this ) );
            } );

            function changeBackgrounds( $el ) {
              var len = $el.val().length;
              if ( 4 == len || 7 == len ) {
                var color = $el.val();
                $el.css( 'background', color );
                $el.closest( 'tr' ).find( 'input.color' ).css( 'background', color );
              }
            }

            function changeColors( $el ) {
              var len = $el.val().length;
              var $bg = $el.closest( 'tr' ).find( '.background' );
              if ( 4 == len || 7 == len ) {
                var color = $el.val();
                $el.css( 'color', color );
                if ( !$el.hasClass( 'link' ) ) {
                  $bg.css( 'color', color );
                } else {
                  $el.css( 'background', $bg.val() );
                  return;
                }
              }

              if ( !DRCheckColorDarkness( color, 200 ) ) {
                $el.css( 'background-color', '#000000' );
              } else {
                $el.css( 'background-color', '#ffffff' );
              }
            }

            function setStyle() {
              $( 'td input' ).css( { border: '1px solid #888', padding: '5px 10px' } );
              $( 'input.background' ).each( function () {
                changeBackgrounds( $( this ) );
              } );
              $( 'input.color' ).each( function () {
                changeColors( $( this ) );
              } );
            }

            function DRCheckColorDarkness( color, threshold ) {
              if ( !color ) {
                return false;
              }

              threshold = threshold || 40;
              if ( color.indexOf( 'rgb' ) >= 0 ) {
                color   = color.replace( 'rgb', '' );
                color   = color.replace( '(', '' );
                color   = color.replace( ')', '' );
                var rgb = color.split( ',' );
                r       = rgb[ 0 ];
                g       = rgb[ 1 ];
                b       = rgb[ 2 ];
              } else {
                color = color.replace( '#', '' );      // strip #
                if ( color.length < 4 ) {
                  color = color[ 0 ] + color[ 0 ] + color[ 1 ] + color[ 1 ] + color[ 2 ] + color[ 2 ];
                }
                var rgb = parseInt( color, 16 );   // convert rrggbb to decimal
                var r   = ( rgb >> 16 ) & 0xff;  // extract red
                var g   = ( rgb >> 8 ) & 0xff;  // extract green
                var b   = ( rgb >> 0 ) & 0xff;  // extract blue
              }

              var luma = ( 0.2126 * r ) + ( 0.7152 * g ) + ( 0.0722 * b ); // per ITU-R BT.709

              if ( luma < threshold ) {
                return true;
              }

              return false;
            }
          } );
        </script>
		<?php
	}

	/**
	 * Provides color chooser for Sections interface.
	 *
	 * @param string $name
	 * @param array  $data
	 * @param string $class
	 */
	private function color_scheme_chooser( $name, $data, $class = 'section-chooser' ) {
		$this->load_colors();
		$color   = _::get( $data, 'color' );
		$current = NULL;

		if ( $color ) {
			$current = _::get( $this->color_schemes, $color );
		}

		$current_bg    = _::get( $current, 'background', '#fff' );
		$current_color = _::get( $current, 'color', '#444' );
		$current_name  = _::get( $current, 'name', 'Select...' );

		if ( ! ( _::get( $data, 'skip_label', FALSE ) ) ) {
			echo '<div class="color-scheme-wrapper ' . $class . '"><label>Color Scheme</label>';
		}

		echo '<div class="mosaic-color-chooser">';
		echo '<div class="mosaic-color-selected" style="background: ' . $current_bg . '; color: ' . $current_color . '">' . $current_name . '</div>';
		echo '<div class="mosaic-color-options">';

		echo '<div class="mosaic-color-option no-color-scheme" data-bg="#fff" data-color="#444" data-option="" style="background: #fff; color: #444;">No Color Scheme</div>';

		foreach ( $this->color_schemes AS $value => $color_scheme ) {
			$current_bg    = _::get( $color_scheme, 'background' );
			$current_color = _::get( $color_scheme, 'color' );
			$current_name  = _::get( $color_scheme, 'name' );
			echo '<div class="mosaic-color-option" data-bg="' . $current_bg . '" data-color="' . $current_color . '" data-option="' . $value . '" style="background: ' . $current_bg . '; color: ' . $current_color . '">' . $current_name . '</div>';
		}

		echo '</div>';
		echo '<input type="hidden" name="' . $name . '" value="' . $color . '">';
		echo '</div>';

		if ( ! ( _::get( $data, 'skip_label', FALSE ) ) ) {
			echo '</div>';
		}
	}

	public function color_scheme_picker( $name, $selected, $skip_label = TRUE ) {
		$this->color_scheme_chooser( $name, [ 'color' => $selected, 'skip_label' => $skip_label ] );
	}

	/**
	 * Provides color chooser for Sections interface.
	 *
	 * @param string $name
	 * @param array  $data
	 * @param string $label
	 */
	public function color_highlight_chooser( $name, $data, $label = 'Color' ) {
		$this->load_colors();
		$color   = _::get( $data, 'color' );
		$current = NULL;

		if ( $color ) {
			$current = _::get( $this->colors, $color );
		}

		$current_color = _::get( $current, 'color', '#444' );
		$current_name  = _::get( $current, 'name', 'Select...' );

		echo '<div><label>' . $label . '</label>';
		echo '<div class="mosaic-color-chooser">';
		echo '<div class="mosaic-color-selected" style="background: ' . $current_color . '; color: white; text-shadow: 0 0 3px black;">' . $current_name . '</div>';
		echo '<div class="mosaic-color-options">';

		echo '<div class="mosaic-color-option no-color-scheme" data-bg="#fff" data-color="#444" data-option="" style="background: #fff; color: #444;">No Color Scheme</div>';

		foreach ( $this->colors AS $value => $data ) {
			$current_color = _::get( $data, 'color' );
			$current_name  = _::get( $data, 'name' );
			echo '<div class="mosaic-color-option" data-bg="' . $current_color . '" data-color="#fff" data-option="' . $value . '" style="background: ' . $current_color . '; color: black; text-shadow: 0 0 3px white;">' . $current_name . '</div>';
		}

		echo '</div>';
		echo '<input type="hidden" name="' . $name . '" value="' . $color . '">';
		echo '</div>';
		echo '</div>';

	}

	/**
	 * Loads the color schemes into the class variables.
	 *
	 * @param bool $force
	 */
	private function load_colors( $force = FALSE ) {
		if ( ! $force && NULL !== $this->color_schemes ) {
			return;
		}

		$colors        = get_site_option( self::COLORS_SETTING );
		$schemes       = get_site_option( self::COLORS_SCHEMES_SETTING );
		$header_scheme = get_site_option( self::COLORS_SCHEMES_HEADER_SETTING );
		$footer_scheme = get_site_option( self::COLORS_SCHEMES_FOOTER_SETTING );

		$all_color_settings = [
			self::COLORS_SETTING                => $colors,
			self::COLORS_SCHEMES_SETTING        => $schemes,
			self::COLORS_SCHEMES_HEADER_SETTING => $header_scheme,
			self::COLORS_SCHEMES_FOOTER_SETTING => $footer_scheme
		];

		$all_color_settings = array_map( function ( $color_settings ) {
			return [
				'colors' => $color_settings,
                // Will be FALSE if value of color setting is empty/FALSE
				'is_set' => ! ( empty( $color_settings ) )
			];
		}, $all_color_settings );

		$colors        = self::ensure_colors( $colors );
		$schemes       = self::ensure_color_schemes( $schemes );
		$header_scheme = self::ensure_mini_scheme( $header_scheme );
		$footer_scheme = self::ensure_mini_scheme( $footer_scheme );

		$all_color_settings[ self::COLORS_SETTING ]['colors']                = $colors;
		$all_color_settings[ self::COLORS_SCHEMES_SETTING ]['colors']        = $schemes;
		$all_color_settings[ self::COLORS_SCHEMES_HEADER_SETTING ]['colors'] = $header_scheme;
		$all_color_settings[ self::COLORS_SCHEMES_FOOTER_SETTING ]['colors'] = $footer_scheme;

		foreach ( $all_color_settings as $setting => $data ) {

			if ( ! _::get( $data, 'is_set' ) ) {
				// Ensure setting has appropriate color values
				update_site_option( $setting, _::get( $data, 'colors' ) );
			}
		}

		$this->color_schemes           = $schemes;
		$this->colors                  = $colors;
		$this->navigation_color_scheme = $header_scheme;
		$this->footer_color_scheme     = $footer_scheme;
	}

	public static function ensure_mini_scheme( $mini_scheme ) {
		$default = [
			'color'            => '#444',
			'background'       => '#fff',
			'link-color'       => '#444',
			'link-hover-color' => '#000'
		];

		foreach ( $default AS $key => $values ) {
			if ( ! isset( $mini_scheme[ $key ] ) ) {
				$mini_scheme[ $key ] = $values;
			}
		}

		return $mini_scheme;
	}

	public static function ensure_colors( $colors ) {
		$default = [
			'color-fifteen'    => [
				'name'  => 'Color One',
				'color' => '#444'
			],
			'color-sixteen'    => [
				'name'  => 'Color Two',
				'color' => '#444'
			],
			'color-seventeen'  => [
				'name'  => 'Color Three',
				'color' => '#444'
			],
			'color-eighteen'   => [
				'name'  => 'Color Four',
				'color' => '#444'
			],
			'color-nineteen'   => [
				'name'  => 'Color Five',
				'color' => '#444'
			],
			'color-twenty'     => [
				'name'  => 'Color Six',
				'color' => '#444'
			],
			'color-twenty-one' => [
				'name'  => 'Color Seven',
				'color' => '#444'
			]
		];

		foreach ( $default AS $key => $values ) {
			if ( ! isset( $colors[ $key ] ) ) {
				$colors[ $key ] = $values;
			}
		}

		return $colors;
	}

	public static function ensure_color_schemes( $schemes ) {
		$default = [
			'color-one'      => [
				'name'       => 'Scheme One',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-two'      => [
				'name'       => 'Scheme Two',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-three'    => [
				'name'       => 'Scheme Three',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-four'     => [
				'name'       => 'Scheme Four',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-five'     => [
				'name'       => 'Scheme Five',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-six'      => [
				'name'       => 'Scheme Six',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-seven'    => [
				'name'       => 'Scheme Seven',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-eight'    => [
				'name'       => 'Scheme Eight',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-nine'     => [
				'name'       => 'Scheme Nine',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-ten'      => [
				'name'       => 'Scheme Ten',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-eleven'   => [
				'name'       => 'Scheme Eleven',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-twelve'   => [
				'name'       => 'Scheme Twelve',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-thirteen' => [
				'name'       => 'Scheme Thirteen',
				'background' => '#fff',
				'color'      => '#444'
			],
			'color-fourteen' => [
				'name'       => 'Scheme Fourteen',
				'background' => '#fff',
				'color'      => '#444'
			]
		];

		foreach ( $default AS $key => $colors ) {
			if ( ! isset( $schemes[ $key ] ) ) {
				$schemes[ $key ] = $colors;
			}
		}

		return $schemes;
	}

	/**
	 * Compile the SASS into CSS, utilizing the newly defined colors.
	 *
	 * @return mixed
	 */
	public static function process_sass( $via_ajax = FALSE ) {
		if ( ! $via_ajax && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return NULL;
		}

		if ( isset( $_POST['mosaic_theme_color_schemes'] ) ) {
			$schemes                 = $_POST['mosaic_theme_color_schemes'];
			$colors                  = $_POST['mosaic_theme_colors'];
			$navigation_color_scheme = $_POST['mosaic_theme_navigation_color_scheme'];
			$footer_color_scheme     = $_POST['mosaic_theme_footer_color_scheme'];
		} else {
			$schemes                 = get_site_option( 'mosaic_theme_color_schemes' );
			$colors                  = get_site_option( 'mosaic_theme_colors' );
			$navigation_color_scheme = get_site_option( 'mosaic_theme_navigation_color_scheme' );
			$footer_color_scheme     = get_site_option( 'mosaic_theme_footer_color_scheme' );
		}

		$plugin_styles = plugin_dir_path( __FILE__ );
		$plugin_styles = str_replace( '/includes/', '/', $plugin_styles );

		$theme_scss_file = $plugin_styles . 'style-theme-core.scss';
		$variables_file  = $plugin_styles . 'style-theme-variables.scss';
		$theme_css_file  = $plugin_styles . 'style-theme-core.css';

		$scss = '';

		// Indenting look odd? It's necessary for pretty output in css file...
		$comments = <<<SCSS
/*! **********************************************************************
 * **** **** **** ***** **** * WARNING! * *** *** *** **** **** **** **** 
 * *** This file is auto-generated.  DO NOT make changes to this file,
 * *** they will be overwritten!
 ************************************************************************

SCSS;

		//Create the variables SCSS for "Navigation"
		foreach ( $navigation_color_scheme as $key => $value ) {
			$comment  = 'navigation-' . $key . ': ' . $value . PHP_EOL;
			$comments .= $comment;
			$scss     .= '//' . $comment;
			$scss     .= '$navigation-' . $key . ': ' . $value . ';' . PHP_EOL;
		}

		//Create the variables SCSS for "Footer"
		foreach ( $footer_color_scheme as $key => $value ) {
			$comment  = 'footer-' . $key . ': ' . $value . PHP_EOL;
			$comments .= $comment;
			$scss     .= '//' . $comment;
			$scss     .= '$footer-' . $key . ': ' . $value . ';' . PHP_EOL;
		}

		// Create the variables SCSS for "Schemes"
		foreach ( $schemes AS $key => $scheme ) {
			$word     = ( str_ireplace( 'color', 'scheme', $key ) );
			$comment  = $word . ': ' . $scheme['name'] . ' (' . $scheme['color'] . ' on ' . $scheme['background'] . ')' . PHP_EOL;
			$comments .= $comment;
			$scss     .= '// ' . $comment;
			$scss     .= '$' . $key . '-background: ' . $scheme['background'] . ';' . PHP_EOL;
			$scss     .= '$' . $key . ': ' . $scheme['color'] . ';' . PHP_EOL;
		}

		// Create the variables SCSS for "Highlights"
		foreach ( $colors AS $key => $color ) {
			$compliment = ( self::is_dark( $color['color'], 180 ) ) ? '#fff' : '#000';
			$comment    = $key . ': ' . $color['name'] . ' (' . $color['color'] . ')' . PHP_EOL;
			$comments   .= $comment;
			$scss       .= '// ' . $comment;
			$scss       .= '$' . $key . ': ' . $color['color'] . ';' . PHP_EOL;
			$scss       .= '$' . $key . '-compliment: ' . $compliment . ';' . PHP_EOL;
		}

		$comments .= '*/' . PHP_EOL;
		$scss     = $comments . $scss;

		// Output the variables SCSS file
		file_put_contents( $variables_file, $scss );

		// Load the theme-core-styles.scss file for processing, which imports the variables SCSS file
		$styles = file_get_contents( $theme_scss_file );

		// Load additional styles
		$additional_files = apply_filters( 'mosaic_additional_scss', [] );

		if ( ! empty( $additional_files ) ) {
			foreach ( $additional_files AS $file ) {
				if ( file_exists( $file ) ) {
					$styles .= file_get_contents( $file );
				}
			}
		}

		require_once 'vendor/scssphp/scss.inc.php';

		try {
			$compiler = new Compiler();
			$compiler->setImportPaths( $plugin_styles );
			$compiler->setFormatter( 'Leafo\ScssPhp\Formatter\Compressed' );
			$compiled = $compiler->compile( $styles );

			$success = file_put_contents( $theme_css_file, $compiled );

			if ( ! $success ) {
				self::$message = 'IMPORTANT: The stylesheet could NOT be written.  Please check your file permissions.';
			} else {
				self::$success = 'Colors saved, and SCSS sheet transpiled successfully.';
			}
		} catch ( \Exception $e ) {
			self::$message = 'IMPORTANT: The stylesheet could not be transpiled. Please ensure the scss files are not corrupt.';
			self::$message .= '<br>' . $e->getMessage() . ' (' . $e->getCode() . ')';
		}

		return $colors;
	}

	public function manage_mega_menu() {
		echo '<div class="wrap mosaic-mega-wrap">';
		echo '<h2>Mega Menu Setup</h2>';

		if ( isset( $_GET['settings-updated'] ) && 'true' == $_GET['settings-updated'] ) {
			echo '<div class="updated">';
			echo '<p>Mega Menus Saved.</p>';
			echo '</div>';
		}

		$existing = get_option( 'mosaic_theme_mega_menu' );

		$locations     = get_nav_menu_locations();
		$location_name = apply_filters( 'mosaic_mega_menu_location_name', 'primary' );

		$create_menu_url  = admin_url( 'nav-menus.php' );
		$create_menu_link = '<a href="' . $create_menu_url . '">';

		if ( ! isset( $locations[ $location_name ] ) || empty( $locations[ $location_name ] ) ) {
			echo '<div class="error"><p>';
			echo sprintf( _( 'A Mega Menu can be built for your "Primary Navigation Menu".  First, %sCreate a Menu%s, and assign it to the "Primary Navigation Menu" Display Location.' ), $create_menu_link, '</a>' );
			echo '</p></div>';

			return;
		}

		$location = $locations[ $location_name ];

		$menu           = wp_get_nav_menu_object( $location );
		$menu_items     = wp_get_nav_menu_items( $location );
		$edit_menu_url  = add_query_arg( [ 'menu' => $location ], $create_menu_url );
		$edit_menu_link = '<a href="' . $edit_menu_url . '">';

		if ( ! $menu_items ) {
			echo '<div class="error"><p>';
			echo sprintf( _( 'A Mega Menu must have menu items in order to build a menu.  Please %sEdit your "%s" Menu%s, and add items to the menu.' ), $edit_menu_link, $menu->name, '</a>' );
			echo '</p></div>';

			return;
		}

		$top_level_menu_items = $this->filter_parent_menu_items( $menu_items );

		foreach ( $top_level_menu_items AS $index => $parent ) {
			// Find all of the menu items set to be a "child" of this parent item
			$children = $this->filter_child_menu_items( $menu_items, $parent->ID );

			$top_level_menu_items[ $index ]->children        = $children;
			$top_level_menu_items[ $index ]->number_children = count( $children );
		}

		$available = get_terms( 'nav_menu', [ 'hide_empty' => TRUE ] );

		if ( ! $available ) {
			echo '<div class="error"><p>';
			echo sprintf( _( 'A Mega Menu utilizes custom menus that can be assigned as the "Child Menus".  To add custom menus to your Mega Menu, %sCreate a Menu%s, and make note of the menu name.' ), $create_menu_link, '</a>' );
			echo '</p></div>';

			return;
		}

		// Reject the main menu we're working with
		$available = array_filter( $available, function ( $menu ) use ( $location ) {
			return ( $menu->term_id != $location );
		} );

		echo '<form class="mosaic-admin-colors" method="post" action="options.php">';
		settings_fields( self::MENU_SETTINGS_GROUP );

		echo '<h3>Menu: ' . $menu->name . '</h3>';

		$tabs        = '';
		$tab_content = '';
		$tab_class   = 'current';

		foreach ( $top_level_menu_items AS $index => $parent ) {
			$tabs        .= '<a class="' . $tab_class . '" href="javascript:void(0);" data-menu-id="' . $parent->ID . '">' . $parent->title . '</a>';
			$tab_content .= '<div class="menu-item mega-menu-item ' . $tab_class . '" data-menu-id="' . $parent->ID . '">';
			$tab_content .= '<div class="menu-controls">';

			$data = [ 'color' => _::get( $existing, [ $parent->ID, 'background' ] ) ];
			$name = self::MENU_SETTINGS . '[background][' . $parent->ID . ']';
			ob_start();
			$this->color_highlight_chooser( $name, $data, 'Background Color' );

			$data = [ 'color' => _::get( $existing, [ $parent->ID, 'color' ] ) ];
			$name = self::MENU_SETTINGS . '[color][' . $parent->ID . ']';
			$this->color_highlight_chooser( $name, $data, 'Text Color' );
			$tab_content .= ob_get_clean();

			$tab_content .= '<div><label>Opacity</label><input name="' . self::MENU_SETTINGS . '[opacity][' . $parent->ID . ']" type="number" max="100" min="0" value="' . _::get( $existing, [
					$parent->ID,
					'opacity'
				], 100 ) . '" />%</div>';
			$tab_content .= '<div><a href="javascript:void(0);" class="button button-primary button-small preview preview-' . $parent->ID . '">Preview</a></div>';
			$tab_content .= '</div>';
			$tab_content .= '<div data-menu-id="' . $parent->ID . '" class="mega-menu">';
			$tab_content .= '</div>';
			$tab_content .= '<div class="menu-background"></div>';
			$tab_content .= '</div>';
			$tab_class   = '';
		}

		echo '<div id="mega-menu-tabs">' . $tabs . '</div>';
		echo '<div id="mega-menu-content">' . $tab_content . '</div>';

		echo '<div class="available-menus">';
		echo '<h3>Available Menus</h3>';
		echo '<div id="available-menus">';

		foreach ( $available AS $menu ) {
			echo '<div data-menu-id="' . $menu->term_id . '"><p class="menu-title">' . $menu->name . ' <small>(' . $menu->count . ' items)</small></p></div>';
		}

		echo '</div>';
		echo '<div style="text-align: center; padding-bottom: 5px;"><a class="button button-small" href="' . $create_menu_url . '">Create New Menu</a></div>';
		echo '</div>';

		echo '<div class="submit-buttons"><input type="submit" class="button button-primary" value="Save Menu"></div>';

		echo '</form>';
		echo '</div>';

		$menu_data = [];

		foreach ( $available AS $menu ) {
			$data         = wp_get_nav_menu_items( $menu->term_id );
			$parent_items = $this->filter_parent_menu_items( $data );

			foreach ( (array) $parent_items AS $index => $item ) {
				$children = $this->filter_child_menu_items( $data, $item->ID );

				foreach ( (array) $children AS $child_index => $child_item ) {
					$children[ $child_index ] = [
						'ID'    => $child_item->ID,
						'title' => $child_item->title
					];
				}

				$parent_items[ $index ] = [
					'ID'       => $item->ID,
					'title'    => $item->title,
					'children' => $children
				];
			}

			$menu_data[ $menu->term_id ] = [
				'name'  => $menu->name,
				'items' => $parent_items
			];
		}

		if ( ! empty( $existing ) ) {
			foreach ( $existing AS $mega_menu_id => $data ) {
				$existing[ $mega_menu_id ] = _::get( $data, 'menus' );
			}
		}

		echo '<script>var mosaicAvailableMenus = ' . json_encode( $menu_data ) . ';
		var mosaicExistingMenus = ' . json_encode( $existing ) . ';</script>';
	}

	/**
	 * Given an array of menu items, return only the top-level ("parent") menu items.
	 *
	 * @param $menu
	 *
	 * @return array
	 */
	private function filter_parent_menu_items( $menu ) {
		if ( ! $menu || empty( $menu ) || ! is_array( $menu ) ) {
			return [];
		}

		return array_filter( $menu, function ( $item ) {
			return ( empty( $item->menu_item_parent ) );
		} );
	}

	/**
	 * Given an array of menu items, return the children of a given menu item ID
	 *
	 * @param array $menu
	 * @param int   $parent_id
	 *
	 * @return array
	 */
	private function filter_child_menu_items( $menu, $parent_id ) {
		if ( ! $menu || empty( $menu ) || ! is_array( $menu ) ) {
			return [];
		}

		// Find all of the menu items set to be a "child" of this parent item
		return array_filter( $menu, function ( $item ) use ( $parent_id ) {
			return ( $parent_id == $item->menu_item_parent );
		} );
	}

	/**
	 * Alter the structure of the saved data for easier use on the front-end.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function process_menu_save( $data ) {
		$save = [];

		$menu_ids      = _::get( $data, 'menu_id' );
		$menu_headings = _::get( $data, 'menu_heading' );
		$opacities     = _::get( $data, 'opacity' );
		$colors        = _::get( $data, 'color' );
		$backgrounds   = _::get( $data, 'background' );
		$menu_images   = _::get( $data, 'image' );
		$menu_classes  = _::get( $data, 'menu_classes' );


		foreach ( $opacities AS $mega_menu_id => $opacity ) {
			$headings  = _::get( $menu_headings, $mega_menu_id );
			$images    = _::get( $menu_images, $mega_menu_id );
			$classes   = _::get( $menu_classes, $mega_menu_id );
			$mega_menu = [];

			$menus = _::get( $data, [ 'menu_id', $mega_menu_id ], [] );

			foreach ( $menus AS $index => $menu_id ) {
				$mega_menu[] = [
					'menu_id' => $menu_id,
					'heading' => _::get( $headings, $index ),
					'image'   => _::get( $images, $index ),
					'classes' => _::get( $classes, $index )
				];
			}

			$save[ $mega_menu_id ] = [
				'menus'      => $mega_menu,
				'opacity'    => $opacity,
				'color'      => _::get( $colors, $mega_menu_id ),
				'background' => _::get( $backgrounds, $mega_menu_id )
			];
		}

		return $save;
	}

	/**
	 * Utility to determine if a given color is "dark" or "light".
	 * Utilized to automatically calculate the "complimentary color" of a highlight color.
	 *
	 * @param string $color
	 * @param int    $threshold
	 *
	 * @return bool
	 */
	private static function is_dark( $color, $threshold = 40 ) {
		if ( ! $color ) {
			return FALSE;
		}

		$color = str_replace( '#', '', $color );      // strip #

		if ( strlen( $color ) < 4 ) {
			$color = str_split( $color );
			$color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
		}

		$rgb = base_convert( $color, 16, 10 );   // convert rrggbb to decimal
		$r   = ( $rgb >> 16 ) & 0xff;  // extract red
		$g   = ( $rgb >> 8 ) & 0xff;  // extract green
		$b   = ( $rgb >> 0 ) & 0xff;  // extract blue

		$luma = ( 0.2126 * $r ) + ( 0.7152 * $g ) + ( 0.0722 * $b ); // per ITU-R BT.709

		if ( $luma < $threshold ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Given an integer, return the word representation.  (eg, pass in "3" and get "Three")
	 *
	 * @param (int)$number
	 *
	 * @return string
	 */
	private function get_number_word( $number ) {
		$words = [
			'Zero',
			'One',
			'Two',
			'Three',
			'Four',
			'Five',
			'Six',
			'Seven',
			'Eight',
			'Nine',
			'Ten'
		];

		return ( ! empty( $words[ $number ] ) ) ? $words[ $number ] : $number;
	}

	/**
	 * Utilized in media upload.
	 * Allows rendering / using specific media sizes (medium, large, etc)
	 */
	public function ajax_media_upload() {
		$attachment_id = _::get( $_POST, 'attachment_id' );
		$size          = _::get( $_POST, 'size' );

		echo wp_get_attachment_image_url( $attachment_id, $size );
		die();
	}

	public function ajax_save_to_library() {
		$section     = _::get( $_POST, 'section' );
		$name        = _::get( $_POST, 'name' );
		$description = _::get( $_POST, 'description' );
		$index       = _::get( $_POST, 'index' );
		$section     = $section[ $index ];

		if ( ! $name ) {
			$name = 'unnamed';
		}

		$this->add_to_library( $name, $description, $section );
		die();
	}

	public function ajax_load_library() {
		$library = stripslashes_deep( $this->get_library() );
		echo json_encode( $library );
		die();
	}

	public function add_to_library( $name, $description, $section ) {
		$library   = $this->get_library();
		$library[] = [
			'name'        => $name,
			'description' => $description,
			'data'        => $section
		];

		// strip any accidentally empty sections
		$library = array_filter( $library );
		// rebase keys to be sequential and 0 based
		$library = array_values( $library );

		$this->update_library( $library );
	}

	public function ajax_delete_library() {
		$index   = _::get( $_POST, 'index' );
		$section = _::get( $_POST, 'section' );

		$sections = $this->get_library();
		$search   = _::get( $sections, $index );

		if ( $search ) {
			if ( $search == $section ) {
				unset( $sections[ $index ] );
				$sections = array_values( $sections );
				$this->update_library( $sections );
			}
		}

		// TODO: return meaningful messages
		die();
	}

	public function get_library() {
		$library = (array) get_option( 'mosaic_sections_library' );
		// strip any accidentally empty sections
		$library = array_filter( $library );
		// rebase keys to be sequential and 0 based
		$library = array_values( $library );

		return $library;
	}

	public function update_library( $library ) {
		update_option( 'mosaic_sections_library', $library );
	}

	/**
	 * Utility for creating dropdowns from an array of options.
	 *
	 * @param string $name
	 * @param array  $array
	 * @param mixed  $selected_value
	 * @param string $select_text
	 *
	 * @return string
	 */
	public function create_dropdown( $name, $array, $selected_value, $select_text = 'Please Select...', $class = '' ) {
		$select = '<select name="' . $name . '" class="' . $class . '">';

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
	 * Method to build / retrieve the list of utility classes available to use.
	 */
	private function utility_classes() {
		// be sure to mirror the classes in style-utilities.scss
		$margins = [
			'0'    => '0',
			'25em' => '.25em',
			'5em'  => '.5em',
			'75em' => '.75em',
			'1em'  => '1em',
			'15em' => '1.5em',
			'2em'  => '2em',
			'auto' => 'auto'
		];

		$sides = [
			'top'    => 't',
			'right'  => 'r',
			'bottom' => 'b',
			'left'   => 'l'
		];

		$margin_classes  = [];
		$padding_classes = [];

		foreach ( $margins AS $name => $em ) {
			foreach ( $sides AS $side => $abbr ) {
				$margin_classes["m{$abbr}-{$name}"]  = "margin {$side} {$em}";
				$padding_classes["p{$abbr}-{$name}"] = "padding {$side} {$em}";
			}
		}

		$classes = array_merge( $margin_classes, $padding_classes );

		$classes['text-left']   = 'left align text';
		$classes['text-center'] = 'center align text';
		$classes['text-right']  = 'right align text';

		$classes['pull-left']   = 'left float the item';
		$classes['right-right'] = 'right float the item';

		$classes['row']            = 'row container for columns';
		$classes['col']            = 'generic column class';
		$classes['col-1']          = 'full width (100%) column';
		$classes['col-2']          = 'two-column (50%) column';
		$classes['col-3']          = 'three-column (33%) column';
		$classes['col-4']          = 'four-column (25%) column';
		$classes['col-one-third']  = 'three column (33%) image-wrapper';
		$classes['col-two-thirds'] = 'two-thirds (66%) image-wrapper';

		$classes['same-height'] = 'cause adjacent boxes to be the same height';

		return apply_filters( 'mosaic_class_chooser', $classes );
	}
}

