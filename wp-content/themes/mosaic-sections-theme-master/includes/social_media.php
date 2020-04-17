<?php

/**
 * @package Alpha Channel Group Base Theme
 * @author  Alpha Channel Group (www.alphachannelgroup.com)
 */
class MosaicSocialMedia {
	/**
	 * Local instance of page_id so we don't have to global it in
	 * @var integer
	 */
	private static $page_id;

	private static $apps = [
		'facebook_like',
		'facebook_share',
		'twitter_tweet',
		'google_plus_one',
		'pinterest_pinit',
		'linkedin_share',
		'email_share'
	];

	private static $shortcode_on_page = FALSE;

	/**
	 * The type of button to display (vendor, custom, custom with counts)
	 * @var string
	 */
	private static $button_type = '';

	/**
	 * The type of button to display (vendor, custom, custom with counts)
	 * @var string
	 */
	private static $custom_buttons = [];

	private static $ignore = FALSE;

	const VENDOR = 'vendor';
	const CUSTOM = 'custom';
	const CUSTOM_COUNTS = 'custom_counts';

	/**
	 * Local instance of the various options and settings
	 * @var array
	 */
	private static $options = NULL;

	const MENU_SLUG = 'admin_social';
	const SETTINGS = 'acg_social_options';
	const SETTINGS_GROUP = 'acg_social_options_group';

	public static function initialize() {
		if ( ! class_exists( 'MosaicTheme' ) ) {
			echo '<div style="font-weight: bold; border: 2px solid red;">The class ' . __CLASS__ . ' relies on the MosaicTheme class, which is not installed.';

			return;
		}

		self::$button_type    = self::get_option( 'button_style' );
		self::$custom_buttons = [ self::CUSTOM, self::CUSTOM_COUNTS ];

		self::add_filters();
		self::add_actions();

		add_shortcode( 'social_share', [ __CLASS__, 'shortcode' ] );
	}

	public static function add_actions() {
		add_action( 'wp', [ __CLASS__, 'wp' ] );
		add_action( 'wp_head', [ __CLASS__, 'social_head' ] );
		add_action( 'wp_footer', [ __CLASS__, 'social_footer' ], 999 );
		add_action( 'admin_init', [ __CLASS__, 'admin_init' ] );
		add_action( 'acg_admin_submenu', [ __CLASS__, 'admin_menu' ] );
	}

	public static function add_filters() {
		add_filter( 'the_content', [ __CLASS__, 'do_social' ], 999 );
		add_filter( 'the_excerpt', [ __CLASS__, 'do_social' ], 999 );
	}

	public static function admin_menu() {
		add_submenu_page( MosaicTheme::MENU_SLUG, 'Social Media', 'Social Media', MosaicTheme::$allowed_group, self::MENU_SLUG, [
			__CLASS__,
			'admin_social'
		] );
	}

	public static function admin_init() {
		register_setting( self::SETTINGS_GROUP, self::SETTINGS );
	}

	public static function wp() {
		self::$shortcode_on_page = MosaicTheme::has_shortcode( 'social_share' );

		if ( self::$shortcode_on_page ) {
			self::$shortcode_on_page = self::construct_shortcode_atts( self::$shortcode_on_page );
		}
	}

	public static function social_head() {

		self::$page_id = get_queried_object_id();

		$pinterest = self::get_option( 'pinterest_pinit' );
		if ( ( $pinterest && self::$button_type == self::VENDOR ) || self::check_shortcode_value( 'pinterest' ) ) { ?>
          <script type="text/javascript" async src="//assets.pinterest.com/js/pinit.js"></script>
		<?php }
	}

