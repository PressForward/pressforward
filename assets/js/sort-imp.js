/**
 * Implementation of sort for pf
**/
jQuery(document).ready(function() {

	jQuery('#sortbyitemdate').click().toggle(function (evt){ 
		evt.preventDefault();
		jQuery("div.feed-item").tsort("span.sortableitemdate", {order:'desc'});
	}, function (evt){
		evt.preventDefault();
		jQuery("div.feed-item").tsort("span.sortableitemdate", {order:'asc'});
	}
	);
	jQuery('#sortbyfeedindate').click().toggle(function (evt){ 
		evt.preventDefault();
		jQuery("div.feed-item").tsort("span.sortablerssdate", {order:'desc'});
	}, function (evt) {
		evt.preventDefault();
		jQuery("div.feed-item").tsort("span.sortablerssdate", {order:'asc'});		
	}
	);
	
	jQuery('#fullscreenfeed').click(function(e){
		e.preventDefault();
		jQuery('.container-fluid').fullScreen({
			'background'	: '#ecf3f9',
			'callback'		: function(isFullScreen){     
					// ...
					// Do some cleaning up here
					// ...
					if (isFullScreen){
						//jQuery('#fullscreenfeed').prepend('Exit ')
					}
			}
		});
		
	});
	
	jQuery('.itemInfobutton').click(function (e){
		e.preventDefault();
		var element		= jQuery(this);
		var itemID		= element.attr('id');
		jQuery('#info-box-'+itemID).toggle();
	});
	
	jQuery('.navwidget').scrollspy()
	
});