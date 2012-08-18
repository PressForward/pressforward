jQuery(document).ready(function() {
	var item_title 		= jQuery("#item_title").val();
	var source_title 	= jQuery("#source_title").val();
	var item_date 		= jQuery("#item_date").val();
	var item_author 	= jQuery("#item_author").val();
	var item_content 	= jQuery("#item_content").val();
	var item_link 		= jQuery("#item_link").val();
	var item_feat_img 	= jQuery("#item_feat_img").val();
	var item_id 		= jQuery("#item_id").val();
	var errorThrown		= 'Broken';
	jQuery("#PleasePushMe").click(function(){ jQuery.ajax({
		type: 'POST',
		url: 'admin-ajax.php',
		data: {
			action: 'build_a_nomination',
			item_title: item_title,
			source_title: source_title,
			item_date: item_date,
			item_author: item_author,
			item_content: item_content,
			item_link: item_link,
			item_feat_img: item_feat_img,
			item_id: item_id
		},
		success: function(data, textStatus, XMLHttpRequest){
			jQuery("#test-div1").html('');
			//jQuery("#test-div1").append(data);
		},
		error: function(MLHttpRequest, textStatus, errorThrown){
			alert(errorThrown);
		}
	  });
	  });
});