/**
 * Implementation of sort for pf
**/
jQuery(document).ready(function() {

	jQuery('#sortbyitemdate').toggle(function (evt){ 
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortableitemdate", {order:'desc'});
	}, function (evt){
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortableitemdate", {order:'asc'});
	}
	);
	jQuery('#sortbyfeedindate').toggle(function (evt){ 
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortablerssdate", {order:'desc'});
	}, function (evt) {
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortablerssdate", {order:'asc'});		
	}
	);
	
	jQuery('#sortbynomdate').toggle(function (evt){ 
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_timestamp", {order:'desc'});
	}, function (evt) {
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_timestamp", {order:'asc'});		
	}
	);	
	
	jQuery('.pf_container').on('click', "#fullscreenfeed", function(e){
		e.preventDefault();
		jQuery('.pf_container').fullScreen({
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