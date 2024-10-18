/* global pfBlockEditorFeeds */

import { registerPlugin } from '@wordpress/plugins'
import { useDispatch, useSelect } from '@wordpress/data'
import { PluginDocumentSettingPanel } from '@wordpress/edit-post'
import { __ } from '@wordpress/i18n'
import { TextControl } from '@wordpress/components'

const BlockEditorFeedsInfobox = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const { feedPostType } = pfBlockEditorFeeds

	const {
		feedUrl,
		postType
	} = useSelect( ( select ) => {
		const editedPostId = select( 'core/editor' ).getCurrentPostId()
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		return {
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

	const {
		lastChecked,
		lastRetrieved,
		nextCheck,
		postType
	} = useSelect( ( select ) => {
		return {
			lastChecked: select( 'core/editor' ).getEditedPostAttribute( 'last_checked' ),
			lastRetrieved: select( 'core/editor' ).getEditedPostAttribute( 'last_retrieved' ),
			nextCheck: select( 'core/editor' ).getEditedPostAttribute( 'next_check' ),
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
			name="pressforward-block-editor-feed-retrieval-infobox"
			title={ __( 'Retrieval', 'pressforward' ) }
		>
			<dl>
				<dt>{ __( 'Feed Last Checked:', 'pressforward' ) }</dt>
				<dd>
					{ lastChecked ? lastChecked : __( 'Never', 'pressforward' ) }
				</dd>
			</dl>

			<dl>
				<dt>{ __( 'Feed Item Last Retrieved:', 'pressforward' ) }</dt>
				<dd>
					{ lastRetrieved ? lastRetrieved : __( 'Never', 'pressforward' ) }
				</dd>
			</dl>

			<dl>
				<dt>{ __( 'Next Scheduled Retrieval', 'pressforward' ) }</dt>
				<dd>
					{ nextCheck ? nextCheck : __( 'None', 'pressforward' ) }
				</dd>
			</dl>
		</PluginDocumentSettingPanel>
	)
}

registerPlugin(
	'pressforward-block-editor-feed-retrieval-infobox',
	{
		render: BlockEditorFeedRetrievalInfobox,
	}
);
