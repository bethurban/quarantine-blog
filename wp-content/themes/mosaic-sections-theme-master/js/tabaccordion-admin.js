jQuery( document ).ready( function ( $ ) {
  if ( $( ".tabaccordion" ).length > 0 ) {
    $( ".tabaccordion #set" ).sortable( {
      stop: function () {
        updatetabaccordionOrder();
      },
      placeholder: 'ui-state-placeholder',
      forcePlaceholderSize: true,
      helper: 'clone'
    } );

    $( ".tabaccordion ul li" ).hover(
      function () {
        $( this ).addClass( "hover" );
      },
      function () {
        $( this ).removeClass( "hover" );
      }
    );
    $( "div.newcategory" ).css( "display", "none" );

    $( ".categorylist" ).bind( "change", function () {
      if ( $( this ).val() == "||NEW||" ) {
        $( ".newcategory" ).slideDown();
      } else {
        $( ".newcategory" ).slideUp();
      }
    } );

    $( ".tabaccordion .delete" ).click(
      function () {
        return confirm( "Are you sure you want to delete this item?" );
      }
    );
  }

  function updatetabaccordionOrder() {
    var str = "";
    $( ".tabaccordion #set li" ).each( function () {
      str += jQuery( this ).attr( "title" ) + ",";
    } );
    $( ".tabaccordionsort" ).val( str );
    if ( !$( "#sortsave" ).is( ":visible" ) ) {
      $( "#sortsave" ).fadeIn();
    }
  }

} );
