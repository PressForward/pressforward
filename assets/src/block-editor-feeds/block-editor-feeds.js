/* global ajaxurl, pfBlockEditorFeeds */

import './block-editor-feeds.scss'

import { registerPlugin } from '@wordpress/plugins'
import { useState } from '@wordpress/element'
import { useDispatch, useSelect } from '@wordpress/data'
import { PluginDocumentSettingPanel } from '@wordpress/edit-post'
import { __, sprintf } from '@wordpress/i18n'
import { Button, Spinner, TextControl } from '@wordpress/components'

const BlockEditorFeedsInfobox = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const { feedPostType } = pfBlockEditorFeeds

	const {
		errorMessage,
		feedUrl,
		postType
	} = useSelect( ( select ) => {
		const editedPostId = select( 'core/editor' ).getCurrentPostId()
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )
		const editedErrorMessage = select( 'core/editor' ).getEditedPostAttribute( 'alert_message' )

		return {
			errorMessage: editedErrorMessage,
			feedUrl: editedPostMeta?.feed_url || '',
			postId: editedPostId,
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
		}
	} )

	const isFeed = feedPostType === postType

	// Show only on Feed.
	if ( ! isFeed ) {
		return null
	}

	return (
		<PluginDocumentSettingPanel
			icon="controls-forward"
			name="pressforward-block-editor-feeds-infobox"
			title={ __( 'Feed Information', 'pressforward' ) }
		>
			<TextControl
				label={ __( 'Feed URL', 'pressforward' ) }
				value={ feedUrl }
				onChange={ ( newValue ) => {
					editPost( { meta: { 'feed_url': newValue } } )
				} }
			/>

			{ errorMessage && (
				<p className="pf-error-message">
					<strong>
						{ sprintf( 'Error: %s', errorMessage ) }
					</strong>
				</p>
			) }
		</PluginDocumentSettingPanel>
	)
}

registerPlugin(
	'pressforward-block-editor-feeds-infobox',
	{
		render: BlockEditorFeedsInfobox,
	}
);

const BlockEditorFeedRetrievalInfobox = ( {} ) => {
	const { feedPostType } = pfBlockEditorFeeds
	const [ requestInProgress, setRequestInProgress ] = useState( false );

	const {
		lastChecked,
		lastRetrieved,
		nextCheck,
		postId,
		postType
	} = useSelect(
		( select ) => {
			return {
				lastChecked: select( 'core/editor' ).getEditedPostAttribute( 'last_checked' ),
				lastRetrieved: select( 'core/editor' ).getEditedPostAttribute( 'last_retrieved' ),
				nextCheck: select( 'core/editor' ).getEditedPostAttribute( 'next_check' ),
				postId: select( 'core/editor' ).getCurrentPostId(),
				postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
			}
		}
	)

	const isFeed = feedPostType === postType

	// Show only on Feed.
	if ( ! isFeed ) {
		return null
	}

	return (
		<PluginDocumentSettingPanel
			icon="controls-forward"
			name="pressforward-block-editor-feed-retrieval-infobox"
			title={ __( 'Retrieval', 'pressforward' ) }
		>
			<dl className="pf-feed-retrieval-info">
				<dt>{ __( 'This feed was last checked on:', 'pressforward' ) }</dt>
				<dd>
					{ lastChecked ? lastChecked : __( 'Never', 'pressforward' ) }
				</dd>

				<dt>{ __( 'A new item was last fetched from the feed on:', 'pressforward' ) }</dt>
				<dd>
					{ lastRetrieved ? lastRetrieved : __( 'Never', 'pressforward' ) }
				</dd>

				<dt>{ __( 'The next scheduled check of this feed will be on:', 'pressforward' ) }</dt>
				<dd>
					{ nextCheck ? nextCheck : __( 'None', 'pressforward' ) }
				</dd>
			</dl>

			{ postId && (
				<p>
					<Button
						isSecondary={ true }
						disabled={ requestInProgress }
						// On click, send request to ajaxurl ajax_update_feed_handler with feed_id=postId
						onClick={ () => {
							const requestUrl = `${ ajaxurl }?action=ajax_update_feed_handler&feed_id=${ postId }`

							const URLParams = new URLSearchParams();
							URLParams.append( 'feed_id', postId )

							// Set the request in progress
							setRequestInProgress( true )

							const fetchArgs = {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded',
								},
								body: URLParams.toString(),
							}

							fetch( requestUrl, fetchArgs )
								.then( ( response ) => {
									return response.json()
								} )
								.then( () => {
									// Refetch the post from the server
									wp.data.dispatch( 'core' ).invalidateResolution( 'getEntityRecord', [ 'postType', feedPostType, postId ] );

									// This will trigger a refetch of the post and update the store automatically
									wp.data.select( 'core' ).getEntityRecord( 'postType', feedPostType, postId );

									// Reset the request in progress
									setRequestInProgress( false )
								} )

						} }
					>{ __( 'Refresh Feed Now', 'pressforward' ) }</Button>
					{ requestInProgress && ( <Spinner isBusy={ requestInProgress } /> ) }
				</p>
			) }
		</PluginDocumentSettingPanel>
	)
}

registerPlugin(
	'pressforward-block-editor-feed-retrieval-infobox',
	{
		render: BlockEditorFeedRetrievalInfobox,
	}
);
