jQuery(window).load(function() {
	jQuery(".redoFeeds").click(function (evt){
		evt.preventDefault();
		jQuery.post(ajaxurl, {
			action: 'deal_with_old_feedlists'
		},
		function (response) {
			alert('Feeds moved. ' + response);
		});
	});


	jQuery(".resetFeedOps").click(function (evt){
			evt.preventDefault();


		//jQuery('.loading-'+itemID).show();
		jQuery.post(ajaxurl, {
				action: 'feed_retrieval_reset'
			},
			function(response) {
				//jQuery('.loading-'+itemID).hide();
				//jQuery(".o_feed_"+itemID).html(response);
				//jQuery("#test-div1").append(data);
				alert('Feed options reset.');
			});
	});

	jQuery('.pressforward_page_pf-feeder .rss-box').on('click', '.button-primary', function(evt){

		jQuery( ".pressforward_page_pf-feeder .rss-box .button-primary:last" ).hide();
		jQuery( ".pressforward_page_pf-feeder .rss-box" ).append( '<div class="pf-loader" style="margin-top:-80px;"></div>' );

	});


});