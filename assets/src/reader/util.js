/* global ajaxurl, jQuery, editorialCommentReply */

import { __, sprintf } from '@wordpress/i18n'

export function pf_make_url_hashed(hashed) {
	//via http://stackoverflow.com/questions/1844491/intercepting-call-to-the-back-button-in-my-ajax-application-i-dont-want-it-to
	window.location.hash = '#' + hashed;
}

export function assure_closed_menus() {
	jQuery('.dropdown li > *').on('click', function () {
		jQuery('.dropdown.open').removeClass('open');
	});
}

//via http://stackoverflow.com/questions/1662308/javascript-substr-limit-by-word-not-char
export function trim_words(theString, numWords) {
	var expString = theString.split(/\s+/, numWords);
	var theNewString = expString.join(" ");
	return theNewString;
}

export function assure_next_obj(tabindex, obj, advance) {
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

export function modalNavigator(tabindex) {
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

		// translators: Link to previous item.
		var prevHTML = '<h5 class="prev_title">' + sprintf( __( 'Previous: %s', 'pressforward' ), '<a href="' + prevItemID + '" role="button" class="modal-nav" data-dismiss="modal" data-toggle="modal" data-backdrop="false">' + prevTitle + '</a>' ) + '</h5>';
		prevHTML += '<p class="prev_source_title">' + prevSource + '</p>';
		prevHTML += '<p class="prev_author">' + prevAuthor + '</p>';
		prevHTML += '<p class="prev_excerpt">' + prevExcerpt + '</p>';
		prevHTML += '<p class="prev_date">' + prevDate + '</p>';
		//alert(modalID);
		jQuery(modalID + ' div.modal-body-row div.modal-sidebar div.goPrev').html(prevHTML);
		jQuery(modalID + ' div.mobile-goPrev').html('<i class="icon-arrow-left"></i> <a href="' + prevItemID + '" role="button" data-bs-dismiss="modal" class="mobile-modal-navlink modal-nav">' + prevTitle + '</a> ');


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

		// translators: Link to next item.
		var nextHTML = '<h5 class="next_title">' + sprintf( __( 'Next: %s', 'pressforward' ), '<a href="' + nextItemID + '" role="button" class="modal-nav" data-dismiss="modal" data-toggle="modal" data-backdrop="false">' + nextTitle + '</a>' ) + '</h5>';
		nextHTML += '<p class="next_source_title">' + nextSource + '</p>';
		nextHTML += '<p class="next_author">' + nextAuthor + '</p>';
		nextHTML += '<p class="next_excerpt">' + nextExcerpt + '</p>';
		nextHTML += '<p class="next_date">' + nextDate + '</p>';
		//alert(modalID);
		jQuery(modalID + ' div.modal-body-row div.modal-sidebar div.goNext').html(nextHTML);
		jQuery(modalID + ' div.mobile-goNext').html('&nbsp;| <a href="' + nextItemID + '" role="button" class="mobile-modal-navlink modal-nav" data-bs-dismiss="modal">' + nextTitle + '</a> <i class="icon-arrow-right"></i>');

	}


}

export function commentPopModal() {

	jQuery('.pfmodal').on('shown.bs.modal', function (evt) {
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
	});

	jQuery('.pfmodal').on('hide.bs.modal', function (evt) {
		jQuery('#ef-comments_wrapper').remove();
	});
}

export function reshowModal() {
	jQuery('.pfmodal').on('show.bs.modal', function (evt) {
		var element = jQuery(this);
		var modalID = element.attr('id');
		pf_make_url_hashed(modalID);
	});

	jQuery('.comment-modal').on('shown.bs.modal', function (evt) {
		var element = jQuery(this);
		var modalID = element.attr('id');
		document.body.style.overflow = 'hidden';
		var bigModal = {
			'display': 'block',
			'position': 'fixed',
			'top': '32px',
			'right': '0',
			'bottom': '100%',
			'left': '0',
			'margin': '0',
			'width': '100%',
			'height': 'calc(100% - 32px)',
			'overflow': 'hidden',
			'z-index': '9999'
		};

		jQuery( '#' + modalID + '.pfmodal' ).css( bigModal );
	});
}

