<?php
/**
 * @package Alpha Channel Group Base Theme
 * @author  Alpha Channel Group (www.alphachannelgroup.com)
 */

// Define this for use in a few places
define( 'ACG_THEME_NAME', "Mosaic Sections Theme" );
// Theme version defined in ACGTheme class below

// Require the relevant module files
$mosaic_plugin_path = plugin_dir_path( __FILE__ );
require_once $mosaic_plugin_path . "includes/_.php";
// Custom post types must be before "sidebars" in order to properly add sidebars via filter
require_once $mosaic_plugin_path . "includes/custom-post-type.php";
//require_once $mosaic_plugin_path . "includes/custom-post-events.php";
//require_once $mosaic_plugin_path . "includes/custom-post-team.php";
require_once $mosaic_plugin_path . "includes/megamenus.php";
require_once $mosaic_plugin_path . "includes/postrender.php";
require_once $mosaic_plugin_path . "includes/custom-taxonomies.php";
require_once $mosaic_plugin_path . "includes/galleries.php";
require_once $mosaic_plugin_path . "includes/projects.php";
require_once $mosaic_plugin_path . "includes/sidebars.php";
require_once $mosaic_plugin_path . "includes/shortcodes.php";
require_once $mosaic_plugin_path . "includes/widgets.php";
require_once $mosaic_plugin_path . "includes/menus.php";
require_once $mosaic_plugin_path . "includes/social_media.php";
require_once $mosaic_plugin_path . "includes/version.class.php";
require_once $mosaic_plugin_path . "includes/home.template.admin.functions.php";
require_once $mosaic_plugin_path . "includes/home.template.render.functions.php";
require_once $mosaic_plugin_path . "includes/taxonomies.php";

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once $mosaic_plugin_path . "includes/cli.class.php";
}

/**
 * @var MosaicHomeTemplateRender
 */
global $mosaic_home_template;

/**
 * Core theme class.
 * Designed to be extensible with select hooks and actions.
 */
class MosaicTheme {

	const THEME_VERSION = '2.2.0';

	private static $initialized = FALSE;
	public static $allowed_group = 'manage_options';

	private static $stay_in_cat;
	private static $dequeued = [];


	private static $mega_menu = NULL;
	private static $has_mega_menu = NULL;
	private static $posts_search = NULL;

	const MENU_SLUG = 'acg_admin_menu';
	const SETTINGS = 'acg_options';
	const SETTINGS_GROUP = 'acg_options_group';

	private static $theme_options = [
		'use_testimonials' => 'custom-post-testimonials.php',
		'use_events'       => 'custom-post-events.php',
		'use_team'         => 'custom-post-team.php'
	];

	private static $label_map = [
		'title'      => 'Post Title',
		'featured'   => 'Featured Image',
		'meta'       => 'Post Author / Date',
		'source'     => 'Source',
		'content'    => 'Post Content',
		'excerpt'    => 'Post Excerpt',
		'categories' => 'Categories',
		'tags'       => 'Tags',
		'taxonomies' => 'Custom Taxonomies'
	];


	public static function initialize() {
		if ( self::$initialized ) {
			return;
		}

		global $mosaic_home_template;

		MosaicThemeVersion::check();
		self::add_theme_options();
		self::add_theme_support();
		self::add_filters();
		self::add_actions();

		new MosaicHomeTemplateInterface();
		$mosaic_home_template = new MosaicHomeTemplateRender();

		do_action( 'mosaic_sections_theme_loaded' );
	}

	private static function add_theme_support() {
		// Enable RSS feeds
		add_theme_support( 'automatic-feed-links' );

		// Enable featured images
		add_theme_support( 'post-thumbnails' );

		// Enable the gallery post format
		// See http://codex.wordpress.org/Post_Formats
		add_theme_support( 'post-formats', [ 'gallery' ] );

		// Enable "<title> support
		// See https://codex.wordpress.org/Title_Tag
		add_theme_support( 'title-tag' );

		// Enable excerpts for pages
		// Uncomment to enable
		// TODO: Create setting!
		// add_post_type_support('page', 'excerpt');
	}

	private static function add_theme_options() {
		$mosaic_plugin_path = plugin_dir_path( __FILE__ );

		foreach ( self::$theme_options AS $opt => $include ) {
			if ( self::get_option( $opt ) ) {
				require_once $mosaic_plugin_path . 'includes/' . $include;
			}
		}
	}

	/**
	 * Loop through any actions we want to hook into and hook'em
	 */
	private static function add_actions() {
		$actions = [
			'init',
			'wp_head',
			'admin_init',
			'admin_menu',
			'admin_head',
			'admin_enqueue_scripts',
			'login_head',
			'add_meta_boxes',
			'wp_enqueue_scripts',
			'wp_print_styles',
			'wp_print_scripts',
			'wp_footer',
			'save_post'
		];

		foreach ( $actions AS $action ) {
			if ( method_exists( __CLASS__, $action ) ) {
				add_action( $action, [ __CLASS__, $action ] );
			}
		}

		add_action( 'wp_ajax_mosaic-media-upload', [ self::SETTINGS, 'ajax_media_upload' ] );

		// REMOVE WP EMOJI
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Add any filters we want to utilize.
	 */
	private static function add_filters() {
		//  Take out WP version from <head>
		add_filter( 'the_generator', [ __CLASS__, 'remove_version_info' ] );

		// Enable custom logo on login page
		add_filter( 'login_headerurl', [ __CLASS__, 'loginpage_custom_link' ] );

		global $wp_version;

		if ( 5.2 <= $wp_version ) {
			add_filter( 'login_headertext', [ __CLASS__, 'change_title_on_logo' ] );
		} else {
			add_filter( 'login_headertitle', [ __CLASS__, 'change_title_on_logo' ] );
		}

		// Add more buttons to the visual editor
		add_filter( "mce_buttons", [ __CLASS__, 'enable_more_buttons' ] );

		// Hijack the excerpt so we can use settings
		add_filter( 'the_excerpt', [ __CLASS__, 'the_excerpt' ], 0 );
		add_filter( 'excerpt_length', [ __CLASS__, 'excerpt_length' ], 999 );
		add_filter( 'excerpt_more', [ __CLASS__, 'excerpt_more' ] );

		// Support the "stay in category" functionality when navigating blog posts
		add_filter( 'get_previous_post_join', [ __CLASS__, 'get_previous_post_join_filter' ] );
		add_filter( 'get_next_post_join', [ __CLASS__, 'get_next_post_join_filter' ] );
		add_filter( 'previous_post_link', [ __CLASS__, 'previous_post_link_filter' ] );
		add_filter( 'next_post_link', [ __CLASS__, 'next_post_link_filter' ] );

		add_filter( 'body_class', [ __CLASS__, 'body_class' ] );

		add_filter( 'pre_get_posts', [ __CLASS__, 'search_query_include_sections' ] );
		add_filter( 'posts_where_paged', [ __CLASS__, 'posts_search' ], 10, 2 );
	}


	public static function init() {
		define( 'TEMPLATE_URL', get_bloginfo( "template_url" ) );
		define( 'SITE_URL', get_bloginfo( "url" ) );
		define( 'SITE_NAME', get_bloginfo( "name" ) );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui' );

		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'jquery-theme-common', TEMPLATE_URL . '/js/theme.common.js', [ 'jquery' ], self::THEME_VERSION );

		// Only register here.  Gets printed in footer only when needed.
		wp_register_script( 'acg_masonry_script', TEMPLATE_URL . '/js/masonry.js' );

		if ( self::get_option( 'font_awesome' ) || MosaicSocialMedia::get_option( 'fa_custom_button_style' ) == 1 ) {
			wp_enqueue_style( 'mosaic-font-awesome', TEMPLATE_URL . '/font-awesome/css/font-awesome.min.css">' );
		}

		add_shortcode( 'social_media_links', [ __CLASS__, 'social_media_shortcode' ] );
	}

	public static function wp_head() {
		self::google_font();
		$modernizr = self::get_option( 'modernizr' );
		if ( ! $modernizr || $modernizr == 'head' || ( $modernizr == 'default' && ! self::get_option( 'scripts_to_footer' ) ) ) {
			echo '<script type="text/javascript" src="' . TEMPLATE_URL . '/js/modernizr.js"></script>' . PHP_EOL;
		}

		self::styles_in_head();
		$data = [
			'ajaxUrl' => admin_url( 'admin-ajax.php' )
		];
		?>
        <script>
          var mosaicUData = <?php echo json_encode( $data ); ?>;
        </script>
		<?php
	}

	public static function admin_init() {
		register_setting( self::SETTINGS_GROUP, self::SETTINGS );
		wp_enqueue_style( 'mosaic-font-awesome', TEMPLATE_URL . '/font-awesome/css/font-awesome.min.css">' );
	}

	public static function admin_menu() {
		add_menu_page( ACG_THEME_NAME, ACG_THEME_NAME, self::$allowed_group, self::MENU_SLUG, [
			__CLASS__,
			'admin_settings'
		], 'dashicons-welcome-view-site', 61 );
		do_action( 'acg_admin_submenu', self::MENU_SLUG );
	}

	public static function admin_head() {
		self::google_font();
		echo '<link rel="stylesheet" type="text/css" href="' . TEMPLATE_URL . '/css/style-admin.css" />' . "\r\n";
	}

