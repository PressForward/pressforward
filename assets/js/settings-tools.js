jQuery(window).load(function () {
	var firstTab = jQuery('.pressforward #pf-settings-tabs .nav-tab').first();
	var firstTarget = firstTab.attr('data-tab-target');

	function tabToTarget(target, tab) {
		jQuery('.pftab').removeClass('active');
		jQuery(target).addClass('active');

		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(tab).addClass('nav-tab-active');
		window.location.hash = '#top' + target;
	}

	tabToTarget(firstTarget, firstTab);

	jQuery('.pressforward #pf-settings-tabs').on('click', '.nav-tab', function (evt) {
		evt.preventDefault();
		var tab = this;
		var target = jQuery(tab).attr('data-tab-target');
		tabToTarget(target, tab)
		return false;
	});


	if (window.location.hash.indexOf("#") < 0) {
		window.location.hash = '#ready#top';
		var theHash = jQuery(jQuery('.nav-tab')[0]).attr('data-tab-target');
		window.location.hash = '#ready#top' + theHash;
		var tab = theHash + '-tab';
		jQuery('.pftab').removeClass('active');
		jQuery(theHash).addClass('active');
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(tab).addClass('nav-tab-active');
	} else {
		var theHash = window.location.hash;
		theHash = theHash.replace(/#top/, '');
		theHash = theHash.replace(/#/g, '');
		theHash = '#' + theHash;
		var tab = theHash + '-tab';
		jQuery('.pftab').removeClass('active');
		jQuery(theHash).addClass('active');
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(tab).addClass('nav-tab-active');
	}

});
