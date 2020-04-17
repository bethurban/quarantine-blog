jQuery( function ( $ ) {
  placeholderShiv( $ );
  bindTestimonials.init();
  responsiveVideos( $ );
  headerScroll( $ );
  acgNavGlide.init( 'nav.nav-glide' );
  fancyRecentPosts.init();
  stickyNav.init( 'body > .container' );
  megaMenu.init();
  videoLightbox.init();

  // intentional, based on images loading (http://stackoverflow.com/questions/18849296/masonry-js-overlapping-items)
  jQuery( window ).on( 'load', function () {
    acgMasonry.init( 'div.slides' );
    // To use lightbox, add "lightbox" class to the parent container
  } );

  acgMasonry.init( '.team' );

  $( '.lightbox .gallery' ).acgLightbox();

  // Used for Responsive Scrollable Menu.  Causes the pact to scroll to the top when the menu is expanded.
  //$( 'input.toggle' ).change( function () {
  //  if ( $( this ).is( ':checked' ) ) {
  //    $( 'body, html' ).animate( { scrollTop: 0 }, 350 );
  //  }
  //} );

  //stickySidebar.init( '.sidebar', 'aside', '.with-sidebar-content-wrapper' );

  // Used to dim the Team Members on About Us Page
  $( '#team li' ).hover(
    // This is the "mouse over" function.  Triggers on hover
    function () {
      // Find all siblings (li's other than the one hovered) and add the dim class
      $( this ).siblings().addClass( 'dim' );
    },
    // This is the "mouse out" function. Triggers on end of hover
    function () {
      // Find ALL li's within the team list and remove the dim class
      $( this ).closest( 'ul' ).find( 'li' ).removeClass( 'dim' );
    }
  );

  // Used to add a class to the Image Widget description
  $( '.widget_sp_image-description a' ).hover(
    function () {
      $( this ).closest( 'li' ).find( 'img' ).addClass( 'hovered' );
    },
    function () {
      $( this ).closest( 'li' ).find( 'img' ).removeClass( 'hovered' );
    }
  );

  $( window ).on( 'resize', adjustSubNavTop );
  adjustSubNavTop();

  videoEmbedShortcode.init();
} );

const videoLightbox = (function ( $ ) {
  let $videos;
  let $modal;
  let $overlay;
  let videoID;
  let player;
  let isIOS;

  function prepVideos() {
    if ( !$videos.length ) {
      return;
    }

    isIOS = navigator.userAgent.match( /(iPad)|(iPhone)|(iPod)/i ) !== null;

    $videos.each( function () {
      $( this ).attr( 'data-video', getYouTubeID( $( this ).attr( 'href' ) ) );
      $( this ).attr( 'href', '#' );
      $( this ).on( 'click', function () {
        videoID = $( this ).attr( 'data-video' );
        openVideo();
      } );
    } );

    let $body   = $( 'body' );
    let $script = '<script src="https://www.youtube.com/iframe_api"></script>';
    $body.append( $script );

    $modal = '<div class="video-modal-overlay"></div>';
    $body.append( $modal );

    $modal = '<div class="video-modal-container"><div class="video-modal-dialog"><div id="video-modal-player"></div></div></div>';
    $body.append( $modal );

    $modal   = $( '.video-modal-container' );
    $overlay = $( '.video-modal-overlay' );

    $modal.on( 'click', function () {
      closeVideo();
    } );

    $modal.find( '.video-modal-dialog' ).on( 'click', function ( e ) {
      e.stopPropagation();
    } );
  }

  // Extracts the Youtube video ID from a well formed Youtube URL
  function getYouTubeID( link ) {
    if ( !link ) {
      return '';
    }

    // Assumed Youtube URL formats
    // https://www.youtube.com/watch?v=Pe0jFDPHkzo
    // https://youtu.be/Pe0jFDPHkzo
    // https://www.youtube.com/v/Pe0jFDPHkzo
    // and more

    //See http://stackoverflow.com/a/6904504/4360074
    let youtubeidreg = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i;
    let id           = youtubeidreg.exec( link ); // return Youtube video ID portion of link
    return (id) ? id[ 1 ] : '';
  }

  function openVideo() {
    if ( !videoID ) {
      return;
    }

    $modal.addClass( 'open' );
    $overlay.addClass( 'open' );
    if ( 'undefined' === typeof player ) {
      player = new YT.Player( 'video-modal-player', {
        videoId: videoID,
        playerVars: { autoplay: 1 }
      } );
    } else {
      if ( isIOS ) {
        player.cueVideoById( videoID );
      } else {
        player.loadVideoById( videoID );
      }
    }

    setTimeout( function () {
      responsiveVideos( $ );
    }, 500 );
  }

  function closeVideo() {
    $modal.removeClass( 'open' );
    $overlay.removeClass( 'open' );
    player.stopVideo();
  }

  return {
    init: function () {
      $videos = $( '.video-lightbox' );
      prepVideos();
    }
  }
})( jQuery );

function adjustSubNavTop() {
  // Set up correct "top" value for sub-nav based on actual height
  jQuery( 'nav#main ul.menu > li' ).not( 'div.mega-menu li' ).each(
    function () {
      let top = jQuery( 'nav#main ul.menu > li' ).outerHeight();
      jQuery( this ).children( 'ul.sub-menu' ).first().css( { top: top } );
    }
  );
}

/**
 * Gets called to handle rendering image placeholder for `mst-video` shortcode
 * @see /includes/shortcodes.php L:64
 *
 * @type {{init}}
 */
var videoEmbedShortcode = (function ( $ ) {
  var $videos;

  function loadShortcodeVideos() {
    $videos = $( ".mst-video" );

    //TODO: Wire up better fallback. Perhaps add a class to body if shortcode is on the page.
    if ( !$videos ) {
      return;
    }

    $videos.each( loadVideo );
  }

  /**
   * Load placeholder image for video
   *
   * @param index
   * @param el
   */
  function loadVideo( index, el ) {
    var $el    = $( el );
    var source = $el.data( 'image' );

    var $image = $( '<img />' );
    $image.attr( 'src', source );

    if ( $el.hasClass( 'lazyload' ) ) {
      lazyLoadImage( $image, $el );
    } else {
      $el.append( $image );
    }

    $el.on( 'click', createIFrame );
  }

  /**
   * Lazy load image placeholder for video
   *
   * @param $image
   * @param $el
   */
  function lazyLoadImage( $image, $el ) {
    $image.on( "load", function () {
      $el.appendChild( $image );
    } );
  }

  /**
   * Create video iframe where 'this' is the jQuery element with the class 'mst-video'
   */
  function createIFrame() {
    var $iframe      = $( '<iframe/>' );
    var $video       = $( this );
    var iframeWidth  = $video.width();
    var iframeHeight = $video.height();

    var hideRelatedVideos = '';

    if ( $video.data( 'hide-related-videos' ) ) {
      hideRelatedVideos = '&rel=0';
    }

    $iframe.attr( {
      "width": iframeWidth,
      "height": iframeHeight,
      "frameborder": "0",
      "allowfullscreen": "",
      "webkitallowfullscreen": "",
      "mozallowfullscreen": "",
      "src": $video.data( 'video' ) + "?autoplay=1" + hideRelatedVideos
    } );

    $video.html( "" );
    $video.addClass( 'showing-video' );
    $video.append( $iframe );
  }

  return {
    init: function () {
      loadShortcodeVideos();
    }
  }
})( jQuery );

