/* global jQuery */
jQuery(window).load(function () {

	var firstTab = jQuery('.pressforward #pf-settings-tabs .nav-tab').first();
	if ( firstTab.length === 0 ) {
		return;
	}

	// Ensure we're scrolled to the top on page load.
	const currentTab = window.location.hash;
	if ( currentTab ) {
		// Wait for the browser to scroll to the anchor.
		setTimeout(function() {
			jQuery(window).scrollTop(0);
		}, 0);
	}

	jQuery( '#pf-settings-box' ).show();

	function tabToTarget(target, tab) {
		jQuery('.pftab').removeClass('active');
		jQuery(target).addClass('active');

		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(tab).addClass('nav-tab-active');

		// Note current scroll location.
		var scrollLocation = jQuery(window).scrollTop();

		window.location.hash = target;

		// Reset scroll location.
		jQuery(window).scrollTop(scrollLocation);

		// Update the 'action' attribute of the form to reflect the current tab.
		const form = tab.closest( 'form' );
		form.action = form.action.replace(/#.*$/, '') + target;
	}

	jQuery('.pressforward #pf-settings-tabs').on('click', '.nav-tab', function (evt) {
		evt.preventDefault();
		var tab = this;
		var target = jQuery(tab).attr('data-tab-target');
		tabToTarget(target, tab)
		return false;
	});

	if (window.location.hash.indexOf("#") < 0) {
		const firstTabHash = firstTab.attr('data-tab-target');
		window.location.hash = firstTabHash;
		var tab = firstTabHash + '-tab';
		jQuery('.pftab').removeClass('active');
		jQuery(firstTabHash).addClass('active');
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(tab).addClass('nav-tab-active');
	} else {
		theHash = currentTab.replace(/#/g, '');
		theHash = '#' + theHash;
		var tab = theHash + '-tab';
		jQuery('.pftab').removeClass('active');
		jQuery(theHash).addClass('active');
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(tab).addClass('nav-tab-active');
	}
});
