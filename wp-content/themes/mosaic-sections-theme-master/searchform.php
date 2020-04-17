<?php
$placeholder   = MosaicTheme::get_option( 'placeholder_text', 'Search' );
$search_button = MosaicTheme::get_option( 'search_button_text', 'Search &raquo;' );
?>
<form method="get" id="searchform" action="<?php echo home_url(); ?>">
    <div>
        <input type="text" name="s" id="s" value="" placeholder="<?php echo $placeholder; ?>"/>
        <input type="submit" id="searchsubmit" value="<?php echo $search_button; ?>" class="button-search"/>
    </div>
</form>        