	private static function check_shortcode_value( $who, $type = self::VENDOR ) {
		if ( ! self::$shortcode_on_page ) {
			return FALSE;
		}

		if ( empty( self::$shortcode_on_page['button'] ) ) {
			return FALSE;
		}

		// TRUE means ANY / ALL button types
		if ( TRUE === $type ) {
			$type = [
				self::VENDOR,
				self::CUSTOM,
				self::CUSTOM_COUNTS
			];
		} else if ( ! is_array( $type ) ) {
			$type = [ $type ];
		}

		// If it's not the right kind of button type, then return FALSE
		if ( ! in_array( self::$shortcode_on_page['button'], $type ) ) {
			return FALSE;
		}

		if ( '*' == $who ) {
			return TRUE;
		}

		foreach ( self::$apps AS $app ) {
			if ( FALSE !== stripos( $app, $who ) ) {
				if ( ! empty( self::$shortcode_on_page[ $app ] ) ) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	public static function ignore( $ignore = TRUE ) {
		self::$ignore = $ignore;
	}

	public static function facebook_sdk() {
		$like  = self::get_option( 'facebook_like', FALSE );
		$share = self::get_option( 'facebook_share', FALSE );
		if ( ! $like && ! $share && ! self::check_shortcode_value( 'facebook', TRUE ) ) {
			return;
		}

		if ( ( $share && self::use_custom_buttons() ) || self::$button_type == self::VENDOR || self::check_shortcode_value( 'facebook', TRUE ) ) {
			$app_id = self::get_option( 'facebook_app_id', NULL ); ?>
          <div id="fb-root"></div>
          <script>
            window.fbAsyncInit = function () {
              FB.init( {
                appId: '<?php echo $app_id; ?>',
                status: true,
                xfbml: true
              } );
            };
          </script>
			<?php
		}

		if (self::$button_type == self::VENDOR || self::check_shortcode_value('facebook')) { ?>
		      (function(d, s, id){
		         var js, fjs = d.getElementsByTagName(s)[0];
		         if (d.getElementById(id)) {return;}
		         js = d.createElement(s); js.id = id;
		         js.src = "//connect.facebook.net/en_US/all.js";
		         fjs.parentNode.insertBefore(js, fjs);
		       }(document, 'script', 'facebook-jssdk'));
		    </script>
	<?php
		} else { ?>
		<script>
			(function(d, debug){
				var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];if   (d.getElementById(id)) {
					return;
				}js = d.createElement('script'); js.id = id; js.async = true;js.src = "//connect.facebook.net/en_US/all" + (debug ? "/debug" : "") + ".js";ref.parentNode.insertBefore(js, ref);
			}(document, /*debug*/ false));
			function postToFeed(title, desc, url, image){
				var obj = {
					method: 'share', href: url, picture: image, name: title, description: desc};
					function callback(response){
					}
					FB.ui(obj, callback);
			}
			</script>
		<?php
		}
	}

	public static function social_footer() {
		$include = self::get_option( 'facebook_og_tags', FALSE );
		if ( $include ) {
			$app_id  = self::get_option( 'facebook_app_id', NULL );
			$user_id = self::get_option( 'facebook_user_id', NULL );
			if ( ! $app_id || ! $user_id ) {
				echo PHP_EOL . '<!-- IMPORTANT: Facebook Open Graph tags not added because App ID or User ID is missing -->' . PHP_EOL;
			} ?>
          <!-- Social Media Open Graph Tags added by Alpha Channel Group theme -->
          <meta property="fb:admins" content="<?php echo $user_id; ?>"/>
          <meta property="fb:app_id" content="<?php echo $app_id; ?>"/>
          <meta property="og:type" content="article"/>
			<?php if ( is_single() ) { ?>
            <meta property="og:url" content="<?php the_permalink() ?>"/>
            <meta property="og:title" content="<?php single_post_title( '' ); ?>"/>
            <meta property="og:description" content="<?php echo strip_tags( get_the_excerpt( $post->ID ) ); ?>"/>
            <meta property="og:type" content="article"/>
            <meta property="og:image" content="<?php if ( function_exists( 'wp_get_attachment_thumb_url' ) ) {
				echo wp_get_attachment_thumb_url( get_post_thumbnail_id( $post->ID ) );
			} ?>"/>
			<?php } else { ?>
            <meta property="og:site_name" content="<?php bloginfo( 'name' ); ?>"/>
            <meta property="og:description" content="<?php bloginfo( 'description' ); ?>"/>
            <meta property="og:type" content="website"/>
            <meta property="og:image" content="<?php echo TEMPLATE_URL; ?>/images/logo.png"/>
			<?php }
		}

		$share = self::get_option( 'facebook_share', FALSE );
		if ( ( $share && self::use_custom_buttons() ) || self::check_shortcode_value( 'facebook_share', [
				self::CUSTOM,
				self::CUSTOM_COUNTS
			] ) ) { ?>
          <script>
            jQuery( function ( $ ) {
              $( '.social_media a.custom-fb' ).click( function ( event ) {
                event.preventDefault();
                elem = $( this );
                postToFeed( elem.data( 'title' ), elem.data( 'desc' ), elem.data( 'url' ), elem.data( 'image' ) );
                return false;
              } );
            } );
          </script>
			<?php
		}

		/**
		 * Only vendor buttons require javascript
		 */
		if ( self::$button_type != self::VENDOR && ! self::check_shortcode_value( '*' ) ) {
			return;
		}

		if ( self::get_option( 'google_plus_one' ) || self::check_shortcode_value( 'google' ) ) { ?>
          <script>
            ( function () {
              var po   = document.createElement( 'script' );
              po.type  = 'text/javascript';
              po.async = true;
              po.src   = 'https://apis.google.com/js/platform.js';
              var s    = document.getElementsByTagName( 'script' )[ 0 ];
              s.parentNode.insertBefore( po, s );
            } )();
          </script>
		<?php }

		if ( self::get_option( 'linkedin_share' ) || self::check_shortcode_value( 'linkedin' ) ) { ?>
          <script>
            /**
             * Super-lame hack required to get the buttons to line up.
             */
            jQuery( function ( $ ) {
              var lifix = setInterval(
                function () {
                  if ( $( 'span.IN-widget span' ).length > 0 ) {
                    var span = $( 'span.IN-widget, span.IN-widget > span' );
                    $.each( span, function ( el ) {
                      var styles = $( this ).attr( 'style' );
                      styles     = styles.replace( 'baseline', 'top' );
                      $( this ).attr( 'style', styles );
                    } );
                    clearInterval( lifix );
                  }
                }, 250
              );
            } );
          </script>
		<?php }
	}

	private static function use_custom_buttons() {
		return ( in_array( self::$button_type, self::$custom_buttons ) ) ? TRUE : FALSE;
	}

	private static function get_counts() {
		return ( self::$button_type == self::CUSTOM_COUNTS ) ? TRUE : FALSE;
	}

	private static function build_custom_button( $type ) {
		$count  = FALSE;
		$encode = TRUE;
		switch ( $type ) {
			case 'facebook':
			case 'fb':
				$url    = '[URL]';
				$encode = FALSE;
				$class  = 'fb';
				if ( self::get_counts() ) {
					$count = self::get_fb_share_count();
				}
				break;
			case 'twitter';
			case 'tweet':
			case 'tw':
				$url   = 'http://twitter.com/intent/tweet?url=[URL]&text=[TITLE]';
				$class = 'twitter';
				if ( self::get_counts() ) {
					$count = self::get_tweet_count();
				}
				break;
			case 'google+':
			case 'google':
			case 'plus':
				$url   = 'https://plus.google.com/share?url=[URL]';
				$class = 'google-plusone';
				if ( self::get_counts() ) {
					$count = self::get_plusones();
				}
				break;
			case 'linkedin':
			case 'linked':
			case 'li':
				$url   = 'http://www.linkedin.com/shareArticle?mini=true&url=[URL]&title=[TITLE]&source=[SOURCE]';
				$class = 'linkedin';
				break;
			case 'pinterest':
			case 'pinit':
			case 'pin':
				$url   = 'http://pinterest.com/pin/create/bookmarklet/?media=[MEDIA]&url=[URL]&is_video=false&description=[TITLE]';
				$class = 'pinterest';
				break;
			case 'email':
				$url    = 'mailto:?subject=[TITLE]&body=I want to share this with you: [URL]';
				$class  = 'email';
				$encode = FALSE;
				break;
		}

		$atts = ' data-title="[TITLE]" data-image="[MEDIA]" data-url="[URL]"';

		$fa_class     = self::get_font_awesome_class( $type );
		$button_class = 'custom custom-' . $class;
		$class        = ( ! $fa_class ) ? 'custom custom-' . $class : $fa_class;

		$button = '<a' . self::parse_share_url( $atts, FALSE ) . ' target="_blank" href="' . self::parse_share_url( $url, $encode ) . '" class="' . $button_class . '">';
		$button .= '<i class="' . $class . '"></i>';
		if ( $count !== FALSE ) {
			$button .= '<span class="count">' . $count . '</span>';
		}
		$button .= '</a>';

		return $button;
	}

	private static function get_font_awesome_class( $type ) {
		if ( ! self::get_option( 'fa_custom_button_style' ) && ( empty( self::$shortcode_on_page['fontawesome'] ) ) ) {
			return '';
		}

		switch ( $type ) {
			case 'facebook':
			case 'fb':
				$class = 'facebook';
				break;
			case 'twitter';
			case 'tweet':
			case 'tw':
				$class = 'twitter';
				break;
			case 'google+':
			case 'google':
			case 'plus':
				$class = 'google-plus';
				break;
			case 'linkedin':
			case 'linked':
			case 'li':
				$class = 'linkedin';
				break;
			case 'pinterest':
			case 'pinit':
			case 'pin':
				$class = 'pinterest';
				break;
			case 'email':
				$class = 'envelope';
				break;
		}

		return ' fa fa-' . $class;
	}

	private static function parse_share_url( $url, $encode = TRUE ) {
		global $post;
		$media     = '';
		$title     = get_the_title( $post );
		$permalink = get_permalink( $post->ID );
		$site      = get_bloginfo( 'name' );
		if ( stripos( $url, '[MEDIA]' ) !== FALSE ) {
			if ( has_post_thumbnail( $post->ID ) ) {
				$media = wp_get_attachment_thumb_url( $post->ID );
			}
		}

		if ( $encode ) {
			$title     = urlencode( $title );
			$permalink = urlencode( $permalink );
			$media     = urlencode( $media );
		}

		$url = str_ireplace( [ '[TITLE]', '[URL]', '[MEDIA]', '[SITE]' ], [
			$title,
			$permalink,
			$media,
			$site
		], $url );

		return $url;
	}

	public static function facebook_like() {
		if ( self::$button_type == self::VENDOR ) {
			return '<div class="fb-like" data-href="' . get_permalink() . '" data-layout="button_count" data-action="like" data-show-faces="true"></div>';
		} else if ( self::use_custom_buttons() ) {
			return '<!-- Facebook Like not supported with custom buttons -->';
		}
	}

	public static function facebook_share() {
		if ( self::$button_type == self::VENDOR ) {
			return '<div class="fb-share-button" data-href="' . get_permalink() . '" data-type="button_count"></div>';
		} else if ( self::use_custom_buttons() ) {
			return self::build_custom_button( 'fb' );
		}
	}

	public static function twitter_tweet() {
		if ( self::$button_type == self::VENDOR ) {
			return '<a href="https://twitter.com/share" class="twitter-share-button">Tweet</a>
			<script>!function(d,s,id){
				var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?"http":"https";if(!d.getElementById(id)){
					js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);
				}
			}(document, "script", "twitter-wjs");</script>';
		} else if ( self::use_custom_buttons() ) {
			return self::build_custom_button( 'twitter' );
		}
	}

