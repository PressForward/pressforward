jQuery(window).load(function() {
	jQuery(".refreshfeed").click(function (evt){
		evt.preventDefault();
		jQuery('.loading-top').show();
		jQuery.post(ajaxurl, {
			action: 'assemble_feed_for_pull'
		},
		function(response) {
			//jQuery('.loading-top').hide();
			jQuery('#responses').css('display', 'block');
			jQuery('#responses').html('<span class="basic-response">'+response.action_taken+'</span>');
			jQuery('#errors').html(response);
			//jQuery("#test-div1").append(data);
		});

	});

	jQuery(".cleanfeeds").click(function (evt){
		evt.preventDefault();
		jQuery('.loading-top').show();
		jQuery.post(ajaxurl, {
			action: 'disassemble_item'
		},
		function(response) {
			//jQuery('.loading-top').hide();
			jQuery('#responses').css('display', 'block');
			jQuery('#responses').html('<span class="basic-response">'+response.action_taken+'</span>');
			jQuery('#errors').html(response);
			//jQuery("#test-div1").append(data);
		});

	});
});
