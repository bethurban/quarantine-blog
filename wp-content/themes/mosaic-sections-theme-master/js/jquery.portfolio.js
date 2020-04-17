jQuery( function ( $ ) {

  if ( typeof galleryFormat !== 'undefined' && galleryFormat == 'slideshow' ) {

    if ( jQuery( ".gallery" ).length > 0 ) {
      $( '.gallery .slides li' ).css( 'display', 'none' );
      $( '.gallery .slides li img:first' ).load(
        function () {
          $( this ).closest( 'li' ).fadeIn();
        }
      );
    }

    jQuery( window ).load( function () {
      if ( jQuery( ".gallery" ).length > 0 ) {
        bindGallery();
      }
    } );

    function bindGallery() {
      // $(".slide_controls li.slide-page img").css({height: '125px', width: 'auto'});
      $( 'ul.slide_controls a' ).each( function ( i ) {
        $( this ).attr( 'data-slide-index', (i - 1) );
      } );

      // Initiate carousel
      $( 'ul.slide_controls' ).bxSlider( {
        slideWidth: 148,
        slideMargin: 10,
        maxSlides: 6,
        minSlides: 6,
        moveSlides: 1,
        pager: false,
        auto: false,
        autoStart: false,
        onSliderLoad: function () {
          //jQuery('div.gallery ul.slides').css('display', 'none');
          bindGalleryToThumbs();
        }
      } );

      return;

      // Resize gallery
      $( window ).resize(
        function () {
          resizeGallery();
        }
      );

      resizeGallery();
      setInterval( function () {
        resizeGallery();
      }, 500 );
    }

    function bindGalleryToThumbs() {
      jQuery( 'div.gallery' ).fadeGalleryPortfolio( {
        slideElements: 'ul.slides > li',
        pagerLinks: '.slide_controls a',
        switchTime: 4000,
        duration: 750
      } );

      jQuery( 'div.gallery ul.slides li a' ).contents().unwrap();

      var portfolionav = jQuery( 'div.gallery a.prev, div.gallery a.next' );
      portfolionav.fadeOut( 'fast' );
      jQuery( 'div.gallery' ).hover(
        function () {
          portfolionav.fadeIn( 'fast' );
        },
        function () {
          portfolionav.fadeOut( 'slow' );
        }
      );
    }

    function resizeGallery() {
      return;
      var width       = parseInt( $( "div.gallery" ).width() );
      var thumbswidth = 0; // parseInt($(".gallery .bx-wrapper").outerWidth(true));
      width           = width - thumbswidth - 10;
      $( ".slides li img" ).css( { "width": width + "px", "margin-right": thumbswidth + "px" } );
      var height = $( ".slides li img:visible" ).height();
      $( ".slides, .slides li" ).css( "height", height + "px" );
      // 6$(".gallery .bx-viewport").css("height", (height - 30) + "px");
    }

    var slideshownav = jQuery( '#slideshow a.prev, #slideshow a.next' );
    slideshownav.fadeOut( 'fast' );
    jQuery( '#slideshow' ).hover(
      function () {
        slideshownav.fadeIn( 'fast' );
      },
      function () {
        slideshownav.fadeOut( 'slow' );
      }
    );
  }
} );