	public static function google_plus_one() {
		if ( self::$button_type == self::VENDOR ) {
			return '<div class="g-plusone" data-size="medium"></div>';
		} else if ( self::use_custom_buttons() ) {
			return self::build_custom_button( 'google' );
		}

	}

	public static function linkedin_share() {
		if ( self::$button_type == self::VENDOR ) {
			return '<script src="//platform.linkedin.com/in.js" type="text/javascript">
				  lang: en_US
				</script>
				<script type="IN/Share" data-url="' . get_permalink() . '" data-counter="right"></script>';
		} else if ( self::use_custom_buttons() ) {
			return self::build_custom_button( 'linkedin' );
		}
	}

	public static function pinterest_pinit() {
		if ( self::$button_type == self::VENDOR ) {
			global $post;
			$url         = urlencode( get_permalink() );
			$image       = wp_get_attachment_thumb_url( get_post_thumbnail_id( $post->ID ) );
			$media       = ( $image ) ? '&media=' . urlencode( $image ) : '';
			$description = urlencode( get_the_title() );

			return '<a href="//www.pinterest.com/pin/create/button/?url=' . $url . $media . '&description=' . $description . '" data-pin-do="buttonPin" data-pin-config="beside"><img src="//assets.pinterest.com/images/pidgets/pinit_fg_en_rect_gray_20.png" /></a>';
		} else if ( self::use_custom_buttons() ) {
			return self::build_custom_button( 'pinterest' );
		}
	}

