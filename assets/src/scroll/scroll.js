/* global jQuery */
import { __ } from '@wordpress/i18n'

import InfiniteScroll from 'infinite-scroll'

import {
	reshowModal,
	reviewModal,
	hideModal,
	PFBootstrapInits
} from '../reader/util.js'

jQuery(document).ready(function () {
	const feedNextLink = document.querySelector( '.feednext a' );

	if ( ! feedNextLink ) {
		return;
	}

	const infScroll = new InfiniteScroll( document.getElementById( 'entries' ), {
		path: '.feednext a',
		hideNav: '.pf-navigation',    // selector for the paged navigation
		append: '.feed-item',     // selector for all items you'll retrieve
		debug: false,
		history: false,
		status: '.page-load-status',
	});

	infScroll.on( 'append', function( event, body, path, arrayOfNewElems ) {

		jQuery(arrayOfNewElems).each(function (elem) {
			var element = jQuery(elem);
			var itemID = element.attr('id');
		});

		jQuery('.feed-item').each(function (index) {
			var element = jQuery(this);
			var itemID = element.attr('id');
			//Function here via http://stackoverflow.com/questions/482763/jquery-to-check-for-duplicate-ids-in-a-dom
			var ids = jQuery('[id="' + itemID + '"]');
			if (ids.length > 1) {
				console.warn('Multiple IDs #' + itemID);
				ids.each(function (index) {
					if (index > 0) {
						jQuery(this).remove();
					}
				});
			}
			if (jQuery('.nom-container').length != 0) {
				jQuery('.list #' + itemID + ' header .actions').appendTo('#' + itemID + ' footer');
			} else {
				if (jQuery('#' + itemID + ' header .actions').length === 0) {
					jQuery('.grid #' + itemID + ' header .actions').appendTo('#' + itemID + ' header');
				}
				if (jQuery('#' + itemID + ' footer .actions').length === 0) {
					jQuery('.list #' + itemID + ' header .actions').appendTo('#' + itemID + ' footer');
				}
			}
		});

		reshowModal();
		reviewModal();
		hideModal();
		allContentModal();
		modalReadReset();
		PFBootstrapInits();
	} )
});