	public static function admin_enqueue_scripts() {
		$render = FALSE;

		if ( ! empty( $_GET['page'] ) && 'acg_admin_menu' == $_GET['page'] ) {
			$render = TRUE;
		}

		if ( 'page' == get_post_type() ) {
			$render = TRUE;
		}

		// Allow taxonomies like `source` to utilize theme files
		if ( array_key_exists( 'taxonomy', $_GET ) ) {
			$render = TRUE;
		}

		if ( ! $render ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'mosaic-utilities', get_stylesheet_directory_uri() . '/js/utilities.admin.jquery.js', [ 'jquery' ] );
		wp_enqueue_style( 'admin', get_stylesheet_directory_uri() . '/css/admin-home-template.css' );
		wp_register_script( 'mosaic-home-template', get_stylesheet_directory_uri() . '/js/home.template.admin.jquery.js', [
			'jquery',
			'mosaic-utilities'
		] );
		wp_enqueue_script( 'mosaic-home-template' );
	}

	public static function save_post() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		$full_background   = _::get( $_POST, '_full_background_image' );
		$square_background = _::get( $_POST, '_square_background_image' );
		$overlay_content   = _::get( $_POST, '_image_content_overlay' );
		update_post_meta( $post->ID, '_full_background_image', $full_background );
		update_post_meta( $post->ID, '_square_background_image', $square_background );
		update_post_meta( $post->ID, '_image_content_overlay', $overlay_content );
	}

	public static function wp_enqueue_scripts() {
		if ( self::get_option( 'scripts_to_footer' ) ) {
			self::move_scripts_to_footer();
		}

		wp_register_script( 'eventDatePicker', get_stylesheet_directory_uri() . '/js/eventDatePicker.jquery.js' );
		wp_register_script( 'jquery-timepicker', get_template_directory_uri() . '/js/jquery.timepicker.js' );
		wp_register_script( 'bootstrap-datepicker', get_template_directory_uri() . '/js/bootstrap-datepicker.js' );
		wp_register_script( 'datepair', get_template_directory_uri() . '/js/Datepair.js' );
		wp_register_script( 'jquery-datepair', get_template_directory_uri() . '/js/jquery.datepair.js' );
	}

	public static function styles_in_head() {
		$rendered = FALSE;

		if ( self::get_option( 'styles_in_head' ) ) {
			self::sanitize_styles( get_stylesheet_directory() . '/style.css' );
			self::sanitize_styles( get_stylesheet_directory() . '/style-theme-core.css' );
			$rendered = TRUE;
		}

		if ( ! $rendered ) {
			echo '<link type="text/css" rel="stylesheet" href="' . get_stylesheet_uri() . '" />' . PHP_EOL;
			echo '<link type="text/css" rel="stylesheet" href="' . get_stylesheet_directory_uri() . '/style-theme-core.css" />' . PHP_EOL;
		}
	}

	private static function sanitize_styles( $stylesheet ) {
		if ( ! file_exists( $stylesheet ) ) {
			return;
		}

		$styles = file_get_contents( $stylesheet );

		if ( stripos( $styles, "*/" ) !== FALSE && stripos( $styles, "*/" ) < 500 ) {
			$styles = substr( $styles, stripos( $styles, "*/" ) + 2 );
		}

		$stylesheet_directory = get_stylesheet_directory_uri();

		preg_match_all( '/url\((\'|")?images\//i', $styles, $matches, PREG_SET_ORDER );

		if ( ! empty( $matches ) ) {
			foreach ( $matches as $match ) {
				$delimiter = ( ! empty( $match[1] ) ) ? $match[1] : '';
				$str       = "url({$delimiter}{$stylesheet_directory}/images/";

				$styles = str_replace( $match[0], $str, $styles );
			}
		}

		echo "<style>" . str_ireplace( "\n", " ", $styles ) . "</style>";
	}

	/**
	 * Adds body classes to be consistent with some of the feature settings.
	 * This permits targeting specific options (such as "sidebar on left" for the blog) with CSS.
	 * Looks for features such as "blog_sidebar", "blog_featured", etc.
	 * Creates classes such as "sidebar-left", "sidebar-right", etc.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public static function body_class( $classes ) {
		global $wp_query;

		$which = '';

		if ( isset( $wp_query ) && (bool) $wp_query->is_posts_page ) {
			$which = 'blog';
		} else if ( is_archive() ) {
			$which = 'blog';
		} else if ( is_single() ) {
			$which = 'single';
		}

		if ( $which ) {
			$features = [ 'sidebar', 'featured' ];

			foreach ( $features AS $feature ) {
				$sidebar = self::get_option( "{$which}_{$feature}" );
				if ( $sidebar ) {
					$classes[] = "{$feature}-{$sidebar}";
				}
			}
		}

		return $classes;
	}

	/**
	 * Performance function.
	 * Checks a setting, then dequeues any styles to prevent them from being output in the <head>.
	 */
	public static function wp_print_styles() {
		if ( ! self::get_option( 'dequeue_styles' ) ) {
			return;
		}
		if ( ! is_user_logged_in() && ! is_admin() ) {
			$wp_styles = wp_styles();
			foreach ( $wp_styles->queue AS $handle ) {
				echo '<!-- stylesheed "' . $handle . '" dequeued for performance based on Performance Settings in theme dashboard -->' . PHP_EOL;
				wp_dequeue_style( $handle );
				self::$dequeued[] = $handle;
			}
		} else if ( is_admin() ) {
			echo '<!-- No styles dequeued because viewing admin dashboard. -->' . PHP_EOL;
		} else {
			echo '<!-- No styles dequeued because user is logged in. -->' . PHP_EOL;
		}
	}

	public static function wp_print_scripts() {
		// If the option is set to move to footer, then re-enqueue styles that were dequeued for the <head>
		if ( self::get_option( 'dequeue_styles' ) == 'footer' ) {
			foreach ( self::$dequeued AS $handle ) {
				wp_enqueue_style( $handle );
			}
		}
	}

	/**
	 * Performance function.
	 * Removes scripts from the <head> and puts them in the footer.
	 */
	public static function move_scripts_to_footer() {
		remove_action( 'wp_head', 'wp_print_scripts' );
		remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
		remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );

		add_action( 'wp_footer', 'wp_print_scripts', 5 );
		add_action( 'wp_footer', 'wp_enqueue_scripts', 5 );
		add_action( 'wp_footer', 'wp_print_head_scripts', 5 );

		// cause Gravity Forms to load scripts in footer instead of in-line at the form
		add_filter( 'gform_init_scripts_footer', '__return_true' );
	}

	public static function wp_footer() {
		if ( self::get_option( 'modernizr' ) == 'default' && self::get_option( 'scripts_to_footer' ) ) {
			echo '<script type="text/javascript" src="' . TEMPLATE_URL . '/js/modernizr.js"></script>' . PHP_EOL;
		}

		if ( defined( 'EVENTS_WIDGET_DISPLAYED' ) ) {
			wp_enqueue_script( 'jquery-timepicker', get_template_directory_uri() . '/js/jquery.timepicker.js' );
			wp_enqueue_script( 'bootstrap-datepicker', get_template_directory_uri() . '/js/bootstrap-datepicker.js' );
			wp_enqueue_script( 'datepair', get_template_directory_uri() . '/js/Datepair.js' );
			wp_enqueue_script( 'jquery-datepair', get_template_directory_uri() . '/js/jquery.datepair.js' );
			wp_enqueue_style( 'bootsrap-datepicker-css', get_template_directory_uri() . '/css/bootstrap-datepicker.css' );
			wp_enqueue_style( 'jquery-timepicker-css', get_template_directory_uri() . '/css/jquery.timepicker.css' );
			wp_enqueue_script( 'eventDatePicker', get_stylesheet_directory_uri() . '/js/eventDatePicker.jquery.js' );
		}
		?>
        <script>
          jQuery( function ( $ ) {
            $( '#wp-toolbar .mosaic-compile-scss' ).on( 'click', function ( e ) {
              e.preventDefault();

              var $this    = $( this );
              var ajax_url = window.ajaxurl || mosaicUData.ajaxUrl;
              $this.addClass( 'busy' ).find( 'a' ).html( 'Rebuilding...' );
              $.ajax(
                {
                  url: ajax_url,
                  type: 'post',
                  dataType: 'json',
                  data: {
                    action: 'rebuild_scss'
                  },
                  success: function ( data ) {
                    if ( data && data.success ) {
                      $( this ).find( 'a' ).html( 'Reloading Page' );
                      location.reload();
                    } else {
                      alert( 'CSS could not be compiled.\nBe sure to visit the WordPress dashboard, and go to\nMosaic Sections Theme => Color Schemes and define your colors.' );
                    }
                  }
                }
              )
            } );
          } );
        </script>
		<?php
	}

	/**
	 * Provides:
	 * 1. background image meta box
	 * 2. Rich text editor for excerpts
	 */
	public static function add_meta_boxes() {
		add_meta_box( 'background-image', 'Background Images', [
			__CLASS__,
			'background_images_meta_box'
		], [ 'page' ], 'side', 'high' );
		if ( ! post_type_supports( $GLOBALS['post']->post_type, 'excerpt' ) ) {
			return;
		}

		remove_meta_box(
			'postexcerpt' // ID
			, ''            // Screen, empty to support all post types
			, 'normal'      // Context
		);

		add_meta_box(
			'postexcerpt2'     // Reusing just 'postexcerpt' doesn't work.
			, __( 'Excerpt' )    // Title
			, [ __CLASS__, 'show_post_excerpt_editor' ] // Display function
			, NULL              // Screen, we use all screens with meta boxes.
			, 'normal'          // Context
			, 'core'            // Priority
		);
	}

	/**
	 * Displays the rich editor for the excerpt
	 *
	 * @param $post
	 */
	public static function show_post_excerpt_editor( $post ) { ?>
        <label class="screen-reader-text" for="excerpt"><?php
			_e( 'Excerpt' )
			?></label>
		<?php
		// We use the default name, 'excerpt', so we don’t have to care about
		// saving, other filters etc.
		wp_editor(
			self::excerpt_editor_unescape( $post->post_excerpt ),
			'excerpt',
			[
				'textarea_rows' => 15,
				'media_buttons' => FALSE,
				'teeny'         => TRUE,
				'tinymce'       => TRUE
			]
		);
	}

	/**
	 * The excerpt is escaped usually. This breaks the HTML editor.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function excerpt_editor_unescape( $str ) {
		return str_replace(
			[ '&lt;', '&gt;', '&quot;', '&amp;', '&nbsp;', '&amp;nbsp;' ]
			, [ '<', '>', '"', '&', ' ', ' ' ]
			, $str
		);
	}

	public static function enable_more_buttons( $buttons ) {
		array_push( $buttons, "backcolor", "anchor", "hr", "fontselect", "sub", "sup" );

		return $buttons;
	}

	public function ajax_media_upload() {
		$attachment_id = _::get( $_POST, 'attachment_id' );
		$size          = _::get( $_POST, 'size' );

		echo wp_get_attachment_image_url( $attachment_id, $size );
		die();
	}

	public static function background_images_meta_box() {
		global $post;
		$full_background   = get_post_meta( $post->ID, '_full_background_image', TRUE );
		$square_background = get_post_meta( $post->ID, '_square_background_image', TRUE );
		$content           = get_post_meta( $post->ID, '_image_content_overlay', TRUE );

		echo '<h4>Large Background Image</h4>';
		echo self::image_input( $full_background, '_full_background_image' );
		echo '<h4>Square Background Image</h4>';
		echo self::image_input( $square_background, '_square_background_image' );

		echo '<h4>Text Over Image</h4>';
		wp_editor( $content, '_image_content_overlay', [ 'textarea_rows' => 3 ] );
	}

	public static function social_media_shortcode( $attr ) {
		$content = '<div class="social-media-links-wrapper">';
		$content .= ( isset( $attr['facebook'] ) && $attr["facebook"] ) ? '<a target="_blank" class="facebook" href="' . $attr["facebook"] . '">		<span class="follow">Facebook</span>      <i class="fa fa-facebook"></i>      </a>' . "\r\n" : "";
		$content .= ( isset( $attr['twitter'] ) && $attr["twitter"] ) ? '<a target="_blank" class="twitter" href="' . $attr["twitter"] . '">		<span class="follow">Twitter</span>       <i class="fa fa-twitter"></i>       </a>' . "\r\n" : "";
		$content .= ( isset( $attr['google'] ) && $attr["google"] ) ? '<a target="_blank" class="google" href="' . $attr["google"] . '">			    <span class="follow">Google</span>        <i class="fa fa-google-plus"></i>   </a>' . "\r\n" : "";
		$content .= ( isset( $attr['linkedin'] ) && $attr["linkedin"] ) ? '<a target="_blank" class="linkedin" href="' . $attr["linkedin"] . '">			<span class="follow">Linkedin</span>      <i class="fa fa-linkedin"></i>      </a>' . "\r\n" : "";
		$content .= ( isset( $attr['pintrest'] ) && $attr["pintrest"] ) ? '<a target="_blank" class="pintrest" href="' . $attr["pintrest"] . '">			<span class="follow">Pintrest</span>      <i class="fa fa-pinterest-p"></i>   </a>' . "\r\n" : "";
		$content .= ( isset( $attr['instagram'] ) && $attr["instagram"] ) ? '<a target="_blank" class="instagram" href="' . $attr["instagram"] . '">          <span class="follow">Instagram</span>     <i class="fa fa-instagram"></i>     </a>' . "\r\n" : "";
		$content .= ( isset( $attr['youtube'] ) && $attr["youtube"] ) ? '<a target="_blank" class="youtube" href="' . $attr["youtube-page"] . '">		<span class="follow">YouTube</span>       <i class="fa fa-youtube-play"></i>  </a>' . "\r\n" : "";
		$content .= ( isset( $attr['amazon'] ) && $attr["amazon"] ) ? '<a target="_blank" class="amazon" href="' . $attr["amazon"] . '">		<span class="follow">Amazon</span>       <i class="fa fa-amazon"></i>  </a>' . "\r\n" : "";
		$content .= ( isset( $attr["rss-feed"] ) && $attr["rss-feed"] ) ? '<a target="_blank" class="rss" href="' . get_bloginfo( "rss2_url" ) . '">	    <span class="follow">RSS</span>           <i class="fa fa-rss"></i>           </a>' . "\r\n" : "";
		$content .= ( isset( $attr['email'] ) && $attr["email"] ) ? '<a class="email" href="mailto:' . $attr["email-address"] . '">		<span class="follow">Email</span>         <i class="fa fa-envelope"></i>      </a>' . "\r\n" : "";
		$content .= '</div>';

		return $content;
	}

// *** Site options - footer, social media, etc.
	public static function admin_settings() {

		$home_h1 = $footer_info = $blog_page_title = '';

		$options = get_option( self::SETTINGS );
		if ( $options ) {
			extract( $options );
		}

		//TODO: Commented out due to of lack of utilization

//		$pageslist            = wp_dropdown_pages( array(
//			'selected' => $options['site_option_page'],
//			'name'     => self::SETTINGS . '[site_option_page]',
//			'echo'     => FALSE
//		) );

		$section_admin = MosaicHomeTemplateInterface::get_instance();

		$font_awesome_checked = ( isset( $options['font_awesome'] ) && $options['font_awesome'] ) ? ' checked' : '';
		$google_fonts         = ( isset( $options['google_fonts'] ) ) ? $options['google_fonts'] : '';

		$modernizr = ( isset( $options['modernizr'] ) ) ? $options['modernizr'] : '';

		$opts = [
			''        => 'Select...',
			'off'     => 'Do Not Load',
			'head'    => 'Load - Always in &lt;head&gt;',
			'default' => 'Load - Honor other performance settings'
		];

		$modernizr_dropdown = self::dropdown_array( self::SETTINGS . '[modernizr]', $opts, $modernizr );

		$scripts_to_footer_checked = ( isset( $options['scripts_to_footer'] ) && $options['scripts_to_footer'] ) ? ' checked' : '';
		$styles_in_head_checked    = ( isset( $options['styles_in_head'] ) && $options['styles_in_head'] ) ? ' checked' : '';
		$dequeue_styles            = ( isset( $options['dequeue_styles'] ) ) ? $options['dequeue_styles'] : '';

		$comments_enabled = ( isset( $options['comments_enabled'] ) ) ? $options['comments_enabled'] : '';

		$blog_content    = ( isset( $options['blog_content'] ) ) ? $options['blog_content'] : '';
		$archive_content = ( isset( $options['archive_content'] ) ) ? $options['archive_content'] : '';

		$blog_excerpt_length    = ( isset( $options['blog_excerpt_length'] ) ) ? (int) $options['blog_excerpt_length'] : 55;
		$archive_excerpt_length = ( isset( $options['archive_excerpt_length'] ) ) ? (int) $options['archive_excerpt_length'] : 55;

		$blog_pagination    = ( isset( $options['blog_pagination'] ) && $options['blog_pagination'] ) ? ' checked' : '';
		$archive_pagination = ( isset( $options['archive_pagination'] ) && $options['archive_pagination'] ) ? ' checked' : '';

		$blog_read_more    = ( isset( $options['blog_read_more'] ) ) ? $options['blog_read_more'] : '';
		$archive_read_more = ( isset( $options['archive_read_more'] ) ) ? $options['archive_read_more'] : '';

		$stay_in_category = ( isset( $options['stay_in_category'] ) && $options['stay_in_category'] ) ? ' checked' : '';

		$gallery_type        = ( isset( $options['gallery_type'] ) ) ? $options['gallery_type'] : '';
		$gallery_image_width = ( isset( $options['gallery_image_width'] ) ) ? $options['gallery_image_width'] : '236';
		$gallery_gutter      = ( isset( $options['gallery_gutter'] ) ) ? $options['gallery_gutter'] : '10';

		$use_testimonials = ( isset( $options['use_testimonials'] ) && $options['use_testimonials'] ) ? ' checked' : '';
		$use_events       = ( isset( $options['use_events'] ) && $options['use_events'] ) ? ' checked' : '';
		$use_team         = ( isset( $options['use_team'] ) && $options['use_team'] ) ? ' checked' : '';

		$placeholder_text   = ( isset( $options['placeholder_text'] ) ) ? $options['placeholder_text'] : 'Search';
		$search_button_text = ( isset( $options['search_button_text'] ) ) ? $options['search_button_text'] : 'Search &raquo;';

		$logo_url = ( ! empty( $options['logo_url'] ) ) ? $options['logo_url'] : get_home_url();


		$opts = [
			''        => 'Select...',
			'content' => 'Full Article',
			'excerpt' => 'Excerpt'
		];

		$blog_dropdown    = self::dropdown_array( self::SETTINGS . '[blog_content]', $opts, $blog_content );
		$archive_dropdown = self::dropdown_array( self::SETTINGS . '[archive_content]', $opts, $archive_content );

		$opts = [
			''     => 'Comments Off',
			'page' => 'Pages Only',
			'post' => 'Posts Only',
			'both' => 'Both Pages and Posts'
		];

		$comments_dropdown = self::dropdown_array( self::SETTINGS . '[comments_enabled]', $opts, $comments_enabled );

		$opts = [
			'default'   => 'Default',
			'slideshow' => 'Slideshow',
			'masonry'   => 'Masonry',
		];

		$gallery_dropdown = self::dropdown_array( self::SETTINGS . '[gallery_type]', $opts, $gallery_type );

		$image_link_type = ( ! empty( $image_link_type ) ) ? $image_link_type : 'url';

		update_option( 'image_default_link_type', $image_link_type );

		$opts = [
			'file'   => 'Media File',
			'post'   => 'Attachment Page',
			'custom' => 'Custom Url',
			'none'   => 'No Link'
		];

		$image_link_dropdown = self::dropdown_array( self::SETTINGS . '[image_link_type]', $opts, $image_link_type );

		$opts = [
			''       => 'Off. Do Not Dequeue.',
			'remove' => 'On. Remove Styles Completely.',
			'footer' => 'On. Move Styles to Footer.',
		];

		$dequeue_dropdown = self::dropdown_array( self::SETTINGS . '[dequeue_styles]', $opts, $dequeue_styles );

		$event_category_label        = ( isset( $options['event_category_label'] ) ) ? $options['event_category_label'] : 'Category';
		$event_category_label_plural = ( isset( $options['event_category_label_plural'] ) ) ? $options['event_category_label_plural'] : 'Categories';
		$event_location_label        = ( isset( $options['event_location_label'] ) ) ? $options['event_location_label'] : 'Location';
		$event_location_label_plural = ( isset( $options['event_location_label_plural'] ) ) ? $options['event_location_label_plural'] : 'Locations';

		$job_category_label        = ( isset( $options['job_category_label'] ) ) ? $options['job_category_label'] : 'Category';
		$job_category_label_plural = ( isset( $options['job_category_label_plural'] ) ) ? $options['job_category_label_plural'] : 'Categories';
		$jobs_zero_found           = ( isset( $options['no_current_jobs_text'] ) ) ? $options['no_current_jobs_text'] : 'There are no current job postings';

		$theme_logo           = ( isset ( $options['theme_logo'] ) ) ? $options['theme_logo'] : '';
		$fixed_header_checked = ( isset( $options['fixed_header'] ) && $options['fixed_header'] ) ? ' checked' : '';

		$team_category_label        = ( isset( $options['team_category_label'] ) ) ? $options['team_category_label'] : 'Category';
		$team_category_label_plural = ( isset( $options['team_category_label_plural'] ) ) ? $options['team_category_label_plural'] : 'Categories';

		$main_nav_positions = [
			''               => 'Next to Logo',
			'nav_below_logo' => 'Below Logo'
		];

		$main_nav_position          = ( isset( $options['main_nav_position'] ) ) ? $options['main_nav_position'] : '';
		$main_nav_position_dropdown = self::dropdown_array( self::SETTINGS . '[main_nav_position]', $main_nav_positions, $main_nav_position );

		$side_opts = [
			''      => 'Off / None',
			'left'  => 'On Left Side',
			'right' => 'On Right Side'
		];

		$blog_sidebar          = ( isset( $options['blog_sidebar'] ) ) ? $options['blog_sidebar'] : '';
		$blog_sidebar_dropdown = self::dropdown_array( self::SETTINGS . '[blog_sidebar]', $side_opts, $blog_sidebar );

		$archive_sidebar          = ( isset( $options['archive_sidebar'] ) ) ? $options['archive_sidebar'] : '';
		$archive_sidebar_dropdown = self::dropdown_array( self::SETTINGS . '[archive_sidebar]', $side_opts, $archive_sidebar );

		$single_sidebar          = ( isset( $options['single_sidebar'] ) ) ? $options['single_sidebar'] : '';
		$single_sidebar_dropdown = self::dropdown_array( self::SETTINGS . '[single_sidebar]', $side_opts, $single_sidebar );

		$options['display']['blog']    = self::get_display_options( 'blog' );
		$options['display']['archive'] = self::get_display_options( 'archive' );
		$options['display']['single']  = self::get_display_options( 'single' );

		$blog_scheme    = _::get( $options, 'blog_scheme' );
		$archive_scheme = _::get( $options, 'archive_scheme' );
		$single_scheme  = _::get( $options, 'single_scheme' );

		$db_version = ( isset( $options['db_version'] ) ) ? $options['db_version'] : 0;
		?>
        <div id="acg_options" class="wrap wrap-mosaic-options">
            <h2>Manage Website Options</h2>

			<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == 'true' ) { ?>
                <div class="updated"><p>Settings Updated</p></div>
			<?php } ?>

            <form method="post" action="options.php">
                <div class="tabs">
                    <a href="javascript:void(0);" data-tab="general">General</a>
                    <a href="javascript:void(0);" data-tab="performance">Performance</a>
                    <a href="javascript:void(0);" data-tab="home">Home</a>
                    <a href="javascript:void(0);" data-tab="footer">Footer</a>
                    <a href="javascript:void(0);" data-tab="blog">Blog</a>
                    <a href="javascript:void(0);" data-tab="archive">Archive</a>
                    <a href="javascript:void(0);" data-tab="single">Single</a>
                    <a href="javascript:void(0);" data-tab="misc">Misc Options</a>
                    <a href="javascript:void(0);" data-tab="taxonomies">Taxonomies</a>
					<?php do_action( 'mosaic_settings_custom_tabs' ); ?>
                </div>
				<?php settings_fields( self::SETTINGS_GROUP );
				echo '<input type="hidden" name="' . self::SETTINGS . '[db_version]" value="' . esc_attr( $db_version ) . '">';
				?>
                <table class="form-table the-acg general">
                    <tr>
                        <td colspan="2">
                            <h3>General Options</h3>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Theme Logo</th>
                        <td><?php echo self::image_input( $theme_logo ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Logo Click URL</th>
                        <td><input type="text" class="widefat" name="<?php echo self::SETTINGS; ?>[logo_url]"
                                   value="<?php echo esc_attr( $logo_url ); ?>">
                            <p class="description">Leave blank to automatically go to the home page of this site
                                (<?php echo get_home_url(); ?>)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Fixed Header</th>
                        <td>
                            <input type="checkbox"
                                   name="<?php echo self::SETTINGS; ?>[fixed_header]"<?php echo $fixed_header_checked ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Main Nav Position</th>
                        <td>
							<?php echo $main_nav_position_dropdown; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Include Font Awesome</th>
                        <td><input type="checkbox"
                                   name="<?php echo self::SETTINGS; ?>[font_awesome]"<?php echo $font_awesome_checked; ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Google Font Families</th>
                        <td><input type="text" class="widefat" name="<?php echo self::SETTINGS; ?>[google_fonts]"
                                   value="<?php echo $google_fonts; ?>"/>

                            <p class="description">Separate families with pipes. Example:
                                Montez|Averia+Sans+Libre:400,300,300italic</p>
							<?php if ( $google_fonts ) { ?>
                                <div class="font_demo">
									<?php
									$fonts = explode( '|', $google_fonts );
									foreach ( $fonts AS $font ) {
										$base    = explode( ':', $font );
										$font    = str_replace( '+', ' ', $base[0] );
										$weights = ( isset( $base[1] ) ) ? explode( ',', $base[1] ) : NULL;
										if ( ! empty( $weights ) ) {
											foreach ( $weights AS $weight ) {
												$style  = preg_replace( "/[0-9]/", "", $weight );
												$weight = preg_replace( "/[^0-9]/", "", $weight );
												echo '<p style="font-family: \'' . $font . '\';';
												echo 'font-weight: ' . $weight . ';';
												echo ( $style ) ? 'font-style: ' . $style . ';' : '';
												echo '">' . $font . ' ' . $style . ' ' . $weight . ': ';

												echo 'The quick brown fox jumps over the lazy dog.';

												echo '</p>';
											}
										} else {
											echo '<p style="font-family: \'' . $font . '\'">' . $font . ': ';

											echo 'The quick brown fox jumps over the lazy dog.';

											echo '</p>';
										}
									}
									?>
                                </div>
							<?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Image Link</th>
                        <td><?php echo $image_link_dropdown; ?><p class="description">Set the default link when
                                adding
                                media.</p></td>
                    </tr>
                    <tr>
                        <th scope="row">Search Placeholder Text</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[placeholder_text]" size="20"
                                   value="<?php echo stripslashes( $placeholder_text ); ?>"/></td>
                    </tr>
                    <tr>
                        <th scope="row">Search Button Text</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[search_button_text]" size="20"
                                   value="<?php echo stripslashes( $search_button_text ); ?>"/></td>
                    </tr>
                </table>
                <table class="form-table the-acg performance">
                    <tr>
                        <td colspan="2">
                            <h3>Performance Options</h3>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Use Modernizr</th>
                        <td><?php echo $modernizr_dropdown; ?>
                            <p class="description">WHY USE IT: Modernizr Adds support for HTML5 features to older
                                browsers (such as IE8 or lower), so disabling TURNS OFF that support. Moving to
                                footer
                                may result in a Flash of Unstyled Content in older browsers</td>
                    </tr>
                    <tr>
                        <th scope="row">Move Scripts to Footer</th>
                        <td><input type="checkbox"
                                   name="<?php echo self::SETTINGS; ?>[scripts_to_footer]"<?php echo $scripts_to_footer_checked; ?> />

                            <p class="description">Check for better performance.<br>WHY DO IT: Moves all scripts
                                (that
                                are being loaded the proper way) to the footer, making them non-content
                                blocking.<br>DANGER:
                                This may break plugins that rely on script in the &lt;head&gt;</td>
                    </tr>
                    <tr>
                        <th scope="row">Load Theme Styles Inline in Head</th>
                        <td><input type="checkbox"
                                   name="<?php echo self::SETTINGS; ?>[styles_in_head]"<?php echo $styles_in_head_checked; ?> />

                            <p class="description">Check for better performance.<br>WHY DO IT: Loads the theme
                                stylesheet contents into a &lt;style&gt; block in the head, rather than linking to
                                the
                                stylesheet, eliminating one more server request.<br>DANGER: When you view source,
                                it's
                                not as pretty!</td>
                    </tr>
                    <tr>
                        <th scope="row">Dequeue Plugin and Other Styles</th>
                        <td><?php echo $dequeue_dropdown; ?>
                            <p class="description">Turn on for better performance.<br>WHY DO IT: Eliminates any
                                stylesheet
                                requests that may be added by plugins. DANGER: When you add a plugin, it may be
                                unstyled
                                and not look correct.<br>NOTE: Does not dequeue styles in Admin Dashboard, or for
                                logged
                                in
                                users.</p></td>
                    </tr>
                </table>
                <table class="form-table the-acg home">
                    <tr>
                        <td colspan="2">
                            <h3>Home Page</h3>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Home Heading (h1)</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[home_h1]" size="40"
                                   value="<?php echo stripslashes( $home_h1 ); ?>"/></td>
                    </tr>
                </table>
                <table class="form-table the-acg footer">
                    <tr>
                        <td colspan="2">
                            <h3>Footer Information</h3>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Footer Info</th>
                        <td><textarea
                                    style="width: 400px; height: 80px; font-family: Arial, Sans-Serif; font-size: 13px;"
                                    name="<?php echo self::SETTINGS; ?>[footer_info]"><?php echo stripslashes( htmlentities( $footer_info ) ); ?></textarea>
                        </td>
                    </tr>
                </table>
                <table class="form-table the-acg blog">
                    <tr>
                        <td colspan="2"><h3>Blog Listing Display</h3></td>
                    </tr>
                    <tr class="">
                        <th scope="row">Blog Page Title</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[blog_page_title]"
                                   value="<?php echo stripslashes( htmlentities( $blog_page_title ) ); ?>"/></td>
                    </tr>
                    <tr class="blog dropdown blog_dropdown">
                        <th scope="row">Full Post or Excerpt</th>
                        <td><?php echo $blog_dropdown; ?><p class="description">Whether to show full post content,
                                or
                                excerpt, on the blog page.</p></td>
                    </tr>
                    <tr class="blog excerpt blog_excerpt">
                        <th scope="row">Excerpt Length</th>
                        <td><input type="text" size="4" name="<?php echo self::SETTINGS; ?>[blog_excerpt_length]"
                                   value="<?php echo $blog_excerpt_length; ?>"/></td>
                    </tr>
                    <tr class="blog excerpt blog_excerpt">
                        <th scope="row">Read More Link</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[blog_read_more]"
                                   value="<?php echo $blog_read_more; ?>"/>

                            <p class="description">The text to display in the link to read the full article.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Use Pagination Navigation</th>
                        <td><input type="checkbox"
                                   name="<?php echo self::SETTINGS; ?>[blog_pagination]"<?php echo $blog_pagination; ?> />
                        </td>
                    </tr>
                    <tr>
                        <th>Blog Listing Fields<p class="description">Drag and drop to set the order of display. Check /
                                uncheck
                                to control if the content is displayed.</p></th>
                        <td>
                            <ul class="sortable">
								<?php
								foreach ( $options['display']['blog'] AS $key => $option ) {
									self::render_display_option( 'blog', $key, $option );
								}; ?>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sidebar on Blog</th>
                        <td><?php echo $blog_sidebar_dropdown; ?></td>
                    </tr>
                    <tr>
                        <th>Color Scheme</th>
                        <td><?php $section_admin->color_scheme_picker( self::SETTINGS . '[blog_scheme]', $blog_scheme ); ?></td>
                    </tr>
                </table>
                <table class="form-table the-acg archive">
                    <tr>
                        <td colspan="2">
                            <h3>Archive Listing</h3>
                        </td>
                    </tr>
                    <tr class="archive dropdown archive_dropdown">
                        <th scope="row">Full Post or Excerpt</th>
                        <td><?php echo $archive_dropdown; ?><p class="description">Whether to show full post
                                content, or
                                excerpt, on the archive pages (category, date, author, etc).</p></td>
                    </tr>
                    <tr class="archive excerpt archive_excerpt">
                        <th scope="row">Excerpt Length</th>
                        <td><input type="text" size="4" name="<?php echo self::SETTINGS; ?>[archive_excerpt_length]"
                                   value="<?php echo $archive_excerpt_length; ?>"/></td>
                    </tr>
                    <tr class="archive excerpt archive_excerpt">
                        <th scope="row">Archive Read More</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[archive_read_more]"
                                   value="<?php echo $archive_read_more; ?>"/>

                            <p class="description">The text to display in the link to read the full article.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Use Pagination Navigation</th>
                        <td><input type="checkbox"
                                   name="<?php echo self::SETTINGS; ?>[archive_pagination]"<?php echo $archive_pagination; ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Single Nav Stays in Category</th>
                        <td><input type="checkbox"
                                   name="<?php echo self::SETTINGS; ?>[stay_in_category]"<?php echo $stay_in_category; ?> />

                            <p class="description">When you view a blog post via the Category archive, the previous /
                                next
                                navigation in the single will stay in the category.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Archive Fields<p class="description">Drag and drop to set the order of display. Check /
                                uncheck to
                                control if the content is displayed.</p></th>
                        <td>
                            <ul class="sortable">
								<?php
								foreach ( $options['display']['archive'] AS $key => $option ) {
									self::render_display_option( 'archive', $key, $option );
								}; ?>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sidebar on Archive</th>
                        <td><?php echo $archive_sidebar_dropdown; ?></td>
                    </tr>
                    <tr>
                        <th>Color Scheme</th>
                        <td><?php $section_admin->color_scheme_picker( self::SETTINGS . '[archive_scheme]', $archive_scheme ); ?></td>
                    </tr>
                </table>
                <table class="form-table the-acg single">
                    <tr>
                        <td colspan="2"><h3>Blog Single Post</h3></td>
                    </tr>
                    <tr>
                        <th>Single Post Fields<p class="description">Drag and drop to set the order of display. Check /
                                uncheck to
                                control if the content is displayed.</p></th>
                        <td>
                            <ul class="sortable">
								<?php
								foreach ( $options['display']['single'] AS $key => $option ) {
									self::render_display_option( 'single', $key, $option );
								}; ?>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sidebar on Single</th>
                        <td><?php echo $single_sidebar_dropdown; ?></td>
                    </tr>
                    <tr>
                        <th>Color Scheme</th>
                        <td><?php $section_admin->color_scheme_picker( self::SETTINGS . '[single_scheme]', $single_scheme ); ?></td>
                    </tr>
                </table>
                <table class="form-table the-acg misc">
                    <tr>
                        <td colspan="2">
                            <h3>Misc Options</h3>
                        </td>
                    </tr>
                    <tr>
                        <th>Use Testimonials</th>
                        <td><input type="checkbox" class="theme_option" data-option=".testimonials"
                                   name="<?php echo self::SETTINGS; ?>[use_testimonials]"<?php echo $use_testimonials; ?> />
                        </td>
                    </tr>
                    <tr>
                        <th>Use Events</th>
                        <td><input type="checkbox" class="theme_option" data-option=".events"
                                   name="<?php echo self::SETTINGS; ?>[use_events]"<?php echo $use_events; ?> />
                        </td>
                    </tr>
                    <tr>
                        <th>Use Team</th>
                        <td><input type="checkbox" class="theme_option" data-option=".team"
                                   name="<?php echo self::SETTINGS; ?>[use_team]"<?php echo $use_team; ?> />
                        </td>
                    </tr>
                    <tr class="dropdown gallery">
                        <th>WP Gallery Style</th>
                        <td><?php echo $gallery_dropdown; ?><p class="description">Which style of display for
                                galleries
                                (non-portfolio).</p></td>
                    </tr>
                    <tr>
                        <th>Masonry Gallery Image Width</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[gallery_image_width]"
                                   value="<?php echo $gallery_image_width; ?>"/>px<p class="description">When using
                                masonry, sets the width of the images.</p></td>
                    </tr>
                    <tr>
                        <th>Masonry Gallery Gutter</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[gallery_gutter]"
                                   value="<?php echo $gallery_gutter; ?>"/>px<p class="description">When using
                                masonry,
                                sets the gutter between images.</p></td>
                    </tr>
                </table>
                <table class="form-table the-acg taxonomies">
                    <tr>
                        <td colspan="2">
                            <h3>Team Taxonomy Labels</h3>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Team Category Label</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[team_category_label]"
                                   value="<?php echo stripslashes( htmlentities( $team_category_label ) ); ?>"/>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Team Category Label Plural</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[team_category_label_plural]"
                                   value="<?php echo stripslashes( htmlentities( $team_category_label_plural ) ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h3>Event Taxonomy Labels</h3>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Event Category Label</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[event_category_label]"
                                   value="<?php echo stripslashes( htmlentities( $event_category_label ) ); ?>"/>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Event Category Label Plural</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[event_category_label_plural]"
                                   value="<?php echo stripslashes( htmlentities( $event_category_label_plural ) ); ?>"/>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Event Location Label</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[event_location_label]"
                                   value="<?php echo stripslashes( htmlentities( $event_location_label ) ); ?>"/>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Event Location Plural Label</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[event_location_label_plural]"
                                   value="<?php echo stripslashes( htmlentities( $event_location_label_plural ) ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h3>Job Taxonomy Labels</h3>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Job Category Label</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[job_category_label]"
                                   value="<?php echo stripslashes( htmlentities( $job_category_label ) ); ?>"/>
                        </td>
                    </tr>
                    <tr class="">
                        <th scope="row">Job Category Plural Label</th>
                        <td><input type="text" name="<?php echo self::SETTINGS; ?>[job_location_label_plural]"
                                   value="<?php echo stripslashes( htmlentities( $job_category_label_plural ) ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h3>Jobs Post Type</h3>
                        </td>
                    </tr>
                    <tr>
                        <td>'No current Jobs Available' text:</td>
                        <td><textarea
                                    style="width: 400px; height: 80px; font-family: Arial, Sans-Serif; font-size: 13px;"
                                    name="<?php echo self::SETTINGS; ?>[no_current_jobs_text]"><?php echo stripslashes( htmlentities( $jobs_zero_found ) ); ?></textarea>
                        </td>
                    </tr>
                </table>
				<?php do_action( 'mosaic_settings_custom_tab_views', $options ); ?>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>"/>
                </p>
            </form>
        </div>
        <style>
            .image-container {
                position: relative;
                cursor: pointer;
                width: 200px;
                border: 1px solid #555;
            }

            .image-container .image-wrapper {
                margin: 2px;
            }

            .image-container .image-wrapper:empty {
                line-height: 200px;
                color: #666;
                background: #ccc;
                font-size: 16px;
                text-shadow: 1px 1px 1px white;
            }

            .image-container .image-wrapper:empty:before {
                display: block;
                content: 'Choose Image';
                text-align: center;
                width: 100%;
            }

            .image-container:hover a.delete {
                opacity: 1;
            }

            .image-container a.delete {
                position: absolute;
                top: -10px;
                right: -10px;
                text-decoration: none;
                background: red;
                display: inline-block;
                width: 24px;
                height: 24px;
                text-align: center;
                border-radius: 12px;
                opacity: .6;
            }

            .image-container img {
                width: 100%;
                height: auto;
            }

            .image-container a.delete span.dashicons {
                color: white;
                line-height: 24px;
            }

            .sortable {
                max-width: 250px;
                padding: 10px;
                background: white;
                border: 1px solid #444;
            }

            .sortable li {
                padding: 5px 10px;
                background: #eee;
                border: 2px solid #bbb;
                border-radius: 2px;
                color: #999;
                cursor: move;
            }

            .sortable li label {
                display: inline-block;
                width: calc(100% - 30px);
                cursor: move;
            }

            .sortable li.selected {
                border-color: green;
                color: black;
                background: white;
            }

            .tabs {
                display: block;
                padding-left: 10px;
                border-bottom: 1px solid #aaa;
            }

            .tabs a {
                display: inline-block;
                padding: 5px 12px;
                background: #ddd;
                border: 1px solid #aaa;
                margin-top: 5px;
                margin-bottom: -1px;
                margin-right: -5px;
                vertical-align: bottom;
            }

            .tabs a.active {
                margin-top: 0;
                padding: 7px 13px;
                font-weight: bold;
                background: #eee;
                border-bottom: 1px solid #eee;
            }

            .tabs a:focus {
                box-shadow: none;
            }

            .form-table td[colspan="2"] {
                padding-left: 0;
            }
        </style>
        <script>
          jQuery( function ( $ ) {

            var $wrap = $( '.wrap-mosaic-options' );
            $( '.tabs a', $wrap ).on( 'click', function () {
              var tab = $( this ).data( 'tab' );
              $( 'table', $wrap ).hide();
              $( 'table.' + tab, $wrap ).fadeIn();
              $( '.tabs a', $wrap ).removeClass( 'active' );
              $( this ).addClass( 'active' );
            } );

            $( '.tabs a', $wrap ).first().click();

            MosaicImageUpload.init();
            $( 'tr.dropdown select' ).change(
              function () {
                var val  = $( this ).val();
                var type = $( this ).closest( 'tr' ).hasClass( 'blog' ) ? 'blog' : 'archive';
                var el   = $( 'tr.' + type + '_excerpt' );
                if ( val == 'excerpt' ) {
                  el.fadeIn();
                } else {
                  el.fadeOut();
                }
              }
            ).trigger( 'change' );

            $( 'input.theme_option' ).click(
              function () {
                var cname = $( this ).attr( 'data-option' );
                if ( $( this ).is( ':checked' ) ) {
                  $( cname ).fadeIn();
                } else {
                  $( cname ).fadeOut();
                }
              }
            ).each(
              function () {
                if ( !$( this ).is( ':checked' ) ) {
                  var cname = $( this ).attr( 'data-option' );
                  $( cname ).hide();
                }
              }
            );

            $( '.action-show-hide' ).change( function () {
              var val = $( this ).val();
              var key = $( this ).attr( 'name' );
              key     = /\[(.+)\]/g.exec( key );
              if ( key[ 1 ] ) {
                key     = key[ 1 ];
                var $el = $( '.' + key );
                if ( $el.length ) {
                  var show = $el.data( 'show' );
                  if ( show.indexOf( '|' ) ) {
                    show = show.split( '|' );
                  } else {
                    show = [ show ];
                  }

                  show = show.includes( val );

                  if ( false !== show ) {
                    $el.show();
                  } else {
                    $el.hide();
                  }
                }
              }
            } ).trigger( 'change' );

            $( '.sortable' ).sortable( {
              stop: updateSortable
            } );

            $( '.sortable' ).on( 'change', 'input[type="checkbox"]', function () {
              updateDisplayOption( $( this ) );
            } );

            $( '.sortable input[type="checkbox"]' ).each( function () {
              updateDisplayOption( $( this ) );
            } );

            function updateDisplayOption( $el ) {
              var $container = $el.closest( 'li' );
              $container.removeClass( 'selected' );
              if ( $el.is( ':checked' ) ) {
                $container.addClass( 'selected' );
              }
            }

            function updateSortable( event, ui ) {
              console.log( ui, event );
              var $container = $( ui.item ).closest( 'ul' );
              $( 'li', $container ).each( function ( i ) {
                var $this = $( this );
                $this.find( 'input[type="hidden"]' ).val( i );
                $this.removeClass( 'selected' );
                if ( $this.find( 'input[type="checkbox"]' ).is( ':checked' ) ) {
                  $this.addClass( 'selected' );
                }
              } );
            }
          } );

          var MosaicImageUpload = ( function () {
            var $;
            var $container;
            var $element;
            var customMedia = true;
            var buttonUpdate;
            var sendAttachment;

            function doUpload( $el ) {
              $element                  = $el;
              customMedia               = true;
              var _orig_send_attachment = wp.media.editor.send.attachment;
              var _orig_editor_insert   = wp.media.editor.insert;
              var _orig_string_image    = wp.media.string.image;

              // This function is required to return a "clean" URL for the "Insert from URL"
              wp.media.string.image = function ( embed ) {
                if ( customMedia ) {
                  sendAttachment = false;
                  return embed.url;
                }

                return _orig_string_image.apply( embed );
              };

              // This function handles passing the URL in for the "Insert from URL"
              wp.media.editor.insert = function ( html ) {
                if ( customMedia ) {
                  if ( sendAttachment ) {
                    return;
                  }

                  renderImage( html );
                  return;
                }

                return _orig_editor_insert.apply( html );
              };

              // This function handles passing in the image url from an uploaded image
              wp.media.editor.send.attachment = function ( props, attachment ) {
                sendAttachment = true;
                if ( customMedia ) {
                  getSizedImage( attachment.id, props.size, attachment.url );
                  //renderImage( attachment.url );
                  //console.log( props, attachment );
                } else {
                  return _orig_send_attachment.apply( this, [ props, attachment ] );
                }
                clearInterval( buttonUpdate );
              };

              wp.media.editor.open( 1 );

              buttonUpdate = setInterval( function () {
                  $( 'div.media-modal .media-button-insert' ).html( 'Choose Image' );
                }
                , 300 );
              return false;
            }

            function getSizedImage( attachment_id, size, url ) {
              console.log( url, attachment_id, size );

              if ( 'full' == size ) {
                renderImage( url );
                return;
              }

              $.ajax(
                ajaxurl,
                {
                  method: 'POST',
                  data: {
                    action: 'mosaic-media-upload',
                    attachment_id: attachment_id,
                    size: size
                  },
                  success: function ( url ) {
                    renderImage( url );
                  }
                }
              )
            }

            function renderImage( src ) {
              if ( $container.find( "img" ).length <= 0 ) {
                $container.find( '.image-wrapper' ).prepend( '<img src="" />' );
              }

              if ( $container.find( "a.delete" ).length <= 0 ) {
                $container.prepend( '<a class="delete" href="javascript:void(0);"><span class="dashicons dashicons-no"></span></a>' );
              }

              $container.find( "img" ).attr( "src", src );
              $container.find( "input" ).val( src );
            }

            function removeMedia( $el ) {
              $el.closest( '.image-container' ).find( 'input' ).val( '' );
              $el.closest( '.image-container' ).find( 'img, a.delete' ).fadeOut( 500, function () {
                $( this ).remove();
              } );
            }

            return {
              init: function () {
                $ = jQuery;
                // Media upload functionality. Use live method, because add / edit can be created dynamically
                $( document ).on( 'click', '.image-container', function ( e ) {
                  e.preventDefault();
                  // Set the container element to ensure actions take place within container
                  $container = $( this );
                  console.log( $container );
                  // Set the type.  media or image
                  doUpload();
                  return false;
                } );

                $( document ).on( 'click', '.image-container .delete', function ( e ) {
                  e.stopPropagation();
                  removeMedia( $( this ) );
                } );
              }
            }

          } )();
        </script>
		<?php
	}

	private static function uasort_display_options( $a, $b ) {
		if ( $a['order'] == $b['order'] ) {
			return 0;
		}

		return ( $a['order'] < $b['order'] ) ? -1 : 1;
	}

	private static function render_display_option( $type, $key, $option ) {
		echo '<li>';
		echo '<label>' . self::$label_map[ $key ] . '</label>';
		echo '<input type="checkbox" name="' . self::SETTINGS . '[display][' . $type . '][' . $key . '][on]"';
		echo ( ! empty( $option['on'] ) ) ? ' checked>' : '>';
		echo '<input type="hidden" name="' . self::SETTINGS . '[display][' . $type . '][' . $key . '][order]"';
		echo ' value="' . $option['order'] . '">';
		echo '</li>';
	}

	/**
	 * Returns the "display" settings for blog, posts, etc.
	 * Currently does not handle custom post types.
	 *
	 * @param      $type
	 * @param bool $omit_off_items
	 *
	 * @return array|string
	 */
	public static function get_display_options( $type, $omit_off_items = FALSE ) {
		$options = self::get_option( 'display' );

		$options = ( ! empty( $options[ $type ] ) ) ? $options[ $type ] : [];

		$default =
			[
				'title'      => [
					'on'    => 1,
					'order' => 0
				],
				'meta'       => [
					'on'    => 1,
					'order' => 1
				],
				'content'    => [
					'on'    => 1,
					'order' => 2
				],
				'featured'   => [
					'on'    => 0,
					'order' => 3
				],
				'source'     => [
					'on'    => 0,
					'order' => 4
				],
				'categories' => [
					'on'    => 1,
					'order' => 5
				],
				'tags'       => [
					'on'    => 0,
					'order' => 5
				],
				'taxonomies' => [
					'on'    => 0,
					'order' => 6
				]
			];

		// additional field, and minor tweaks for non-single "defaults"
		if ( 'single' != $type ) {
			$default['content']['on']     = 0;
			$default['featured']['order'] = 0;
			$default['title']['order']    = 1;
			$default['categories']        = 0;
			$default['tags']              = 0;
			$default['excerpt']           = [
				'on'    => 1,
				'order' => 3
			];
		}

		foreach ( $default AS $key => $option ) {
			if ( ! array_key_exists( $key, $options ) ) {
				$options[ $key ] = $option;
			}
		}

		uasort( $options, [ __CLASS__, 'uasort_display_options' ] );

		if ( $omit_off_items ) {
			foreach ( $options AS $key => $option ) {
				if ( empty( $option['on'] ) ) {
					unset( $options[ $key ] );
				}
			}
		}

		return $options;
	}

	public static function dropdown_array( $name, $opts, $selected = '', $class = '' ) {
		$dropdown = '<select name="' . $name . '" class="' . $class . '">';

		foreach ( $opts AS $val => $text ) {
			$dropdown .= '<option value="' . $val . '"';
			$dropdown .= ( $selected == $val ) ? ' selected' : '';
			$dropdown .= '>' . $text . '</option>';
		}

		$dropdown .= '</select>';

		return $dropdown;
	}

	public static function image_input( $image, $name = '' ) {
		$image_content = '<div class="image-container"><div class="image-wrapper">';
		if ( $image ) {
			$image_content .= '<img src="' . esc_attr( $image ) . '" />';
			$image_content .= '<a class="delete"><span class="dashicons dashicons-no"></span></a>';
		}

		if ( empty( $name ) ) {
			$name = self::SETTINGS . '[theme_logo]';
		}

		$image_content .= '</div><input type="hidden" name="' . $name . '" value="' . esc_attr( $image ) . '" /></div>';

		return $image_content;
	}

	private static function google_font() {
		$google_fonts = self::get_option( 'google_fonts' );

		if ( $google_fonts ) {
			$google_fonts = explode( '|', $google_fonts );
			$google_fonts = implode( "', '", $google_fonts ); ?>
            <script type="text/javascript">
              WebFontConfig = {
                google: { families: [ '<?php echo $google_fonts; ?>' ] }
              };
              ( function () {
                var wf   = document.createElement( 'script' );
                wf.src   = 'https://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
                wf.type  = 'text/javascript';
                wf.async = 'true';
                var s    = document.getElementsByTagName( 'script' )[ 0 ];
                s.parentNode.insertBefore( wf, s );
              } )(); </script>
			<?php
//             echo '<link href="//fonts.googleapis.com/css?family=' . $google_fonts . '" rel="stylesheet">' . PHP_EOL;
		}
	}

	/**
	 * Getter for admin option
	 *
	 * @param string $key
	 * @param string $default
	 *
	 * @return string|mixed
	 */
	public static function get_option( $key, $default = '' ) {
		$options = get_option( self::SETTINGS );

		return ( isset( $options[ $key ] ) ) ? $options[ $key ] : $default;
	}

	public static function update_option( $key, $value ) {
		$options         = get_option( self::SETTINGS );
		$options[ $key ] = $value;
		update_option( self::SETTINGS, $options );
	}

	public static function the_excerpt( $content = NULL ) {
		$readmore = self::excerpt_read_more();
		$content  .= $readmore;

		return $content;
	}

	public static function excerpt_read_more( $read_more = "Read More" ) {
		$type = ( is_archive() ) ? 'archive' : 'blog';

		$read_more = self::get_option( $type . '_read_more' );
		if ( ! $read_more ) {
			$read_more = 'Read More...';
		}

		return '<span class="read-more-wrapper"><a class="button-outline read-more" href="' . get_permalink( get_the_ID() ) . '">' . $read_more . '</a></span>';
	}

	public static function excerpt_more( $more ) {
		return '...';
	}


	function custom_excerpt_length( $length ) {
		$type   = ( is_archive() ) ? 'archive' : 'blog';
		$length = (int) self::get_option( $type . '_excerpt_length', 55 );

		return $length;
	}

	public static function navigation( $location = 'blog' ) {
		$pagination = self::get_option( $location . '_pagination', FALSE );
		if ( $pagination ) {
			echo '<div class="navigation pagination">';
			echo self::paginate_links();
			echo '</div>';

		} else {
			?>
            <div class="navigation">
                <div class="alignleft"><?php next_posts_link( '&laquo; Older Entries' ) ?></div>
                <div class="alignright"><?php previous_posts_link( 'Newer Entries &raquo;' ) ?></div>
            </div>
		<?php }
	}

	public static function the_content( $type = NULL ) {
		global $post;
		$type = ( is_archive() ) ? 'archive' : 'blog';

		$content = self::get_option( $type . '_content', 'content' );
		if ( $content == 'excerpt' ) {
			the_excerpt();
		} else {
			the_content();
		}
		edit_post_link( 'Edit ' . $post->post_type, '<div class="edit_link">', '</div>' );
	}

	public static function excerpt_length( $length = 55 ) {
		return $length;
	}

	public static function comments() {
		global $post;
		$enabled = self::get_option( 'comments_enabled' );
		if ( $post->post_type == $enabled || $enabled == 'both' ) {
			comments_template();
		}
	}

	public static function paginate_links() {
		global $wp_query;
		$big = 999999999; // need an unlikely integer

		$args = [
			'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'    => '?paged=%#%',
			'current'   => max( 1, get_query_var( 'paged' ) ),
			'total'     => $wp_query->max_num_pages,
			'end_size'  => 1,
			'mid_size'  => 4,
			'prev_text' => '<i class="fa fa-chevron-left"></i><span class="title">Previous</span>',
			'next_text' => '<span class="title">Next</span><i class="fa fa-chevron-right"></i>'
		];

		$args = apply_filters( 'mosaic_pagination', $args );

		return paginate_links( $args );
	}

