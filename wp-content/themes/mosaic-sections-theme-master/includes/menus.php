<?php

/**
 * @package Alpha Channel Group Base Theme
 * @author  Alpha Channel Group (www.alphachannelgroup.com)
 */

if ( function_exists( 'register_nav_menus' ) ) {
	mosaic_register_nav_menus();
}

function mosaic_register_nav_menus() {
	$menus = [
		'primary' => 'Primary Navigation Menu',
		'global'  => 'Global Menu',
		'footer'  => 'Footer Navigation Menu'
	];

	$menus = apply_filters( 'mosaic_custom_nav_menus', $menus );

	register_nav_menus( $menus );
}

