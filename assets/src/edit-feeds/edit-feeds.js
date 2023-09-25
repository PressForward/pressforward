/* global ajaxurl, jQuery */

import { __ } from '@wordpress/i18n'

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

			const element	= jQuery(this);
			const feedId = element.attr('data-pf-feed');

			jQuery.post(
				ajaxurl,
				{
					action: 'ajax_update_feed_handler',
					feed_id: feedId // eslint-disable-line camelcase
				},
				function(response) {}
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