/**
 * Code to cause navigation to be "sticky" at the top of the page.
 * May need altering depending on markup, but should work if
 * called stickyNav.init();
 */

var stickyNav = {};

stickyNav = (function ( $ ) {
  var $header;
  var $nav;
  var $body;
  var top;
  var header_height;
  var nav_height;
  var sticky       = false;
  var original_css = {};
  var headerOffset = 0;

  function checkSticky() {
    top = $( window ).scrollTop();
    if ( top > headerOffset ) { // header_height
      stick();
    } else {
      unstick();
    }
  }

  function stick() {
    $body.css( {
      paddingTop: original_css.body.paddingTop + header_height
      //'background-position': 'center ' + height + 'px'
    } );

    if ( sticky ) {
      return;
    }

    /**
     * NO padding on body: Content jumps, but image stays neat, does not jump
     * WITH padding on body: Content jumps, image moves down
     *
     */
    var bg  = $header.data( 'background' );
    var css = {
      position: 'fixed',
      left: 0,
      top: 0,
      zIndex: 99998
    };

    if ( bg ) {
      css[ 'background' ] = bg;
    }

    $header.css( css );
    $nav.css( css );

    sticky = true;
  }

  function unstick() {
    if ( !sticky ) {
      return;
    }

    $body.css( original_css.body );
    $nav.css( original_css.nav );
    $header.css( original_css.header );

    sticky = false;
  }

  return {
    init: function ( sel ) {
      if ( $( '.header.sticky' ).length <= 0 ) {
        return;
      }

      $body = (sel) ? $( sel ) : $( 'body' );

      $header       = $( '.header.sticky' );
      headerOffset  = $header.offset().top;
      $nav          = $( '.nav-wrapper.sticky' );
      header_height = $header.outerHeight();
      nav_height    = $nav.outerHeight();

      original_css.nav                 = {};
      original_css.nav.position        = $nav.css( 'position' );
      original_css.nav.left            = $nav.css( 'left' );
      original_css.nav.top             = $nav.css( 'top' );
      original_css.nav.backgroundColor = $nav.css( 'background' );

      original_css.header                 = {};
      original_css.header.position        = $header.css( 'position' );
      original_css.header.left            = $header.css( 'left' );
      original_css.header.top             = $header.css( 'top' );
      original_css.header.backgroundColor = $header.css( 'background' );

      original_css.body            = {};
      original_css.body.paddingTop = parseInt( $body.css( 'padding-top' ) );

      $( window ).scroll( function () {
        checkSticky();
      } );
    }
  }
})( jQuery );

/**
 * Header Scroll
 */
function headerScroll( $ ) {
  var $header = $( '.header' );
  var offset  = $header.offset().top;

  $( window ).scroll( function () {
    if ( $( window ).scrollTop() > offset ) {
      $header.addClass( 'scrolled' );
    } else {
      $header.removeClass( 'scrolled' );
    }
  } );
}

var stickySidebar = {};

stickySidebar = (function ( $ ) {
  var $stickySidebar;
  var $sidebarContainer;
  var $contentWrapper;
  var windowScrollTop;
  var startSticky;
  var stopSticky;
  var top;
  var bottom;

  /**
   * Calculates start of 'sticky' sidebar
   *
   * @returns {number} start
   */
  function calculateStart() {
    var start = $stickySidebar.offset().top;

    if ( $( '.header.sticky' ).length > 0 ) {
      start = start - $( '.header.sticky' ).outerHeight();
    }

    return start;
  }

  /**
   * Calculates end of 'sticky' sidebar
   *
   * @returns {number}
   */
  function calculateStop() {
    var stop;
    var endOfContainer = $contentWrapper.offset().top + $contentWrapper.outerHeight();

    stop = endOfContainer - $stickySidebar.outerHeight();

    if ( $( '.header.sticky' ).length > 0 ) {
      var tmp = stop;
      stop    = tmp - $( '.header.sticky' ).outerHeight();
    }

    return stop;
  }

  /**
   * Calculates correct CSS 'top' value
   *
   * @returns {number} top
   */
  function calculateTop() {
    var top = 0;

    if ( $( '.header.sticky' ).length > 0 ) {
      top = $( '.header.sticky' ).outerHeight();
    }

    return top;
  }

  /**
   * Calculate when the 'sidebar' reached the ends of the main content container
   *
   * @returns {int}
   */
  function calculateBottom() {
    return -$contentWrapper.outerHeight() + $stickySidebar.outerHeight();
  }

  /**
   * Helper function for setting CSS props
   *
   * @param {jQuery} $el
   * @param {object} options
   */
  function setCSSProps( $el, options ) {
    $el.css( options );
  }

  function setStickySidebar() {
    windowScrollTop = $( window ).scrollTop();
    $stickySidebar.addClass( 'sticky-sidebar' );

    var options = {
      position: 'relative',
      top: '',
      bottom: ''
    };

    if ( windowScrollTop >= startSticky && windowScrollTop < stopSticky ) {
      options = {
        position: 'fixed',
        top: top,
        bottom: ''
      };
    }

    if ( windowScrollTop >= stopSticky ) {
      options = {
        position: 'absolute',
        top: '',
        bottom: bottom
      };
    }

    setCSSProps( $stickySidebar, options );
  }

  function removeStickyness() {
    var options = {
      position: '',
      top: '',
      bottom: ''
    };

    setCSSProps( $stickySidebar, options );
  }

  return {
    init: function ( sel, container, contentWrapper ) {
      $contentWrapper   = $( contentWrapper );
      $sidebarContainer = $( container, $contentWrapper );
      $stickySidebar    = $( sel, $contentWrapper );

      if ( !stickySidebar ) {
        return;
      }

      $( window ).on( 'load resize', function () {
        startSticky = calculateStart();
        stopSticky  = calculateStop();
        top         = calculateTop();
        bottom      = calculateBottom();

        $stickySidebar.width( $sidebarContainer.width() );

        $( window ).scroll( function () {
          if ( $( window ).scrollTop() > 0 && $( window ).width() > 992 ) {
            setStickySidebar();
          } else {
            removeStickyness();
          }
        } );
      } );
    }
  }
})( jQuery );

/**
 * Code to provide shiv to placeholders
 */
