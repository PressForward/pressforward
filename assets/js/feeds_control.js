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
});