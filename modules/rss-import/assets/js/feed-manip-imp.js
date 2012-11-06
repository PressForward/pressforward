jQuery(document).ready(function() {
	jQuery(".removeMyFeed").click(function (evt){ 
		evt.preventDefault();
		
		var element		= jQuery(this);
		var itemID		= element.attr('id');
	//var o_feed_title 	= jQuery("#o_feed_title_"+itemID).val();
	var o_feed_url 		= jQuery("#o_feed_url_"+itemID).val(); 
	var theNonce		= jQuery.trim(jQuery('#rsspf_feed_op_nonce').val())

	jQuery('.loading-'+itemID).show();
	jQuery.post(ajaxurl, {
			action: 'remove_a_feed',
			//o_feed_title: o_feed_title,
			o_feed_url: o_feed_url,
			rsspf_o_feed_nonce: theNonce
		},
		function(response) {
			jQuery('.loading-'+itemID).hide();
			jQuery(".o_feed_"+itemID).html(response);
			//jQuery("#test-div1").append(data);
			jQuery(".feed-"+itemID).remove();
		});
	  });
});