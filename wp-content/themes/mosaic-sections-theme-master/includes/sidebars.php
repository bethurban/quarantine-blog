<?php

add_action( 'widgets_init', 'mosaic_register_sidebars' );

function mosaic_register_sidebars() {
	// Now set up the custom sidebars
	global $wpdb, $table_prefix;
	$results = $wpdb->get_results( "SELECT meta_value FROM " . $table_prefix . "postmeta WHERE meta_key='_mosaic_sidebar_option' GROUP BY meta_value" );

	foreach ( $results as $row ) {
		$pairs = explode( "||", $row->meta_value );
		if ( $pairs[0] ) {
			$sidebars[ $pairs[0] ] = ucwords( $pairs[1] ) . " Page";
		}

	}

	// Set up default sidebars
	$sidebars["header_sidebar"]       = "Header";
	$sidebars["home_sidebar"]         = "Home Page";
	$sidebars["default_sidebar"]      = "Default";
	$sidebars["blog_sidebar"]         = "Blog";
	$sidebars["footer_sidebar"]       = "Footer";
	$sidebars["after_footer_sidebar"] = "After Footer";
	$sidebars["after_nav"]            = "After Nav";

	// Allow addition of sidebars by plugins / modules
	$sidebars = apply_filters( 'mosaic_sidebars_array', $sidebars );

	// Now loop through and register them...
	foreach ( $sidebars as $id => $name ) {
		if ( $id != "none" ) {
			register_sidebar( [
				'name'          => $name,
				'id'            => $id,
				'before_widget' => '<li id="%1$s" class="widget %2$s">',
				'after_widget'  => '</li>',
				'before_title'  => '<h2 class="widgettitle">',
				'after_title'   => '</h2>'
			] );
		}
	}
}

/**
 * Get the sidebar selected / setup for the location installed.
 * May be either the sidebar passed in, or the sidebar defined for that page.
 *
 * @param $defaultid - the default sidebar to use
 * @param $class     - the css class to apply to the sidebar
 * @param $hammer    - forces the function to use the $defaultid sidebar passed in
 */
function mosaic_get_sidebar( $defaultid, $class = "", $hammer = FALSE ) {
	MosaicSocialMedia::ignore();
	global $post, $wp_registered_sidebars;
	$sidebarid = "";


	if ( isset( $post->ID ) && ! is_search() && $defaultid != "blog_sidebar" ) {
		$options   = get_post_meta( $post->ID, '_mosaic_sidebar_option', TRUE );
		$sidebar   = explode( "||", $options );
		$sidebarid = $sidebar[0];
	}

	if ( ! $class ) {
		$class = ( stripos( $sidebarid, "left" ) !== FALSE ) ? " sidebarleft" : '';
	}

	if ( ! isset( $wp_registered_sidebars[ $sidebarid ] ) || stripos( $defaultid, "footer_" ) !== FALSE ) {
		$sidebarid = $defaultid;
	}

	if ( $hammer ) {
		$sidebarid = $defaultid;
	}

	echo '<div id="sidebar-' . $sidebarid . '" class="sidebar ' . $class . '">' . "\r\n";
	// BEGIN: standard sidebar-getting-stuff
	echo '<ul>' . "\r\n";

	if ( ! function_exists( 'dynamic_sidebar' ) || ! dynamic_sidebar( $sidebarid ) ) {
		// echo '<li>Insert widgets into <em>' . acg_strtoproper(str_replace("-", " ", str_replace("_", " ", $sidebarid))) . '</em></li>';
	}

	echo '</ul>' . "\r\n" . '</div>' . "\r\n";
	MosaicSocialMedia::ignore( FALSE );
}


// Function to display post meta box 
function mosaic_sidebar_metabox() {
	global $post;
	// Load the option values from the db
	$options  = get_post_meta( $post->ID, '_mosaic_sidebar_option', TRUE );
	$position = get_post_meta( $post->ID, '_mosaic_sidebar_position', TRUE );
	$options  = explode( "||", $options );
	$id       = $options[0];
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'acg_sidebar_per_page_nonce' );
	echo '<p>Select Sidebar: </p>' . mosaic_sidebar_list( $id, $post->post_title );

	if ( MosaicHomeTemplateInterface::is_home_template( $post ) ) {
		echo '<p>Select Sidebar Position: </p>';
		echo mosaic_sidebar_position_dropwdown( $position );
	}
}

