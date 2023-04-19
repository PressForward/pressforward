import { __ } from '@wordpress/i18n'

window.wp = window.wp || {};

(function($){
	var post_id,
	    post_feed_url;

	$( document ).ready( function() {
		$( '#the-list' ).on( 'click', '.editinline', function( ev ) {
			// Gag. We set a timeout to ensure that core JS has moved the quick
			// edit box before accessing pf-quick-edit-feed-url.
			setTimeout( function( ) {
				post_id = inlineEditPost.getId( ev.target );
				post_feed_url = $( '#pf-feed-' + post_id + '-url' ).val();
				var set_post_status = $( '#inline_' + post_id + ' ._status' ).text();
				$( '#pf-quick-edit-feed-url' ).val( post_feed_url );

				$( 'body.post-type-pf_feed [name="_status"] option' ).each(
					(index, theOption) => {
						if ( 'publish' === theOption.value ) {
							$( theOption ).text( __( 'Active', 'pressforward' ) )
						} else if ( 'draft' === theOption.value ) {
							$( theOption ).text( __( 'Inactive', 'pressforward' ) )
						}
					}
				);

				if ( 'under_review' == set_post_status ) {
					jQuery( 'body.post-type-pf_feed' ).find( '[name="_status"]' ).val( 'pending' );
				}
			}, 100 );
		} );
	} );
})(jQuery);
