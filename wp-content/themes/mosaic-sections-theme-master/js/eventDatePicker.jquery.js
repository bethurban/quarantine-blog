(function ( $ ) {
  var $container = $( '#mosaic-event-run-time' );
  $( '.time', $container ).timepicker( {
    'showDuration': true,
    'step': 15,
    'scrollDefault': 'now',
    'timeFormat': 'g:ia'
  } );

  $( '.date', $container ).datepicker( {
    'format': 'mm/dd/yyyy',
    'autoclose': true
  } );

  $container.datepair( {} );
})( jQuery );