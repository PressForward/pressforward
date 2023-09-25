/* global ajaxurl, jQuery */

import { __, sprintf } from '@wordpress/i18n'

jQuery(window).load(function() {
	pfSwitchStatusLook();

	jQuery( 'body.post-type-pf_feed' ).on(
		'click',
		'.edit-post-status',
		function() {
			pfSwitchStatusLook();
		}
	);

	jQuery( 'body.post-type-pf_feed' ).on(
		'click',
		'.save-post-status',
		function() {
			pfSwitchStatusLook();
		}
	);

	jQuery( 'body.post-type-pf_feed' ).on(
		'click',
		'.refresh-feed',
		function(evt){
			evt.preventDefault();

			evt.target.classList.add( 'loading' );

			const element = jQuery(this);
			const feedId  = element.attr('data-pf-feed');

			// Remove all existing status rows.
			jQuery( '.pf-feed-refresh-status' ).remove()

			jQuery.post(
				ajaxurl,
				{
					action: 'ajax_update_feed_handler',
					feed_id: feedId // eslint-disable-line camelcase
				},
				( response ) => {
					const { success, data } = response

					const {
						feedItemCount,
						itemsAdded,
						nextRetrievalDate,
						nextRetrievalString,
					} = data // eslint-disable-line camelcase

					const feedRow = evt.target.closest( 'tr' )

					const statusEl = document.createElement( 'div' )
					statusEl.classList.add( 'notice' )
					statusEl.classList.add( success ? 'notice-success' : 'notice-error' )

					if ( success ) {
						// translators: Number of feed items added.
						statusEl.textContent = sprintf( __( 'Feed refreshed successfully. Created %s new feed items.', 'pressforward' ), itemsAdded )
					} else {
						statusEl.textContent = __( 'There was an error refreshing the feed.', 'pressforward' )
					}

					// Put the statusEl inside of a td and then a tr.
					const statusElTd = document.createElement( 'td' )

					// Give it a colspan equal to the number of columns in the table.
					statusElTd.setAttribute( 'colspan', feedRow.children.length )

					statusElTd.appendChild( statusEl )

					const statusElTr = document.createElement( 'tr' ).appendChild( statusElTd )
					statusElTr.classList.add( 'pf-feed-refresh-status' )

					// Insert before the closest tr to statusEl.
					feedRow.before( statusElTr )

					// Replace updated row values.
					if ( success ) {
						if ( feedItemCount ) {
							feedRow.querySelector( '.items_retrieved' ).textContent = feedItemCount
						}

						if ( nextRetrievalDate && nextRetrievalString ) {
							const dateAbbr = feedRow.querySelector( '.last_retrieved abbr' )
							if ( dateAbbr ) {
								dateAbbr.setAttribute( 'title', nextRetrievalDate )
								dateAbbr.textContent = nextRetrievalString
							}
						}
					}

					evt.target.classList.remove( 'loading' );
				}
			);
		}
	);
});

function pfSwitchStatusLook(){
  jQuery('body.post-type-pf_feed #submitdiv h3.hndle span').text( __( 'Activate', 'pressforward' ) );
  jQuery('body.post-type-pf_feed').find('#post_status option[value="draft"]').text( __( 'Inactive', 'pressforward' ) );
  jQuery('body.post-type-pf_feed').find('#post_status option[value="publish"]').text( __( 'Active', 'pressforward' ) );

  const statusField = document.getElementById( 'post_status' )
  if ( ! statusField ) {
	  return
  }

  const selectedStatus = statusField.value

  if ( selectedStatus === 'publish' ) {
    jQuery('body.post-type-pf_feed').find('#post-status-display').text( __( 'Active', 'pressforward' ) );
  }
  if ( selectedStatus === 'Draft' ) {
    jQuery('body.post-type-pf_feed').find('#post-status-display').text( __( 'Inactive', 'pressforward' ) );
  }

  jQuery('body.post-type-pf_feed').find('#save-post').attr('value', __( 'Save Inactive', 'pressforward' ) );

  if ( selectedStatus !== 'publish' ) {
    jQuery('body.post-type-pf_feed').find('#publish').attr('value', __( 'Make Active', 'pressforward' ) );
  }

  jQuery('body.post-type-pf_feed #save-post').click(function() {
    setTimeout(
		function(){
			jQuery('body.post-type-pf_feed').find('#save-post').attr('value', __( 'Save Inactive', 'pressforward' ) );
		},
		50
	);
  });
}
