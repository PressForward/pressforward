jQuery(window).load(function() {

	jQuery('.pf_primary_media_opml_upload').click(function(e) {
			e.preventDefault();
			// via http://stackoverflow.com/questions/13847714/wordpress-3-5-custom-media-upload-for-your-theme-options?cachebusterTimestamp=1405277969630
			var custom_uploader = wp.media({
				title: 'Upload .OPML or .XML',
				button: {
					text: 'Add to Subscription list'
				},
				multiple: false  // Set this to true to allow multiple files to be selected
			})
			.on('select', function() {
				var attachment = custom_uploader.state().get('selection').first().toJSON();
				//$('.custom_media_image').attr('src', attachment.url);
				jQuery('.pf_opml_file_upload_field').val(attachment.url);
				//$('.custom_media_id').val(attachment.id);
			})
			.open();
		return false;       
	});

});