// Enable custom login logo
	public static function login_head() {
		$options = get_option( self::SETTINGS );
		if ( empty( $options['theme_logo'] ) ) {
			echo '<!-- NOTE: No theme logo set. -->';

			return;
		}

		echo '<style type="text/css">
		.login h1 a { background-image:url(' . $options['theme_logo'] . ') !important; background-size: contain !important; width: 198px !important; height: 100px !important; margin: 0 auto !important; }
		</style>';

	}

// Enable custom login logo link
	public static function loginpage_custom_link() {
		return get_bloginfo( 'url' );
	}

// Enable custom tooltip on login logo
	public static function change_title_on_logo() {
		return 'Welcome to the ' . ACG_THEME_NAME . ' dashboard. Login to manage your site below.';
	}

//  Removes the WordPress version number from the <head>
	public static function remove_version_info() {
		return '';
	}

	public static function get_previous_post_link( $format, $link ) {
		if ( self::get_option( 'stay_in_category' ) ) {
			self::$stay_in_cat = ( isset( $_GET['cat_specific'] ) ) ? $_GET['cat_specific'] : FALSE;
		}

		return get_previous_post_link( $format, $link, self::$stay_in_cat );
	}

	public static function get_next_post_link( $format, $link ) {
		if ( self::get_option( 'stay_in_category' ) ) {
			self::$stay_in_cat = ( isset( $_GET['cat_specific'] ) ) ? $_GET['cat_specific'] : FALSE;
		}

		return get_next_post_link( $format, $link, self::$stay_in_cat );
	}

