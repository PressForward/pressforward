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
		var objs = jQuery('article[pf-item-post-id='+id+'] .star-item');
		jQuery( objs ).each(function( index ) {
			var obj = jQuery(this);
			if (jQuery(obj).hasClass('btn-warning')){

				jQuery(obj).removeClass('btn-warning');
			} else {

				jQuery(obj).addClass('btn-warning');
			}
		});

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
		var objs;
		objs = jQuery('article[pf-post-id="'+id+'"] [pf-schema="'+schema+'"]');
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
		if(jQuery(selectableObj.selector).is('[pf-schema-class]')){
			schemaclass = jQuery(selectableObj.selector).attr('pf-schema-class');
		} else {
			schemaclass = false;
		}
		
		
		
		if(obj.is('[pf-schema-targets]')){
			schematargets = jQuery(this).attr('pf-schema-targets');
		} else {
			schematargets = false;
		}
		
		if ((schematargets != false) || (null != schematargets) || (typeof schematargets != 'undefined') ){
			targetedObj = jQuery(this).closest('article').find('.'+schematargets);

			if(targetedObj.is('[pf-schema-class]')){
				tschemaclass = targetedObj.attr('pf-schema-class');
			} else {
				tschemaclass = false;
			}
			doschemastuff(targetedObj, item, id, parent, otherschema, tschemaclass, objs);
		} else {

			doschemastuff(selectableObj, item, id, parent, otherschema, schemaclass, objs);
		
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


	function doschemastuff(obj, item, id, parent, otherschema, schemaclass, objs){
		otherschema = jQuery(otherschema.selector);
		//console.log(otherschema);
		obj = jQuery(obj.selector);
		//var is_it_done = false;
		//console.log(objs);
		jQuery( objs ).each(function( index ) {
			var obj = jQuery(this);
			var schemaclass = obj.attr('pf-schema-class');
			
						
			if ((schemaclass != false) && (typeof schemaclass != 'undefined')){

				if (obj.hasClass(schemaclass) && obj.hasClass('schema-switchable')){
					console.log(obj);
					console.log('Switchable schema class ' +schemaclass+': on - turn it off');
					obj.removeClass(schemaclass);
					
					console.log(otherschema);
					otherschema.removeClass(schemaclass);
					return false;
				} else {
					console.log(obj);
					console.log('Switchable schema class ' +schemaclass+': off - turn it on');
					obj.addClass(schemaclass);
					
					console.log(otherschema);
					otherschema.addClass(schemaclass);
					return false;
				}

			}
			
			if (obj.hasClass('schema-active') && obj.hasClass('schema-switchable')){
				console.log(obj);
				console.log('Switchable Active schema-active: class');
				obj.removeClass('schema-active');
				
				console.log(otherschema);
				otherschema.removeClass('schema-active');
				return false;
			} else {
				console.log(obj);
				console.log('Non-switchable schema-active: make active');
				obj.addClass('schema-active');
				
				console.log(otherschema);
				otherschema.addClass('schema-active');
				return false;
			}
		});


	}

});
