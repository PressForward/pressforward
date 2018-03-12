/**
 * Display transform for pf
 **/

function pf_make_url_hashed(hashed) {
	//via http://stackoverflow.com/questions/1844491/intercepting-call-to-the-back-button-in-my-ajax-application-i-dont-want-it-to
	window.location.hash = '#' + hashed;
}

function assure_closed_menus() {
	jQuery('.dropdown li > *').on('click', function () {
		jQuery('.dropdown.open').removeClass('open');
	});
}

//via http://stackoverflow.com/questions/1662308/javascript-substr-limit-by-word-not-char
function trim_words(theString, numWords) {
	expString = theString.split(/\s+/, numWords);
	theNewString = expString.join(" ");
	return theNewString;
}

function assure_next_obj(tabindex, obj, advance) {
	var lastobj = jQuery('article:last-child');
	var lastindex = lastobj.attr('tabindex');
	// If we've hidden a next object, the tabs won't adjust, so check and fix.
	if ((0 == obj.length) || obj.is(':hidden')) {
		if (1 == advance) {
			tabindex = tabindex + 1;
		} else {
			tabindex = tabindex - 1;
		}
		obj = jQuery('article[tabindex="' + tabindex + '"]');
	}
	if ((0 == obj.length || obj.is(':hidden')) && (0 <= tabindex) && (lastindex > tabindex)) {
		obj = assure_next_obj(tabindex, obj, advance);
	}
	if (obj.is(':hidden')) {
		return false;
	}
	return obj;
}


function modalNavigator(tabindex) {
	tabindex = parseInt(tabindex);
	var currentObj = jQuery('article[tabindex="' + tabindex + '"]');
	//alert(tabindex);
	var currentID = jQuery(currentObj).attr('id');
	var prevTab = tabindex - 1;
	var nextTab = tabindex + 1;
	var prevObj = jQuery('article[tabindex="' + prevTab + '"]');
	var nextObj = jQuery('article[tabindex="' + nextTab + '"]');
	var modalID = currentObj.children('header').children('h1').children('a').attr('href');

	// If we've hidden a previous object, the tabs won't adjust, so check and fix.
	prevObj = assure_next_obj(prevTab, prevObj, 0);
	nextObj = assure_next_obj(nextTab, nextObj, 1);
	/**		if (jQuery.isEmptyObject(prevObj)){
				prevTab = prevTab-1;
				prevObj = jQuery('article[tabindex="'+prevTab+'"]');
			}

			// If we've hidden a next object, the tabs won't adjust, so check and fix.
			if (jQuery.isEmptyObject(nextObj)){
				nextTab = nextTab+1;
				nextObj = jQuery('article[tabindex="'+nextTab+'"]');
			}
	**/
	//First lets assemble variables for the previous group.
	if (false != jQuery(prevObj) && jQuery(prevObj).is('*')) {
		var prevItemID = jQuery(prevObj).children('header').children('h1').children('a').attr('href');
		var prevTitle = jQuery(prevObj).children('header').children('h1').text();
		var prevSource = jQuery(prevObj).children('header').children('p.source_title').text();
		var prevAuthor = jQuery(prevObj).children('header').children('div.feed-item-info-box').children('span.item_authors').text();
		var prevExcerpt = jQuery(prevObj).children('div.content').children('div.item_excerpt').text();
		prevExcerpt = trim_words(prevExcerpt, 20);
		var prevDate = jQuery(prevObj).children('footer').children('p.pubdate').text();

		var prevHTML = '<h5 class="prev_title">Previous: <a href="' + prevItemID + '" role="button" class="modal-nav" data-dismiss="modal" data-toggle="modal" data-backdrop="false">' + prevTitle + '</a></h5>';
		prevHTML += '<p class="prev_source_title">' + prevSource + '</p>';
		prevHTML += '<p class="prev_author">' + prevAuthor + '</p>';
		prevHTML += '<p class="prev_excerpt">' + prevExcerpt + '</p>';
		prevHTML += '<p class="prev_date">' + prevDate + '</p>';
		//alert(modalID);
		jQuery(modalID + ' div.modal-body-row div.modal-sidebar div.goPrev').html(prevHTML);
		jQuery(modalID + ' div.mobile-goPrev').html('<i class="icon-arrow-left"></i> <a href="' + prevItemID + '" role="button" data-dismiss="modal" class="mobile-modal-navlink modal-nav" data-toggle="modal" data-backdrop="false">' + prevTitle + '</a> ');


	}
	//Next lets assemble variables for the next group.
	if (false != jQuery(nextObj) && jQuery(nextObj).is('*')) {
		var nextItemID = jQuery(nextObj).children('header').children('h1').children('a').attr('href');
		var nextTitle = jQuery(nextObj).children('header').children('h1').text();
		var nextSource = jQuery(nextObj).children('header').children('p.source_title').text();
		var nextAuthor = jQuery(nextObj).children('header').children('div.feed-item-info-box').children('span.item_authors').text();
		var nextExcerpt = jQuery(nextObj).children('div.content').children('div.item_excerpt').text();
		nextExcerpt = trim_words(nextExcerpt, 20);
		var nextDate = jQuery(nextObj).children('footer').children('p.pubdate').text();

		var nextHTML = '<h5 class="next_title">Next: <a href="' + nextItemID + '" role="button" class="modal-nav" data-dismiss="modal" data-toggle="modal" data-backdrop="false">' + nextTitle + '</a></h5>';
		nextHTML += '<p class="next_source_title">' + nextSource + '</p>';
		nextHTML += '<p class="next_author">' + nextAuthor + '</p>';
		nextHTML += '<p class="next_excerpt">' + nextExcerpt + '</p>';
		nextHTML += '<p class="next_date">' + nextDate + '</p>';
		//alert(modalID);
		jQuery(modalID + ' div.modal-body-row div.modal-sidebar div.goNext').html(nextHTML);
		jQuery(modalID + ' div.mobile-goNext').html('&nbsp;| <a href="' + nextItemID + '" role="button" class="mobile-modal-navlink modal-nav" data-dismiss="modal" data-toggle="modal" data-backdrop="false">' + nextTitle + '</a> <i class="icon-arrow-right"></i>');

	}


}

