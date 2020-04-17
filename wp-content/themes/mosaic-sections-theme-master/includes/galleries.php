<?php

function acg_image_orientation() {
	global $theme_options;
	if ( ! $theme_options || ! isset( $theme_options['layout'] ) ) {
		return 'horizontal';
	}

	if ( isset( $theme_options['layout'] ) ) {
		$orientation = $theme_options['layout'];
	}

	if ( isset( $orientation ) && ( $orientation == "2" ) ) {
		return 'square';
	} elseif ( isset( $orientation ) && ( $orientation == "1" ) ) {
		return 'horizontal';
	} else {
		return 'vertical';
	}
}