function placeholderShiv( $ ) {
  // Test for native browser support, exit if it exists
  var i = document.createElement( 'input' );
  if ( 'placeholder' in i ) {
    return;
  }

  $( 'input[placeholder], textarea[placeholder], select[placeholder]' ).focus(
    function () {
      if ( $( this ).val() == $( this ).attr( 'data-original-ph' ) ) {
        $( this ).val( '' );
        $( this ).removeClass( 'placeholder' );
      }
    }
  ).blur( function () {
      if ( $( this ).val() == '' ) {
        $( this ).val( $( this ).attr( 'data-original-ph' ) );
        $( this ).addClass( 'placeholder' );
      }
    }
  ).each( function () {
      $( this ).attr( 'data-original-ph', $( this ).attr( 'placeholder' ) );
      if ( $( this ).is( ':required' ) ) {
        $( this ).addClass( 'required' );
      }
    }
  ).trigger( 'blur' );

  $( 'input[placeholder], textarea[placeholder], select[placeholder]' ).closest( 'form' ).submit(
    function () {
      $( 'input[placeholder], textarea[placeholder], select[placeholder]' ).each(
        function () {
          if ( $( this ).val() == $( this ).attr( 'data-original-ph' ) ) {
            $( this ).val( '' );
            $( this ).removeClass( 'placeholder' );
          }
        }
      );
    }
  );
}

/**
 * Code for BindTestimonials
 *
 */
var bindTestimonials = (function () {
  var $;
  var testi_container;
  var testi_controls;
  var animate      = false;
  var controls     = false;
  var controlsMove = false;
  var auto         = false;
  var max_height   = 0;
  var testi_pause  = false;
  var testi_delay  = 5000;
  var testiTimer;
  var testiDebounceTimer;
  var running      = false;

  function runTestimonials() {
    if ( testi_pause ) {
      return;
    }

    running = true;
    incrementTestimonial( 1, true );
  }

  function incrementTestimonial( dir, auto ) {
    var cur = testi_container.find( 'li:visible' ).first();
    var nex = (dir > 0) ? cur.next() : cur.prev();

    if ( nex.length < 1 ) {
      var sel = (dir > 0) ? 'li:first' : 'li:last';
      nex     = testi_container.find( sel );
    }

    if ( !auto ) {
      var top = testi_container.offset().top;
      $( 'html, body' ).animate( { scrollTop: top } );
    }

    cur.fadeOut( 500, function () {
      testi_container.children( 'li' ).removeClass( 'active' );
      nex.addClass( 'active' ).fadeIn( 500 );
      clearTimeout( testiTimer );

      if ( auto ) {
        testiTimer = setTimeout( function () {
          runTestimonials();
        }, testi_delay );
      }
    } );

    testi_controls.fadeOut( 400, function () {
      testi_controls.fadeIn( 700 );
    } );
  }

  function testiDebounce() {
    clearTimeout( testiDebounceTimer );

    testiDebounceTimer = setTimeout( function () {
      setMaxHeight();
      //runTestimonials();
    }, 300 );
  }

  /**
   * Only run when we're animating.  This sets the viewport to a single testimonial
   */
  function setMaxHeight() {
    if ( controlsMove ) {
      setMaxHeightWidget();
    } else {
      setMaxHeightItems();
    }

    testi_container.children( 'li' ).hide();

    if ( testi_container.children( 'li.active' ).length < 1 ) {
      testi_container.children( 'li:first' ).show();
    } else {
      testi_container.children( 'li.active' ).show();
    }
  }

  function setMaxHeightItems() {
    // Without this, the height calculation may be incorrect (if there's an item at the top with a margin)
    testi_container.children( 'li' ).css( { paddingTop: '1px', position: 'relative', height: 'auto' } );
    // Get the maximimum height of all the li elements, so we can set the entire thing to the right height
    max_height = Math.max.apply( null, $( ".widget_acg_testimonials_widget li" ).map( function () {
      return $( this ).outerHeight();
    } ).get() );

    testi_container.css( { height: max_height, position: 'relative' } );

    testi_container.children( 'li' ).css( { position: 'absolute', top: 0, left: 0, height: max_height } );
  }

  /**
   * Only run when we're animating.  This sets the viewport to a single testimonial
   */
  function setMaxHeightWidget() {
    // Without this, the hieght calculation may be incorrect (if there's an item at the top with a margin)
    testi_container.children( 'li' ).css( { paddingTop: '1px', position: 'relative', height: 'auto' } );
    // Get the maximimum height of all the li elements, so we can set the entire thing to the right height
    max_height = Math.max.apply( null, $( ".widget_acg_testimonials_widget li" ).map( function () {
      return $( this ).outerHeight();
    } ).get() );

    max_height += testi_controls.outerHeight();

    testi_container.closest( '.widget' ).css( { height: max_height, position: 'relative' } );
  }

  function setAnimation() {
    if ( testi_container.hasClass( 'animate-controls' ) ) {
      controls = true;
    }

    if ( testi_container.hasClass( 'action-animate' ) ) {
      animate = true;
    }

    if ( testi_container.hasClass( 'animate-animate' ) ) {
      auto = true;
    }

    if ( testi_container.hasClass( 'controls-move' ) ) {
      controlsMove = true;
    }
  }

  function animationStyles() {
    if ( animate ) {
      setMaxHeight();
    }

    if ( controls ) {
      $( '.testi_controls li.control a' ).click(
        function () {
          var dir = $( this ).hasClass( 'prev' ) ? -1 : 1;
          incrementTestimonial( dir, false );
        }
      );

      $( '.testi_controls a.pause' ).click(
        function () {
          testi_pause = (testi_pause == false);
          if ( !testi_pause ) {
            $( this ).html( 'Pause' ).removeClass( 'paused' );
            incrementTestimonial( 1, true );
          } else {
            $( this ).html( 'Unpause' ).addClass( 'paused' );
          }
        }
      );
    }

    if ( auto ) {
      runTestimonials();
    }
  }

  function startAnimation() {
    if ( animate ) {
      $( window ).on( 'resize', testiDebounce );
    }

    if ( auto ) {
      runTestimonials();
    }
  }

  return {
    init: function () {
      $               = jQuery;
      testi_container = $( '.widget_acg_testimonials_widget > ul:first' );
      if ( testi_container.length < 1 || testi_container.find( 'li' ).length < 1 ) {
        return;
      }

      testi_controls = $( '.widget_acg_testimonials_widget > ul.testi_controls' );

      testi_delay = $( '.widget_acg_testimonials_widget > ul:first' ).attr( 'data-delay' ) || 5000;

      setAnimation();
      animationStyles();
      startAnimation();
    }
  }
})();

/**
 * Code to make videos responsive
 * @param jQuery - $
 */
function responsiveVideos( $ ) {
  // Find all YouTube and Vimeo videos
  var $allVideos = $( "iframe[src*='//player.vimeo.com'], iframe[src*='//www.youtube.com']" );
  if ( typeof $allVideos != 'undefined' && $allVideos ) {
    // Figure out and save aspect ratio for each video
    $allVideos.each( function () {
      var w = $( this ).attr( 'width' );
      var h = $( this ).attr( 'height' );

      if ( isNaN( parseFloat( w ) ) || isNaN( parseFloat( h ) ) ) {
        w = $( this ).width();
        h = $( this ).height();
      }

      var a = h / w;
      $( this )
        .data( 'aspectRatio', a )
        .removeAttr( 'height' )
        .removeAttr( 'width' );

    } );
  }

  // When the window is resized
  $( window ).resize( function () {
    // Resize all videos according to their own aspect ratio
    $allVideos.each( function () {
      var newWidth = $( this ).parent().width();
      var $el      = $( this );
      $el.width( newWidth ).height( newWidth * $el.data( 'aspectRatio' ) );
    } );

    // Kick off one resize to fix all videos on page load
  } ).resize();
}

