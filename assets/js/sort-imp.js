function pf_set_sort_statement(statement, sort_class){
		if (jQuery('.'+sort_class).length){

		} else if (jQuery('.title h1 .sorted').length) {
			jQuery('.title h1 .sorted').remove();
		}
		if (jQuery('.'+sort_class).length){
			jQuery('.title h1 .'+sort_class).html(' | '+statement);
		} else {
			jQuery('.title h1').append('<span class="sorted '+sort_class+'"> | '+statement+'</span>');
		}
}

/**
 * Implementation of sort for pf
**/
jQuery(window).load(function() {
	jQuery(".feed-item").tsort("span.sortable_nom_timestamp", {order:'desc'});

	pf_show_unsort();
	jQuery('#sortbyitemdate').toggle(function (evt){
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortableitemdate", {order:'desc'});
		pf_set_sort_statement('Sorted by item date, descending', 'by-item-date');
	}, function (evt){
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortableitemdate", {order:'asc'});
		pf_set_sort_statement('Sorted by item date, ascending', 'by-item-date');
	}
	);
	jQuery('#sortbyfeedindate').toggle(function (evt){
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortablerssdate", {order:'desc'});
		pf_set_sort_statement('Sorted by time retrieved, descending', 'by-feed-in-date');
	}, function (evt) {
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortablerssdate", {order:'asc'});
		pf_set_sort_statement('Sorted by time retrieved, ascending', 'by-feed-in-date');
	}
	);

	jQuery('#sortbynomdate').toggle(function (evt){
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_timestamp", {order:'desc'});
		pf_set_sort_statement('Sorted by time nominated, descending', 'by-nominated-date');
	}, function (evt) {
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_timestamp", {order:'asc'});
		pf_set_sort_statement('Sorted by time nominated, ascending', 'by-nominated-date');
	}
	);

	jQuery('#sortbynomcount').toggle(function (evt){
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_count", {order:'desc'});
		pf_set_sort_statement('Sorted by nominations, descending', 'by-nominations-date');
	}, function (evt) {
		evt.preventDefault();
		jQuery(".feed-item").tsort("span.sortable_nom_count", {order:'asc'});
		pf_set_sort_statement('Sorted by time nominations, ascending', 'by-nominations-date');
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
		jQuery('.title h1 .sorted').remove();
	});
}
