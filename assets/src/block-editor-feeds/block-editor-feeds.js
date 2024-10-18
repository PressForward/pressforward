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
		postId,
		postStatus,
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