/**
 * Code for Fancy RecentPosts
 */
var fancyRecentPosts = {};

fancyRecentPosts = (function () {
  var $;
  var container;
  var on;
  var current;
  var mask;
  var top;
  var left;
  var width;
  var height;
  var timeout;

  function bindPosts() {
    container.css( { position: 'relative', overflow: 'hidden' } );
    container.find( 'li' ).hoverIntent(
      function () {
        setOn( true, $( this ) );
      }
    );

    container.hoverIntent(
      function () {
        on = true;
      },
      function ( e ) {
        on = false;
        clearOn( e );
      }
    );
  }

  function setOn( on, el ) {
    clearTimeout( timeout );
    if ( $( el )[ 0 ] === $( current )[ 0 ] ) {
      return;
    }
    current    = el;
    var offset = current.position();
    top        = offset.top;
    left       = current.offset().left;
    width      = current.width();
    height     = current.height();

    if ( !on ) {
      mask.css( { left: left } );
    }

    on = true;
    animateHere();
  }

  function clearOn( e ) {
    timeout = setTimeout( function () {
        top     = (e.pageY < (container.position().top + 10)) ? (mask.height() * -1) : container.height();
        top *= 1.05;
        on      = false;
        current = null
        mask.stop( true, true ).animate( { top: top, left: left }, 100 );
      }
      , 150 );
  }

  function setupMask() {
    var el = $( '<a />' );
    el.prop( 'id', 'mask' );
    el.css( { position: 'absolute', left: 0, top: -1000, zIndex: '255555' } );
    container.append( el );
    mask = container.find( '#mask' );
    mask.css( 'z-index', 2 );
  }

  function animateHere() {
    setContents();
    mask.css( { width: width, height: height } );
    if ( Math.abs( left - mask.position().left ) > 5 ) {
      mask.animate( { left: left }, 200, function () {
        mask.animate( { top: top }, 200 );
      } );
    } else {
      mask.animate( { top: top } );
    }
  }

  function setContents() {
    mask.html( current.find( '.content' ).html() );
    mask.attr( 'href', current.find( 'a' ).attr( 'href' ) );
  }

  return {
    init: function () {
      $ = jQuery;
      if ( $( '.widget_recent_posts_featured_image_author ul.fancy_hover' ).length > 0 ) {
        container = $( '.widget_recent_posts_featured_image_author' );
        container.find( 'ul' ).wrap( '<div class="mask_container" />' );
        container = container.find( 'div.mask_container' );
        container.find( 'ul' ).css( 'z-index', 1 );
        bindPosts();
        setupMask();
      } else {
        return;
      }
    }
  }
})();

/**
 * Code to add navigation line that glides / animates left / right on hover.
 * Usage:
 * add the class "nav-glide" to any nav to enable this.
 * Add the class "nav-glide-include-padding" to cause the line to be as wide as the whole LI (rather than just the text)
 * Style the elements to get the desired look:
 * .nav-line - this will allow you to present a line across the whole navigation
 * .nav-glider  the element that underlines the current / hovered li
 */

var acgNavGlide = {};

acgNavGlide = (function () {
  var $;
  var timer;
  var bound;

  function setUp( el ) {
    $( window ).bind( 'resize.glider', function () {
      checkIfResponsive( el );
    } );

    checkIfResponsive( el );
  }

  function bind( el ) {
    var ul = el.find( 'ul' ).first();

    var left = ul.position().left;
    var w    = 0;
    ul.children( 'li' ).each( function () {
      w += $( this ).outerWidth();
    } );
    var pad = getPadding( ul.children( 'li' ).first() );

    var line = $( '<div class="nav-line"></div>' );
    line.css( { width: w, left: left } );

    var liner = $( '<div class="nav-liner"></div>' );
    liner.css( { position: 'relative', width: '100%' } );

    var span = $( '<span class="glider"></span>' );
    span.css( { left: '-999em' } );

    liner.append( span );
    line.append( liner );
    ul.after( line );

    var active = getActiveElement( ul );
    moveTo( active );

    ul.children( 'li' ).hoverIntent(
      function () {
        glideTo( $( this ) );
      },
      function () {
        if ( !$( this ).closest( 'ul' ).is( ':hover' ) ) {
          var li = getActiveElement( $( this ).closest( 'ul' ) );
          timer  = setTimeout( function () {
            glideTo( li )
          }, 450 );
        }
      }
    );

    bound = true;
  }

  function glideTo( el ) {
    clearTimeout( timer );
    var left = getLeft( el );
    var w    = getWidth( el );
    el.closest( 'nav' ).find( 'span.glider' ).stop().animate( { left: left, width: w }, 'easeInQuad' );
  }

  function moveTo( el ) {
    var left = getLeft( el );
    var w    = getWidth( el );
    el.closest( 'nav' ).find( 'span.glider' ).css( { left: left, width: w } );
  }

  function getWidth( el ) {
    var w   = el.width();
    var pad = getPadding( el );
    w -= pad;
    return w;
  }

  function getLeft( el ) {
    var left = el.position().left;
    var pad  = getPadding( el );
    left += (pad / 2);
    return left;
  }

  function getPadding( el ) {
    if ( el.closest( 'nav' ).hasClass( 'nav-glide-include-padding' ) ) {
      return 0;
    }
    var pad = el.innerWidth() - el.width();
    pad += (el.find( 'a' ).first().innerWidth() - el.find( 'a' ).first().width());
    return pad;
  }

  function getActiveElement( ul ) {
    var current = ul.children( '.current-menu-ancestor' );
    if ( !current.length ) {
      current = ul.children( '.current-menu-item' )
    }
    if ( !current.length ) {
      // How did we get here?
    }
    return current;
  }

  /**
   * This effect causes mobile devices to require a double-tap to go to the page.
   * So, with this function, we watch to see if the menu is responsive or not, and
   * unbind the effects if so.
   */
  function checkIfResponsive( el ) {
    responsive = (el.find( 'label.toggle' ).is( ':visible' )) ? true : false;

    if ( responsive ) {
      if ( bound ) {
        var ul = el.find( 'ul' ).first();
        ul.children( 'li' ).unbind( 'hoverIntent' );
        ul.children( 'li' ).unbind( "mouseenter" ).unbind( "mouseleave" );
        ul.children( 'li' ).removeProp( 'hoverIntent_t' );
        ul.children( 'li' ).removeProp( 'hoverIntent_s' );
        bound = false;
      }
    } else {
      if ( !bound ) {
        bind( el );
      }
    }
  }

  return {
    init: function ( sel ) {
      $ = jQuery;

      $( sel ).each( function () {
        setUp( $( this ) );
      } );
    }
  }
})();

//Provides lightbox for Gallery with specific class from template.
var acgLightbox = {};

