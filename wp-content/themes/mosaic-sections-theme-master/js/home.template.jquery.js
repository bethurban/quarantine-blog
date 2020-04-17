jQuery( function ( $ ) {
  watchElemHeights.init();

  var bxSliderConfig = {
    slideSelector: 'div.bucket',
    adaptiveHeight: true,
    pager: true,
    responsive: true,
    controls: true,
    auto: false,
    mode: 'horizontal',
    slideWidth: 200, // REQUIRED
    minSlides: 1,
    maxSlides: 4,
    prevText: '<i class="fa fa-angle-left"></i>',
    nextText: '<i class="fa fa-angle-right"></i>'
  };

  function getSliderAttributes( config, $el ) {
    var $section  = $el.closest( 'section' );
    var newConfig = {};

    $.each( config, function ( i, val ) {
      var dataAttrName = i.toLowerCase();

      newConfig[ i ] = (undefined !== $section.attr( 'data-' + dataAttrName )) ? $section.data( dataAttrName ) : val;
      newConfig[ i ] = ('false' === newConfig[ i ]) ? false : newConfig[ i ];
      newConfig[ i ] = ('true' === newConfig[ i ]) ? true : newConfig[ i ];
    } );

    return newConfig;
  }

  $( '.section-bucket-carousel' ).each( function () {
      var config = getSliderAttributes( bxSliderConfig, $( this ) );
      $( this ).find( '.buckets' ).bxSlider( config );
    }
  );

  $( '.section-content-slider' ).each( function () {
      bxSliderConfig.slideSelector = 'div.content-slide';
      delete bxSliderConfig.slideWidth;

      var config = getSliderAttributes( bxSliderConfig, $( this ) );
      $( this ).find( '.content-slider' ).bxSlider( config );
    }
  );

  $( 'div.mega-sub-menu-image' ).each( function () {
      var $subMenuContainer = $( this ).closest( '.mega-sub-menu-container' );

      if ( $( 'ul.menu li', $subMenuContainer ).length === 1 ) {
        $( this ).css( { cursor: 'pointer' } );
        var link = $( 'ul.menu li a', $subMenuContainer ).attr( 'href' );

        $( this ).on( 'click', function () {
          window.location = link;
        } );
      }
    }
  );

  /**
   * Handles Accordion event
   */
  $( '.section-accordions' ).on( 'click', '.accordion-headline', function () {
    var $accordion = $( this ).closest( '.accordion' );

    $accordion.is( '.open' ) ? closeAccordion( $accordion ) : openAccordion( $accordion );
  } );

  /**
   * Handle closing accordion
   *
   * @param $accordion
   */
  function closeAccordion( $accordion ) {
    $( '.accordion-body', $accordion ).slideUp( 500 );

    setTimeout( function () {
      $accordion.removeClass( 'open' );
      $( '.accordion-headline', $accordion ).removeClass( 'active' );
    }, 500 );
  }

  /**
   * Handle opening accordion
   *
   * @param $accordion
   */
  function openAccordion( $accordion ) {
    $( '.accordion-body', $accordion ).slideDown( 500 );

    setTimeout( function () {
      $accordion.addClass( 'open' );
      $( '.accordion-headline', $accordion ).addClass( 'active' );
    }, 500 );
  }

  /**
   * Events Section location handler
   */
  $( document ).on( 'click', 'a.upcoming-events', function () {
    var location       = $( this ).data( 'location' );
    var category       = $( this ).data( 'category' );
    var numberOfEvents = $( this ).data( 'number-of-events' );
    var $section       = $( this ).closest( 'section' );

    setActiveEventFilter( $( this ), $section );
    getUpcomingEvents( location, category, numberOfEvents, $section );
  } );

  /**
   * Hide Mobile menu on anchor link click (handles one-pagers on mobile)
   */
  $( '.header nav .menu li a' ).on( 'click', function () {
    if ( $( window ).width() <= 768 ) {
      $( 'label.toggle' ).click();
    }
  } );

  /**
   * Retrieve events via AJAX call
   *
   * @param location
   * @param category
   * @param numberOfEvents
   * @param $section
   */
  function getUpcomingEvents( location, category, numberOfEvents, $section ) {
    $.ajax( mosaicData.ajaxUrl, {
      method: 'POST',
      data: {
        action: 'mosaic-upcoming-events',
        eventlocation: location,
        eventcategory: category,
        number_of_events: numberOfEvents
      },
      success: function ( response ) {
        renderEvents( response, $section );
      }
    } );
  }

  /**
   * Set active event filter
   *
   * @param $filter
   * @param $section
   */
  function setActiveEventFilter( $filter, $section ) {
    $( 'a.upcoming-events', $section ).each( function () {
      $( this ).removeClass( 'active' );
    } );

    $filter.addClass( 'active' );
  }

  /**
   * Render events
   *
   * @param events
   * @param $section
   */
  function renderEvents( events, $section ) {
    $( '#upcoming-events', $section ).html( events );
  }

  // adds loading effect to sections
  if ( $( '.effect-fade-in' ).length > 0 && $( window ).width() > 768 ) {
    // Ensure runs immediately after page loads, in case parallax section in viewport on initial load
    $( window )
      .load( function () {
        // Only hide elements not currently in viewport
        $( '.mosaic-section .sub-contents' )
          .filter( function () {
            var $section = $( this ).closest( '.mosaic-section' );

            return !isVisibleWithinViewport( $section );
          } )
          .css( 'opacity', '0' )
          .addClass( 'loading-effect-hidden' )
      } )
      .scroll( parallaxLoader )
  }

  function parallaxLoader() {
    $( '.mosaic-section' ).each( function () {
      var $section     = $( this );
      var $subContents = $( '.sub-contents', $section );

      if ( isVisibleWithinViewport( $section ) && $subContents.is( '.loading-effect-hidden' ) ) {
        // Can be overridden using WP 'mosaic_render_render' filter
        var loadingEffectDuration = $section.data( 'loading-effect-duration' );
        loadingEffectDuration     = loadingEffectDuration ? loadingEffectDuration : 500;

        var $slideUpElements    = $( '.highlight-image, .image-after-text, .event, .bucket, .image-grid-item, .team-member', $( this ) );
        var $slideRightElements = $( '.donate-button, .right, .highlights-right', $( this ) );
        var $slideLeftElements  = $( '.donate-text, .left, .highlights-left', $( this ) );

        $subContents.removeClass( 'loading-effect-hidden' );
        $subContents.animate( { 'opacity': '1' }, loadingEffectDuration );

        $slideRightElements.addClass( 'slide-right' );
        $slideLeftElements.addClass( 'slide-left' );
        $slideUpElements.addClass( 'slide-up' );

        if ( $( this ).find( '.image-list-item' ).length > 0 ) {
          $( this ).find( '.image-list-item-wrapper' ).each( function () {
            $( this ).children( 'div:first' ).addClass( 'slide-left' );
            $( this ).children( 'div:last' ).addClass( 'slide-right' );
          } );
        }
      }
    } );
  }

  /**
   * Determine if `.mosaic-section` is within user's viewport
   *
   * @param $section
   * @returns {boolean}
   */
  function isVisibleWithinViewport( $section ) {
    var bottomOfViewport      = $( window ).scrollTop() + $( window ).height();
    var $subContents          = $( '.sub-contents', $section );
    var subContentsPaddingTop = parseInt( $subContents.css( "padding-top" ) );
    var sectionOffsetTop      = $section.offset().top;
    var subContentsOffsetTop  = $subContents.offset().top + (subContentsPaddingTop * 2);

    // '.section-wrapper' might be higher up the page than it's descendent '.sub-contents'
    var topOfObject = (sectionOffsetTop < subContentsOffsetTop) ? (sectionOffsetTop + subContentsPaddingTop) : subContentsOffsetTop;

    return (bottomOfViewport > topOfObject);
  }

  // anchor link smoothing
  // NOTE: only works on links that START WITH the hash, ie: <a href="#jumpto">Jump To</a>
  $( 'a[href^="\\#"]' ).on( 'click', function ( event ) {
    event.preventDefault();
    if ( !$( this.hash ).length ) {
      return;
    }

    var headerHeight;
    if ( $( document ).find( '.header.sticky' ).length > 0 ) {
      headerHeight = $( '.header.sticky' ).outerHeight();
    }

    if ( headerHeight ) {
      var offset = $( this.hash ).offset().top - headerHeight;
      $( 'html,body' ).animate( { scrollTop: offset }, 500 );
    } else {
      $( 'html,body' ).animate( { scrollTop: $( this.hash ).offset().top }, 500 );
    }
  } );

  // vertically aligns element in the center
  $.fn.centerElementVertically = function () {
    $( this ).each( function () {
      var valign             = $( this );
      var valignHeight       = valign.outerHeight();
      var valignParentHeight = valign.parent().height();
      var valignMargin       = valignParentHeight - valignHeight;
      valignMargin           = valignMargin / 2;
      valign.css( 'marginTop', valignMargin );
    } );
  };

  $.fn.bottomElementVertically = function () {
    $( this ).each( function () {
      var valign             = $( this );
      var valignHeight       = valign.outerHeight();
      var valignParentHeight = valign.parent().height();
      var valignMargin       = valignParentHeight - valignHeight;
      valign.css( 'marginTop', valignMargin );
    } );
  };

  // resets matching elements height to 'auto'. May be useful down the line
  $.fn.resetHeight = function () {
    $( this ).each( function () {
      $( this ).outerHeight( 'auto' );
    } );
  };
} );

