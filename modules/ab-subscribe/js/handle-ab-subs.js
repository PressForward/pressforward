jQuery(window).load(function() {

	jQuery('.pressforward').on('click', "#academic-sub", function (evt){ 
        evt.preventDefault();
    
	var ab_cats 		= jQuery("#ab-cats").val();
	var ab_subcats  	= jQuery("#ab-subcats").val(); 
	var ab_blogs 		= jQuery("#ab-blogs").val(); 
	alert('Academic blogs loaded in.');
    jQuery.post(ajaxurl, {
			action: 'ab_add_validator',
			ab_cats: ab_cats,
			ab_subcats: ab_subcats,
			ab_blogs: ab_blogs
		},
		function(response) {
            var alert_string = jQuery(response).find("response_data").text();
            alert(alert_string);

		});
	  });
	  

	
});