(function ( $ ) {
  $.fn.acgLightbox = function () {
    return this.each( function () {
      var h;
      var p;
      var w;
      var s;
      var item_name;
      var $container          = $( this );
      var $items;
      var blur;
      var lbwrap;
      var lb;
      var curdisplay;
      var prev;
      var next;
      var close;
      var KEYCODE_LEFT_ARROW  = 37;
      var KEYCODE_RIGHT_ARROW = 39;
      var KEYCODE_ENTER       = 13;
      var KEYCODE_ESC         = 27;

      $container.find( 'br' ).remove();
      item_name = '.gallery-item';
      assignData();
      setPort();
      bindEvents();

      function removeBlur() {
        $( '#blur, #lbwrap' ).fadeOut( 500, function () {
          $( this ).remove();
        } );
      }

      function setPort() {
        h = $( document ).height();
        p = $( window ).height();
        w = $( window ).width();
        s = $( document ).scrollTop();
      }

      function showLightbox() {
        blur = $( '<div />' );
        blur.attr( 'id', 'blur' );
        blur.css( {
          position: 'absolute',
          top: 0,
          left: 0,
          width: '100%',
          height: h,
          background: 'black',
          opacity: .7,
          zIndex: 255555
        } );
        blur.click(
          function () {
            removeBlur();
          }
        );

        $( 'body' ).append( blur );

        lbwrap = $( '<div />' );
        lbwrap.attr( 'id', 'lbwrap' );
        lbwrap.css( {
          position: 'absolute',
          top: 0,
          left: 0,
          width: '100%',
          height: h,
          background: 'transparent',
          zIndex: 255556,
          textAlign: 'center'
        } );

        lb = $( '<div />' );
        lb.attr( 'id', 'lightbox' );
        lb.css( {
          position: 'relative',
          boxSizing: 'content-box',
          margin: '0 auto',
          width: 0,
          height: 0,
          marginTop: (p / 2)
        } );

        close = $( '<a href="javascript:void(0);" class="fa fa-times close"><span>X</span></a>' );
        lb.append( close );

        close.click(
          function ( event ) {
            event.stopPropagation();
            removeBlur();
          }
        );

        lbwrap.append( lb );

        $( 'body' ).append( lbwrap );
      }

      function setCounts() {
        if ( lb.find( 'p' ).length <= 0 ) {
          var p = $( '<p />' );
          p.attr( 'class', 'controlwrapper' );
          //p.css({color: '#aaa', fontSize: '13px', textAlign: 'left', padding: 0, fontFamily: 'Arial, Helvetica, SansSerif', position: 'absolute', left: 10, bottom: 0, width: '100%'});
          curdisplay = $( '<span class="current" />' );
          //p.append(curdisplay);
          //p.append(' of ' + (galleryImages.length));

          var controls = $( '<span />' );
          controls.attr( 'class', 'controls' );
          //controls.css({float: 'right', marginRight: 10, textAlign: 'right'});
          html = '';

          //var css = {display: 'inline-block', color: 'white', padding: '0 5px'};
          prev = $( '<a class="prev" href="javascript:void(0);"><i class="fa fa-arrow-left"></i></a>' );
          next = $( '<a class="next" href="javascript:void(0);"><i class="fa fa-arrow-right"></i></a>' );

          //prev.css(css);
          //next.css(css);

          controls.append( prev );
          controls.append( next );

          prev.click(
            function ( event ) {
              event.stopPropagation();
              incrementImage( -1 );
            }
          );

          next.click(
            function ( event ) {
              event.stopPropagation();
              incrementImage( 1 );
            }
          );

          p.append( controls );
          lb.append( p );
        }

        if ( current > 0 ) {
          prev.show();
        } else {
          prev.hide();
        }

        if ( current < ($items.length - 1) ) {
          next.show();
        } else {
          next.hide();
        }

        curdisplay.html( parseFloat( current ) + 1 );
      }

      function setCaption() {
        lb.find( '.lb-caption-container' ).remove();
        var lbcontainer = $( '<div class="lb-caption-container"></div>' );
        lb.append( lbcontainer );
        var title = $( '[data-index="' + current + '"] dd.wp-title-text' ).html();
        lbcontainer.append( '<div class="lb-title">' + title + '</div>' );
        var caption = $( '[data-index="' + current + '"] dd.wp-caption-text' ).html();
        lbcontainer.append( '<div class="lb-caption">' + caption + '</div>' );
      }

      function incrementImage( add ) {
        current = parseFloat( current );

        if ( isNaN( current ) ) {
          current = 0;
        }

        current += add;

        if ( current > ($items.length - 1) ) {
          current = 0;
        } else if ( current < 0 ) {
          current = $items.length - 1;
        }

        transitionImage( true );
      }

      function transitionImage( hide ) {
        setPort();

        var img = $items.eq( current ).attr( 'data-src' );
        if ( !img ) {
          img = $( galleryImages[ current ] );
        } else {
          img = $( '<img src="' + img + '">' );
        }

        var iw = img[ 0 ].width;
        var ih = img[ 0 ].height;
        var ip = iw / ih;
        var tw = iw * .9;
        var th = ih * .9;

        if ( tw > w ) {
          tw = (w - 40);
          th = tw / ip;
        }

        if ( (th + 60) > p ) {
          th = (p - 70);
          tw = th * ip
        }

        var lw = tw;
        var lh = th + 0;

        var mt = (s + (p - lh) / 2) - 15;

        var int = 400;
        if ( hide ) {
          lb.find( 'img, .lb-caption' ).remove();
          img.css( { width: tw, height: th } );
        } else {
          int = 200;
          lb.find( 'img' ).stop( true, true ).animate( { width: tw, height: th }, int );
        }

        close.hide();

        lb.stop( true, true ).animate( { width: lw, height: lh, marginTop: mt }, int, function () {
          if ( hide ) {
            close.fadeIn();
            lb.prepend( img );
            setCaption();
            setCounts();
          }
        } );
      }

      function assignData() {
        $items = $container.find( item_name );
        $container.find( item_name ).each(
          function () {
            $( this ).attr( 'data-index', $( this ).index() ).addClass( 'item' );
          }
        );
      }

      function bindEvents() {
        $container.on( 'click', '.item a', function ( event ) {
          event.preventDefault();
          current = $( this ).closest( '.item' ).attr( 'data-index' );
          showLightbox();
          setCounts();

          lbwrap.click(
            function () {
              removeBlur();
            }
          );

          lb.click(
            function ( event ) {
              event.stopPropagation();
              incrementImage( 1 );
            }
          );

          transitionImage( true );
        } );

        $( window ).resize( function () {
            if ( lb && lb.is( ':visible' ) ) {
              transitionImage( false );
            }
          }
        );

        $( document ).keyup( function ( e ) {
          if ( e.keyCode == KEYCODE_LEFT_ARROW ) {
            incrementImage( -1 );
          }
          if ( e.keyCode == KEYCODE_RIGHT_ARROW ) {
            incrementImage( 1 );
          }
          if ( e.keyCode == KEYCODE_ESC ) {
            removeBlur();
          }
        } );
      }

    } );
  }
})( jQuery );

