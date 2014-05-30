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
});