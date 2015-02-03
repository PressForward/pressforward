window.wp = window.wp || {};

(function($){
	var ev,
	    post_id,
	    post_feed_url;

	$(document).ready( function() {
		$( '.wp-list-table' ).on( 'click', function() {
		} );
		$( '#the-list' ).on( 'click', 'a.editinline', function() {
			ev = this;
			// Gag. We set a timeout to ensure that core JS has moved the quick
			// edit box before accessing pf-quick-edit-feed-url.
			setTimeout( function( ) {
				post_id = inlineEditPost.getId( ev );
				post_feed_url = $( '#pf-feed-' + post_id + '-url' ).val();
				$( '#pf-quick-edit-feed-url' ).val( post_feed_url );
			}, 100 );
		} );
	} );
})(jQuery);