//Customized Masonry class.  Provides lightbox, initiates Masonry.
var acgMasonry = {};

acgMasonry = (function () {
  var $;
  var h;
  var p;
  var w;
  var s;
  var blur;
  var lbwrap;
  var lb;
  var close;

  var socialMediaTemplate = {
    linkedin: 'LinkedIn',
    facebook: 'Facebook',
    twitter: 'Twitter',
    google_plus: ' Google Plus'
  };

  var metaDataTemaplate = [
    'facebook',
    'linkedin',
    'twitter',
    'google_plus',
    'external_link_text',
    'external_link_url'
  ];

  //var KEYCODE_LEFT_ARROW  = 37;
  //var KEYCODE_RIGHT_ARROW = 39;
  //var KEYCODE_ESC         = 27;
  var $container;
  var $member;
  var maxWidth;
  var debounce;

  function removeBlur() {
    $( '#blur, #lbwrap' ).fadeOut( 500, function () {
      $( this ).remove();
    } );
  }

  function setPort() {
    h = $( document ).height();
    p = $( window ).height();
    w = $( window ).width();
    s = $( document ).scrollTop();
  }

  function showLightbox() {
    blur = $( '<div />' );
    blur.attr( 'id', 'blur' );
    blur.css( {
      position: 'fixed',
      top: 0,
      left: 0,
      width: '100%',
      height: '100%',
      background: 'black',
      opacity: .7,
      zIndex: 255555
    } );
    blur.click(
      function () {
        removeBlur();
      }
    );

    $( 'body' ).append( blur );

    lbwrap = $( '<div />' );
    lbwrap.attr( 'id', 'lbwrap' );
    lbwrap.css( {
      position: 'fixed',
      top: 0,
      left: 0,
      width: '100%',
      height: '100%',
      background: 'transparent',
      zIndex: 255556,
    } );

    lb = $( '<div />' );
    lb.attr( 'id', 'lightbox' );
    lb.addClass( 'lightbox-content' );
    lb.css( {
      position: 'absolute',
      boxSizing: 'content-box'
    } );

    close = $( '<a href="javascript:void(0);" class="fa fa-times close"><span>X</span></a>' );
    lb.append( close );

    lb.append( retrieveTeamMember() );

    close.click(
      function ( event ) {
        event.stopPropagation();
        removeBlur();
      }
    );

    lbwrap.append( lb );

    $( 'body' ).append( lbwrap );
  }

  /**
   * Create and return HTML elements with member data (using mosaicTeamData as the array that holds serialized team data)
   *
   * @returns {*|jQuery|HTMLElement}
   */
  function retrieveTeamMember() {
    var memberID       = $member.data( 'id' );
    var memberData     = mosaicTeamData[ memberID ];
    var memberMetaData = memberData.meta_data;

    var contentWrapper = $( '<div/>' );
    contentWrapper.attr( 'class', 'team-member-wrapper' );

    var memberInfoWrapper = $( '<div/>' ).attr( 'class', 'team-member-info' );

    var title    = $( '<h1/>' ).append( memberData.title );
    var position = $( '<h2/>' ).append( memberMetaData[ 'position' ] );
    var bio      = $( '<div />' ).addClass( 'bio' ).append( memberData.bio );

    memberInfoWrapper.append( title, position, bio );

    // Check for team member photo
    if ( memberData.image ) {
      contentWrapper.append( createImage( memberData.image, 'Team Member Photo', 'team-member-photo' ) );
    }

    // Create anchor links for social media
    metaDataTemaplate.forEach( function ( value ) {
      if ( value.indexOf( 'external' ) === -1 && memberMetaData[ value ] ) {
        var metaWrapper = $( '<div/>' ).addClass( value );
        var anchor      = createAnchorLink( memberMetaData[ value ], socialMediaTemplate[ value ], memberMetaData[ value ] );

        metaWrapper.append( anchor );
        memberInfoWrapper.append( metaWrapper );
      }
    } );

    //Create anchor link with external_link fields
    if ( memberMetaData.external_link_text && memberMetaData.external_link_url ) {
      var metaWrapper = $( '<div/>' ).attr( 'class', 'external_link_text' );
      var anchor      = createAnchorLink( memberMetaData.external_link_url, memberMetaData.external_link_text, 'external_link_text' );

      metaWrapper.append( anchor );
      memberInfoWrapper.append( metaWrapper );
    }

    contentWrapper.append( memberInfoWrapper );

    return contentWrapper;
  }

  /**
   * Create an 'a' HTML element
   *
   * @param url
   * @param text
   * @param className
   * @returns {void | * | jQuery}
   */
  function createAnchorLink( url, text, className ) {
    return $( "<a target='_blank'/>" ).attr( 'href', url ).addClass( className ).append( text );
  }

  /**
   * Create a 'img' HTML element and wrap it in a 'div'
   *
   * @param src
   * @param alt
   * @param wrapperClass
   * @returns {*|void|jQuery}
   */
  function createImage( src, alt, wrapperClass ) {
    var wrapper = $( '<div/>' ).addClass( wrapperClass );
    var img     = $( '<img />' ).attr( {
      src: src,
      alt: alt
    } );

    wrapper.append( img );

    return wrapper;
  }

  function bindEvents() {
    $container.on( 'click', '.team-member', function ( event ) {
      event.preventDefault();
      $member = $( this );
      current = $( this ).closest( 'li' ).attr( 'data-index' );

      if ( !current ) {
        current = $( this ).closest( '.item' ).attr( 'data-id' );
      }

      showLightbox();

      lbwrap.click(
        function () {
          removeBlur();
        }
      );

      lb.click(
        function ( event ) {
          event.stopPropagation();
        }
      );
    } );

    $( window ).resize( function () {
        clearTimeout( debounce );
      }
    );

    $( document ).keyup( function ( e ) {
      removeBlur();
    } );
  }

  return {
    init: function ( sel ) {
      $          = jQuery;
      $container = $( sel );
      setPort();
      bindEvents();
    }

  }
})();

var megaMenu = (function ( $ ) {
  var $megaMenus;

  /**
   * Set up the event bindings to display / hide the mega menus
   */
  function bindEvents() {
    $megaMenus.each( function () {
      var mID = $( this ).data( 'menu-id' );
      var $li = $( this ).closest( 'li.menu-item-' + mID );

      $li.data( 'menu-id', mID );

      $li.on( 'mouseenter', function () {
        positionMegaMenu( $( this ) );

        $( document ).on( 'scroll', function () {
          hideMegaMenu( $( this ) );
        } )
      } );

      $li.on( 'mouseleave', function () {
        hideMegaMenu( $( this ) );
      } )
    } );
  }

  /**
   * Render the appropriate mega menu item
   *
   * @param {jQuery} $li - the hovered LI item
   * @param menuID
   */
  function positionMegaMenu( $li ) {
    var $megaMenu = $li.find( 'div.mega-menu' );
    var top       = $li.offset().top;
    var liHeight  = $li.outerHeight();
    var scroll    = $( window ).scrollTop();
    top += liHeight - scroll;

    $megaMenu.css( {
      position: 'fixed',
      top: top,
      display: 'block'
    } );
  }

  function hideMegaMenu( $li ) {
    var $megaMenu = $li.find( 'div.mega-menu' );

    $megaMenu.css( {
      display: 'none'
    } );
  }

  return {
    init: function () {
      $megaMenus = $( 'nav#main div.mega-menu' );
      if ( !$megaMenus.length ) {
        return;
      }

      bindEvents();
    }
  }
})( jQuery );

