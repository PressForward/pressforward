jQuery(document).ready(function() {

	jQuery(".nom-to-draft").click(function (evt){ 
		evt.preventDefault();
		
	var element			= jQuery(this);
	var itemID			= element.attr('id');
	var nom_title 		= jQuery("#nom_title_"+itemID).val();
	var source_title 	= jQuery("#source_title_"+itemID).val(); 
	var nom_date 		= jQuery("#nom_date_"+itemID).val(); 
	var item_author 	= jQuery("#item_author_"+itemID).val();
	var nom_content 	= jQuery("#nom_content_"+itemID).val();
	var item_link 		= jQuery("#item_link_"+itemID).val();
	var nom_feat_img 	= jQuery("#nom_feat_img_"+itemID).val();
	var item_id 		= jQuery("#item_id_"+itemID).val();
	var nom_date		= jQuery("#nom_date_"+itemID).val();
	var nom_tags		= jQuery("#nom_tags_"+itemID).val();
//	var errorThrown		= 'Broken';
	var theNonce		= jQuery.trim(jQuery('#pf_drafted_nonce').val())
	jQuery('.loading-'+itemID).show();
	jQuery.post(ajaxurl, {
			action: 'build_a_nom_draft',
			nom_title: nom_title,
			nom_id: nom_id,
			nom_date: nom_date,
			nom_modified_date,
			nom_tags: nom_tags,
			nom_count: nom_count,
			nom_users: nom_users,
			nom_content: nom_content,	
			nom_feat_img: nom_feat_img,			
			source_title: source_title,
			source_link: source_link,
			source_slug: source_slug,
			item_id: item_id,			
			item_date: item_date,
			item_author: item_author,
			item_link: item_link,
			//Nom comments will sit here eventually.
			pf_drafted_nonce: theNonce
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