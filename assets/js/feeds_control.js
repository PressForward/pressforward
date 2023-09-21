jQuery( window ).load(function() {
	jQuery( '.pressforward_page_pf-feeder .rss-box' ).on('click', '.button-primary', function(evt){

		jQuery( ".pressforward_page_pf-feeder .rss-box .button-primary:last" ).hide();
		jQuery( ".pressforward_page_pf-feeder .rss-box" ).append( '<div class="pf-loader" style="margin-top:-50px;"></div>' );

	});
});
