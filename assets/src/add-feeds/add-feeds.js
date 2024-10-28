/* global ajaxurl, jQuery */

import { __ } from '@wordpress/i18n'

import './add-feeds.scss'

(function($) {
	let feedIsValidated = false;
	let validationInProgress = false;
	let validatedFeedUrl = ''

	$(window).load(function() {
		const feedValidationMessage = document.getElementById( 'feed-validation-message' );
		const rssSubmitButton = document.getElementById( 'rss-submit-button' );
		const rssSubmitButtonJs = document.getElementById( 'rss-submit-button-js' );
		const feedUrlField = document.getElementById( 'pf_feedlist-single' );

		const showRssSubmitButtonJs = ( show ) => {
			if ( ! rssSubmitButtonJs ) {
				return;
			}

			rssSubmitButtonJs.style.display = show ? 'block' : 'none';
		}

		const validateFeed = () => {
			$( feedValidationMessage ).html( '' );
			rssSubmitButton.disabled = true;
			validationInProgress = true;
			setButtonText( __( 'Validating...', 'pressforward' ) );

			$.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'pf_validate_feed',
					feedUrl: feedUrlField.value
				},
				success: ( response ) => {
					validationInProgress = false;
					rssSubmitButton.disabled = false;

					const { feedUrl, message } = response.data

					let successMessage = message;

					if ( feedUrl.length > 0 && feedUrl !== feedUrlField.value ) {
						successMessage += '&nbsp;<button class="accept-suggested-feed-url button button-secondary">' + __( 'Use detected URL', 'pressforward' ) + '</button>';
						validatedFeedUrl = feedUrl
					}

					$( feedValidationMessage ).html( successMessage );

					setButtonText( __( 'Check Feed', 'pressforward' ) );

					if ( response.success ) {
						showRssSubmitButtonJs( true );
					} else {
						showRssSubmitButtonJs( false );
					}
				}
			});
		}

		const setButtonText = ( text ) => {
			rssSubmitButton.value = text || __( 'Submit', 'pressforward' );
		}

		if ( rssSubmitButton ) {
			setButtonText( __( 'Check Feed', 'pressforward' ) );

			$( rssSubmitButton ).click( function( e ) {
				e.preventDefault();

				if ( ! feedIsValidated ) {
					validateFeed();
				}
			} );

			showRssSubmitButtonJs( false );
		}

		$( feedValidationMessage ).on( 'click', '.accept-suggested-feed-url', function( e ) {
			e.preventDefault();
			$( feedUrlField ).val( validatedFeedUrl );
		} );

		$('.pf_primary_media_opml_upload').click(function(e) {
				e.preventDefault();
				// via http://stackoverflow.com/questions/13847714/wordpress-3-5-custom-media-upload-for-your-theme-options?cachebusterTimestamp=1405277969630
				var custom_uploader = wp.media({
					title: __( 'Upload .OPML or .XML', 'pressforward' ),
					button: {
						text: __( 'Add to Subscription list', 'pressforward' )
					},
					multiple: false  // Set this to true to allow multiple files to be selected
				})
				.on('select', function() {
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					//$('.custom_media_image').attr('src', attachment.url);
					jQuery('.pf_opml_file_upload_field').val(attachment.url);
					//$('.custom_media_id').val(attachment.id);
				})
				.open();
			return false;
		});

	});
})(jQuery);