// Modification to stay in the category set in archive.php
	public static function get_previous_post_join_filter( $join ) {
		if ( self::$stay_in_cat ) {
			$join = preg_replace( '/tt.term_id IN \([^(]+\)/', "tt.term_id IN (" . self::$stay_in_cat . ")", $join );
		}

		return $join;
	}

	public static function get_next_post_join_filter( $join, $in_same_cat = FALSE, $excluded_categories = '' ) {
		if ( self::$stay_in_cat ) {
			$join = preg_replace( '/tt.term_id IN \([^(]+\)/', "tt.term_id IN (" . self::$stay_in_cat . ")", $join );
		}

		return $join;
	}

	public static function previous_post_link_filter( $link = '' ) {
		if ( self::$stay_in_cat && $link ) {
			$link = self::add_query_arg( 'cat_specific', self::$stay_in_cat, $link );
		}

		return $link;
	}

	public static function next_post_link_filter( $link = '' ) {
		if ( self::$stay_in_cat && $link ) {
			$link = self::add_query_arg( 'cat_specific', self::$stay_in_cat, $link );
		}

		return $link;
	}

	public static function add_query_arg( $key, $value, $link ) {
// Adds the parameter $key=$value to $link, or replaces it if already there.
// Necessary because add_query_arg fails on previous/next_post_link.
		if ( strpos( $link, 'href' ) ) {
			$hrefpat = '/(href *= *([\"\']?)([^\"\' ]+)\2)/';
		} else {
			$hrefpat = '/(([\"\']?)(http([^\"\' ]+))\2)/';
		}

		if ( preg_match( $hrefpat, $link, $matches ) ) {
			$url    = $matches[3];
			$newurl = add_query_arg( $key, $value, $url );
			$link   = str_replace( $url, $newurl, $link );
		}

		return $link;
	}

	public static function has_mega_menu() {
		if ( NULL === self::$has_mega_menu ) {
			self::$has_mega_menu = FALSE;

			self::$mega_menu = get_option( 'mosaic_theme_mega_menu' );

			if ( ! self::$mega_menu ) {
				return FALSE;
			}

			$mega_menus = array_map( function ( $menu ) {
				return ( ! empty( $menu['menus'] ) );
			}, self::$mega_menu );

			$mega_menus = array_filter( $mega_menus );

			self::$has_mega_menu = ( ! empty( $mega_menus ) );
		}

		return self::$has_mega_menu;
	}

	/**
	 * Utility to determine if the page has the shortcode on it.
	 * Necessary in order to handle builders such as Themify or Divi.
	 *
	 * @return bool
	 */
	public static function has_shortcode( $shortcode ) {
		if ( is_admin() ) {
			return FALSE;
		}

		$post = get_post( get_queried_object_id() );

		if ( empty( $post->ID ) ) {
			return FALSE;
		}

		$has_shortcode = has_shortcode( $post->post_content, $shortcode );

		if ( $has_shortcode ) {
			return self::parse_shortcode( $post->post_content, $shortcode );
		}

		$meta = get_post_meta( $post->ID );

		if ( ! $meta ) {
			return FALSE;
		}

		if ( ! $meta ) {
			return FALSE;
		}

		$has_shortcode = self::check_array_deep( $meta, "[{$shortcode}" );
		if ( $has_shortcode ) {
			return self::parse_shortcode( $has_shortcode, $shortcode );
		}

		return FALSE;
	}

	/**
	 * Walks the array, as deep as it goes, looking for "Value" (the shortcode) within the content.
	 *
	 * @param array  $array
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function check_array_deep( $array, $value ) {
		if ( is_object( $array ) ) {
			$array = (array) $array;
		}

		if ( ! is_array( $array ) ) {
			if ( FALSE !== stripos( $array, $value ) ) {
				return $value;
			}

			return FALSE;
		}

		foreach ( $array AS $key => $data ) {
			if ( is_string( $data ) && FALSE !== stripos( $data, '{' ) ) {
				$test = @json_decode( $data );
				if ( $test ) {
					$data = $test;
				} else {
					$test = maybe_unserialize( $data );
					if ( $test ) {
						$data = $test;
					}
				}
			}

			if ( is_object( $data ) || is_array( $data ) ) {
				$result = self::check_array_deep( $data, $value );
				if ( $result ) {
					return $data;
				}
			}

			if ( is_string( $data ) && FALSE !== stripos( $data, $value ) ) {
				return self::parse_shortcode( $data, $value );
			}
		}

		return FALSE;
	}

	private static function parse_shortcode( $content, $shortcode ) {
		if ( is_array( $content ) ) {
			$content = implode( '', $content );
		}

		$regex = get_shortcode_regex( [ $shortcode ] );
		preg_match( "/{$regex}/", $content, $matches );
		$atts = ( ! empty( $matches[3] ) ) ? shortcode_parse_atts( $matches[3] ) : TRUE;

		return $atts;
	}

	/**
	 * When in admin, and searching pages, this ensures that the "sections" pages meta boxes ALSO get searched.
	 * Detects if on a "page" listing.  If so, modifies the query, adding a meta query to LIKE the search term.
	 *
	 * NOTE: Does not work properly without the related posts_search function below.
	 *
	 * @param WP_Query $query
	 */
	public static function search_query_include_sections( $query ) {
		global $pagenow;
		global $post_type;
		self::$posts_search = FALSE;

		$search = ( ! empty( $_GET['s'] ) ) ? $_GET['s'] : '';

		if ( ! trim( $search ) ) {
			return;
		}

		$include = [ 'page' ];

		$is_search = ( ( is_search() && $query->is_main_query() ) || ( ! empty( $pagenow ) && 'edit.php' == $pagenow && in_array( $post_type, $include ) ) );

		if ( $is_search ) {
			$meta_query =
				[
					'key'     => '_mosaic_home_sections',
					'value'   => $search,
					'compare' => 'LIKE'
				];

			$existing = $query->get( 'meta_query' );

			if ( empty( $existing ) ) {
				$existing = [];
			}

			$existing[] = $meta_query;

			$query->set( 'meta_query', $existing );

			self::$posts_search = TRUE;
		}
	}

	/**
	 * After injecting the meta_query above, there is a problem with the way the query is built: it's an "AND"
	 * For the search to work as desired, an OR is needed.
	 * This function parses the newly formed search query, and replaces the AND with an OR, as well as moving the
	 * necessary closing paren to get proper grouping.
	 *
	 * Only runs if / when the search_query_include_sections function modifies the query.
	 *
	 * @param string   $search
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_search( $search, $query ) {
		// ONLY run if the search_query_include_sections function above has modified the query
		if ( ! self::$posts_search ) {
			return $search;
		}

		global $table_prefix;

		// capture the AND and the (meta query) in separate variable
		$find_regex = "/\)\)(\s+AND\s+)(\(\s*\(\s*{$table_prefix}postmeta\.meta_key\s*=.*{$table_prefix}postmeta\.meta_value.*\))/i";
		$matches    = preg_match( $find_regex, $search, $meta_query );

		// Ooops.  Something is wrong, bye bye...
		if ( ! $matches || empty( $meta_query[2] ) ) {
			return $search;
		}

		// get the full match, which should be )) AND (( w_postmeta.meta_key ... ))
		$replace = $meta_query[0];
		// swap the AND for OR, remove ONE of the initial closing parens to be readded to the very end
		$replace_pattern = '/\)\)\s+AND\s+\(/i';
		$replace_with    = preg_replace( $replace_pattern, ') OR (', $replace, 1 );
		// add the closing paren to the END of the query
		$replace_with .= ')';

		// perform the replacement with the new query
		$search = str_ireplace( $replace, $replace_with, $search );

		return $search;
	}
}

MosaicTheme::initialize();

/*
 * Functions to get the Previous and Next Post Title for the Single Navigation
 */

