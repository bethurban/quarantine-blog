jQuery( function ( $ ) {
  MosaicMegaMenu.init();
} );

/**
 * Mega-Menu Admin class.
 */
var MosaicMegaMenu = (function () {
  /**
   * Private class variables
   */
  var $; // jQuery
  var $available; // available menu container
  var $megaMenus; // all mega-menu panels
  var $megaMenuTabs; // tabs for mega-menus

  /**
   * Private Methods
   */

  /**
   * Bind the various events, such as sortable / draggable, tab clicks, etc.
   */
  function bindEvents() {
    $megaMenuTabs.on( 'click', 'a', function () {
      handleMenuTabs( $( this ) );
    } );

    $megaMenus.on( 'click', '.delete-submenu', function () {
      deleteSubMenu( $( this ) );
    } );

    $( '.mega-menu' ).sortable( {
      placeholder: {
        element: function () {
          return $( '<div class="menu-placeholder">Drop Menu Here</div>' );
        },
        update: function () {
          return;
        }
      },
      receive: function ( event, ui ) {
        renderMenu( ui.item.data( 'menu-id' ), $( this ) );
      }
    } );

    $( document ).on( 'upload:render', function () {
      adjustHeights( true );
    } );

    $( 'div', $available ).draggable( {
      connectToSortable: '.mega-menu',
      helper: 'clone',
      revert: 'invalid'
    } );

    $megaMenus.on( 'click', 'a.preview', function () {
      $( this ).text( ($( this ).hasClass( 'button-default' )) ? 'Show Preview' : 'Stop Previewing' );

      $( this ).toggleClass( 'button-default button-primary' );
      $( this ).closest( '.menu-item' ).find( '.mega-menu' ).toggleClass( 'menu-preview' );
      applyColors();
    } );

    $megaMenus.on( 'keyup blur change click', 'input[type="number"]', function () {
      applyColors();
    } )
  }

  /**
   * Check the loaded CDATA for existing menus (on initial load),
   * and then render them if they exist.
   */
  function loadExistingMenus() {
    var megaMenus = mosaicExistingMenus;
    if ( megaMenus ) {
      $.each( megaMenus, function ( megaMenuID, menus ) {
        var $megaMenu = $megaMenus.find( '.mega-menu[data-menu-id="' + megaMenuID + '"]' );
        $.each( menus, function ( index, menu ) {
          var $menu = $( '<div class="mega-menu-submenu">' );
          $menu.attr( 'data-menu-id', menu.menu_id );
          var menuTitle = mosaicAvailableMenus[ menu.menu_id ].name;
          $menu.append( '<p class="menu-title">' + menuTitle + '</p>' );
          $megaMenu.append( $menu );
          renderSingleMenu( $menu, menu.menu_id, menu.heading, menu.image, menu.classes );
        } );
      } );

      adjustHeights();
    }
  }

  /**
   * Render an entire "mega menu" panel, including each sub-menu panel.
   *
   * @param menuID
   * @param $megaMenu
   */
  function renderMenu( menuID, $megaMenu ) {
    var $menu = $( '[data-menu-id="' + menuID + '"]', $megaMenu );

    $menu.each( function () {
      renderSingleMenu( $( this ), menuID );
    } );

    adjustHeights( true );
    applyColors();
  }

  /**
   * Render a single "sub-menu" panel.
   *
   * @param $menu
   * @param menuID
   * @param heading
   * @param image
   * @param classes
   */
  function renderSingleMenu( $menu, menuID, heading, image, classes ) {
    $menu.css( { height: 'auto', width: 'auto' } );
    if ( $menu.find( 'div.menu-display' ).length ) {
      return;
    }

    heading = heading || '';

    var megaMenuID = $menu.closest( '.mega-menu' ).data( 'menu-id' );
    $menu.addClass( 'mega-menu-submenu' );
    $menu.append( '<a class="delete delete-submenu" href="javascript:void(0);"><span class="dashicons dashicons-no"></span></a>' );
    $menu.append( '<input type="hidden" name="mosaic_theme_mega_menu[menu_id][' + megaMenuID + '][]" value="' + menuID + '">' );
    $menu.append( renderImage( megaMenuID, image ) );
    $menu.append( '<input type="text" name="mosaic_theme_mega_menu[menu_heading][' + megaMenuID + '][]" placeholder="Menu Heading" value="' + heading + '">' );
    $menu.append( '<input type="text" name="mosaic_theme_mega_menu[menu_classes][' + megaMenuID + '][]" placeholder="CSS Classes" value="' + ( (undefined !== classes) ? classes : '') + '" style="display: block">' );
    $menu.append( '<div class="menu-display">' );
    $menu = $( 'div.menu-display', $menu );

    var menu = mosaicAvailableMenus[ menuID ].items;
    $.each( menu, function ( i, m ) {

      var item = '<span>' + m.title;

      $.each( m.children, function ( i, c ) {
        item += '<span>' + c.title + '</span>';
      } );

      item += '</span>';

      $menu.append( item );
    } );
  }

  function renderImage( megaMenuID, image ) {
    var content = '<div class="image-container"><div class="image-wrapper">';
    if ( image ) {
      content += '<img src="' + image + '" />';
      content += '<a class="delete"><span class="dashicons dashicons-no"></span></a>';
    }
    content += '</div><input type="hidden" name="mosaic_theme_mega_menu[image][' + megaMenuID + '][]" value="' + image + '" /></div>';
    return content;
  }

  /**
   * For prettiness sake, make all the sub-menu panels within a given mega-menu the same height.
   */
  function adjustHeights( immediate ) {
    if ( true === immediate ) {
      actuallyAdjustHeights();
      return;
    }

    $( window ).on( 'load', actuallyAdjustHeights );
  }

  function actuallyAdjustHeights() {
    console.log( "DO ADJUST" );
    var $items    = $megaMenus.find( '.current .mega-menu' ).find( 'div[data-menu-id]' );
    var maxHeight = 0;
    $items.each( function () {
      maxHeight = Math.max( $( this ).height(), maxHeight );
    } );

    $items.height( maxHeight );
  }

  /**
   * Event Handler for the menu tabs.
   * Switches which mega-menu panel is current / visible.
   * Tidy up a bit by adjusting heights
   *
   * @param $tab
   */
  function handleMenuTabs( $tab ) {
    $megaMenuTabs.find( 'a' ).removeClass( 'current' );
    $tab.addClass( 'current' );

    var index = $tab.index();

    $megaMenus.find( '.mega-menu-item' ).removeClass( 'current' );
    $megaMenus.find( '.mega-menu-item:eq(' + index + ')' ).addClass( 'current' );
    adjustHeights();
  }

  /**
   * Smartly determines how to apply the "Preview" of the colors for the menu.
   * If the menu is in "Preview" state, applies selected color, background, and opacity.
   * Only does so for the currently-displayed menu.
   */
  function applyColors() {
    var $currentMegaMenu = $( '.current', $megaMenus );
    // retrieve background chooser color
    var backgroundColor  = $( '.mosaic-color-selected', $currentMegaMenu ).first().css( 'background-color' );
    var color            = $( '.mosaic-color-selected', $currentMegaMenu ).eq( 1 ).css( 'background-color' );

    //find opacity value of current tab
    // TODO: Cesar - modified to have simpler access
    //var opacityLevel = $megaMenus.find( '[name*="\[opacity\]\[' + elementdata + '\]"]' )[ 0 ].value;
    var opacityLevel = $( '[name*="\[opacity\]\"]', $currentMegaMenu ).val();
    var opacityValue = opacityLevel / 100;
    // TODO: Cesar - We can't do opacity, that makes the text hard to see.  We need to apply RGBA to background....
    backgroundColor  = backgroundColor.replace( ')', ', ' + opacityValue + ')' );
    backgroundColor  = backgroundColor.replace( 'rgb', 'rgba' );

    if ( !$( '.mega-menu', $currentMegaMenu ).hasClass( 'menu-preview' ) ) {
      backgroundColor = 'white';
      color           = '#444';
    }

    // set css values for background color and opacity
    $( '.mega-menu', $currentMegaMenu ).css( { backgroundColor: backgroundColor } );
    $( '.mega-menu *', $currentMegaMenu ).css( { color: color } );
  }

  function deleteSubMenu( $submenu ) {
    $submenu.closest( '.mega-menu-submenu' ).remove();
  }

  /**
   * Public Functions
   */
  return {
    init: function () {
      $             = jQuery;
      $available    = $( '#available-menus' );
      $megaMenus    = $( '#mega-menu-content' );
      $megaMenuTabs = $( '#mega-menu-tabs' );

      loadExistingMenus();
      bindEvents();

      // Initialize the color chooser drop-downs
      MosaicColorChooser.init( this.applyColors );
      MosaicImageUpload.init();
    },
    applyColors: function () {
      applyColors();
    }
  }
})();
