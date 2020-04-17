<?php do_action( 'mosaic_before_footer' ); ?>
<?php if ( is_active_sidebar( 'footer_sidebar' ) ) { ?>
    <div class="footerwrapper">
        <footer>
			<?php
			// Combines with 'add_filter( 'mosaic_sidebars_array', [ $this, 'sidebars_array' ]'
			// This allow us to extend footer sidebars via plugins
			do_action( 'mosaic_before_footer_sidebar' );

			mosaic_get_sidebar( 'footer_sidebar', '', TRUE );

			// This allow us to extend footer sidebars via plugins
			// Combines with 'add_filter( 'mosaic_sidebars_array', [ $this, 'sidebars_array' ]'
			do_action( 'mosaic_after_footer_sidebar' ); ?>
        </footer>
    </div>
<?php } ?>
<?php if ( is_active_sidebar( 'after_footer_sidebar' ) ) { ?>
    <div class="footerwrapper">
        <footer>
			<?php mosaic_get_sidebar( 'after_footer_sidebar', '', TRUE ); ?>
        </footer>
    </div>
<?php }
$footer_info = trim( MosaicTheme::get_option( 'footer_info' ) );
if ( $footer_info ) { ?>
    <div class="footerbyline">
        <div class="footerbyline-inner">
			<?php echo $footer_info; ?>
        </div>
    </div>
	<?php
}

do_action( 'mosaic_after_footer' );
/**
 * This function returns all of the content added via themes, plugins, and WP Core.
 * This should always live just before the closing body tag.
 */
wp_footer();
?>
</body>
</html>