import { __, sprintf } from '@wordpress/i18n'

import {
	reshowModal,
	reviewModal,
	hideModal,
	commentPopModal,
	PFBootstrapInits,
	detect_view_change
} from './util.js'

import {
	Modal
} from 'bootstrap';

import './reader.scss';

/**
 * Display transform for pf
 **/
jQuery(window).on('load', function () {
	// executes when complete page is fully loaded, including all frames, objects and images

	var $allModals = jQuery('.pfmodal');

	jQuery('.pf-loader').delay(300).fadeOut("slow", function () {
		var theModal, $closeEl, $modalEl;
		jQuery('.pf_container').fadeIn("slow");
		if (window.location.hash.indexOf("#") < 0) {
			window.location.hash = '#ready';
		} else if ((window.location.hash.toLowerCase().indexOf("modal") >= 0)) {
			var hash = window.location.hash;
			$modalEl = jQuery(hash);
			if ( 0 !== $modalEl.length && !jQuery(hash).hasClass('in')) {
				$closeEl = $modalEl.find( '.close' );
				theModal = new Modal( $modalEl );
				theModal.show( $closeEl );
			} else {
				window.location.hash = '#ready';
			}
		}

		jQuery(window).on('hashchange', function () {
			if (window.location.hash == '#ready') {
				jQuery('.modal').modal('hide');
			}
			if ((window.location.hash.toLowerCase().indexOf("modal") >= 0)) {
				var hash = window.location.hash;
				if (!jQuery(hash).hasClass('in')) {
					$modalEl = jQuery(hash);
					$closeEl = $modalEl.find( '.close' );
					theModal = new Modal( $modalEl );
					theModal.show( $closeEl );

					$allModals.each(function(){
						var $modalToClose = Modal.getInstance( this );
						if ( $modalToClose ) {
							$modalToClose.hide();
						}
					});
				}
			}
		});

	});

});

jQuery(window).on('load', function () {

	jQuery('#gogrid').on('click', function (evt) {
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

	jQuery('#golist').on( 'click', function (evt) {
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

	jQuery('#gomenu').on( 'click', function (evt) {
		evt.preventDefault();
		jQuery('#feed-folders').hide('slide', {
			direction: 'right',
			easing: 'linear'
		}, 150);
	});

	jQuery('#gomenu').on( 'click', function (evt) {
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
	jQuery('#gofolders').on( 'click', function (evt) {
		evt.preventDefault();
		jQuery('#tools').hide('slide', {
			direction: 'right',
			easing: 'linear'
		}, 150);
	});
	jQuery('#gofolders').on( 'click', function (evt) {
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



	jQuery('#feed-folders .folder').on( 'click', function (evt) {
		evt.preventDefault();
		var obj = jQuery(this);
		var id = obj.attr('href');
		var url = window.location.href;
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

	jQuery('#feed-folders .feed').on('click', function (evt) {
		evt.preventDefault();
		var obj = jQuery(this);
		var id = obj.children('a').attr('href');
		var url = window.location.href;
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

	jQuery('.scroll-toggler').on('click', function (evt) {
		evt.preventDefault();
		var element = jQuery(this);
		var go_scroll_id = element.attr('id');
		var scroll_setting = 'true';

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
					alert( __( 'PressForward has lost its connection to your server. Reload page and try again.', 'pressforward' ) );
				} else {
					location.reload();
				}
			});

	});

	jQuery('#deletefeedarchive').on( 'click', function (evt) {
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
		window.open("?page=pf-all-content&by=nominated", "_self")
	});
	jQuery('.pf_container').on('click', '#showMyHidden', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-all-content&reveal=no_hidden", "_self")
	});
	jQuery('.pf_container').on('click', '#showUnread', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-all-content&reveal=unread", "_self")
	});
	jQuery('.pf_container.pf-all-content').on('click', '#showDrafted', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-all-content&reveal=drafted", "_self")
	});
	jQuery('.pf_container').on('click', '#showMyStarred', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-all-content&by=starred", "_self")
	});
	jQuery('.pf_container').on('click', '#showNormal', function (evt) {
		evt.preventDefault();
		window.open("?page=pf-all-content", "_self")
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