// Function to dynamically create select list for metabox
function mosaic_sidebar_list( $sel, $page ) {
	global $wp_registered_sidebars;
	$newname  = html_entity_decode( wp_kses_decode_entities( $page ) );
	$newname  = preg_replace( "/[^a-zA-Z0-9\s]/", "", $newname );
	$newid    = str_replace( " ", "_", strtolower( $newname ) ) . "_sidebar";
	$list     = '<select name="mosaic_sidebar_option">' . "\r\n";
	$selected = ( $sel == "default_sidebar" ) ? ' selected="selected"' : '';
	$list     .= '<option value="default_sidebar"' . $selected . '>&nbsp;- Default Sidebar -</option>' . "\r\n";
	$selected = ( $sel == "none" ) ? ' selected="selected"' : '';
	$list     .= '<option value="none"' . $selected . '>&nbsp;- No Sidebar -</option>' . "\r\n";
	$list     .= '<<<NEWOPTION>>>';
	// $list.= '<optgroup label="Existing Sidebars">' . "\r\n";
	// $list.= '<option value="||new||">NEW: ' . $newname . '</option>';
	$exists = FALSE;

	foreach ( $wp_registered_sidebars as $id => $a ) {
		if ( $id != "default_sidebar" && $id != "none" ) {
			$list .= '<option value="' . $id . '"';
			$list .= ( $id == $sel ) ? ' selected="selected"' : '';
			$list .= '>' . $a["name"] . '</option>' . "\r\n";

			if ( strtolower( $id ) == $newid ) {
				$exists = TRUE;
			}
		}
	}
	// $list.= '</optgroup>' . "\r\n";
	$list .= '</select>' . "\r\n";

	if ( ! $newname ) {
		$newname = "[your page title]";
	}

	$newoption = ( $exists ) ? "" : '<option value="||NEW||">* CREATE NEW: ' . $newname . ' Sidebar *</option>';
	$list      = str_replace( "<<<NEWOPTION>>>", $newoption, $list );

	return $list;
}

/**
 * Returns 0 or 1 (Right and Left respectively)
 *
 * @param $post_id
 *
 * @return string
 */
function mosaic_sidebar_position( $post_id ) {
	return get_post_meta( $post_id, '_mosaic_sidebar_position', TRUE );
}

function mosaic_sidebar_position_dropwdown( $sel ) {
	$options = '<select name="mosaic_sidebar_position">';
	$options .= '<option value="0" ' . ( ( isset( $sel ) ) && ( $sel == '0' ) ? 'selected' : '' ) . '>Right</option>';
	$options .= '<option value="1" ' . ( ( isset( $sel ) ) && ( $sel == '1' ) ? 'selected' : '' ) . '>Left</option>';
	$options .= '</select>';

	return $options;
}

// Function to save the meta
function mosaic_sidebar_meta_save( $post_id ) {
	global $wpdb;

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( empty( $_POST['acg_sidebar_per_page_nonce'] ) || ! wp_verify_nonce( $_POST['acg_sidebar_per_page_nonce'], plugin_basename( __FILE__ ) ) ) {
		return $post_id;
	}

	if ( ! isset( $_POST['mosaic_sidebar_position'] ) ) {
		return $post_id;
	}

	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// Check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}
	}

	//make sure we have the published post ID and not a revision
	if ( $parent_id = wp_is_post_revision( $post_id ) ) {
		$post_id = $parent_id;
	}

	$var = $_POST["mosaic_sidebar_option"];
	if ( $var == "||NEW||" ) {
		// If we've got a new one, we need to CREATE the name based on the title
		$id  = html_entity_decode( wp_kses_decode_entities( get_the_title( $post_id ) ) );
		$id  = preg_replace( "/[^a-zA-Z0-9\s]/", "", $id );
		$id  = str_replace( " ", "_", strtolower( $id ) ) . "_sidebar";
		$var = $id;
	}
	$var  = ( ! $var ) ? "default" : $var;
	$name = str_replace( "_", " ", str_replace( "_sidebar", "", $var ) );
	$var  .= "||" . $name;

	$temp = $_POST['mosaic_sidebar_position'];

	update_post_meta( $post_id, '_mosaic_sidebar_position', $temp );
	update_post_meta( $post_id, '_mosaic_sidebar_option', $var );

}

function mosaic_sidebar_admin_init() {
	add_meta_box( 'mosaic_sidebar_per_page', __( 'Sidebar Per Page', 'acg_language_file' ), 'mosaic_sidebar_metabox', 'page', 'side', 'low' );
}

// Add action to set up metabox for sidebars
add_action( 'admin_init', 'mosaic_sidebar_admin_init' );

// Do something with the metabox data on save
add_action( 'save_post', 'mosaic_sidebar_meta_save' );
