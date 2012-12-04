jQuery(document).ready(function() {

	jQuery(".feed-item").on('show', function(evt){
		var element = jQuery(this);
		var itemID = element.attr('id');
		alert(itemID);
		//At this point it should have grabbed the direct feeditem hashed ID. That allows us to do things specifically to that item past this point.
		//BUG: Escaping everything incorrectly. <-one time issue?
		var content = jQuery("#"+itemID+" .item_content").html();
		var url = jQuery("#"+itemID+" .item_url").attr('href');
		var authorship = jQuery("#"+itemID+" span.item-authorship").html();
		//I suppose I should nonce here right? 
		var theNonce		= jQuery.trim(jQuery('#rsspf_nomination_nonce').val());
		//At some point a waiting graphic should go here. 
		jQuery.post(ajaxurl, {
			action: 'make_it_readable',
			//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.			
			read_item_id: itemID,
			url: url,
			content: content,
			//We need to pull the source data to determine if it is aggregation as well. 
			authorship: authorship,
			rsspf_nomination_nonce: theNonce
			
		}, 
		function(response) {
			// Don't bother doing anything if we don't need it.
			if (response != 'readable') {
				if (response == 'secured') {
					alert('The content cannot be retrieved. The post may be on a secure page or it may have been removed.');
				} else {
					jQuery("#"+itemID+" .item_content").html(response);
				}
			}
		});
		
	});
	
	//This also needs to rewrite the content of the form!!

})