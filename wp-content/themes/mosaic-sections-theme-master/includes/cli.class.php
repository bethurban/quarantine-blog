<?php

/**
 * This class has functions related to this theme's WP-CLI functionality.
 */
class MosaicThemeCLI extends WP_CLI_Command {
	/**
	 * Force SCSS processing.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp mosaic process_sass
	 *
	 * @synopsis
	 */
	function process_sass( $args, $assoc_args ) {
		// Process new SASS files
		$result = MosaicHomeTemplateInterface::process_sass();

		// may return void or a $colors array
		if ( ! empty( $result ) ) {
			WP_CLI::success( 'Ran process_sass()' );
		} else {
			WP_CLI::warning( 'process_sass returned a falsy value' );
		}
	}
}

WP_CLI::add_command( 'mosaic', 'MosaicThemeCLI' );
