/**
 * Implementation of sort for pf
**/
jQuery(window).load(function() {
	jQuery(".feed-item").tsort("span.sortable_nom_timestamp", {order:'desc'});

	pf_show_unsort();
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

	jQuery('#sortbynomcount').toggle(function (evt){
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_count", {order:'desc'});
	}, function (evt) {
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_count", {order:'asc'});
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


	jQuery('.navwidget').scrollspy();

	jQuery('.pf_container').on('click', ".pf-item-remove", function(e){
		e.preventDefault();
		var element		= jQuery(this);
		var postID		= element.attr('pf-post-id');
		jQuery('article[pf-post-id="'+postID+'"]').remove();
		jQuery.post(ajaxurl, {
			action: 'pf_ajax_thing_deleter',
			post_id: postID,
		}, function (response) {

		});

	});

	jQuery('.pf_container').on('click', ".hide-item", function(e){
		e.preventDefault();
		var element		= jQuery(this);
		var postID		= element.attr('pf-item-post-id');
		if (jQuery('.pf_container').hasClass('archived_visible')){
			jQuery('article[pf-item-post-id="'+postID+'"] i.pf-item-archive').addClass('relationship-button-active');
		} else {
			jQuery('article[pf-item-post-id="'+postID+'"]').remove();
		}

	});
});


function pf_show_unsort(){
	//console.log('b');
	jQuery('.feedsort').on('click', function() {
		console.log('a');
		jQuery('#sort-reset').show();
	});
	jQuery('#sort-reset').on('click', function(){
		jQuery(".feed-item").tsort("span.sortable_nom_timestamp", {order:'desc'});
	});
}
