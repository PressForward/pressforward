/**
 * Display transform for pf
**/
jQuery(document).ready(function() {

	jQuery('#gogrid').click(function (evt){ 
			evt.preventDefault();
			jQuery("div.pf_container").removeClass('list').addClass('grid');
		});

	jQuery('#golist').click(function (evt){ 
			evt.preventDefault();
			jQuery("div.pf_container").removeClass('grid').addClass('list');
			jQuery('.feed-item').each(function (index){
				var element		= jQuery(this);
				var itemID		= element.attr('id');
				jQuery('#'+itemID+' header .actions').appendTo('#'+itemID+' footer');
			});
		}
	);
	
	jQuery('#gomenu').toggle(function (evt){ 
			evt.preventDefault();
			jQuery("div.pf_container").removeClass('full');
		}, function() {
			jQuery("div.pf_container").addClass('full');
		});	
	
});