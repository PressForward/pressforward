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
			//window.pf.location = 'authors';
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
		if ( 'stats' === window.pf.location || ( jQuery('body').hasClass("pressforward_page_pf-tools") ) ){
			window.pf.stats.valid_posts = {
				endpoint: function(){
					return wp.api.utils.getRootUrl()+'wp-json/pf/v1/stats/pf_posted';
				},
				arguments: function( page ){
					return this.endpoint()+'?page='+page;
				},
				pages: 1,
				pagesFull: false,
				leaderboard: {},
				pageFillerFunction: function( pages ){
					return jQuery.getJSON(window.pf.stats.valid_posts.arguments(pages),
						function( data ){
							console.log('PF Valid Posts Leaderboard Fill', data);
							console.log('PF Check: ', ( typeof data.length !== 'undefined' ) && ( 0 === data.length ));
							if ( ( typeof data.length !== 'undefined' ) && ( 0 === data.length ) ){
								window.pf.stats.valid_posts.pagesFull = true;
								console.log('PF Valid Posts Leaderboard Complete');
								return true;
							} else {
								console.log('PF Valid Posts Leaderboard Fills in', data);
								jQuery.each( data, function( key, val ) {
									console.log('PF Valid Posts Leaderboard advancing to page ', pages);
									window.pf.stats.valid_posts.leaderboard[key] = val;
								});
								return false;
							}
						}
					);
				},
				getLeaderboard: function(){
					//var postsCollection = new wp.api.collections.Posts();
					//postsCollection.fetch({ data: { per_page: 40,  } });
					console.log('PF Valid Posts Page set', window.pf.stats.valid_posts.pages);
					var pageFilled = this.pageFillerFunction(window.pf.stats.valid_posts.pages);
					return pageFilled.done(function(e){
						console.log('Checking for next step of pages ', window.pf.stats.valid_posts.pages);
						if ( window.pf.stats.valid_posts.pagesFull === false ){
							window.pf.stats.valid_posts.pages = window.pf.stats.valid_posts.pages+1;
							return window.pf.stats.valid_posts.getLeaderboard();
						}
					});
				}
			};
			window.pf.stats.wordcount = {
				limits: [],
				count: 0,
				results: {},
				setLimit: function(key, value){
					this.limits.push( { 'key': key, 'value': value } );
				},
				pullPostsTogether: function( resultTag ) {
					if ( !window.pf.stats.valid_posts.pagesFull ){
						window.pf.stats.valid_posts.getLeaderboard();
					}
					window.pf.stats.wordcount.count = 0;
					jQuery.each(
						window.pf.stats.valid_posts.leaderboard,
						function( index ){
							window.pf.stats.wordcount.count += window.pf.stats.valid_posts.leaderboard[index].wordcount;
						}
					);
					if ( undefined === resultTag ){
						resultTag = new Date().getTime();
					}
					this.results[resultTag] = JSON.parse(window.pf.stats.wordcount.count);
				},
				total: function(){
					this.pullPostsTogether( 'total' );
					return this.results.total;
				}
			}
			window.pf.stats.sources = {
				limits: [],
				count: 0,
				results: {},
				resultObj: {},
				setLimit: function(key, value){
					this.limits.push( { 'key': key, 'value': value } );
				},
				assemble: function (resultTag){
					window.pf.stats.wordcount.count = 0;
					jQuery.each(
						window.pf.stats.valid_posts.leaderboard,
						function( index ){
							var source = window.pf.stats.valid_posts.leaderboard[index].source;
							if (undefined === window.pf.stats.sources.resultObj[source]){
								window.pf.stats.sources.resultObj[source] = 1;
							} else {
								window.pf.stats.sources.resultObj[source] += 1;
							}
						}
					);
					if ( undefined === resultTag ){
						resultTag = new Date().getTime();
					}
					this.results[resultTag] = JSON.parse(JSON.stringify(window.pf.stats.wordcount.count));
				},
				pullPostsTogether: function( resultTag ) {
					if ( !window.pf.stats.valid_posts.pagesFull ){
						window.pf.stats.valid_posts.getLeaderboard();
						window.pf.stats.sources.assemble(resultTag);
					} else {
						window.pf.stats.sources.assemble(resultTag);
					}
				},
				total: function(){
					this.pullPostsTogether( 'total' );
					return this.results.total;
				}
			}
		}
	});
});