// Customized fadeGallery for the Portfolio slideshow
jQuery.fn.fadeGalleryPortfolio = function ( _options ) {
  var _options = jQuery.extend( {
    slideElements: 'div.slideset > div',
    pagerLinks: '.slide_controls a',
    btnNext: 'a.next',
    btnPrev: 'a.prev',
    btnPlayPause: 'a.play-pause',
    btnPlay: 'a.play',
    btnPause: 'a.pause',
    pausedClass: 'paused',
    disabledClass: 'disabled',
    playClass: 'playing',
    activeClass: 'active',
    currentNum: false,
    allNum: false,
    startSlide: null,
    noCircle: false,
    pauseOnHover: true,
    autoRotation: true,
    autoHeight: false,
    onChange: false,
    switchTime: 4000,
    duration: 2000,
    event: 'click',
    callback: ''
  }, _options );

  return this.each( function () {
    // gallery options
    var _this          = jQuery( this );
    var _slides        = jQuery( _options.slideElements, _this );
    var _pagerLinks    = jQuery( _options.pagerLinks, _this );
    var _btnPrev       = jQuery( _options.btnPrev, _this );
    var _btnNext       = jQuery( _options.btnNext, _this );
    var _btnPlayPause  = jQuery( _options.btnPlayPause, _this );
    var _btnPause      = jQuery( _options.btnPause, _this );
    var _btnPlay       = jQuery( _options.btnPlay, _this );
    var _pauseOnHover  = _options.pauseOnHover;
    var _autoRotation  = _options.autoRotation;
    var _activeClass   = _options.activeClass;
    var _disabledClass = _options.disabledClass;
    var _pausedClass   = _options.pausedClass;
    var _playClass     = _options.playClass;
    var _autoHeight    = _options.autoHeight;
    var _duration      = _options.duration;
    var _switchTime    = _options.switchTime;
    var _controlEvent  = _options.event;
    var _currentNum    = (_options.currentNum ? jQuery( _options.currentNum, _this ) : false);
    var _allNum        = (_options.allNum ? jQuery( _options.allNum, _this ) : false);
    var _startSlide    = _options.startSlide;
    var _noCycle       = _options.noCircle;
    var _onChange      = _options.onChange;
    var callback       = _options.callback;

    // gallery init
    var _hover        = false;
    var _prevIndex    = 0;
    var _currentIndex = 0;
    var _slideCount   = _slides.length;
    var _timer;
    if ( _slideCount < 2 ) return;

    _prevIndex = _slides.index( _slides.filter( '.' + _activeClass ) );
    if ( _prevIndex < 0 ) _prevIndex = _currentIndex = 0;
    else _currentIndex = _prevIndex;
    if ( _startSlide != null ) {
      if ( _startSlide == 'random' ) _prevIndex = _currentIndex = Math.floor( Math.random() * _slideCount );
      else _prevIndex = _currentIndex = parseInt( _startSlide );
    }
    _slides.hide().eq( _currentIndex ).show();
    if ( _autoRotation ) _this.removeClass( _pausedClass ).addClass( _playClass );
    else _this.removeClass( _playClass ).addClass( _pausedClass );

    // gallery control
    if ( _btnPrev.length ) {
      _btnPrev.bind( _controlEvent, function () {
        prevSlide();
        return false;
      } );
    }
    if ( _btnNext.length ) {
      _btnNext.bind( _controlEvent, function () {
        nextSlide();
        return false;
      } );
    }
    if ( _pagerLinks.length ) {
      _pagerLinks.each( function ( _ind ) {
        jQuery( this ).live( _controlEvent, function () {
          _ind = jQuery( this ).attr( 'data-slide-index' );
          if ( _currentIndex != _ind ) {
            _prevIndex    = _currentIndex;
            _currentIndex = _ind;
            switchSlide();
          }
          return false;
        } );
      } );
    }

    // play pause section
    if ( _btnPlayPause.length ) {
      _btnPlayPause.bind( _controlEvent, function () {
        if ( _this.hasClass( _pausedClass ) ) {
          _this.removeClass( _pausedClass ).addClass( _playClass );
          _autoRotation = true;
          autoSlide();
        } else {
          _autoRotation = false;
          if ( _timer ) clearTimeout( _timer );
          _this.removeClass( _playClass ).addClass( _pausedClass );
        }
        return false;
      } );
    }
    if ( _btnPlay.length ) {
      _btnPlay.bind( _controlEvent, function () {
        _this.removeClass( _pausedClass ).addClass( _playClass );
        _autoRotation = true;
        autoSlide();
        return false;
      } );
    }
    if ( _btnPause.length ) {
      _btnPause.bind( _controlEvent, function () {
        _autoRotation = false;
        if ( _timer ) clearTimeout( _timer );
        _this.removeClass( _playClass ).addClass( _pausedClass );
        return false;
      } );
    }

    // gallery animation
    function prevSlide() {
      _prevIndex = _currentIndex;
      if ( _currentIndex > 0 ) _currentIndex--;
      else {
        if ( _noCycle ) return;
        else _currentIndex = _slideCount - 1;
      }
      switchSlide();
    }

    function nextSlide() {
      _prevIndex = _currentIndex;
      if ( _currentIndex < _slideCount - 1 ) _currentIndex++;
      else {
        if ( _noCycle ) return;
        else _currentIndex = 0;
      }
      switchSlide();
    }

    function refreshStatus() {
      if ( _pagerLinks.length ) _pagerLinks.removeClass( _activeClass ).eq( _currentIndex ).addClass( _activeClass );
      if ( _currentNum ) _currentNum.text( _currentIndex + 1 );
      if ( _allNum ) _allNum.text( _slideCount );
      _slides.eq( _prevIndex ).removeClass( _activeClass );
      _slides.eq( _currentIndex ).addClass( _activeClass );
      if ( _noCycle ) {
        if ( _btnPrev.length ) {
          if ( _currentIndex == 0 ) _btnPrev.addClass( _disabledClass );
          else _btnPrev.removeClass( _disabledClass );
        }
        if ( _btnNext.length ) {
          if ( _currentIndex == _slideCount - 1 ) _btnNext.addClass( _disabledClass );
          else _btnNext.removeClass( _disabledClass );
        }
      }
      if ( typeof _onChange === 'function' ) {
        _onChange( _this, _currentIndex );
      }
    }

    function switchSlide() {
      if ( callback ) {
        callback( _slides.eq( _prevIndex ), _slides.eq( _currentIndex ), _duration );
      }
      _slides.eq( _prevIndex ).fadeOut( _duration );
      _slides.eq( _currentIndex ).fadeIn( _duration );
      if ( _autoHeight ) _slides.eq( _currentIndex ).parent().animate( { height: _slides.eq( _currentIndex ).outerHeight( true ) }, {
        duration: _duration,
        queue: false
      } );
      refreshStatus();
      autoSlide();
    }

    // autoslide function
    function autoSlide() {
      if ( !_autoRotation || _hover ) return;
      if ( _timer ) clearTimeout( _timer );
      _timer = setTimeout( nextSlide, _switchTime + _duration );
    }

    if ( _pauseOnHover ) {
      _this.hover( function () {
        _hover = true;
        if ( _timer ) clearTimeout( _timer );
      }, function () {
        _hover = false;
        autoSlide();
      } );
    }
    refreshStatus();
    autoSlide();
  } );
}