	public static function email_share() {
		global $post;
		$url   = get_permalink();
		$title = get_the_title();
		$link  = 'mailto:?subject=' . $title . '&body=I want to share this with you: ' . $url;

//	    return '<a href="' . $link . '">' . self::build_custom_button('email') . '</a>';
		return self::build_custom_button( 'email' );
	}

	/**
	 * Get like count from Facebook FQL
	 */
	public static function get_fb_share_count() {
		global $post;
		$post_id = $post->ID;
		$count   = get_transient( 'wds_post_like_count' . $post_id );
		if ( ! $count ) {
			// Setup query arguments based on post permalink
			$fql = "SELECT url, ";
			//$fql .= "share_count, "; // total shares
			//$fql .= "like_count, "; // total likes
			//$fql .= "comment_count, "; // total comments
			$fql .= "total_count "; // summed total of shares, likes, and comments (fastest query)
			$fql .= "FROM link_stat WHERE url = '" . get_permalink( $post_id ) . "'";

			// Do API call
			$response = wp_remote_retrieve_body( wp_remote_get( 'https://api.facebook.com/method/fql.query?format=json&query=' . urlencode( $fql ) ) );

			// If error in API call, stop and don't store transient
			if ( is_wp_error( $response ) ) {
				return 'error';
			}

			// Decode JSON
			$json = json_decode( $response );

			if ( ! empty( $json->error_code ) ) {
				return 0;
			}

			// Set total count
			$count = absint( $json[0]->total_count );

			// Set transient to expire every 30 minutes
			set_transient( 'wds_post_like_count' . $post_id, absint( $count ), 30 * MINUTE_IN_SECONDS );

		}

		return absint( $count );
	}

