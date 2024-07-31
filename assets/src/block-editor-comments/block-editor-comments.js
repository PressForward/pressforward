/* global pfBlockEditorComments */

import { registerPlugin } from '@wordpress/plugins'
import { useDispatch, useSelect } from '@wordpress/data'
import { useEffect, useState } from '@wordpress/element'
import { PluginDocumentSettingPanel } from '@wordpress/edit-post'
import { __ } from '@wordpress/i18n'

import apiFetch from '@wordpress/api-fetch'

import { Button, TextareaControl } from '@wordpress/components'

const BlockEditorCommentsControl = ( {} ) => {
	const [ comments, setComments ] = useState( [] )

	const { apiBaseUrl, nominationPostType } = pfBlockEditorComments

	const { editPost } = useDispatch( 'core/editor' )

	const {
		nomthisCommentText,
		postId,
		postStatus,
		postType
	} = useSelect( ( select ) => {
		const editedPostId = select( 'core/editor' ).getCurrentPostId()
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		return {
			nomthisCommentText: editedPostMeta?.pf_nomthis_comment || '',
			postId: editedPostId,
			postStatus: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
		}
	} )

	useEffect( () => {
		if ( ! postId ) {
			return
		}

		apiFetch( { path: `/wp/v2/comments?post=${ postId }` } )
			.then( ( data ) => {
				setComments( data )
			} )
	}, [ apiBaseUrl, postId ] )

	const isNominateThis = nominationPostType === postType && 'publish' !== postStatus

	// For now, show only on Nominate This.
	if ( ! isNominateThis ) {
		return null
	}

	return (
		<PluginDocumentSettingPanel
			icon="controls-forward"
			name="pressforward-block-editor-comments-control"
			title={ __( 'Comments', 'pressforward' ) }
		>
			{ isNominateThis && (
				<>
					<TextareaControl
						label={ __( 'Start the Conversation', 'pressforward' ) }
						value={ nomthisCommentText }
						onChange={ ( newValue ) => {
							editPost( { meta: { 'pf_nomthis_comment': newValue } } )
						} }
						help={ __( 'Text entered here will be added as the first editorial comment on your nomination.', 'pressforward' ) }
					/>
				</>
			) }
		</PluginDocumentSettingPanel>
	)
}

registerPlugin(
	'pressforward-block-editor-comments-control',
	{
		render: BlockEditorCommentsControl
	}
);
