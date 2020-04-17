<?php

class MosaicThemeVersion {

	private static $version;

	/**
	 * @var MosaicHomeTemplateInterface
	 */
	private static $admin_class;

	public static function check() {
		self::$version    = (float) MosaicTheme::get_option( 'db_version' );
		$previous_version = self::$version;

		if ( self::$version < 0.1 ) {
			self::update_color_schemes();
		}

		if ( self::$version < 0.2 ) {
			self::update_to_multisite();
		}

		if ( self::$version < 0.3 ) {
			self::update_post_meta_keys();
		}

		if ( self::$version != $previous_version ) {
			MosaicTheme::update_option( 'db_version', self::$version );
		}
	}

	/**
	 * Switching from dnr naming to Mosaic naming.
	 * Switching from ~7 colors to 14 colors
	 */
	private static function update_color_schemes() {

		// Highlight colors.  Originally occupied ten to sixteen, now occupy fifteen to twenty one
		$colors = get_option( 'dnrhome_colors' );
		// Scheme colors.  Expanded from one to seven to one to fourteen
		$schemes = get_option( 'dnrhome_color_schemes' );

		// Set the version here so we increment it at the correct time.
		self::$version = 0.1;

		// If this is run again, it will break things
		$check = get_option( 'mosaic_theme_colors' );
		if ( ! empty( $check ) ) {
			return;
		}

		// If colors aren't set, no need to run this at all.
		if ( empty( $colors ) ) {
			return;
		}

		if ( array_key_exists( 'color-twenty', $colors ) ) {
			return;
		}

		// Ensure schemes are fully built out to new / desired size
		$schemes = MosaicHomeTemplateInterface::ensure_color_schemes( $schemes );

		$map = [
			'color-ten'      => 'color-fifteen',
			'color-eleven'   => 'color-sixteen',
			'color-twelve'   => 'color-seventeen',
			'color-thirteen' => 'color-eighteen',
			'color-fourteen' => 'color-nineteen',
			'color-fifteen'  => 'color-twenty',
			'color-sixteen'  => 'color-twenty-one'
		];

		// If run in original order, would loose some colors.  Start and highest and work backwards
		array_reverse( $map );

		// Map highlight colors to new positions
		foreach ( $map AS $original => $new ) {
			$colors[ $new ] = $colors[ $original ];
			// Remove color-ten through color-fourteen
			if ( ! in_array( $original, $map ) ) {
				unset( $colors[ $original ] );
			}
		}

		update_option( 'mosaic_theme_colors', $colors );
		update_option( 'mosaic_theme_color_schemes', $schemes );
		delete_option( 'dnrhome_colors' );
		delete_option( 'dnrhome_color_schemes' );

		// Process new SASS files
		MosaicHomeTemplateInterface::process_sass();
	}

	private static function update_to_multisite() {
		// Migrate to common color color schemes across ALL sites in multi-site
		$colors = get_site_option( 'mosaic_theme_colors' );

		if ( ! $colors ) {
			$colors  = get_option( 'mosaic_theme_colors' );
			$schemes = get_option( 'mosaic_theme_color_schemes' );

			if ( $colors ) {
				update_site_option( 'mosaic_theme_colors', $colors );
				update_site_option( 'mosaic_theme_color_schemes', $schemes );
			}
		}

		self::$version = 0.2;
	}

	private static function update_post_meta_keys() {
		global $wpdb;

		$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = '_mosaic_sidebar_option' WHERE meta_key = '_acg_sidebar_option'" );

		self::$version = 0.3;
	}
}
