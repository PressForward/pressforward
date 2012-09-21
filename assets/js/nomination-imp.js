jQuery(document).ready(function() {

	jQuery(".PleasePushMe").click(function (evt){ 
		evt.preventDefault();
		
		var element		= jQuery(this);
		var itemID		= element.attr('id');
	var item_title 		= jQuery("#item_title_"+itemID).val();
	var source_title 	= jQuery("#source_title_"+itemID).val(); 
	var item_date 		= jQuery("#item_date_"+itemID).val(); 
	var item_author 	= jQuery("#item_author_"+itemID).val();
	var item_content 	= jQuery("#item_content_"+itemID).val();
	var item_link 		= jQuery("#item_link_"+itemID).val();
	var item_feat_img 	= jQuery("#item_feat_img_"+itemID).val();
	var item_id 		= jQuery("#item_id_"+itemID).val();
	var item_wp_date	= jQuery("#item_wp_date_"+itemID).val();
	var item_tags		= jQuery("#item_tags_"+itemID).val();
//	var errorThrown		= 'Broken';
	var theNonce		= jQuery.trim(jQuery('#rsspf_nomination_nonce').val())
	jQuery('.loading-'+itemID).show();
	jQuery.post(ajaxurl, {
			action: 'build_a_nomination',
			item_title: item_title,
			source_title: source_title,
			item_date: item_date,
			item_author: item_author,
			item_content: item_content,
			item_link: item_link,
			item_feat_img: item_feat_img,
			item_id: item_id,
			item_wp_date: item_wp_date,
			item_tags: item_tags,
			rsspf_nomination_nonce: theNonce
		},
		function(response) {
			jQuery('.loading-'+itemID).hide();
			jQuery(".nominate-result-"+itemID).html(response);
			//jQuery("#test-div1").append(data);
		});
	  });
	  
	jQuery(".refreshfeed").click(function (evt){ 
		evt.preventDefault();
		jQuery('.loading-top').show();
		jQuery.post(ajaxurl, {
			action: 'assemble_feed_for_pull'
		},
		function(response) {
			jQuery('.loading-top').hide();
			jQuery('#errors').html(response);
			//jQuery("#test-div1").append(data);
		});
	
	});
	
	jQuery('#deletefeedarchive').click(function (evt) {
		evt.preventDefault();
		jQuery('.loading-top').show();
		jQuery.post(ajaxurl, {
			action: 'reset_feed'
		},
		function(response) {
			jQuery('.loading-top').hide();
			jQuery('#errors').html(response);
		});
	});
	
});