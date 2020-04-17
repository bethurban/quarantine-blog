jQuery( function ( $ ) {

  /**
   * Tab/accordion
   */

  bindTabAccordion();

  function bindTabAccordion() {
    $( '.tabaccordion div.content' ).hide();
    $( '.tabaccordion a.tab, .tabaccordion a.accordion' ).click( function () {
      tabAccordion( $( this ) );
    } );
    $( '.tabaccordion a.tab:first' ).trigger( "click" );

    $( '.type_accordion' ).prepend( '<div class="show_all"><a href="javascript:void(0);" class="show_all_accordion">Show All</a></div>' );
    $( '.type_accordion a.accordion, .type_accordion div.content' ).removeClass( 'active' );
    $( '.show_all_accordion' ).click( function () {
      tabAccordianShowAll( $( this ) );
    } );

    if ( $( ".tabaccordioncategory" ).length > 0 ) {
      $( ".tabaccordioncategory" ).bind( "change", function () {
        var tc = $( this ).val();
        tc     = (tc) ? "." + tc : tc;
        if ( tc ) {
          $( ".type_accordion > div" ).slideUp( "fast" );
        }
        $( ".type_accordion > div" + tc ).slideDown();
      } );
    }
  }

  function tabAccordion( el ) {
    var tab    = ($( el ).hasClass( "tab" ));
    var parent = $( el ).closest( ".tabaccordion" );
    $( parent ).find( "a.tab" ).removeClass( "active" );
    $( parent ).find( "a.accordion" ).removeClass( "active" );
    $( el ).addClass( "active" );
    var id      = $( el ).attr( "data-tab" );
    var content = "content_" + id;
    $( parent ).find( "div.content" ).each(
      function () {
        if ( $( this ).hasClass( content ) && (tab || !$( this ).is( ':visible' )) ) {
          $( this ).slideDown( 500, "easeOutBack" );
        } else {
          $( this ).slideUp( 300, "linear" );
        }
      }
    );
  }

  function tabAccordianShowAll( el ) {
    var cur_title = $( el ).html();
    var content   = $( el ).closest( ".tabaccordion" ).find( ".accordion .content" );
    var titles    = $( el ).closest( '.tabaccordion' ).find( 'a.accordion' );
    if ( cur_title.indexOf( 'Show' ) >= 0 ) {
      titles.addClass( 'active' );
      content.slideDown();
      $( el ).html( 'Hide All' );
    } else {
      titles.removeClass( 'active' );
      content.slideUp( "fast" );
      $( el ).html( 'Show All' );
    }
  }
} );