	/**
	 * Get tweet count from Twitter API (v1.1)
	 */
	function get_tweet_count() {
		global $post;
		$post_id = $post->ID;
		$count   = get_transient( 'wds_post_tweet_count' . $post_id );
		if ( ! $count ) {
			// Do API call
			$response = wp_remote_retrieve_body( wp_remote_get( 'https://cdn.api.twitter.com/1/urls/count.json?url=' . urlencode( get_permalink( $post_id ) ) ) );

			// If error in API call, stop and don't store transient
			if ( is_wp_error( $response ) ) {
				return 'error';
			}

			// Decode JSON
			$json = json_decode( $response );

			// Set total count
			$count = absint( $json->count );

			// Set transient to expire every 30 minutes
			set_transient( 'wds_post_tweet_count' . $post_id, absint( $count ), 30 * MINUTE_IN_SECONDS );
		}

		return absint( $count );
	}

	/**
	 * Get share count from Google Plus
	 */
	function get_plusones() {
		global $post;
		$post_id = $post->ID;
		$args    = [
			'method'    => 'POST',
			'headers'   => [
				// setup content type to JSON
				'Content-Type' => 'application/json'
			],
			// setup POST options to Google API
			'body'      => json_encode( [
				'method'     => 'pos.plusones.get',
				'id'         => 'p',
				'method'     => 'pos.plusones.get',
				'jsonrpc'    => '2.0',
				'key'        => 'p',
				'apiVersion' => 'v1',
				'params'     => [
					'nolog'   => TRUE,
					'id'      => get_permalink( $post_id ),
					'source'  => 'widget',
					'userId'  => '@viewer',
					'groupId' => '@self'
				]
			] ),
			// disable checking SSL sertificates
			'sslverify' => FALSE
		];

		// retrieves JSON with HTTP POST method for current URL
		$json_string = wp_remote_post( "https://clients6.google.com/rpc", $args );

		if ( is_wp_error( $json_string ) ) {
			// return zero if response is error
			return "0";
		} else {
			$json = json_decode( $json_string['body'], TRUE );

			// return count of Google +1 for requsted URL
			return intval( $json['result']['metadata']['globalCounts']['count'] );
		}
	}