/*!
 * imagesLoaded PACKAGED v3.1.8
 * JavaScript is all like "You images are done yet or what?"
 * MIT License
 */
(function () {
  function e() {
  }

  function t( e, t ) {
    for ( var n = e.length; n--; ) if ( e[ n ].listener === t ) return n;
    return -1
  }

  function n( e ) {
    return function () {
      return this[ e ].apply( this, arguments )
    }
  }

  var i = e.prototype, r = this, o = r.EventEmitter;
  i.getListeners = function ( e ) {
    var t, n, i = this._getEvents();
    if ( "object" == typeof e ) {
      t = {};
      for ( n in i ) i.hasOwnProperty( n ) && e.test( n ) && (t[ n ] = i[ n ])
    } else t = i[ e ] || (i[ e ] = []);
    return t
  }, i.flattenListeners = function ( e ) {
    var t, n = [];
    for ( t = 0; e.length > t; t += 1 ) n.push( e[ t ].listener );
    return n
  }, i.getListenersAsObject = function ( e ) {
    var t, n = this.getListeners( e );
    return n instanceof Array && (t = {}, t[ e ] = n), t || n
  }, i.addListener = function ( e, n ) {
    var i, r = this.getListenersAsObject( e ), o = "object" == typeof n;
    for ( i in r ) r.hasOwnProperty( i ) && -1 === t( r[ i ], n ) && r[ i ].push( o ? n : {
      listener: n,
      once: !1
    } );
    return this
  }, i.on = n( "addListener" ), i.addOnceListener = function ( e, t ) {
    return this.addListener( e, { listener: t, once: !0 } )
  }, i.once = n( "addOnceListener" ), i.defineEvent = function ( e ) {
    return this.getListeners( e ), this
  }, i.defineEvents = function ( e ) {
    for ( var t = 0; e.length > t; t += 1 ) this.defineEvent( e[ t ] );
    return this
  }, i.removeListener = function ( e, n ) {
    var i, r, o = this.getListenersAsObject( e );
    for ( r in o ) o.hasOwnProperty( r ) && (i = t( o[ r ], n ), -1 !== i && o[ r ].splice( i, 1 ));
    return this
  }, i.off = n( "removeListener" ), i.addListeners = function ( e, t ) {
    return this.manipulateListeners( !1, e, t )
  }, i.removeListeners = function ( e, t ) {
    return this.manipulateListeners( !0, e, t )
  }, i.manipulateListeners = function ( e, t, n ) {
    var i, r, o = e ? this.removeListener : this.addListener, s = e ? this.removeListeners : this.addListeners;
    if ( "object" != typeof t || t instanceof RegExp ) for ( i = n.length; i--; ) o.call( this, t, n[ i ] ); else for ( i in t ) t.hasOwnProperty( i ) && (r = t[ i ]) && ("function" == typeof r ? o.call( this, i, r ) : s.call( this, i, r ));
    return this
  }, i.removeEvent = function ( e ) {
    var t, n = typeof e, i = this._getEvents();
    if ( "string" === n ) delete i[ e ]; else if ( "object" === n ) for ( t in i ) i.hasOwnProperty( t ) && e.test( t ) && delete i[ t ]; else delete this._events;
    return this
  }, i.removeAllListeners = n( "removeEvent" ), i.emitEvent = function ( e, t ) {
    var n, i, r, o, s = this.getListenersAsObject( e );
    for ( r in s ) if ( s.hasOwnProperty( r ) ) for ( i = s[ r ].length; i--; ) n = s[ r ][ i ], n.once === !0 && this.removeListener( e, n.listener ), o = n.listener.apply( this, t || [] ), o === this._getOnceReturnValue() && this.removeListener( e, n.listener );
    return this
  }, i.trigger = n( "emitEvent" ), i.emit = function ( e ) {
    var t = Array.prototype.slice.call( arguments, 1 );
    return this.emitEvent( e, t )
  }, i.setOnceReturnValue = function ( e ) {
    return this._onceReturnValue = e, this
  }, i._getOnceReturnValue = function () {
    return this.hasOwnProperty( "_onceReturnValue" ) ? this._onceReturnValue : !0
  }, i._getEvents = function () {
    return this._events || (this._events = {})
  }, e.noConflict = function () {
    return r.EventEmitter = o, e
  }, "function" == typeof define && define.amd ? define( "eventEmitter/EventEmitter", [], function () {
    return e
  } ) : "object" == typeof module && module.exports ? module.exports = e : this.EventEmitter = e
}).call( this ), function ( e ) {
  function t( t ) {
    var n = e.event;
    return n.target = n.target || n.srcElement || t, n
  }

  var n = document.documentElement, i = function () {
  };
  n.addEventListener ? i = function ( e, t, n ) {
    e.addEventListener( t, n, !1 )
  } : n.attachEvent && (i = function ( e, n, i ) {
    e[ n + i ] = i.handleEvent ? function () {
      var n = t( e );
      i.handleEvent.call( i, n )
    } : function () {
      var n = t( e );
      i.call( e, n )
    }, e.attachEvent( "on" + n, e[ n + i ] )
  });
  var r = function () {
  };
  n.removeEventListener ? r = function ( e, t, n ) {
    e.removeEventListener( t, n, !1 )
  } : n.detachEvent && (r = function ( e, t, n ) {
    e.detachEvent( "on" + t, e[ t + n ] );
    try {
      delete e[ t + n ]
    } catch ( i ) {
      e[ t + n ] = void 0
    }
  });
  var o = { bind: i, unbind: r };
  "function" == typeof define && define.amd ? define( "eventie/eventie", o ) : e.eventie = o
}( this ), function ( e, t ) {
  "function" == typeof define && define.amd ? define( ["eventEmitter/EventEmitter", "eventie/eventie"], function ( n, i ) {
    return t( e, n, i )
  } ) : "object" == typeof exports ? module.exports = t( e, require( "wolfy87-eventemitter" ), require( "eventie" ) ) : e.imagesLoaded = t( e, e.EventEmitter, e.eventie )
}( window, function ( e, t, n ) {
  function i( e, t ) {
    for ( var n in t ) e[ n ] = t[ n ];
    return e
  }

  function r( e ) {
    return "[object Array]" === d.call( e )
  }

  function o( e ) {
    var t = [];
    if ( r( e ) ) t = e; else if ( "number" == typeof e.length ) for ( var n = 0,
                                                                           i = e.length; i > n; n++ ) t.push( e[ n ] ); else t.push( e );
    return t
  }

  function s( e, t, n ) {
    if ( !(this instanceof s) ) return new s( e, t );
    "string" == typeof e && (e = document.querySelectorAll( e )), this.elements = o( e ), this.options = i( {}, this.options ), "function" == typeof t ? n = t : i( this.options, t ), n && this.on( "always", n ), this.getImages(), a && (this.jqDeferred = new a.Deferred);
    var r = this;
    setTimeout( function () {
      r.check()
    } )
  }

  function f( e ) {
    this.img = e
  }

  function c( e ) {
    this.src = e, v[ e ] = this
  }

  var a = e.jQuery, u = e.console, h = u !== void 0, d = Object.prototype.toString;
  s.prototype = new t, s.prototype.options = {}, s.prototype.getImages = function () {
    this.images = [];
    for ( var e = 0, t = this.elements.length; t > e; e++ ) {
      var n = this.elements[ e ];
      "IMG" === n.nodeName && this.addImage( n );
      var i = n.nodeType;
      if ( i && (1 === i || 9 === i || 11 === i) ) for ( var r = n.querySelectorAll( "img" ), o = 0,
                                                             s                                  = r.length; s > o; o++ ) {
        var f = r[ o ];
        this.addImage( f )
      }
    }
  }, s.prototype.addImage = function ( e ) {
    var t = new f( e );
    this.images.push( t )
  }, s.prototype.check = function () {
    function e( e, r ) {
      return t.options.debug && h && u.log( "confirm", e, r ), t.progress( e ), n++, n === i && t.complete(), !0
    }

    var t = this, n = 0, i = this.images.length;
    if ( this.hasAnyBroken = !1, !i ) return this.complete(), void 0;
    for ( var r = 0; i > r; r++ ) {
      var o = this.images[ r ];
      o.on( "confirm", e ), o.check()
    }
  }, s.prototype.progress = function ( e ) {
    this.hasAnyBroken = this.hasAnyBroken || !e.isLoaded;
    var t             = this;
    setTimeout( function () {
      t.emit( "progress", t, e ), t.jqDeferred && t.jqDeferred.notify && t.jqDeferred.notify( t, e )
    } )
  }, s.prototype.complete = function () {
    var e           = this.hasAnyBroken ? "fail" : "done";
    this.isComplete = !0;
    var t           = this;
    setTimeout( function () {
      if ( t.emit( e, t ), t.emit( "always", t ), t.jqDeferred ) {
        var n = t.hasAnyBroken ? "reject" : "resolve";
        t.jqDeferred[ n ]( t )
      }
    } )
  }, a && (a.fn.imagesLoaded = function ( e, t ) {
    var n = new s( this, e, t );
    return n.jqDeferred.promise( a( this ) )
  }), f.prototype = new t, f.prototype.check = function () {
    var e = v[ this.img.src ] || new c( this.img.src );
    if ( e.isConfirmed ) return this.confirm( e.isLoaded, "cached was confirmed" ), void 0;
    if ( this.img.complete && void 0 !== this.img.naturalWidth ) return this.confirm( 0 !== this.img.naturalWidth, "naturalWidth" ), void 0;
    var t = this;
    e.on( "confirm", function ( e, n ) {
      return t.confirm( e.isLoaded, n ), !0
    } ), e.check()
  }, f.prototype.confirm = function ( e, t ) {
    this.isLoaded = e, this.emit( "confirm", this, t )
  };
  var v = {};
  return c.prototype = new t, c.prototype.check = function () {
    if ( !this.isChecked ) {
      var e = new Image;
      n.bind( e, "load", this ), n.bind( e, "error", this ), e.src = this.src, this.isChecked = !0
    }
  }, c.prototype.handleEvent = function ( e ) {
    var t = "on" + e.type;
    this[ t ] && this[ t ]( e )
  }, c.prototype.onload = function ( e ) {
    this.confirm( !0, "onload" ), this.unbindProxyEvents( e )
  }, c.prototype.onerror = function ( e ) {
    this.confirm( !1, "onerror" ), this.unbindProxyEvents( e )
  }, c.prototype.confirm = function ( e, t ) {
    this.isConfirmed = !0, this.isLoaded = e, this.emit( "confirm", this, t )
  }, c.prototype.unbindProxyEvents = function ( e ) {
    n.unbind( e.target, "load", this ), n.unbind( e.target, "error", this )
  }, s
} );

