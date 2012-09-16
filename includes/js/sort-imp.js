/**
 * Implementation of sort for RSSPF
**/
jQuery(document).ready(function() {

	jQuery('#sortbyitemdate').click(function (evt){ 
		evt.preventDefault();
		jQuery("div.feed-item").tsort("span.sortableitemdate", {order:'desc'});
	});
	jQuery('#sortbyfeedindate').click(function (evt){ 
		evt.preventDefault();
		jQuery("div.feed-item").tsort("span.sortablerssdate", {order:'desc'});
	});
	
});