function commentPopModal() {

	jQuery('.pf_container').on('shown', '.modal.comment-modal', function (evt) {
		var elementC = jQuery(this);
		var element = elementC.closest('article');
		var modalID = elementC.closest('article').attr('id');
		var modalIDString = '#' + modalID;
		//openModals.push(modalIDString);
		//alert(modalID);
		//showDiv(jQuery('#entries'), jQuery('#'+modalID));
		var itemID = element.attr('pf-item-id');
		var postID = element.attr('pf-post-id');
		var item_post_ID = element.attr('pf-item-post-id');
		jQuery('#ef-comments_wrapper').remove();
		//alert(modalIDString);
		jQuery.post(ajaxurl, {
				action: 'pf_ajax_get_comments',
				//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.
				id_for_comments: item_post_ID,
			},
			function (comment_response) {
				jQuery('#comment_modal_' + item_post_ID + ' .modal-body').html(comment_response);
			});
	});

	jQuery('.pf_container').on('hide', '.modal.comment-modal', function (evt) {
		jQuery('#ef-comments_wrapper').remove();
	});
}

function reshowModal() {
	jQuery('.pf_container').on('show', '.modal.pfmodal', function (evt) {
		var element = jQuery(this);
		var modalID = element.attr('id');
		pf_make_url_hashed(modalID);
	});

	jQuery('.pf_container').on('shown', '.modal.pfmodal', function (evt) {
		var element = jQuery(this);
		var modalID = element.attr('id');
		document.body.style.overflow = 'hidden';
		var bigModal = {
			'display': 'block',
			'position': 'fixed',
			'top': '0',
			'right': '0',
			'bottom': '100%',
			'left': '0',
			'margin': '0',
			'width': '100%',
			'height': '100%',
			'overflow': 'hidden',
			'z-index': '9999'
		};
		jQuery('#' + modalID + '.pfmodal').css(bigModal).load(hide_non_modals());

	});
}

function hide_non_modals() {
	jQuery('#wpadminbar').hide();
	jQuery('#adminmenuback').hide();
	jQuery('#adminmenuwrap').hide();
	jQuery('#wpfooter').hide();
}

