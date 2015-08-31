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
		if (jQuery(obj).hasClass('schema-switchable')) {
			isSwitch = 'on';
		} else {
			isSwitch = 'off';
		}
		do_schema_stuff(id,schema,isSwitch);
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

	function do_schema_stuff(item_id, schema, isSwitch){
		var objs = jQuery('article[pf-post-id="'+item_id+'"] [pf-schema="'+schema+'"]');
		var switchable = 'on';
		if ('off' != isSwitch){
			isSwitch = switchable;
			//console.log('Switchable state is not set, or set to on.');
		}
		objs.each( function(index) {
			//console.log(this);
			var obj = jQuery(this);
			var is_active = obj.hasClass('schema-active');
			var is_switchable = obj.hasClass('schema-switchable');
			var schema_class = 'schema-active';
			if (obj.is('[pf-schema-class]')){
				var added_class = obj.attr('pf-schema-class');
				schema_class = schema_class+' '+added_class;
				if (!is_active){ is_active = obj.hasClass(added_class); }
			}
			if ('off' == isSwitch){
				is_switchable = false;
				//console.log('Switchable is set to off.');
			}
			if (obj.is('[pf-schema-targets]')){
				objs.push(jQuery(obj.attr('pf-schema-targets')));
			}
			if 		  (is_active && is_switchable)  {
				obj.removeClass(schema_class);

			} else if (is_active && !is_switchable) {
				console.log('This is already active.')

			} else if (!is_active && is_switchable) {
				obj.addClass(schema_class);

			} else if (!is_active && !is_switchable){
				obj.addClass(schema_class);
			} else {
				console.log('Something has gone wrong with the schema switch.');
			}
		});

	}

});
