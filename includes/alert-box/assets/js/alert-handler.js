jQuery(window).load(function() {

	jQuery('#delete_all_alert_specimens').click(function (evt) {
		evt.preventDefault();
        var element = jQuery(this);
        var alertConfirm = element.attr('alert-check');
        var post_types = element.attr('alert-types');
        if(confirm(alertConfirm)){
            jQuery.post(ajaxurl, {
                action: 'remove_alerted_posts',
                filtered_post_types: post_types
            },
            function (response) {
                var check = jQuery(response).find("response_data").text();
                alert(check);
                location.reload();
            });
        }
	});

	jQuery('#dismiss_all_alert_specimens').click(function (evt) {
		evt.preventDefault();
				var element = jQuery(this);
				var dismiss_all_confirm = element.attr('data-dismiss-all-check');
				var post_types = element.attr('alert-types');
				if(confirm(dismiss_all_confirm)){
						jQuery.post(ajaxurl, {
								action: 'dismiss_alerts_ajax',
								filtered_post_types: post_types,
								all_alerts: 'true'
						},
						function (response) {
								var check = jQuery(response).find("response_data").text();
								alert(check);
								location.reload();
						});
				}
	});

	jQuery('.alert-dismisser').click(function(evt){

		evt.preventDefault();
		var element = jQuery(this);
		var dismissConfirm = element.attr('data-alert-dismiss-check');
		var post_types = element.attr('alert-types');
		var post_id = element.attr('data-alert-post-id');
		if ( confirm(dismissConfirm)){
			element.parent('p').hide();
			jQuery.post(ajaxurl, {
				action: 'dismiss_alerts_ajax',
				filtered_post_types: post_types,
				alert: post_id
			},
			function (response){
				var check = jQuery(response).find("response_data").text();
				alert(check);

			});
		}

	});

});