function get_prev_nav_with_title( $text = 'Previous', $stay_in_cat = TRUE ) {
	get_nav_link_with_title( TRUE, $text, $stay_in_cat );
}

function get_next_nav_with_title( $text = 'Next', $stay_in_cat = TRUE ) {
	get_nav_link_with_title( FALSE, $text, $stay_in_cat );
}

function get_nav_link_with_title( $previous, $text, $stay_in_cat = TRUE ) {
	if ( $previous ) {
		$class = 'alignleft';
		$post  = get_previous_post( $stay_in_cat );
	} else {
		$class = 'alignright';
		$post  = get_next_post( $stay_in_cat );
	}

	if ( $post && $post->post_title ) {
		$permalink = get_permalink( $post->ID );
		echo "<div class='{$class}'><a class='button-outline button-small button-navigation' href='{$permalink}'><span class='text'>{$text}</span><span class='title'>{$post->post_title}</span></a></div>";
	}
}

/**
 *
 * Wrapper around Custom Field Suite to prevent fatal errors when the plugin
 * is not installed.
 *
 * @param $key - string
 *
 * @return string
 */
function acg_cfs( $key ) {
	if ( class_exists( 'CFS' ) || function_exists( 'CFS' ) ) {
		return CFS()->get( $key );
	} else {
		return '<!-- Custom Field Suite plugin is not installed! -->';
	}
}


