jQuery(window).load(function() {
	jQuery('.pressforward #pf-settings-tabs').on('click', '.nav-tab', function(evt){
		evt.preventDefault;
		//console.log(this);
		var tab = this;
		var target = jQuery(tab).attr('data-tab-target');
		//console.log(target);
		jQuery('.pftab').removeClass('active');
		jQuery(target).addClass('active');

		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(tab).addClass('nav-tab-active');
	});
});