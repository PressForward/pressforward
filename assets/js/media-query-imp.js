
function pf_media_uploader(){
	e.preventDefault();
	// Code via http://stackoverflow.com/questions/13847714/wordpress-3-5-custom-media-upload-for-your-theme-options?cachebusterTimestamp=1405277969630
	alert('Test');
    var send_attachment_bkp = wp.media.editor.send.attachment;

    wp.media.editor.send.attachment = function(props, attachment) {

        //$('.pf_primary_media_opml').attr('src', attachment.url);
        jQuery('.pf_primary_media_opml_url').val(attachment.url);
       // $('.pf_primary_media_opml_id').val(attachment.id);

        wp.media.editor.send.attachment = send_attachment_bkp;
    }

    wp.media.editor.open();

}

jQuery('.pf-opt-group').on('click', '.pf_primary_media_opml_upload', function() {
	
	pf_media_uploader();

    return false;       
});