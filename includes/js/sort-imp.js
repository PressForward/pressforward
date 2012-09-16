/**
 * Implementation of sort for RSSPF
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
	
});