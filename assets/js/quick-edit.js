window.wp = window.wp || {};


(function($){
	var ev,
	    post_id,
	    post_feed_url;

	$(document).ready( function() {
		$( '.wp-list-table' ).on( 'click', function() {
		} );
		console.log('PF Quick Edit modifications loaded.');
		$( '#the-list' ).on( 'click', 'a.editinline', function() {
			ev = this;
			//console.log(ev);
			// Gag. We set a timeout to ensure that core JS has moved the quick
			// edit box before accessing pf-quick-edit-feed-url.
			setTimeout( function( ) {
				post_id = inlineEditPost.getId( ev );
				post_feed_url = $( '#pf-feed-' + post_id + '-url' ).val();
				var set_post_status = $( '#inline_' + post_id + ' ._status').text();
				$( '#pf-quick-edit-feed-url' ).val( post_feed_url );
				jQuery('body.post-type-pf_feed').find('[name="_status"] option').text(
					function (index, post_status){
					  if ('Published' == post_status) {
					    return 'Active';
					  }
					  if ('Draft' == post_status) {
					    return 'Inactive';
					  }
					}
				);
				if ( 'under_review' == set_post_status ){
					jQuery('body.post-type-pf_feed').find('[name="_status"]').val('pending');
				}
				//console.log(pulldownStatus);


			}, 100 );
		} );
	} );
})(jQuery);
