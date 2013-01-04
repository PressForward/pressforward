jQuery(document).ready(function() {

	jQuery(".nom-to-archive").click(function (evt){ 
		evt.preventDefault();
		
	var element			= jQuery(this);
	var nomID			= element.attr('form');
	var theNonce		= jQuery.trim(jQuery('#pf_drafted_nonce').val())
	
	jQuery.post(ajaxurl, {
			action: 'archive_a_nom',
			nom_id: nom_id,
			pf_drafted_nonce: theNonce
		},
		function(response) {
			jQuery('#'+nomID).hide();
			if (response != 0){
				jQuery(".pf-alert").html(response);
				jQuery("pressforward-alertbox").show()
			}
			//jQuery("#test-div1").append(data);
		});
	  });
  	
});

jQuery(document).ready(function() {
	jQuery(".showarchived").click(function (evt){ 
		evt.preventDefault();
		
		jQuery('.archived').show();
	
	  });
});	