function reviewModal() {
	//Need to fix this to only trigger on the specific model, but not sure how yet.

	jQuery('.pressforward_page_pf-review .pf_container').on('shown', ".modal.pfmodal", function (evt) {
		//alert('Modal Triggered.');

		var element = jQuery(this);
		var modalID = element.attr('id');
		var modalIDString = '#' + modalID;
		//openModals.push(modalIDString);
		//alert(modalID);
		//showDiv(jQuery('#entries'), jQuery('#'+modalID));
		//var itemID = element.attr('pf-item-id');
		//var postID = element.attr('pf-post-id');
		var item_post_ID = element.parent().attr('pf-item-post-id');

		jQuery.post(ajaxurl, {
				action: 'pf_ajax_get_comments',
				//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.
				id_for_comments: item_post_ID,
			},
			function (comment_response) {

				jQuery('#' + modalID + '.pfmodal .modal-comments').html(comment_response);

			});


		var tabindex = element.parent().attr('tabindex');

		modalNavigator(tabindex);
	});
}

function hideModal() {
	jQuery('.pf_container').on('hide', ".modal.pfmodal", function (evt) {
		jQuery(".pfmodal .modal-comments").html('');
		if (typeof editorialCommentReply == 'function') {
			editorialCommentReply.close();
		}
		jQuery('#wpadminbar').show();
		jQuery('#adminmenuback').show();
		jQuery('#adminmenuwrap').show();
		jQuery('#wpfooter').show();
		document.body.style.overflow = 'visible';
		pf_make_url_hashed('ready');
	});
}

function commentModal() {
	jQuery('.pf_container').on('show', '.comment-modal', function (evt) {
		var element = jQuery(this);
		var modalID = element.parent('article').attr('id');
		var modalIDString = '#' + modalID;
		//openModals.push(modalIDString);
		//alert(modalID);
		//showDiv(jQuery('#entries'), jQuery('#'+modalID));
		var itemID = element.attr('pf-item-id');
		var postID = element.attr('pf-post-id');
		var item_post_ID = element.parent().attr('pf-item-post-id');

		jQuery.post(ajaxurl, {
				action: 'pf_ajax_get_comments',
				//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.
				id_for_comments: item_post_ID,
			},
			function (comment_response) {
				jQuery('#' + modalID + '.comment-modal .modal-body').html(comment_response);
			});
	});
}

function PFBootstrapInits() {

	jQuery('.nom-to-archive').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Item'

	});
	jQuery('.nom-to-draft').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Item'

	});
	jQuery('.nominate-now').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Nominate'

	});
	jQuery('.star-item').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Star'

	});
	jQuery('.itemInfobutton').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Star'

	});
	jQuery('.itemCommentModal').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Comment'
	});
	jQuery('.nom-count').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Nomination Count'
	});
	jQuery('.pf-amplify').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Amplify'
	});
	jQuery('.meta_form_modal-button').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: 'Edit Metadata'
	});
	jQuery('.itemInfobutton').popover({
			html: true,
			title: '',
			container: '.icon-info-sign',
			content: function () {
				var idCode = jQuery(this).attr('data-target');
				var contentOutput = '<div class="feed-item-info-box">';
				contentOutput += jQuery('#info-box-' + idCode).html();
				contentOutput += '</div>';
				console.log('Popover assembled');
				return contentOutput;
			}
		})
		.on("click", function () {
			jQuery('.popover').addClass(jQuery(this).data("class")); //Add class .dynamic-class to < div>
		});

	jQuery(".modal.pfmodal").on('hide', function (evt) {
		jQuery(".itemInfobutton").popover('hide');
	});
	jQuery(".modal.pfmodal").on('show', function (evt) {
		jQuery(".itemInfobutton").popover('hide');
	});

	jQuery('.info-box-popover').on('click', function (e) {
		e.stopPropagation();
	});

	jQuery('.itemInfobutton').on('click', function (e) {
		e.stopPropagation();
	});

	jQuery(document).on('click', function (e) {
		// Do whatever you want; the event that'd fire if the "special" element has been clicked on has been cancelled.
		jQuery(".itemInfobutton").popover('hide');
	});

	attach_menu_on_scroll_past();
	assure_closed_menus();

}

