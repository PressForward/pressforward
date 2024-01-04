jQuery(window).load(function () {
	jQuery(".cleanfeeds").on( 'click', function (evt) {
		evt.preventDefault();
		jQuery('.loading-top').show();
		jQuery.post(ajaxurl, {
			action: 'disassemble_item'
		},
			function (response) {
				//jQuery('.loading-top').hide();
				jQuery('#responses').css('display', 'block');
				jQuery('#responses').html('<span class="basic-response">' + response.action_taken + '</span>');
				jQuery('#errors').html(response);
				//jQuery("#test-div1").append(data);
			});

	});

	jQuery("#pressforward-nt__regenerate-button").on( 'click', function (evt) {
		// evt.preventDefault();
		// jQuery('.loading-top').show();
		jQuery.post(ajaxurl, {
			action: 'regenerate_user_keys'
		},
			function (response) {
				//jQuery('.loading-top').hide();
				console.log(jQuery('#pfnt__pfSiteData').val());
				var settingsObject = JSON.parse(jQuery('#pfnt__pfSiteData').val());
				// console.log('response', response);
				// console.log('response object', response.response.response_data);
				//var resultObject = JSON.parse(response.response_data);
				settingsObject.ku = response.ku;
				settingsObject.ki = response.ki;
				jQuery('#pfnt__pfSiteData').val(JSON.stringify(settingsObject));
				//jQuery("#test-div1").append(data);
			});

	});

});
