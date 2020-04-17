<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php do_action( 'mosaic_after_body' ); ?>
<?php MosaicSocialMedia::facebook_sdk(); ?>
<?php

if ( ! empty( $post->ID ) ) {
	$full_image            = get_post_meta( $post->ID, '_full_background_image', TRUE );
	$square_image          = get_post_meta( $post->ID, '_square_background_image', TRUE );
	$image_content_overlay = get_post_meta( $post->ID, '_image_content_overlay', TRUE );
}

$logo     = MosaicTheme::get_option( 'theme_logo' );
$logo_url = MosaicTheme::get_option( 'logo_url' );
$logo_url = $logo_url ? $logo_url : get_home_url();

$header_class   = [ 'header' ];
$header_class[] = ( MosaicTheme::get_option( 'fixed_header' ) ) ? 'sticky' : '';
$header_class[] = MosaicTheme::get_option( 'main_nav_position' );
$header_class   = trim( implode( ' ', $header_class ) );

if ( ! empty( $full_image ) || ! empty( $square_image ) ) { ?>
    <style>
        <?php if( $full_image ){ ?>
        body > div.container {
            background-image: url('<?php echo $full_image;?>');
            background-size: 100%;
            background-repeat: no-repeat;
        }

        <?php }
		if ( $square_image ) { ?>
        @media only screen and (max-width: 992px) {
            body > div.container {
                background-image: url('<?php echo $square_image ?>');
            }
        }

        <?php }?>
    </style>
<?php } ?>
<?php do_action( 'mosaic_before_page_content' ); ?>
<div class="container">
	<?php if ( is_active_sidebar( 'header_sidebar' ) ) { ?>
        <div class="top-bar">
            <div class="header">
				<?php mosaic_get_sidebar( 'header_sidebar', '', TRUE ); ?>
            </div>
        </div>
	<?php } ?>
	<?php do_action( 'mosaic_before_header' ); ?>
    <div class="<?php echo $header_class; ?>">
        <div class="header-wrapper ">
            <div class="logo-wrapper">
                <header>
                    <a class="logo" href="<?php echo $logo_url; ?>">
                        <img src="<?php echo $logo; ?>" alt="<?php bloginfo( "name" ); ?>"/>
                    </a>
					<?php do_action( 'mosaic_after_logo' ); ?>
                </header>
                <label for="toggle" class="toggler"><span class="icon"><i class="fa fa-bars"></i></span></label>
            </div>
            <div class="nav-wrapper">
                <nav class="nav-main" id="main">
                    <input id="toggle" type="checkbox" class="toggle"/>
                    <div class="navigation">
						<?php

						$args = [
							'theme_location' => 'primary',
							'title_li'       => FALSE,
							'container'      => '',
							'depth'          => 3
						];

						if ( MosaicTheme::has_mega_menu() ) {
							$args['walker'] = new MegaMenuWalker();
						}

						wp_nav_menu( $args );
						?>

						<?php
						if ( is_active_sidebar( 'after_nav' ) ) {
							mosaic_get_sidebar( 'after_nav', '', TRUE );
						} ?>
                    </div>
                </nav>
            </div>
        </div>
    </div>
	<?php do_action( 'mosaic_after_header' ); ?>

<?php if ( ! empty( $image_content_overlay ) ) {
	echo '<div class="image-content-overlay">';
	echo '<div class="liner">';
	echo apply_filters( 'the_content', $image_content_overlay );
	echo '</div>';
	echo '</div>';
}