var watchElemHeights = (function ( $ ) {
  var elemHeights, elemHeightsBackup;

  function debounceElementHeights() {
    clearTimeout( elemHeights );
    clearTimeout( elemHeightsBackup );
    elemHeights       = setTimeout( doElementHeights, 150 );
    // Delayed longer to let things "settle down" and then do it one more time
    elemHeightsBackup = setTimeout( doElementHeights, 500 );
  }

  function doElementHeights() {
    var viewportWidth = $( window ).width();
    matchElementHeights( '.event' );
    if ( viewportWidth > 768 ) {
      matchElementHeights( '.bucket-grid .wrapper' );
      matchElementHeights( '.bucket-overlay .wrapper' );
      matchElementHeights( '.bucket-stats' );
      matchElementHeights( '.bucket-panel' );
      matchElementHeights( '.bucket-panel .bucket-panel-text' );
      matchElementHeights( '.image-list-item .image-list-item-container' );
      matchElementHeights( '.same-height' );
    } else {
      //Reset bucket heights
      $( '.bucket-grid .wrapper' ).resetHeight();
      $( '.bucket-overlay .wrapper' ).resetHeight();
      $( '.bucket-stats' ).resetHeight();
      $( '.bucket-panel' ).resetHeight();
      $( '.bucket-panel .bucket-panel-text' ).resetHeight();
      $( '.image-list-items .image-list-item-container' ).resetHeight();
      $( '.same-height' ).resetHeight();
    }
  }

  // finds tallest element of selector and sets rest of elements to max height
  function matchElementHeights( sel ) {
    var $container = $( sel ).closest( 'section' );

    $container.each( function () {
      $( sel, this ).resetHeight();
      var maxHeight = 0;

      $( sel, this ).each( function () {
        maxHeight = Math.max( maxHeight, $( this ).outerHeight() );
      } );

      $( sel, this ).outerHeight( maxHeight );
    } );
  }

  return {
    init: function () {
      $( window ).on( 'load resize', function () {
        debounceElementHeights();
      } );
    }
  }
})( jQuery );