/*!
 * hoverIntent v1.8.0 // 2014.06.29 // jQuery v1.9.1+
 * http://cherne.net/brian/resources/jquery.hoverIntent.html
 *
 * You may use hoverIntent under the terms of the MIT license. Basically that
 * means you are free to use hoverIntent as long as this header is left intact.
 * Copyright 2007, 2014 Brian Cherne
 */
(function ( $ ) {
  $.fn.hoverIntent = function ( handlerIn, handlerOut, selector ) {
    var cfg = { interval: 100, sensitivity: 6, timeout: 0 };
    if ( typeof handlerIn === "object" ) {
      cfg = $.extend( cfg, handlerIn )
    } else {
      if ( $.isFunction( handlerOut ) ) {
        cfg = $.extend( cfg, { over: handlerIn, out: handlerOut, selector: selector } )
      } else {
        cfg = $.extend( cfg, { over: handlerIn, out: handlerIn, selector: handlerOut } )
      }
    }
    var cX, cY, pX, pY;
    var track       = function ( ev ) {
      cX = ev.pageX;
      cY = ev.pageY
    };
    var compare     = function ( ev, ob ) {
      ob.hoverIntent_t = clearTimeout( ob.hoverIntent_t );
      if ( Math.sqrt( (pX - cX) * (pX - cX) + (pY - cY) * (pY - cY) ) < cfg.sensitivity ) {
        $( ob ).off( "mousemove.hoverIntent", track );
        ob.hoverIntent_s = true;
        return cfg.over.apply( ob, [ev] )
      } else {
        pX               = cX;
        pY               = cY;
        ob.hoverIntent_t = setTimeout( function () {
          compare( ev, ob )
        }, cfg.interval )
      }
    };
    var delay       = function ( ev, ob ) {
      ob.hoverIntent_t = clearTimeout( ob.hoverIntent_t );
      ob.hoverIntent_s = false;
      return cfg.out.apply( ob, [ev] )
    };
    var handleHover = function ( e ) {
      var ev = $.extend( {}, e );
      var ob = this;
      if ( ob.hoverIntent_t ) {
        ob.hoverIntent_t = clearTimeout( ob.hoverIntent_t )
      }
      if ( e.type === "mouseenter" ) {
        pX = ev.pageX;
        pY = ev.pageY;
        $( ob ).on( "mousemove.hoverIntent", track );
        if ( !ob.hoverIntent_s ) {
          ob.hoverIntent_t = setTimeout( function () {
            compare( ev, ob )
          }, cfg.interval )
        }
      } else {
        $( ob ).off( "mousemove.hoverIntent", track );
        if ( ob.hoverIntent_s ) {
          ob.hoverIntent_t = setTimeout( function () {
            delay( ev, ob )
          }, cfg.timeout )
        }
      }
    };
    return this.on( { "mouseenter.hoverIntent": handleHover, "mouseleave.hoverIntent": handleHover }, cfg.selector )
  }
})( jQuery );