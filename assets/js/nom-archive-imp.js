jQuery(document).ready(function() {

	jQuery('.pf_container').on('click', ".nom-to-archive", function (evt){ 
		evt.preventDefault();
		
	var element			= jQuery(this);
	var nomID			= element.attr('form');
	var theNonce		= jQuery.trim(jQuery('#pf_drafted_nonce').val())
	
	jQuery.post(ajaxurl, {
			action: 'archive_a_nom',
			nom_id: nomID,
			pf_drafted_nonce: theNonce
		},
		function(response) {
			jQuery('#'+nomID).addClass('archived');
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
	jQuery(".showarchived").click().toggle(function (evt){ 
		evt.preventDefault();
		
		jQuery('.archived').show();
		jQuery('.showarchived').html('Hide Archived');
		jQuery('.showarchived').attr('value', 'Hide Archived');
		jQuery('.showarchived').addClass('hidearchived');
	
	}, function (evt){ 
		evt.preventDefault();
		
		jQuery('.archived').hide();
		jQuery('.hidearchived').html('Show Archived');
		jQuery('.hidearchived').attr('value', 'Show Archived');
		jQuery('.showarchived').removeClass('hidearchived');
	
	}
	);		
	  
});	