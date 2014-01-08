
	var archivesVisible = 0;

jQuery(document).ready(function() {
	
	jQuery('.pf_container').on('click', ".nom-to-archive", function (evt){ 
		evt.preventDefault();
		
	var element			= jQuery(this);
	var nomID			= element.attr('form');
	var theNonce		= jQuery.trim(jQuery('#pf_drafted_nonce').val())
	
			jQuery('#'+nomID).addClass('archived');
			if (archivesVisible == 0){
				jQuery('#'+nomID).hide();
			}
	});
});

var archivesVisible = 0;

jQuery(document).ready(function() {
	jQuery(".showarchived").click().toggle(function (evt){ 
		evt.preventDefault();
		archivesVisible = 1;
		jQuery('.archived').show();
		jQuery('.showarchived').html('Hide Archived');
		jQuery('.showarchived').attr('value', 'Hide Archived');
		jQuery('.showarchived').addClass('hidearchived');
	
	}, function (evt){ 
		evt.preventDefault();
		archivesVisible = 0;
		jQuery('.archived').hide();
		jQuery('.hidearchived').html('Show Archived');
		jQuery('.hidearchived').attr('value', 'Show Archived');
		jQuery('.showarchived').removeClass('hidearchived');
	
	}
	);		
	
	jQuery("#archivenoms").click(function(evt){
		jQuery('.loading-top').show();
		jQuery('.nom-container').hide();
		jQuery.post(ajaxurl, {
			action: 'pf_archive_all_nominations'
		},
		function(response) {
			jQuery('.loading-top').hide();
			if (archivesVisible != 1){
				jQuery('.nom-container').addClass('archived');

			}
			jQuery('#errors').html(response);
		});
	});
	
});	