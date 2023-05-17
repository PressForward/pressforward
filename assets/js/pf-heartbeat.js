(function($){
	// 'feed_id'   =>  $aFeed->ID,
	// 'feed_title'    => $aFeed->post_title,
	// 'last_key'  => $last_key,
	// 'feeds_iteration'   =>  $feeds_iteration,
	// 'total_feeds'   =>  count($feedlist)
	// Hook into the heartbeat-send
	$( document ).on('heartbeat-send', function(e, data) {
		data['pf_heartbeat_request'] = 'feed_state';
	});

	// Listen for the custom event "heartbeat-tick" on $(document).
	$( document ).on( 'heartbeat-tick', function(e, data) {
		// Only proceed if our EDD data is present
		if ( ! data['pf_total_feeds'] ) {
			return; }

		if ( data['pf_last_key'] != data['pf_feeds_iteration'] ) {
			// jQuery('#status_check').css('display','block');
			jQuery( "#retrieving_feeds" ).html( data.pf_status_message );
		}

		// var pf_status_string =
		// Update sale count and bold it to provide a highlight
		// $('.edd_dashboard_widget .b.b-sales').text( data['edd-payment-count'] ).css( 'font-weight', 'bold' );
		// Return font-weight to normal after 2 seconds
		// setTimeout(function(){
		// $('.edd_dashboard_widget .b.b-sales').css( 'font-weight', 'normal' );;
		// }, 2000);
		var percentComplete = (( ((parseInt( data['pf_feeds_iteration'],10 )) + 1) / (parseInt( data['pf_total_feeds'],10 )) )) * 100;
		if ( 1 > percentComplete ) {
			percentComplete = 1;
		}
		if ( 100 == percentComplete ) {
			// jQuery('#status_check').css('display', 'none');
		}
		jQuery( "#rf-progressbar" ).progressbar({
			value: percentComplete
		});
	});
}(jQuery));

jQuery( window ).on('load', function() {
	var percentComplete = 0;
	percentComplete = ((parseInt( jQuery( "#rf-iteration" ).text(),10 )) / (parseInt( jQuery( "#rf-total-feeds" ).text(),10 ))) * 100;
	if ( 1 > percentComplete ) {
		percentComplete = 1;
	}
	if ( 100 == percentComplete ) {
		jQuery( "#rf-progressbar" ).progressbar({
			value: percentComplete
		});
	} else {
		jQuery( "#rf-progressbar" ).progressbar({
			value: percentComplete
		});
	}
});
