jQuery(document).ready(function() {

	jQuery(".feed-item").on('show', function(evt){
		var element = jQuery(this);
		var itemID = jQuery(element.attr('id'));
		//At this point it should have grabbed the direct feeditem hashed ID. That allows us to do things specifically to that item past this point.
		var content = jQuery("#"+itemID+" .item_content").html();
		var url = jQuery("#"+itemID+" .item_url").attr('href');
		var authorship = jQuery("#"+itemID+" span.item-authorship").html();
		//I suppose I should nonce here right? 
		var theNonce		= jQuery.trim(jQuery('#rsspf_nomination_nonce').val());
		//At some point a waiting graphic should go here. 
		jQuery.post(ajaxurl, {
			action 'make_it_readable',
			//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.			
			read_item_id: itemID,
			url: url,
			content: content,
			//We need to pull the source data to determine if it is aggregation as well. 
			authorship: authorship,
			rsspf_nomination_nonce: theNonce
			
		}, function(response) {
			jQuery("#"+itemID+" .item_content").html(response);
		});
		
	});

})