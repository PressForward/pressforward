/* global jQuery */

jQuery(window).load(function () {
	window.nom_vars = {
		the_nom_id: 0
	};
	jQuery('.pf_container').on('click', ".nominate-now", function (evt) {
		evt.preventDefault();

		const clickedButton = evt.target;

		if ( jQuery( clickedButton ).hasClass( 'user-has-nominated' ) ) {
			return;
		}

		var element = jQuery(this);
		var itemID = element.attr('form');
		var item_title = jQuery("#item_title_" + itemID).val();
		var source_title = jQuery("#source_title_" + itemID).val();
		var item_date = jQuery("#item_date_" + itemID).val();
		var item_author = jQuery("#item_author_" + itemID).val();
		var item_content = jQuery("#item_content_" + itemID).val();
		var item_link = jQuery("#item_link_" + itemID).val();
		var item_feat_img = jQuery("#item_feat_img_" + itemID).val();
		var item_id = jQuery("#item_id_" + itemID).val();
		var item_wp_date = jQuery("#item_wp_date_" + itemID).val();
		var item_tags = jQuery("#item_tags_" + itemID).val();
		var source_repeat = jQuery("#source_repeat_" + itemID).val();
		var postID = jQuery('#' + itemID).attr('pf-post-id');
		// var errorThrown     = 'Broken';
		var theNonce = jQuery.trim(jQuery('#pf_nomination_nonce').val());
		jQuery('.loading-' + itemID).show();
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
				item_post_id: postID,
				source_repeat: source_repeat,
				pf_nomination_nonce: theNonce
			},
			function (response) {
				jQuery('.loading-' + itemID).hide();
				clickedButton.classList.add( 'user-has-nominated' );
			});
	});
	jQuery('.pf_container').on('click', ".amplify-draft", function (evt) {
		evt.preventDefault();

		var element = jQuery(this);
		var itemID = element.attr('data-form');
		var item_title = jQuery("#item_title_" + itemID).val();
		var source_title = jQuery("#source_title_" + itemID).val();
		var item_date = jQuery("#item_date_" + itemID).val();
		var item_author = jQuery("#item_author_" + itemID).val();
		var item_content = jQuery("#item_content_" + itemID).val();
		var item_link = jQuery("#item_link_" + itemID).val();
		var item_feat_img = jQuery("#item_feat_img_" + itemID).val();
		var item_id = jQuery("#item_id_" + itemID).val();
		var item_wp_date = jQuery("#item_wp_date_" + itemID).val();
		var item_tags = jQuery("#item_tags_" + itemID).val();
		var source_repeat = jQuery("#source_repeat_" + itemID).val();
		var postID = jQuery('#' + itemID).attr('pf-post-id');

		var nomCheck = jQuery('#' + itemID + ' .nominate-now').hasClass("btn-success");
		// var errorThrown     = 'Broken';
		var theNonce = jQuery.trim(jQuery('#pf_nomination_nonce').val());
		nom_vars.the_nom_id = 0;
		jQuery('.loading-' + itemID).show();
		if (true !== nomCheck) {
			console.log('Nominate then press forward');
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
					item_post_id: postID,
					source_repeat: source_repeat,
					pf_amplify: '1',
					pf_nomination_nonce: theNonce
				},
				function (response) {
					// jQuery('.loading-'+itemID).hide();
					// jQuery(".nominate-result-"+itemID).html(response);
					// alert(response);
					// jQuery("#test-div1").append(data);
					window.nom_vars.the_nom_id = jQuery(response).find("nomination").attr('id');
					console.log('Nomination ID found: ' + window.nom_vars.the_nom_id);
					var element_class = element.attr('pf-schema-class');
					jQuery('.nominate-now[form=' + itemID + ']').addClass(element_class);

				}).promise().done(function () {
				if ((0 === window.nom_vars.the_nom_id) || ('' === window.nom_vars.the_nom_id)) {
					alert("Nomination failed and could not be continued into draft process.");
				} else {
					jQuery.post(ajaxurl, {
							action: 'simple_nom_to_draft',
							nom_id: window.nom_vars.the_nom_id,
							pf_nomination_nonce: theNonce
						},
						function (response) {
							jQuery('.loading-' + itemID).hide();
							window.nom_vars.the_nom_id = 0;
						});
				}
			});
		} else {
			console.log('already nominated, so press forward.')
			jQuery.post(ajaxurl, {
					action: 'simple_nom_to_draft',
					post_id: postID,
					pf_nomination_nonce: theNonce
				},
				function (response) {
					jQuery('.loading-' + itemID).hide();
					window.nom_vars.the_nom_id = 0;
				});
		}
	});
});