export function reviewModal() {
	//Need to fix this to only trigger on the specific model, but not sure how yet.

	jQuery('.pfmodal').on('shown.bs.modal', function (evt) {
		//alert('Modal Triggered.');


		var element = jQuery(this);

		var isCommentModal = element.hasClass( 'comment-modal' );

		var modalID = element.attr('id');
		var modalIDString = '#' + modalID;
		//openModals.push(modalIDString);
		//alert(modalID);
		//showDiv(jQuery('#entries'), jQuery('#'+modalID));
		//var itemID = element.attr('pf-item-id');
		//var postID = element.attr('pf-post-id');
		var item_post_ID = element.closest('.feed-item').attr('pf-item-post-id');

		jQuery.post(ajaxurl, {
				action: 'pf_ajax_get_comments',
				//We'll feed it the ID so it can cache in a transient with the ID and find to retrieve later.
				id_for_comments: item_post_ID,
			},
			function( comment_response ) {
				if ( isCommentModal ) {
					jQuery( '#' + modalID + '.comment-modal .modal-body' ).html( comment_response );
				} else {
					jQuery( '#' + modalID + ' .modal-comments' ).html( comment_response );
				}
			});

		setTimeout(
			function() {
				var mainContentHeight = element.find('.modal-body-row').height();
				element.find('.modal-body-row .modal-body').height(mainContentHeight);
			},
			100
		);

		// As the modal loads, we replace the `src` attribute on images, so that they load.
		element.find( '.main-text img' ).each( function( k, v ) {
			var imgSrc = v.dataset.hasOwnProperty( 'src' ) ? v.dataset.src : ''
			if ( imgSrc ) {
				v.setAttribute( 'src', imgSrc )
			}
		} )

		var tabindex = element.parent().attr('tabindex');

		modalNavigator(tabindex);
	});
}

export function hideModal() {
	jQuery('.pfmodal').on('hide.bs.modal', function (evt) {
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

export function commentModal() {
	jQuery('.pfmodal').on('show.bs.modal', function (evt) {
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

export function PFBootstrapInits() {
	jQuery('.dropdown-toggle').on(
		'click',
		function() {
			var $clickedParent = jQuery(this).closest('.dropdown');
			$clickedParent.toggleClass( 'open' );
		}
	);

	jQuery('.nom-to-archive').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Item', 'pressforward' )

	});
	jQuery('.nom-to-draft').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Item', 'pressforward' )

	});
	jQuery('.nominate-now').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Nominate', 'pressforward' )

	});
	jQuery('.star-item').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Star', 'pressforward' )

	});
	jQuery('.itemCommentModal').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Comment', 'pressforward' )
	});
	jQuery('.nom-count').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Nomination Count', 'pressforward' )
	});
	jQuery('.pf-amplify').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Amplify', 'pressforward' )
	});
	jQuery('.meta_form_modal-button').tooltip({
		placement: 'top',
		trigger: 'hover',
		title: __( 'Edit Metadata', 'pressforward' )
	});
	jQuery('.itemInfobutton').popover({
			html: true,
			title: '',
			container: '.icon-info-sign',
			content: function ( button ) {
				var idCode = jQuery( button ).attr('data-target');
				var contentOutput = '<div class="feed-item-info-box">';
				contentOutput += jQuery('#info-box-' + idCode).html();
				contentOutput += '</div>';
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

export function attach_menu_on_scroll_past() {
	const $pfContainer = jQuery( '.pf-container' )

	if ( ! $pfContainer.length ) {
		return
	}

	jQuery(window).on( 'scroll', function () {
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

			var width = $pfContainer.innerWidth();
			var containerOffset = jQuery('.pf_container').offset();
			jQuery('.nav-fix').width(width - 20).offset( { left: containerOffset.left } );
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

	jQuery(window).on('resize', function () {
		var width = jQuery('#entries').innerWidth();
		jQuery('.nav-fix').width(width - 80);
		if (40 > jQuery('#adminmenuback').width()) {
			//jQuery('.nav-fix').css('left', '80px');
		} else {
			//jQuery('.nav-fix').css('left', '200px');
		}
	});
}

export function detect_view_change() {

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
		var parent_d = element.closest( '.dropdown' );
		//console.log(parent_h);
		if (element.hasClass('amplify-down')) {
			element.removeClass('amplify-down');
			jQuery(parent_e).removeClass('show-overflow');
			jQuery(parent_e).css('height', '');
			jQuery(parent_d).removeClass('open');
		} else {
			element.addClass('amplify-down');
			jQuery(parent_e).addClass('show-overflow');
			jQuery(parent_e).height(parent_h);
			jQuery(parent_d).addClass('open');
			is_pf_open = true;
		}
	});

	jQuery('.pressforward #wpbody').on('click', '.grid .amplify-group .pf-amplify', function (evt) {
		var element = jQuery(this);
		//console.log(element);
		var parent_e = element.parents('article');
		var parent_h = parent_e.height();
		var parent_head = parent_e.find('header');
		var parent_d = element.closest( '.dropdown' );
		//console.log(parent_h);
		if (parent_e.hasClass('amplify-down')) {
			parent_e.removeClass('amplify-down');
			parent_d.removeClass( 'open' );
		} else {
			parent_e.addClass('amplify-down');
			parent_d.addClass( 'open' );
			is_pf_open = true;
		}
	});
}

// Via http://stackoverflow.com/a/1634841/2522464
export function removeURLParameter(url, parameter) {
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
