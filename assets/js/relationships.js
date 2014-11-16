jQuery(window).load(function() {
	jQuery('.pf_container').on('click', '.star-item', function(evt){
		evt.preventDefault();
		var obj			= jQuery(this);
		var item 		= jQuery(this).closest('article');
		var id			= item.attr('pf-item-post-id');
		var parent		= jQuery(this).parent();
		var otherstar;
		if (parent.hasClass('modal-btns')){
			otherstar = item.find('header .star-item');
		} else {
			otherstar = item.find('.modal .star-item');
		}
		dostarstuff(obj, item, id, parent, otherstar);
		jQuery.post(ajaxurl, {
				action: 'pf_ajax_star',
				//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.
				post_id: id
		},
		function(response) {
			var read_content = jQuery(response).find("response_data").text();
			if (read_content != false){
				//alert(otherstar);

			} else {
				alert('PressForward was unable to access the relationships database.');
			}
		});


	});

	function dostarstuff(obj, item, id, parent, otherstar){
		if (jQuery(obj).hasClass('btn-warning')){

			jQuery(obj).removeClass('btn-warning');
			otherstar.removeClass('btn-warning');
		} else {

			jQuery(obj).addClass('btn-warning');
			otherstar.addClass('btn-warning');
		}
	}

	jQuery('.pf_container').on('click', '.schema-actor', function(evt){
		evt.preventDefault();
		var obj			= jQuery(this);
		var schema		= obj.attr('pf-schema');
		var item 		= jQuery(this).closest('article');
		var id			= item.attr('pf-post-id');
		var parent		= jQuery(this).closest('.pf-btns');
		var otherschema;
		var schemaclass;
		var isSwitch	= 'off';
		var schematargets;
		var targetedObj;
		var tschemaclass;
		var selectableObj;
		if (parent.hasClass('modal-btns')){
			otherschema = item.find('article[pf-post-id="'+id+'"] header [pf-schema="'+schema+'"]');
			selectableObj = item.find('article[pf-post-id="'+id+'"] .modal-btns [pf-schema="'+schema+'"]');
		} else {
			otherschema = item.find('article[pf-post-id="'+id+'"] .modal-btns [pf-schema="'+schema+'"]');
			selectableObj = item.find('article[pf-post-id="'+id+'"] header [pf-schema="'+schema+'"]');
		}
		if (jQuery(obj).hasClass('schema-switchable')) {
			isSwitch = 'on';
		}

		if(jQuery(selectableObj).is('[pf-schema-class]')){
			schemaclass = jQuery(selectableObj).attr('pf-schema-class');
		} else {
			schemaclass = false;
		}
		if(obj.is('[pf-schema-targets]')){
			schematargets = jQuery(this).attr('pf-schema-targets');
		} else {
			schematargets = false;
		}
		doschemastuff(selectableObj, item, id, parent, otherschema, schemaclass);

		if ((schematargets != false)){
			targetedObj = jQuery(this).closest('article').find('.'+schematargets);

			if(targetedObj.is('[pf-schema-class]')){
				tschemaclass = targetedObj.attr('pf-schema-class');
			} else {
				tschemaclass = false;
			}
			doschemastuff(targetedObj, item, id, parent, otherschema, tschemaclass);
		}

		jQuery.post(ajaxurl, {
				action: 'pf_ajax_relate',
				//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.
				post_id: id,
				schema: schema,
				isSwitch: isSwitch
		},
		function(response) {
			var read_content = jQuery(response).find("response_data").text();
			if (read_content != false){
				//alert(otherschema.attr('id'));

			} else {
				alert('PressForward was unable to access the relationships database.');
			}
		});


	});


	function doschemastuff(obj, item, id, parent, otherschema, schemaclass){
		console.log('schemaclass');
		console.log(schemaclass);
		otherschema = jQuery(otherschema);
		console.log('otherschema');
		console.log(otherschema);
		console.log('obj');
		console.log(obj);
		if (obj.hasClass('schema-active') && obj.hasClass('schema-switchable')){
			obj.removeClass('schema-active');
			otherschema.removeClass('schema-active');
		} else {
			obj.addClass('schema-active');
			otherschema.addClass('schema-active');
		}

		if (schemaclass != false){

			if (obj.hasClass(schemaclass) && obj.hasClass('schema-switchable')){

				obj.removeClass(schemaclass);
				otherschema.removeClass(schemaclass);
			} else {
				obj.addClass(schemaclass);
				otherschema.addClass(schemaclass);
			}

		}
		
		

	}

});
