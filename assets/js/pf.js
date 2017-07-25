window.pf = {
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
wp.api.loadPromise.done( function() {
	//https://github.com/WP-API/client-js/blob/master/js/load.js
	wp.api.init({'versionString' : 'pf/v1',  'apiRoot': wp.api.utils.getRootUrl()+'wp-json/' });

	jQuery(document.body).ready( function(){
		if ( 'authors' === window.pf.location || ( jQuery('body').hasClass("pressforward_page_pf-tools") ) ){

			window.pf.stats.authors = {
				endpoint: function(){

					return wp.api.utils.getRootUrl()+'wp-json/pf/v1/stats/authors';
				},
				arguments: function( page ){
					return this.endpoint()+'?page='+page;
				},
				pages: 1,
				pagesFull: false,
				leaderboard: {},
				pageFillerFunction: function( pages ){
					return jQuery.getJSON(window.pf.stats.authors.arguments(pages),
						function( data ){
							console.log('PF Author Leaderboard Fill', data);
							console.log('PF Check: ', ( typeof data.length !== 'undefined' ) && ( 0 === data.length ));
							if ( ( typeof data.length !== 'undefined' ) && ( 0 === data.length ) ){
								window.pf.stats.authors.pagesFull = true;
								console.log('PF Author Leaderboard Complete');
								return true;
							} else {
								console.log('PF Author Leaderboard Fills in', data);
								jQuery.each( data, function( key, val ) {
									console.log('PF Author Leaderboard advancing to page ', pages);
									if ( window.pf.stats.authors.leaderboard.hasOwnProperty(key) ){
										window.pf.stats.authors.leaderboard[key].count += val.count;
									} else {
										window.pf.stats.authors.leaderboard[key] = val;
									}
								});
								return false;
							}
						}
					);
				},
				getLeaderboard: function(){
					console.log('PF Author Page set', window.pf.stats.authors.pages);
					var pageFilled = this.pageFillerFunction(window.pf.stats.authors.pages);
					pageFilled.done(function(e){
						if ( window.pf.stats.authors.pagesFull === false ){
							window.pf.stats.authors.pages = window.pf.stats.authors.pages+1;
							window.pf.stats.authors.getLeaderboard();
						}
					});
					return '';
					while ( window.pf.stats.authors.pagesFull === false ) {
						var pageFilled = this.pageFillerFunction(window.pf.stats.authors.pages);
						console.log('Add to page count. Paged status: ', window.pf.stats.authors.pagesFull);
						if ( window.pf.stats.authors.pagesFull ){
							break;
						} else {
							window.pf.stats.authors.pages = window.pf.stats.authors.pages+1;
						}
					};
				},
			};
		}
	});
});
