// On document ready, initialize MosaicImageUpload class
jQuery( function ( $ ) {
  MosaicImageUpload.init();
} );

/**
 * COLOR CHOOSER FUNCTIONALITY
 */
var MosaicColorChooser = ( function () {
  var $;
  var callback;

  function bindEvents() {
    /**
     * Event handler for color chooser dropdowns.
     */
    $( document ).on( 'click', '.mosaic-color-chooser', function ( event ) {
      handleColorSelection( $( this ), event );
    } );
  }

  /**
   * Event handler for choosing colors for a section.
   *
   * @param $el
   * @param event
   */
  function handleColorSelection( $el, event ) {
    var $target = $( event.target );
    var type    = $target.hasClass( 'mosaic-color-option' ) ? 'select' : 'toggle';

    if ( 'toggle' == type ) {
      $el.toggleClass( 'chooser-open' );
      return;
    }

    var value = $target.data( 'option' );
    var bg    = $target.data( 'bg' );
    var color = $target.data( 'color' );
    var name  = $target.text();
    $el.find( 'input' ).val( value );
    $el.find( '.mosaic-color-selected' ).css( { background: bg, color: color } ).text( name );
    $el.toggleClass( 'chooser-open' );

    callback();
  }

  return {
    init: function ( cb ) {
      $ = jQuery;

      callback = cb;

      bindEvents();
    }
  }
} )();

/**
 * Image / media upload class
 */
var MosaicImageUpload = ( function () {
  var $;
  var $container;
  var $element;
  var customMedia = true;
  var buttonUpdate;
  var sendAttachment;

  function doUpload( $el ) {
    $element                  = $el;
    customMedia               = true;
    var _orig_send_attachment = wp.media.editor.send.attachment;
    var _orig_editor_insert   = wp.media.editor.insert;
    var _orig_string_image    = wp.media.string.image;

    // This function is required to return a "clean" URL for the "Insert from URL"
    wp.media.string.image = function ( embed ) {
      if ( customMedia ) {
        sendAttachment = false;
        return embed.url;
      }

      return _orig_string_image.apply( embed );
    };

    // This function handles passing the URL in for the "Insert from URL"
    wp.media.editor.insert = function ( html ) {
      if ( customMedia ) {
        if ( sendAttachment ) {
          return;
        }

        renderImage( html );
        return;
      }

      return _orig_editor_insert.apply( html );
    };

    // This function handles passing in the image url from an uploaded image
    wp.media.editor.send.attachment = function ( props, attachment ) {
      sendAttachment = true;
      if ( customMedia ) {
        getSizedImage( attachment.id, props.size, attachment.url );
        //renderImage( attachment.url );
        //console.log( props, attachment );
      } else {
        return _orig_send_attachment.apply( this, [ props, attachment ] );
      }
      clearInterval( buttonUpdate );
    };

    wp.media.editor.open( 1 );

    buttonUpdate = setInterval( function () {
        $( 'div.media-modal .media-button-insert' ).html( 'Choose Image' );
      }
      , 300 );
    return false;
  }

  function getSizedImage( attachment_id, size, url ) {
    if ( 'full' == size ) {
      renderImage( url );
      return;
    }

    $.ajax(
      ajaxurl,
      {
        method: 'POST',
        data: {
          action: 'mosaic-media-upload',
          attachment_id: attachment_id,
          size: size
        },
        success: function ( url ) {
          renderImage( url );
        }
      }
    );
  }

  function renderImage( src ) {
    if ( $container.find( "img" ).length <= 0 ) {
      $container.find( '.image-wrapper' ).prepend( '<img src="" />' );
    }

    if ( $container.find( "a.delete" ).length <= 0 ) {
      $container.prepend( '<a class="delete" href="javascript:void(0);"><span class="dashicons dashicons-no"></span></a>' );
    }

    $container.find( "img" ).attr( "src", src );
    $container.find( "input" ).val( src );

    $( document ).trigger( 'upload:render' );
  }

  /**
   * Handle Video Upload action
   *
   * @param type {string}
   */
  function doVideoUpload( type ) {
    if ( !type ) {
      return;
    }

    var inTypes    = false;
    var video_type = [
      'mp4', 'webm', 'ogg'
    ];

    $.each( video_type, function ( i, v ) {
      if ( type === v ) {
        inTypes = true;
      }
    } );

    if ( !inTypes ) {
      return;
    }

    var options = {
      frame: 'post',
      multiple: false,
      button: {
        text: 'Choose Video'
      },
      library: {
        type: 'video'
      }
    };

    wp.media.editor.send.attachment = function ( props, attachment ) {
      console.log( attachment );

      validateAttachment( attachment, type );
    };

    wp.media.editor.open( 3, options );
  }

  /**
   * Validate video attachment
   *
   * @param attachment {object}
   * @param type {string}
   */
  function validateAttachment( attachment, type ) {
    if ( !attachment ) {
      return;
    }

    var ext      = attachment.subtype;
    var url      = attachment.url;
    var fileType = attachment.type;

    if ( 'video' !== fileType ) {
      alert( 'You can only attach a video' );
      return;
    }

    if ( type !== ext ) {
      alert( 'File must be of type ' + type );
      return;
    }

    attachVideoUrl( url );
  }

  /**
   * Attach Video Url
   *
   * @param src
   */
  function attachVideoUrl( src ) {
    $container.val( src );
  }

  /**
   * Clear Video Url
   */
  function removeVideoUrl() {
    $container.val( '' );
  }

  function removeMedia( $el ) {
    $el.closest( '.image-container' ).find( 'input' ).val( '' );
    $el.closest( '.image-container' ).find( 'img, a.delete' ).fadeOut( 500, function () {
      $( this ).remove();
    } );
  }

  return {
    init: function () {
      $ = jQuery;
      // Media upload functionality. Use live method, because add / edit can be created dynamically
      $( document ).on( 'click', '.image-container', function ( e ) {
        e.preventDefault();
        // Set the container element to ensure actions take place within container
        $container = $( this );
        // Set the type.  media or image
        doUpload();
        return false;
      } );

      $( document ).on( 'click', '.image-container .delete', function ( e ) {
        e.stopPropagation();
        removeMedia( $( this ) );
      } );
      $( document ).on( 'click', '.attach-video', function ( e ) {
        e.preventDefault();
        $container = $( this ).siblings( '.video' );
        var type   = $( this ).data( 'type' );
        doVideoUpload( type );
      } );
      $( document ).on( 'click', '.clear-video', function () {
        $container = $( this ).siblings( '.video' );
        removeVideoUrl();
      } );
    }
  }
} )();