function attach_menu_on_scroll_past() {
	jQuery(window).scroll(function () {
		var y_scroll_pos = window.pageYOffset;
		var scroll_pos_test = 90;
		// set to whatever you want it to be

		if (y_scroll_pos > scroll_pos_test) {
			jQuery('.pf_container .display').addClass('nav-fix');
			jQuery('.pf_container #feed-folders').addClass('right-bar-fix');
			jQuery('.pf_container #tools').addClass('right-bar-fix');

			jQuery('#fullscreenfeed').appendTo('.display .pf-btns');

			jQuery('#feeds-search').insertAfter('.display .pf-btns').addClass('pull-left search-in-bar');
			jQuery('#feeds-search > *').addClass('pull-left');
			jQuery('#feeds-search > label').hide();

			var width = jQuery('#entries').innerWidth();
			jQuery('.nav-fix').width(width - 80);
		} else {
			jQuery('.pf_container .display').removeClass('nav-fix');
			jQuery('.pf_container #feed-folders').removeClass('right-bar-fix');
			jQuery('.pf_container #tools').removeClass('right-bar-fix');

			jQuery('#fullscreenfeed').insertAfter('#h-after');

			jQuery('#feeds-search').appendTo('#app-banner').removeClass('pull-left search-in-bar');
			jQuery('#feeds-search > *').removeClass('pull-left');

			jQuery('.pf_container .display').width('auto');
		}
	});

	jQuery(window).resize(function () {
		var width = jQuery('#entries').innerWidth();
		jQuery('.nav-fix').width(width - 80);
		if (40 > jQuery('#adminmenuback').width()) {
			//jQuery('.nav-fix').css('left', '80px');
		} else {
			//jQuery('.nav-fix').css('left', '200px');
		}
	});
}

function detect_view_change() {

	jQuery('.pf_container').on('click', '.pf-top-menu-selection.display-state', function (evt) {
		var element = jQuery(this);
		var go_layout = element.attr('id');
		console.log(go_layout);
		//alert(modalIDString);
		jQuery.post(ajaxurl, {
				action: 'pf_ajax_retain_display_setting',
				pf_read_state: go_layout

			},
			function (response) {

			});
	});

	var is_pf_open = false;

	jQuery('.pressforward #wpbody').on('click', '.list .amplify-group .pf-amplify', function (evt) {
		var element = jQuery(this);
		//console.log(element);
		var parent_e = element.parents('article');
		var parent_h = parent_e.height();
		//console.log(parent_h);
		if (element.hasClass('amplify-down')) {
			element.removeClass('amplify-down');
			jQuery(parent_e).removeClass('show-overflow');
			jQuery(parent_e).css('height', '');
		} else {
			element.addClass('amplify-down');
			jQuery(parent_e).addClass('show-overflow');
			jQuery(parent_e).height(parent_h);
			is_pf_open = true;
		}
	});

	jQuery('.pressforward #wpbody').on('click', '.list div:not(.amplify-group.open)', function (evt) {
		var element_p = jQuery('.amplify-group.open');
		//console.log(element_p);
		if (is_pf_open) {
			//console.log(element_p.length);
			var element = element_p.find('.pf-amplify');
			var parent_e = element.parents('article');
			var parent_h = parent_e.height();
			//console.log(parent_h);
			element.removeClass('amplify-down');
			jQuery(parent_e).removeClass('show-overflow');
			jQuery(parent_e).css('height', '');
		}
	});

	jQuery('.pressforward #wpbody').on('click', '.grid .amplify-group .pf-amplify', function (evt) {
		var element = jQuery(this);
		//console.log(element);
		var parent_e = element.parents('article');
		var parent_h = parent_e.height();
		var parent_head = parent_e.find('header');
		//console.log(parent_h);
		if (element.hasClass('amplify-down')) {
			parent_e.removeClass('amplify-down');
		} else {
			parent_e.addClass('amplify-down');
			is_pf_open = true;
		}
	});

	jQuery('.pressforward #wpbody').on('click', '.grid div:not(.amplify-group.open)', function (evt) {
		var element_p = jQuery('.amplify-group.open');
		//console.log(element_p);
		if (is_pf_open) {
			//console.log(element_p.length);
			var element = element_p.find('.pf-amplify');
			var parent_e = element.parents('article');
			var parent_h = parent_e.height();
			//console.log(parent_h);
			parent_e.removeClass('amplify-down');
		}
	});

}


