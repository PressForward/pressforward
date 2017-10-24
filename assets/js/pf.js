window.pf = window.pf || {};

var pf_setup = {
	location: 'pf',
	toggler: function( evt, elem, functionOne, functionTwo ){
		evt.preventDefault();
		var toggleState = jQuery( elem ).attr( 'data-toggle-state' );
		if ( undefined !== toggleState && 'toggled' === toggleState ) {
			functionTwo( evt, elem, toggleState );
			jQuery( elem ).attr( 'data-toggle-state', 'off' );
		} else {
			functionOne( evt, elem, toggleState );
			jQuery( elem ).attr( 'data-toggle-state', 'toggled' );
		}
	},
	endpoint: function(){
		return wp.api.utils.getRootUrl()+'wp-json/pf/v1/';
	},
	stats: {},
	onWindowLoader: function(callbackName){
		if(window.attachEvent) {
		    window.attachEvent('onload', callbackName);
		} else {
		    if(window.onload) {
		        var curronload = window.onload;
		        var newonload = function(evt) {
		            curronload(evt);
		            callbackName(evt);
		        };
		        window.onload = newonload;
		    } else {
		        window.onload = callbackName;
		    }
		}
	}
};
window.pf = Object.assign( window.pf, pf_setup );
