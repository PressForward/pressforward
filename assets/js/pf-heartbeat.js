
(function($){
  console.log('hb_test');
//  'feed_id'	=>	$aFeed->ID,
//  'feed_title'	=> $aFeed->post_title,
//  'last_key'	=> $last_key,
//  'feeds_iteration'	=>	$feeds_iteration,
//  'total_feeds'	=>	count($feedlist)
    // Hook into the heartbeat-send
    $(document).on('heartbeat-send', function(e, data) {
        console.log('hb_send');
        data['pf_heartbeat_request'] = 'feed_state';
    });

    // Listen for the custom event "heartbeat-tick" on $(document).
    $(document).on( 'heartbeat-tick', function(e, data) {
        console.log('hb_tick');
        console.log(data);
        // Only proceed if our EDD data is present
        if ( ! data['pf_total_feeds'] )
            return;

        if ( data['pf_last_key'] != data['pf_feeds_iteration'] ){
          //jQuery('#status_check').css('display','block');
          console.log('hb_not_done');
          jQuery("#retrieving_feeds").html(
            function(){
              console.log('hb_write');
              var iterate = (parseInt(data['pf_feeds_iteration'],10))+1;
              return 'Retrieving feeds. Currently at <span id="rf-feed-title">'+data['pf_feed_title']+'</span> feed number <span id="rf-iteration">'+iterate+'</span> of <span id="rf-total-feeds">'+ data['pf_total_feeds']+'</span>.';
            }
          );
          //jQuery('#status_check').css('display', 'block');
        } else {

        }
        // Log the response for easy proof it works
        console.log( data['pf_feed_id'] );
        console.log( data['pf_feed_title'] );
        console.log( data['pf_last_key'] );
        console.log( data['pf_feeds_iteration'] );
        console.log( data['pf_total_feeds'] );

        //var pf_status_string =

        // Update sale count and bold it to provide a highlight
        //$('.edd_dashboard_widget .b.b-sales').text( data['edd-payment-count'] ).css( 'font-weight', 'bold' );

        // Return font-weight to normal after 2 seconds
        //setTimeout(function(){
        //    $('.edd_dashboard_widget .b.b-sales').css( 'font-weight', 'normal' );;
        //}, 2000);
        var percentComplete = (( ((parseInt(data['pf_feeds_iteration'],10))+1) / (parseInt(data['pf_total_feeds'],10)) ))*100;
        if ( 1 > percentComplete ){
          percentComplete = 1;
        }
        if ( 100 == percentComplete ){
          //jQuery('#status_check').css('display', 'none');
        }
        console.log(percentComplete);
        jQuery( "#rf-progressbar" ).progressbar({
           value: percentComplete
        });
    });
}(jQuery));

jQuery(window).load(function() {
  var percentComplete = 0;
  percentComplete = ((parseInt(jQuery("#rf-iteration").text(),10)) / (parseInt(jQuery("#rf-total-feeds").text(),10)))*100;
  console.log(percentComplete);
  if ( 1 > percentComplete ){
    percentComplete = 1;
  }
  if ( 100 == percentComplete ){
    jQuery( "#rf-progressbar" ).progressbar({
       value: percentComplete
    });
  } else {
    jQuery( "#rf-progressbar" ).progressbar({
       value: percentComplete
    });
  }
});
