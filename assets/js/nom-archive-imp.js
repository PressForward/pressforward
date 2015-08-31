
	var archivesVisible = 0;

jQuery(window).load(function() {
	
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

jQuery(window).load(function() {

	jQuery("#showarchived").click( function (evt){ 
		evt.preventDefault();
		if(jQuery('#showarchived').hasClass('hidearchived')){
			archivesVisible = 0;
			jQuery('.archived').hide();
			jQuery('#showarchived').html('Show Archived');
			jQuery('#showarchived').attr('value', 'Show Archived');
			jQuery('#showarchived').removeClass('hidearchived');
		} else {
			archivesVisible = 1;
			jQuery('.archived').show();
			jQuery('#showarchived').html('Hide Archived');
			jQuery('#showarchived').attr('value', 'Hide Archived');
			jQuery('#showarchived').addClass('hidearchived');
		}
	
	}
	);		
	
	jQuery("#archivenoms").click(function(evt){
		jQuery('.loading-top').show();
		jQuery('.nom-container').hide();
		jQuery.post(ajaxurl, {
			action: 'pf_archive_nominations'
		},
		function(response) {
			jQuery('.loading-top').hide();
			if (archivesVisible != 1){
				jQuery('.nom-container').addClass('archived');

			}
			jQuery('#errors').html(response);
		});
	});
	
	jQuery('.pf_container').on('click', "#archivebefore", function (evt){
		evt.preventDefault();	
		var element		= jQuery(this);
		var date_limit  = jQuery('#archiveBeforeOption').val();
		
		
		jQuery.post(ajaxurl, {
			action: 'pf_archive_nominations',
			date_limit:  date_limit
		},
		function(response) {		
			jQuery('#errors').html(response);
		});
	});	

	
});	