console.log('Waiting for load.');
jQuery(window).load(function () {
	// executes when complete page is fully loaded, including all frames, objects and images

	jQuery('.pf-loader').delay(300).fadeOut("slow", function () {
		console.log('Load complete.');
		jQuery('.pf_container').fadeIn("slow");
		if (window.location.hash.indexOf("#") < 0) {
			window.location.hash = '#ready';
		} else if ((window.location.hash.toLowerCase().indexOf("modal") >= 0)) {
			var hash = window.location.hash;
			if (!jQuery(hash).hasClass('in')) {
				jQuery(hash).modal('show');
			}
		}

		jQuery(window).on('hashchange', function () {
			if (window.location.hash == '#ready') {
				jQuery('.modal').modal('hide');
			}
			if ((window.location.hash.toLowerCase().indexOf("modal") >= 0)) {
				var hash = window.location.hash;
				if (!jQuery(hash).hasClass('in')) {
					jQuery(hash).modal('show');
				}
			}
		});

	});

});

// Via http://stackoverflow.com/a/1634841/2522464
function removeURLParameter(url, parameter) {
	//prefer to use l.search if you have a location/link object
	var urlparts = url.split('?');
	if (urlparts.length >= 2) {

		var prefix = encodeURIComponent(parameter) + '=';
		var pars = urlparts[1].split(/[&;]/g);

		//reverse iteration as may be destructive
		for (var i = pars.length; i-- > 0;) {
			//idiom for string.startsWith
			if (pars[i].lastIndexOf(prefix, 0) !== -1) {
				pars.splice(i, 1);
			}
		}

		url = urlparts[0] + '?' + pars.join('&');
		return url;
	} else {
		return url;
	}
}

