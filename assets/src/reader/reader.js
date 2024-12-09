/* global ajaxurl, jQuery, pf */

import { __, sprintf } from '@wordpress/i18n'

import {
	removeURLParameter,
	reshowModal,
	reviewModal,
	hideModal,
	commentPopModal,
	PFBootstrapInits,
	detect_view_change // eslint-disable-line camelcase
} from './util.js'

import {
	Modal
} from 'bootstrap';

import './reader.scss';

/**
 * Display transform for pf
 */
jQuery(window).on('load', function () {
	// executes when complete page is fully loaded, including all frames, objects and images

	var $allModals = jQuery('.pfmodal');

	jQuery('.pf-loader').delay(300).fadeOut("slow", function () {
		var theModal, $closeEl, $modalEl;
		jQuery('.pf_container').fadeIn("slow");
		jQuery( '.page-load-status' ).show();
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
			if (window.location.hash === '#ready') {
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
		jQuery('.feed-item').each(function () {
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
		jQuery('.feed-item').each(function () {
			var element = jQuery(this);
			var itemID = element.attr('id');
			jQuery('#' + itemID + ' header .actions').appendTo('#' + itemID + ' footer');
		});
	});

	jQuery('#gomenu').on( 'click', function (evt) {
		evt.preventDefault();
		jQuery('#feed-folders').hide( {
			direction: 'right',
			duration: 150,
			easing: 'linear'
		} );
	});

	jQuery('#gomenu').on( 'click', function (evt) {
		pf.toggler(evt, this, function () {
			var toolswin = jQuery('#tools');
			jQuery("div.pf_container").removeClass('full');
			jQuery('#feed-folders').hide( {
				direction: 'right',
				duration: 150,
				easing: 'linear'
			} );
			jQuery(toolswin).show( {
				direction: 'right',
				duration: 150,
				easing: 'linear'
			} );
		}, function () {
			var toolswin = jQuery('#tools');
			//jQuery('#feed-folders').hide('slide',{direction:'right', easing:'linear'},150);
			jQuery(toolswin).hide( {
				direction: 'right',
				duration: 150,
				easing: 'linear'
			} );
			jQuery("div.pf_container").addClass('full');
		});
	});
	jQuery('#gofolders').on( 'click', function (evt) {
		evt.preventDefault();
		jQuery('#tools').hide( {
			direction: 'right',
			duration: 150,
			easing: 'linear'
		} );
	});
	jQuery('#gofolders').on( 'click', function (evt) {
		pf.toggler(evt, this, function () {
			var folderswin = jQuery('#feed-folders');
			jQuery("div.pf_container").removeClass('full');

			jQuery(folderswin).show( {
				direction: 'right',
				duration: 150,
				easing: 'linear'
			} );
		}, function () {
			var folderswin = jQuery('#feed-folders');
			//jQuery('#tools').hide('slide',{direction:'right', easing:'linear'},150);
			jQuery(folderswin).hide( {
				direction: 'right',
				duration: 150,
				easing: 'linear'
			} );
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
		var goScrollId = element.attr('id');
		var scrollSetting = 'true';

		if ('gopaged' === goScrollId) {
			scrollSetting = 'false';
		}

		jQuery.post(ajaxurl, {
				action: 'pf_ajax_user_setting',
				pf_user_setting: 'pf_user_scroll_switch',
				setting: scrollSetting

			},
			function (response) {
				var checkSet = html_entity_decode(jQuery(response).find("response_data").text());
				if ('1' != checkSet) {
					alert( __( 'PressForward has lost its connection to your server. Reload page and try again.', 'pressforward' ) );
				} else {
					window.location.reload();
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

	if (jQuery('.list').length !== 0) {
		var actionButtons = jQuery('.list article');
		jQuery.each(actionButtons, function () {
			var tID = jQuery(this).attr('id');
			jQuery('#' + tID + ' header .actions').appendTo('#' + tID + ' footer');
		});
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

	jQuery( '.pf_container' ).on( 'click', '#date-range-button', function( evt ) {
		evt.preventDefault();
		jQuery( '#date-range-options' ).toggleClass( 'show' );
	} );

	jQuery( '.pf_container' ).on( 'click', '.date-range-submit', function( evt ) {
		evt.preventDefault();

		setDateRangeButtonText();

		document.getElementById( 'date-range-options' ).classList.remove( 'show' );

		const start = jQuery( '#date-range-start' ).val();
		const end = jQuery( '#date-range-end' ).val();

		const currentPageType = new URLSearchParams( window.location.search ).get( 'page' );

		window.open( "?page=" + currentPageType + "&date-range-start=" + start + "&date-range-end=" + end, "_self" );
	} );

	const setDateRangeButtonText = () => {
		const start = jQuery( '#date-range-start' ).val();
		const end = jQuery( '#date-range-end' ).val();

		const dateRangeButton = document.getElementById( 'date-range-button' );

		if ( dateRangeButton ) {
			const generateNewButtonText = ( startDate, endDate ) => {
				if ( startDate && endDate ) {
					// translators: %1$s is the start date, %2$s is the end date
					return sprintf( __( '%1$s to %2$s', 'pressforward' ), startDate, endDate );
				}

				if ( startDate ) {
					// translators: %1$s is the start date
					return sprintf( __( 'Newer than %1$s', 'pressforward' ), startDate );
				}

				if ( endDate ) {
					// translators: %1$s is the end date
					return sprintf( __( 'Older than %1$s', 'pressforward' ), endDate );
				}

				return __( 'All Dates', 'pressforward' );
			}

			dateRangeButton.querySelector( '.date-range-text' ).textContent = generateNewButtonText( start, end );
		}
	}
	setDateRangeButtonText();

	jQuery('.pf_container').on('click', '.remove-nom-this-prompt', function (evt) {
		evt.preventDefault();
		jQuery('article.nominate-this-preview').remove();
		jQuery.post(ajaxurl, {
				action: 'pf_ajax_user_setting',
				pf_user_setting: 'have_you_seen_nominate_this',
				setting: 'yes'

			},
			function (response) {
//				var checkSet = html_entity_decode(jQuery(response).find("response_data").text());
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