function remove_head_actions() {
	if ( ! is_callable( [ 'WPSEO_Frontend', 'get_instance' ] ) ) {
		return;
	}

	$yoast = WPSEO_Frontend::get_instance();
	// not Yoast, but WP default. Priority is 1
	remove_action( 'wp_head', '_wp_render_title_tag', 1 );
	// removed your "test" action - no need
	// per Yoast code, this is priority 0
	remove_action( 'wp_head', [ $yoast, 'front_page_specific_init' ], 0 );
	// per Yoast code, this is priority 1
	remove_action( 'wp_head', [ $yoast, 'head' ], 1 );
}

function remove_wpseo_head_actions() {
	if ( ! is_callable( [ 'WPSEO_Frontend', 'get_instance' ] ) ) {
		return;
	}

	$yoast = WPSEO_Frontend::get_instance();
	remove_action( 'wpseo_head', [ $yoast, 'head' ], 50 );
	// per Yoast code, this is priority 6
	remove_action( 'wpseo_head', [ $yoast, 'metadesc' ], 6 );
	// per Yoast code, this is priority 10
	remove_action( 'wpseo_head', [ $yoast, 'robots' ], 10 );
	// per Yoast code, this is priority 11
	remove_action( 'wpseo_head', [ $yoast, 'metakeywords' ], 11 );
	// per Yoast code, this is priority 20
	remove_action( 'wpseo_head', [ $yoast, 'canonical' ], 20 );
	// per Yoast code, this is priority 21
	remove_action( 'wpseo_head', [ $yoast, 'adjacent_rel_links' ], 21 );
	// per Yoast code, this is priority 22
	remove_action( 'wpseo_head', [ $yoast, 'publisher' ], 22 );
}

// hook into the same action, but with a very low number
//add_action( 'wp_head', 'remove_head_actions', - 99999 );
// hook into the same action, but with a very low number
//add_action( 'wp_head', 'remove_wpseo_head_actions', - 99999 );