jQuery(window).load(function () {

	jQuery('#gogrid').click(function (evt) {
		evt.preventDefault();
		jQuery("div.pf_container").removeClass('list').addClass('grid');
		jQuery('#gogrid').addClass('unset');
		jQuery('#golist').removeClass('unset');
		jQuery('.feed-item').each(function (index) {
			var element = jQuery(this);
			var itemID = element.attr('id');
			jQuery('#' + itemID + ' footer .actions').appendTo('#' + itemID + ' header');
		});
	});

	jQuery('#golist').click(function (evt) {
		evt.preventDefault();
		jQuery("div.pf_container").removeClass('grid').addClass('list');
		jQuery('#golist').addClass('unset');
		jQuery('#gogrid').removeClass('unset');
		jQuery('.feed-item').each(function (index) {
			var element = jQuery(this);
			var itemID = element.attr('id');
			jQuery('#' + itemID + ' header .actions').appendTo('#' + itemID + ' footer');
		});
	});

	jQuery('#gomenu').click(function (evt) {
		evt.preventDefault();
		jQuery('#feed-folders').hide('slide', {
			direction: 'right',
			easing: 'linear'
		}, 150);
	});

	jQuery('#gomenu').click(function (evt) {
		pf.toggler(evt, this, function (evt) {
			var toolswin = jQuery('#tools');
			jQuery("div.pf_container").removeClass('full');
			jQuery('#feed-folders').hide('slide', {
				direction: 'right',
				easing: 'linear'
			}, 150);
			jQuery(toolswin).show('slide', {
				direction: 'right',
				easing: 'linear'
			}, 150);
		}, function () {
			var toolswin = jQuery('#tools');
			//jQuery('#feed-folders').hide('slide',{direction:'right', easing:'linear'},150);
			jQuery(toolswin).hide('slide', {
				direction: 'right',
				easing: 'linear'
			}, 150);
			jQuery("div.pf_container").addClass('full');
		});
	});
	jQuery('#gofolders').click(function (evt) {
		evt.preventDefault();
		jQuery('#tools').hide('slide', {
			direction: 'right',
			easing: 'linear'
		}, 150);
	});
	jQuery('#gofolders').click(function (evt) {
		pf.toggler(evt, this, function (evt) {
			var folderswin = jQuery('#feed-folders');
			jQuery("div.pf_container").removeClass('full');

			jQuery(folderswin).show('slide', {
				direction: 'right',
				easing: 'linear'
			}, 150);
		}, function () {
			var folderswin = jQuery('#feed-folders');
			//jQuery('#tools').hide('slide',{direction:'right', easing:'linear'},150);
			jQuery(folderswin).hide('slide', {
				direction: 'right',
				easing: 'linear'
			}, 150);
			jQuery("div.pf_container").addClass('full');
		});
	});



	jQuery('#feed-folders .folder').click(function (evt) {
		evt.preventDefault();
		var obj = jQuery(this);
		var id = obj.attr('href');
		var url = window.location.href; //window.location.origin+window.location.pathname+'?page=pf-menu';
		url = url.replace('#', '&');
		url = removeURLParameter(url, 'folder');
		url = removeURLParameter(url, 'feed');
		url = removeURLParameter(url, 'ready');
		if (url.indexOf('?') > -1) {
			url += '&folder=' + id;
		} else {
			url += '?folder=' + id;
		}
		window.location.href = url;
	});

	jQuery('#feed-folders .feed').click(function (evt) {
		evt.preventDefault();
		var obj = jQuery(this);
		var id = obj.children('a').attr('href');
		var url = window.location.href; //window.location.origin+window.location.pathname+'?page=pf-menu';
		url = url.replace('#', '&');
		url = removeURLParameter(url, 'folder');
		url = removeURLParameter(url, 'feed');
		url = removeURLParameter(url, 'ready');
		if (url.indexOf('?') > -1) {
			url += '&feed=' + id;
		} else {
			url += '?feed=' + id;
		}
		window.location.href = url;
	});

	jQuery('.scroll-toggler').click(function (evt) {
		evt.preventDefault();
		var element = jQuery(this);
		var go_scroll_id = element.attr('id');
		var scroll_setting = 'true';
		console.log(go_scroll_id);
		//alert(modalIDString);
		if ('gopaged' == go_scroll_id) {
			scroll_setting = 'false';
		}
		jQuery.post(ajaxurl, {
				action: 'pf_ajax_user_setting',
				pf_user_setting: 'pf_user_scroll_switch',
				setting: scroll_setting

			},
			function (response) {
				var check_set = html_entity_decode(jQuery(response).find("response_data").text());
				if ('1' != check_set) {
					alert('PressForward has lost its connection to your server. Reload page and try again.');
				} else {
					location.reload();
				}
			});

	});

	jQuery('#deletefeedarchive').click(function (evt) {
		evt.preventDefault();
		jQuery('.loading-top').show();
		jQuery.post(ajaxurl, {
				action: 'reset_feed'
			},
			function (response) {
				jQuery('.loading-top').hide();
				jQuery('#errors').html(response);
			});
	});

	if (jQuery('.list').length != 0) {
		var actionButtons = jQuery('.list article');
		jQuery.each(actionButtons, function (index, value) {
			var tID = jQuery(this).attr('id');
			jQuery('#' + tID + ' header .actions').appendTo('#' + tID + ' footer');
		});
		//console.log('Item Actions in foot.');
	}

	jQuery('.pf_container').on('click', '#showMyNominations', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-menu&by=nominated", "_self")
	});
	jQuery('.pf_container').on('click', '#showMyHidden', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-menu&reveal=no_hidden", "_self")
	});
	jQuery('.pf_container').on('click', '#showUnread', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-menu&reveal=unread", "_self")
	});
	jQuery('.pf_container.pf-all-content').on('click', '#showDrafted', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-menu&reveal=drafted", "_self")
	});
	jQuery('.pf_container').on('click', '#showMyStarred', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-menu&by=starred", "_self")
	});
	jQuery('.pf_container').on('click', '#showNormal', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-menu", "_self")
	});
	jQuery('.pf_container').on('click', '#showNormalNominations', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-review", "_self")
	});

	//update_user_option(pressforward()->form_of->user_id(), 'have_you_seen_nominate_this', false);
	jQuery('.pf_container').on('click', '.remove-nom-this-prompt', function (evt) {
		evt.preventDefault();
		jQuery('article.nominate-this-preview').remove();
		jQuery.post(ajaxurl, {
				action: 'pf_ajax_user_setting',
				pf_user_setting: 'have_you_seen_nominate_this',
				setting: 'yes'

			},
			function (response) {
				var check_set = html_entity_decode(jQuery(response).find("response_data").text());
			});
		if (jQuery(this).is('[href]')) {
			window.open("?page=pf-tools", "_self");
		}
	});

	reshowModal();
	reviewModal();
	hideModal();
	commentPopModal();
	PFBootstrapInits();
	detect_view_change();
	//	commentModal();

});
