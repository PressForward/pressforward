jQuery(document).ready(function() {

	jQuery(".PleasePushMe").click(function (evt){ 
		evt.preventDefault();
		var element		= jQuery(this);
		var itemID		= element.attr('id');
	var itemTitle 		= jQuery("#item_title_"+itemID).val();
//	var source_title 	= jQuery(this).jQuery("#source_title").val();
//	var item_date 		= jQuery(this).jQuery("#item_date").val();
//	var item_author 	= jQuery(this).jQuery("#item_author").val();
	var item_content 	= jQuery("#item_content_"+itemID).val();
//	var item_link 		= jQuery(this).jQuery("#item_link").val();
//	var item_feat_img 	= jQuery(this).jQuery("#item_feat_img").val();
	var item_id 		= jQuery("#item_id_"+itemID).val();
	var item_wp_date	= jQuery("#item_wp_date_"+itemID).val();
//	var errorThrown		= 'Broken';
	var theNonce		= jQuery.trim(jQuery('#rsspf_nomination_nonce').val())
	
	jQuery.post(ajaxurl, {
			action: 'build_a_nomination',
			item_title: itemTitle,
//			source_title: source_title,
//			item_date: item_date,
//			item_author: item_author,
			item_content: item_content,
//			item_link: item_link,
//			item_feat_img: item_feat_img,
			item_id: item_id,
			item_wp_date: item_wp_date,
			rsspf_nomination_nonce: theNonce
		},
		function(response) {
			jQuery(".nominate-result-"+itemID).html(response);
			//jQuery("#test-div1").append(data);
		});
	  });
});