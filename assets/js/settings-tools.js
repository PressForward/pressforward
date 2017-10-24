console.log( 'settings-tools.js loaded.' );
jQuery( window ).load(function() {
	console.log( 'settings-tools loaded.' );
	jQuery( '.pressforward #pf-settings-tabs' ).on('click', '.nav-tab', function(evt){
		console.log( 'nav-tab click.' );
		evt.preventDefault();
		// console.log(this);
		var tab = this;
		var target = jQuery( tab ).attr( 'data-tab-target' );
		console.log( target );
		jQuery( '.pftab' ).removeClass( 'active' );
		jQuery( target ).addClass( 'active' );

		jQuery( '.nav-tab' ).removeClass( 'nav-tab-active' );
		jQuery( tab ).addClass( 'nav-tab-active' );
		window.location.hash = '#top'+target;
		return false;
	});


	if (window.location.hash.indexOf("#") < 0){
		window.location.hash = '#ready#top';
		var theHash = jQuery(jQuery('.nav-tab')[0]).attr('data-tab-target');
		console.log(theHash);
		window.location.hash = '#ready#top'+theHash;
		var tab = theHash+'-tab';
		jQuery( '.pftab' ).removeClass( 'active' );
		jQuery( theHash ).addClass( 'active' );
		jQuery( '.nav-tab' ).removeClass( 'nav-tab-active' );
		jQuery( tab ).addClass( 'nav-tab-active' );
	} else {
		var theHash = window.location.hash;
		theHash = theHash.replace(/#top/, '');
		theHash = theHash.replace(/#/g, '');
		theHash = '#'+theHash;
		var tab = theHash+'-tab';
		jQuery( '.pftab' ).removeClass( 'active' );
		jQuery( theHash ).addClass( 'active' );
		jQuery( '.nav-tab' ).removeClass( 'nav-tab-active' );
		jQuery( tab ).addClass( 'nav-tab-active' );
	}

	jQuery(window).on('hashchange', function() {

	});
});