	// *** Site options - footer, social media, etc.

	public static function admin_social() {

		$facebook_og_tags = ( self::get_option( 'facebook_og_tags', FALSE ) ) ? ' checked' : '';

		$facebook_app_id  = self::get_option( 'facebook_app_id' );
		$facebook_user_id = self::get_option( 'facebook_user_id' );

		$button_style           = self::get_option( 'button_style' );
		$fa_custom_button_style = self::get_option( 'fa_custom_button_style' );

		$locations = [
			''     => 'Do not include',
			'page' => 'On Pages',
			'post' => 'On Posts',
			'both' => 'Both Pages &amp; Posts'
		];

		foreach ( self::$apps AS $app ) {
			${$app}           = self::get_option( $app );
			$dropdown[ $app ] = '<select name="acg_social_options[' . $app . ']">';
			foreach ( $locations AS $val => $text ) {
				$dropdown[ $app ] .= '<option value="' . $val . '"';
				$dropdown[ $app ] .= ( $val == ${$app} ) ? ' selected' : '';
				$dropdown[ $app ] .= '>' . $text . '</option>';
			}
			$dropdown[ $app ] .= '</select>';
		}

		$buttons = [
			''              => 'None',
			'vendor'        => 'Vendor Default Buttons (javascript)',
			'custom'        => 'Custom Buttons (no counts)',
			'custom_counts' => 'Custom Buttons (with counts)',
		];

		$button_style = MosaicTheme::dropdown_array( "acg_social_options[button_style]", $buttons, $button_style );

		$fa_custom_buttons = [
			'0' => 'Background Image (css)',
			'1' => 'Font Awesome Icons'
		];

		$fa_custom_button_style = MosaicTheme::dropdown_array( "acg_social_options[fa_custom_button_style]", $fa_custom_buttons, $fa_custom_button_style );
		?>
      <div id="acg_options" class="wrap">
        <h2>Manage Social Media Options</h2>
		  <?php
		  if ( isset( $_GET['settings-updated'] ) && 'true' == $_GET['settings-updated'] ) {
			  echo '<div class="updated">';
			  echo '<p>Social Media Saved.</p>';
			  echo '</div>';
		  }
		  ?>
        <div>
          <h4>Shortcode Usage</h4>
          All possible options:<br> <code>[social_share facebook_like facebook_share linkedin google twitter pinterest
            button="custom|vendor|custom_count" fontawesome]</code>
          <br><strong>Options:</strong> (all options are optional, none are required, although the shortcode won't do
          anything if you don't include at least one social media channel!)
          <ul>
            <li><strong>facebook_like</strong>: Will show the Facebook Like button</li>
            <li><strong>facebook_share</strong>: Will show the Facebook Share button</li>
            <li><strong>linkedin</strong>: Will show the LinkedIn button</li>
            <li><strong>google</strong>: Will show the Google Plus button</li>
            <li><strong>twitter</strong>: Will show the Twitter button</li>
            <li><strong>pinterest</strong>: Will show the Pinterist Pin button</li>
            <li><strong>email</strong>: Will show the Email Share button</li>
            <li><strong>button</strong>: Style of button to display. Either "vendor" (default), "custom", or
              "custom_count". Vendor are the "standard" buttons. Custom can be styled with CSS. If none selected,
              "vendor" is used.
            </li>
            <li><strong>fontawesome</strong>: If <code>button</code> is custom or custom_count, then this tells the
              custom buttons to use FontAwesome icons
            </li>
          </ul>
        </div>
        <form method="post" action="options.php">
			<?php settings_fields( self::SETTINGS_GROUP ); ?>
          <table class="form-table the-acg">
            <tr>
              <th scope="row">Include Facebook Open Graph Tags</th>
              <td><input type="checkbox" name="acg_social_options[facebook_og_tags]"<?php echo $facebook_og_tags; ?> />
              </td>
            </tr>
            <tr>
              <th scope="row">Facebook App ID</th>
              <td><input type="text" name="acg_social_options[facebook_app_id]" size="40"
                         value="<?php echo $facebook_app_id; ?>"/>
                <p class="description">Required to use Share, Like, or Open Graph Tags. <a
                          href="https://developers.facebook.com/docs/opengraph/getting-started/">How to get your App
                    ID</a></p>
                <p class="description">Scroll the the bottom of the page to see additional instructions and
                  troubleshooting tips</p>
              </td>
            </tr>
            <tr>
              <th scope="row">Facebook User ID</th>
              <td><input type="text" name="acg_social_options[facebook_user_id]" size="40"
                         value="<?php echo $facebook_user_id ?>"/>
                <p class="description">Required to use Open Graph tags. <a href="http://findmyfacebookid.com/">Get your
                    Numeric User ID</a></p>
              </td>
            </tr>
            <tr class="button_type">
              <th scope="row">Button Styles</th>
              <td><?php echo $button_style; ?>
                <p class="description">Custom with counts can show counts only on FB, Twitter, and Google+</p>
              </td>
            </tr>
            <tr class="custom_only">
              <th scope="row">Custom Button Style</th>
              <td><?php echo $fa_custom_button_style; ?>
                <p class="description">Whether to use a custom background image or standard Font Awesome icons</p>
              </td>
            </tr>
            <tr class="no_custom">
              <th scope="row">Include Facebook Like</th>
              <td><?php echo $dropdown['facebook_like']; ?></td>
            </tr>
            <tr>
              <th scope="row">Include Facebook Share</th>
              <td><?php echo $dropdown['facebook_share']; ?></td>
            </tr>
            <tr>
              <th scope="row">Include Twitter Tweet</th>
              <td><?php echo $dropdown['twitter_tweet']; ?></td>
            </tr>
            <tr>
              <th scope="row">Include Google +1</th>
              <td><?php echo $dropdown['google_plus_one']; ?></td>
            </tr>
            <tr>
              <th scope="row">Include Pinterest Pin it</th>
              <td><?php echo $dropdown['pinterest_pinit']; ?></td>
            </tr>
            <tr>
              <th scope="row">Include LinkedIn Share</th>
              <td><?php echo $dropdown['linkedin_share']; ?></td>
            </tr>
            <tr>
              <th scope="row">Include Email Share</th>
              <td><?php echo $dropdown['email_share']; ?></td>
            </tr>
          </table>
          <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>"/>
          </p>
          <div class="description"><strong>Facebook App ID Instructions &amp; Troubleshooting</strong>
            <ul>
              <li><a href="https://developers.facebook.com">Visit https://developers.facebook.com</a> and register as a
                developer if that hasn&apos;t already been done.
              </li>
              <li>Once you are registered or logged in to Facebook, there will be some menu selections along the top nav
                bar.
              </li>
              <li>Choose the &ldquo;Apps&rdquo; dropdown. If you have already set up an app, select it from the list.
              </li>
              <li>If you haven&apos;t set up an app, select, &ldquo;Create a New App&rdquo;
                <ul>
                  <li>From here, create a display name</li>
                  <li>Select the category</li>
                  <li>press the create app button</li>
                  <li>Once the app is creating you will be on the dashboard screen.</li>
                </ul>
              </li>
              <li>If you have already set up the app, the instructions pick up here:
                <ul>
                  <li>On the left menu select &ldquo;Settings&rdquo;</li>
                  <li>On this page, you will need the app id and the app secret</li>
                  <li>Press the "+ Add Platform&rdquo; button</li>
                  <li>Select &ldquo;Website&rdquo;</li>
                  <li>In the Site Url input type in this website site URL (probably <?php echo get_bloginfo( 'url' ); ?>
                    )
                  </li>
                </ul>
              </li>
              <li>If you had to create the App, Return to this page</li>
              <li>copy and paste the App ID from the Facebook Dashboard into this page and hit save</li>
            </ul>
            <p>If you are having issues when someone attempts to Share, please follow these steps:</p>
            <ul>
              <li>Go to <a href="https://developers.facebook.com/">https://developers.facebook.com/</a> and log in</li>
              <li>Click on the Apps menu on the top bar.</li>
              <li>Select the respective app from the drop down.</li>
              <li>Go to 'Status &amp; Review' from the table in the left side of the page.</li>
              <li>To the question "Do you want to make this app and all its live features available to the general
                public?" - Select switch to set YES value.
              </li>
            </ul>
          </div>
        </form>
      </div>
      <script>
        jQuery( function ( $ ) {
          $( 'tr.button_type select' ).change(
            function () {
              if ( $( this ).val() != '' && $( this ).val() != '<?php echo self::VENDOR; ?>' ) {
                $( 'tr.no_custom' ).fadeOut();
                $( 'tr.custom_only' ).fadeIn();
              } else {
                $( 'tr.no_custom' ).fadeIn();
                $( 'tr.custom_only' ).fadeOut();
              }
            }
          ).trigger( 'change' );
        } );
      </script>
      <style>
        ul {
          margin-left: 30px;
          list-style-type: disc;
        }

        ul ul {
          margin-left: 30px;
        }
      </style>
		<?php
	}

	public static function shortcode( $atts ) {
		global $post;
		$type         = ( ! empty( $post->post_type ) ) ? $post->post_type : 'both';
		$ignore_state = self::$ignore;
		$button_state = self::$button_type;
		self::$ignore = FALSE;

		$atts = self::construct_shortcode_atts( $atts );
		foreach ( $atts AS $key => $value ) {
			if ( TRUE === $value ) {
				$atts[ $key ] = $type;
			}
		}

		self::$button_type = $atts['button'];

		$content = self::do_social( '', $atts );

		self::$ignore      = $ignore_state;
		self::$button_type = $button_state;

		return $content;
	}

	private static function construct_shortcode_atts( $atts ) {
		$new_atts = [];
		if ( ! empty( $atts ) ) {
			if ( ! empty( $atts['button'] ) || ! empty( $atts['buttons'] ) ) {
				$type    = ( empty( $atts['button'] ) ) ? $atts['buttons'] : $atts['button'];
				$buttons = ( FALSE !== stripos( $type, 'custom' ) ) ? 'custom' : '';
				$buttons = ( FALSE !== stripos( $type, 'count' ) ) ? 'custom_counts' : $buttons;
			} else {
				$buttons = 'vendor';
			}

			$new_atts['button'] = $buttons;

			if ( ! empty( $atts['fontawesome'] ) || in_array( 'fontawesome', $atts ) ) {
				$new_atts['fontawesome'] = 'fontawesome';
			}

			foreach ( self::$apps AS $app ) {
				$new_atts[ $app ] = FALSE;
			}

			foreach ( $atts AS $key => $value ) {
				$kind = ( is_string( $key ) ) ? $key : $value;

				foreach ( self::$apps AS $app ) {
					if ( FALSE !== stripos( $app, $kind ) || $app == $kind ) {
						$new_atts[ $app ] = TRUE;
					}
				}
			}
		}

		return $new_atts;
	}

	public static function do_social( $content, $where = FALSE ) {
		if ( self::$ignore ) {
			return $content;
		}

		if ( ! self::$button_type ) {
			return $content;
		}

		global $post;
		$type = ( $post->post_type );
		if ( ! $type ) {
			$type = 'post';
		}

		if ( FALSE === $where ) {
			$where = [];

			foreach ( self::$apps AS $app ) {
				$where[ $app ] = self::get_option( $app );
			}
		}

		if ( count( array_filter( $where ) ) ) {
			$class   = ( self::use_custom_buttons() ) ? ' custom' : '';
			$content .= PHP_EOL . '<!-- Social Media Share added by theme settings -->' . PHP_EOL;
			$content .= '<div class="social_media' . $class . '">';
		}

		foreach ( self::$apps AS $app ) {
			if ( $where[ $app ] == $type || $where[ $app ] == 'both' ) {
				$content .= self::$app();
			}
		}

		if ( count( array_filter( $where ) ) ) {
			$content .= '</div>';
		}

		return $content;
	}

	public static function get_option( $key, $default = '' ) {
		if ( ! self::$options ) {
			self::$options = get_option( self::SETTINGS );
		}

		return ( isset( self::$options[ $key ] ) ) ? self::$options[ $key ] : $default;
	}
}

MosaicSocialMedia::initialize();