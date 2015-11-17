
	var archivesVisible = 0;

jQuery(window).load(function() {

	jQuery('.pf_container').on('click', ".nom-to-archive", function (evt){
		//evt.preventDefault();

	var element			= jQuery(this);
	var nomID			= element.attr('form');
	var theNonce		= jQuery.trim(jQuery('#pf_drafted_nonce').val())

			jQuery('#'+nomID).addClass('archived');
			if (archivesVisible == 0){
				jQuery('#'+nomID).hide();
			} else {
				jQuery('#'+nomID).removeClass('archived');
			}
	});
});

var archivesVisible = 0;

jQuery(window).load(function() {

	jQuery("#showarchived").click( function (evt){
		evt.preventDefault();
		if(jQuery('#showarchived').hasClass('hidearchived')){
			archivesVisible = 0;
			jQuery('.archived').hide();
			jQuery('#showarchived').html('Show Archived');
			jQuery('#showarchived').attr('value', 'Show Archived');
			jQuery('#showarchived').removeClass('hidearchived');
		} else {
			archivesVisible = 1;
			jQuery('.archived').show();
			jQuery('#showarchived').html('Hide Archived');
			jQuery('#showarchived').attr('value', 'Hide Archived');
			jQuery('#showarchived').addClass('hidearchived');
		}

	}
	);

	jQuery("#archivenoms").click(function(evt){
		jQuery('.loading-top').show();
		jQuery('.nom-container').hide();
		jQuery.post(ajaxurl, {
			action: 'pf_archive_nominations'
		},
		function(response) {
			jQuery('.loading-top').hide();
			if (archivesVisible != 1){
				jQuery('.nom-container').addClass('archived');

			}
			jQuery('#errors').html(response);
		});
	});

	jQuery('.pf_container').on('click', "#archivebefore", function (evt){
		evt.preventDefault();
		var element		= jQuery(this);
		var date_limit  = jQuery('#archiveBeforeOption').val();


		jQuery.post(ajaxurl, {
			action: 'pf_archive_nominations',
			date_limit:  date_limit
		},
		function(response) {
			jQuery('#errors').html(response);
		});
	});

	jQuery('.pf_container').on('click', ".not-archived .nom-to-archive", function (evt){
		evt.preventDefault();
		console.log('Move to archive.');
		var element		= jQuery(this);
		var itemID		= element.attr('form');
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
	var source_repeat	= jQuery("#source_repeat_"+itemID).val();
	var postID 			= jQuery('#'+itemID).attr('pf-item-post-id');
	var nomID 			= jQuery('#'+itemID).attr('pf-post-id');
//	var errorThrown		= 'Broken';
	var theNonce		= jQuery.trim(jQuery('#pf_nomination_nonce').val());
	jQuery('.loading-'+itemID).show();
	jQuery(this).addClass('btn-warning');
	jQuery('#'+itemID).addClass('archive');
	jQuery.post(ajaxurl, {
			action: 'pf_ajax_move_to_archive',
			item_post_id: postID,
			nom_id: nomID,
			source_repeat: source_repeat,
			pf_nomination_nonce: theNonce
		},
		function(response) {
			jQuery('.loading-'+itemID).hide();
			//jQuery(".nominate-result-"+itemID).html(response);
			//alert(response);
			//jQuery("#test-div1").append(data);
		});
	  });

	jQuery('.pf_container').on('click', ".nom-to-archive.btn-warning", function (evt){
		evt.preventDefault();
		console.log('Move out of archive.');
		var element		= jQuery(this);
		var itemID		= element.attr('form');
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
	var source_repeat	= jQuery("#source_repeat_"+itemID).val();
	var postID 			= jQuery('#'+itemID).attr('pf-item-post-id');
	var nomID 			= jQuery('#'+itemID).attr('pf-post-id');
//	var errorThrown		= 'Broken';
	var theNonce		= jQuery.trim(jQuery('#pf_nomination_nonce').val());
	jQuery('.loading-'+itemID).show();
	jQuery('#'+itemID).removeClass('archived');
	jQuery(this).removeClass('btn-warning');
	jQuery.post(ajaxurl, {
			action: 'pf_ajax_move_out_of_archive',
			item_post_id: postID,
			nom_id: nomID,
			source_repeat: source_repeat,
			pf_nomination_nonce: theNonce
		},
		function(response) {
			jQuery('.loading-'+itemID).hide();
			//jQuery(".nominate-result-"+itemID).html(response);
			//alert(response);
			//jQuery("#test-div1").append(data);
		});
	  });


});
