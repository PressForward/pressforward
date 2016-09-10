window.pf = {
	toggler: function( evt, elem, functionOne, functionTwo ){
		evt.preventDefault();
		var toggleState = jQuery(elem).attr('data-toggle-state');
		if ( undefined !== toggleState && 'toggled' === toggleState ){
			functionOne(evt, elem, toggleState);
			jQuery(elem).attr('data-toggle-state', 'off');
		} else {
			functionTwo(evt, elem, toggleState);
			jQuery(elem).attr('data-toggle-state', 'toggled');
		}
	},
};