wp.api.loadPromise.done(function () {
	//https://github.com/WP-API/client-js/blob/master/js/load.js
	var wpApiSettings = window.wpApiSettings || {};

	wp.api.init({
		'versionString': 'pf/v1',
		'apiRoot': wpApiSettings.root || wp.api.utils.getRootUrl() + 'wp-json/'
	});

	jQuery(document.body).ready(function () {
		window.pf.stats.failSafeGetJSON = function (jsonLocation, callback, getLeaderboardCallback) {
			var dataProcess = function (data) {
				return callback(data);
			};
			var attemptAgain = function () {
				return getLeaderboardCallback(false);
			};
			return jQuery.getJSON(jsonLocation,
				dataProcess
			).fail(function () {
				console.log(' Request failed setting up retry in 15 seconds ');
				var timeout = window.setTimeout(attemptAgain, 15000);
				return timeout;
			});

		};
		window.leaderboardInits = {
			authors: false,
			validPosts: false
		};
		if ('authors' === window.pf.location || (jQuery('body').hasClass("pressforward_page_pf-tools"))) {
			//window.pf.location = 'authors';
			window.pf.stats.authors = {
				endpoint: function () {

					return wp.api.utils.getRootUrl() + 'wp-json/pf/v1/stats/authors';
				},
				arguments: function (page) {
					return window.pf.stats.authors.endpoint() + '?page=' + page;
				},
				pages: 1,
				pagesFull: false,
				leaderboard: {},
				pageFillerFunction: function (pages) {
					return window.pf.stats.failSafeGetJSON(window.pf.stats.authors.arguments(pages),

						function (data) {
							console.log('PF Author Leaderboard Fill', data);
							console.log('PF Check: ', (typeof data.length !== 'undefined') && (0 === data.length));
							if ((typeof data.length !== 'undefined') && (0 === data.length)) {
								window.pf.stats.authors.pagesFull = true;
								console.log('PF Author Leaderboard Complete');
								return true;
							} else {
								console.log('PF Author Leaderboard Fills in', data);
								jQuery.each(data, function (key, val) {
									console.log('PF Author Leaderboard advancing to page ', pages);
									if (window.pf.stats.authors.leaderboard.hasOwnProperty(key)) {
										window.pf.stats.authors.leaderboard[key].count += val.count;
									} else {
										window.pf.stats.authors.leaderboard[key] = val;
									}
								});
								return false;
							}
						},
						window.pf.stats.authors.getLeaderboard
					);
				},
				getLeaderboard: function (isInit) {
					if (isInit) {
						if (window.leaderboardInits.authors) {
							return '';
						}
						window.leaderboardInits.authors = true;
					}
					console.log('PF Author Page set', window.pf.stats.authors.pages);
					var pageFilled = window.pf.stats.authors.pageFillerFunction(window.pf.stats.authors.pages);
					pageFilled.done(function (e) {
						if (window.pf.stats.authors.pagesFull === false) {
							window.pf.stats.authors.pages = window.pf.stats.authors.pages + 1;
							window.pf.stats.authors.getLeaderboard(false);
						} else {
							var sorted = Object.keys(window.pf.stats.authors.leaderboard).sort(function (a, b) {
								return window.pf.stats.authors.leaderboard[b].count - window.pf.stats.authors.leaderboard[a].count
							});
							console.log(sorted);
							var i = 0;
							for (var prop in sorted) {
								// skip loop if the property is from prototype
								if (!sorted.hasOwnProperty(prop)) {
									continue;
								}
								var author = window.pf.stats.authors.leaderboard[sorted[prop]];
								//console.log(author);
								var authorBlock = '<br/><li id="author-' + i + '"><strong>Name:</strong> ' + author.name + '<li><strong>Count:</strong> ' + author.count + '</li></li>';
								jQuery('#author-leaderboard ul').append(authorBlock);
								i++;
							}
							return '';
						}
					});
					return '';
					while (window.pf.stats.authors.pagesFull === false) {
						var pageFilled = window.pf.stats.authors.pageFillerFunction(window.pf.stats.authors.pages);
						console.log('Add to page count. Paged status: ', window.pf.stats.authors.pagesFull);
						if (window.pf.stats.authors.pagesFull) {
							break;
						} else {
							window.pf.stats.authors.pages = window.pf.stats.authors.pages + 1;
						}
					};
				}
			};
		}
		if ('stats' === window.pf.location || (jQuery('body').hasClass("pressforward_page_pf-tools"))) {
			window.pf.stats.valid_posts = {
				endpoint: function () {
					return wp.api.utils.getRootUrl() + 'wp-json/pf/v1/stats/pf_posted';
				},
				arguments: function (page) {
					return window.pf.stats.valid_posts.endpoint() + '?page=' + page;
				},
				pages: 1,
				pagesFull: false,
				postCount: 0,
				leaderboard: {},
				flesch_kincaid_score_total: 0,
				pageFillerFunction: function (pages) {
					return window.pf.stats.failSafeGetJSON(window.pf.stats.valid_posts.arguments(pages),

						function (data) {
							console.log('PF Valid Posts Leaderboard Fill', data);
							console.log('PF Check: ', (typeof data.length !== 'undefined') && (0 === data.length));
							if ((typeof data.length !== 'undefined') && (0 === data.length)) {
								window.pf.stats.valid_posts.pagesFull = true;
								console.log('PF Valid Posts Leaderboard Complete');
								return true;
							} else {
								console.log('PF Valid Posts Leaderboard Fills in', data);
								jQuery.each(data, function (key, val) {
									console.log('PF Valid Posts Leaderboard advancing to page ', pages);
									window.pf.stats.valid_posts.leaderboard[val.ID] = val;
									window.pf.stats.valid_posts.flesch_kincaid_score_total += val.flesch_kincaid_score;
									window.pf.stats.valid_posts.postCount += 1;
								});
								return false;
							}
						},
						window.pf.stats.valid_posts.getLeaderboard
					);
				},
				getLeaderboard: function (isInit) {
					if (isInit) {
						if (window.leaderboardInits.validPosts) {
							return '';
						}
						window.leaderboardInits.validPosts = true;
					}
					//var postsCollection = new wp.api.collections.Posts();
					//postsCollection.fetch({ data: { per_page: 40,  } });
					console.log('PF Valid Posts Page set', window.pf.stats.valid_posts.pages);
					var pageFilled = window.pf.stats.valid_posts.pageFillerFunction(window.pf.stats.valid_posts.pages);
					return pageFilled.done(function (e) {
						console.log('Checking for next step of pages ', window.pf.stats.valid_posts.pages);
						if (window.pf.stats.valid_posts.pagesFull === false) {
							window.pf.stats.valid_posts.pages = window.pf.stats.valid_posts.pages + 1;
							return window.pf.stats.valid_posts.getLeaderboard(false);
						} else {
							var totalPosts = '<strong>Total PressForward Items Published:</strong> ' + window.pf.stats.valid_posts.postCount + '. ';
							jQuery('#top-level').append(totalPosts);
							var totalWordsString = '<strong>Total WordCount:</strong> ' + window.pf.stats.wordcount.total() + '. ';
							window.pf.stats.valid_posts.flesch_kincaid_score_avg = window.pf.stats.valid_posts.flesch_kincaid_score_total / window.pf.stats.valid_posts.postCount;
							jQuery('#top-level').append(totalWordsString);
							var sources = window.pf.stats.sources.total();
							var sourcesSorted = Object.keys(sources).sort(function (a, b) {
								return sources[b] - sources[a]
							});
							console.log(sourcesSorted);
							var i = 0;
							for (var prop in sourcesSorted) {
								// skip loop if the property is from prototype
								if (!sourcesSorted.hasOwnProperty(prop)) {
									continue;
								}
								var source = sources[sourcesSorted[prop]];
								//console.log(author);
								var sourceBlock = '<br/><li id="source-' + i + '"><strong>Items from</strong> <a href="' + sourcesSorted[prop] + '" target="_blank">' + sourcesSorted[prop] + '</a>: ' + source + '</li>';
								jQuery('#sources-leaderboard ul').append(sourceBlock);
								i++;
							}
							return '';
						}
					});
				}
			};
			window.pf.stats.wordcount = {
				limits: [],
				count: 0,
				results: {},
				setLimit: function (key, value) {
					window.pf.stats.wordcount.limits.push({
						'key': key,
						'value': value
					});
				},
				pullPostsTogether: function (resultTag) {
					if (!window.pf.stats.valid_posts.pagesFull) {
						window.pf.stats.valid_posts.getLeaderboard(false);
					}
					window.pf.stats.wordcount.count = 0;
					jQuery.each(
						window.pf.stats.valid_posts.leaderboard,
						function (index) {
							window.pf.stats.wordcount.count += window.pf.stats.valid_posts.leaderboard[index].wordcount;
						}
					);
					if (undefined === resultTag) {
						resultTag = new Date().getTime();
					}
					window.pf.stats.wordcount.results[resultTag] = JSON.parse(window.pf.stats.wordcount.count);
				},
				total: function () {
					window.pf.stats.wordcount.pullPostsTogether('total');
					return window.pf.stats.wordcount.results.total;
				}
			}
			window.pf.stats.sources = {
				limits: [],
				count: 0,
				results: {},
				resultObj: {},
				setLimit: function (key, value) {
					window.pf.stats.sources.limits.push({
						'key': key,
						'value': value
					});
				},
				assemble: function (resultTag) {
					window.pf.stats.sources.count = 0;
					window.pf.stats.sources.resultObj = {};
					jQuery.each(
						window.pf.stats.valid_posts.leaderboard,
						function (index) {
							var source = window.pf.stats.sources.source_link;
							if (undefined === window.pf.stats.sources.resultObj[source]) {
								window.pf.stats.sources.resultObj[source] = 1;
							} else {
								window.pf.stats.sources.resultObj[source] += 1;
							}
						}
					);
					if (undefined === resultTag) {
						resultTag = new Date().getTime();
					}

					window.pf.stats.sources.results[resultTag] = JSON.parse(JSON.stringify(window.pf.stats.sources.resultObj));
				},
				pullPostsTogether: function (resultTag) {
					if (!window.pf.stats.valid_posts.pagesFull) {
						window.pf.stats.valid_posts.getLeaderboard(false);
						window.pf.stats.sources.assemble(resultTag);
					} else {
						window.pf.stats.sources.assemble(resultTag);
					}
				},
				total: function () {
					window.pf.stats.sources.pullPostsTogether('total');
					return window.pf.stats.sources.results.total;
				}
			}
		}
	});
});
