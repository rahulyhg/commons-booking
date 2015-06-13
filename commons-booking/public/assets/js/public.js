(function($) {
  "use strict";

  $(function() {

    /* ========================================================================
     * DOM-based Routing
     * Based on http://goo.gl/EUTi53 by Paul Irish
     *
     * Only fires on body classes that match. If a body class contains a dash,
     * replace the dash with an underscore when adding it to the object below.
     *
     * .noConflict()
     * The routing is enclosed within an anonymous function so that you can
     * always reference jQuery with $, even when in .noConflict() mode.
     * ======================================================================== */

    // Use this variable to set up the common and page specific functions. If you
    // rename this variable, you will also need to rename the namespace below.
    var Commons_Booking = {

      // All pages
      common: {
        init: function() {
          // JavaScript to be fired on all pages
        }
      },
      // Script for booking, fired on single cb_items
      single_cb_items: {

        init: function() {

          var selectedIndexes = [];
          var selectedDates = [];
          var startContainer = $( '#date-start' );
          var endContainer = $( '#date-end' );
          var maxDays = 3; 
          var high;
          var low;
          var sortArray = [];
          var arrayToSort;
          var minMax = 0;


          $('.tooltip').tooltipster({
            animation: 'grow',
            delay: 0,
            theme: 'tooltipster-default',
            touchDevices: false,
          });


        $( ".cb-calendar li" ).on( "click", function( index ) {
          update ( $( this ) );


        });
        function update( obj ) {

          // console.log ("indexeslength:" + selectedIndexes.length);

          var index = obj.index();
          var clickedIndexes = [];


          var needle = $.inArray( index, selectedIndexes ); // look for index in array. 
          var clickedIndexes = selectedIndexes.concat();


          // console.log (needle);

          console.log ("before: " + selectedIndexes);

          // De-Selection
          if ( needle > -1 )  { // already selected, so de-select
            clickedIndexes.splice( needle, 1 );              
          } else {        
            if (selectedIndexes.length > 1 ) { // 2 selected, so exchange first item with it
              clickedIndexes[0] = index;   
            } else {
               clickedIndexes.push ( index );          
            }
          }

          // Valdiation 
          var distance = 0;
          if ( clickedIndexes.length > 1 ) {
             var distance = clickedIndexes.reduce(function(a, b) {
                return Math.abs( a - b );
              });
          }

          if ( ( distance < 3 ) ) {  
            console.log ("smaller");
            selectedIndexes = clickedIndexes;  

            } else {
              console.log ("higher");
          }
          setSelected( selectedIndexes );

          console.log (  "after: " + selectedIndexes);

          }

          // console.log (selectedIndexes);

          // $( "li.bookable" ).get( selectedIndexes[0] ).addClass ( "selected" );

      
        function setSelected( selected ) {
          // console.log ("indexes:" + i);

          $( ".cb-calendar li" ).each(function( myindex ) {

            if ( $.inArray( myindex, selected )  > -1 )  {
              $( this ).addClass(' selected ');
            } else {
              $( this ).removeClass(' selected ');
          }
          });        
        }

        function displayMsg ( msg, class ) {

        }


        }
      }
    };





    // The routing fires all common scripts, followed by the page specific scripts.
    // Add additional events for more control over timing e.g. a finalize event
    var UTIL = {
      fire: function(func, funcname, args) {
        var namespace = Commons_Booking;
        funcname = (funcname === undefined) ? 'init' : funcname;
        if (func !== '' && namespace[func] && typeof namespace[func][funcname] === 'function') {
          namespace[func][funcname](args);
        }
      },
      loadEvents: function() {
        UTIL.fire('common');

        $.each(document.body.className.replace(/-/g, '_').split(/\s+/), function(i, classnm) {
          UTIL.fire(classnm);
        });
      }
    };

    $(document).ready(UTIL.loadEvents);

    // Write in console log the PHP value passed in enqueue_js_vars in public/class-commons-booking.php
    console.log( pn_js_vars.alert );
    
    // Place your public-facing JavaScript here

  });

}(jQuery));