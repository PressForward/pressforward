
function pf_media_uploader(e){
	e.preventDefault();
	// Code via http://stackoverflow.com/questions/13847714/wordpress-3-5-custom-media-upload-for-your-theme-options?cachebusterTimestamp=1405277969630
	alert('Test');
	

    var custom_uploader = wp.media({
        title: 'WordPress OPML Uploader',
        button: {
            text: 'Upload OPML file'
        },
        multiple: false  // Set this to true to allow multiple files to be selected
    })
    .on('select', function() {
        var attachment = custom_uploader.state().get('selection').first().toJSON();
        //$('.custom_media_image').attr('src', attachment.url);
        jQuery('.pf_primary_media_opml_url').val(attachment.url);
        //$('.custom_media_id').val(attachment.id);
    })
    .open();	


}

jQuery('.pf-opt-group').on('click', '.pf_primary_media_opml_upload', function() {
	var e = this;
	pf_media_uploader(e);

    return false;       
});