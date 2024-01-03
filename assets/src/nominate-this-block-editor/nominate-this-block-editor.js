/* global pfBlocks, pfNominateThisBlockEditor */
import { registerPlugin } from '@wordpress/plugins'

import {
	Button,
	CheckboxControl,
} from '@wordpress/components';

import { __ } from '@wordpress/i18n'

import {
	PluginDocumentSettingPanel,
	PluginPrePublishPanel
} from '@wordpress/edit-post'

import { useDispatch, useSelect } from '@wordpress/data'

import { assignTags } from '../util/tags'

import './nominate-this-block-editor.scss'

const NominationPrePublishPanel = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const { postType, sendToDraft, subscribeToFeed } = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		const savedSendToDraft = editedPostMeta?.send_to_draft || false
		const savedSubscribeToFeed = editedPostMeta?.subscribe_to_feed || false

		return {
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
			sendToDraft: savedSendToDraft,
			subscribeToFeed: savedSubscribeToFeed,
		}
	} )

	if ( 'nomination' !== postType ) {
		return;
	}

	return (
		<>
			<PluginPrePublishPanel
				icon="controls-forward"
				title={ __( 'Send to Draft', 'pressforward' ) }
				initialOpen={ true }
			>
				<p>{ __( 'Typically, nominated items are sent to Dashboard > Nominations, where they must be promoted to Draft status. Check this box to send this nomination directly to Draft.', 'pressforward' ) }</p>

				<CheckboxControl
					label={ __( 'Send to Draft', 'pressforward' ) }
					onChange={ ( newValue ) => {
						const newValueString = newValue ? '1' : '0'
						editPost( { meta: { 'send_to_draft': newValueString } } );
					} }
					checked={ sendToDraft }
				/>
			</PluginPrePublishPanel>

			<PluginPrePublishPanel
				icon="controls-forward"
				title={ __( 'Subscribe to Feed', 'pressforward' ) }
				initialOpen={ false }
			>
				<p>{ __( 'If PressForward can find a feed associated with this item, the feed will be added to your Subscribed Feeds list.', 'pressforward' ) }</p>

				<CheckboxControl
					label={ __( 'Subscribe to Feed', 'pressforward' ) }
					onChange={ ( newValue ) => {
						const newValueString = newValue ? '1' : '0'
						editPost( { meta: { 'subscribe_to_feed': newValueString } } );
					} }
					checked={ subscribeToFeed }
				/>

			</PluginPrePublishPanel>
		</>
	)
}

registerPlugin( 'pressforward-nomination-pre-publish-panel', {
	render: NominationPrePublishPanel,
} );

const NominationKeywordsControl = ( {} ) => {
	const {
		keywords,
		postStatus,
		postType
	} = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )
		const savedKeywords = editedPostMeta?.item_tags ? editedPostMeta.item_tags.split( ',' ) : []

		return {
			keywords: savedKeywords,
			postStatus: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
		}
	} )

	// Only show on unpublished nominations.
	const { nominationPostType } = pfBlocks
	if ( nominationPostType !== postType || 'publish' === postStatus ) {
		return null
	}

	const viaBookmarkletTags = [ __( 'via bookmarklet', 'pressforward' ) ]
	const allKeywords = [ ...keywords, ...viaBookmarkletTags ]

	return (
		<PluginDocumentSettingPanel
			icon="controls-forward"
			name="pressforward-nomination-keywords-control"
			title={ __( 'Keywords', 'pressforward' ) }
		>
			{ keywords && keywords.length > 0 && (
				<>
				<p>{ __( 'PressForward has identified the following keywords on the source item:', 'pressforward' ) }</p>

				<p><strong>{ keywords.join( ', ' ) }</strong></p>

				<p>{ __( 'Often, keywords on source content are not useful for the purposes of PressForward curation, so they are not added as nomination tags by default. You may convert the keywords to tags by clicking the button below.', 'pressforward' ) }</p>

				<Button
					isSecondary
					onClick={ () => {
						assignTags( allKeywords )
					} }
				>
					{ __( 'Add Keywords as Tags', 'pressforward' ) }
				</Button>

				</>
			) }

			{ keywords.length === 0 && (
				<p>{ __( 'PressForward was unable to identify any keywords on the source item. Use the "Tags" interface to add your own keywords.', 'pressforward' ) }</p>
			) }
		</PluginDocumentSettingPanel>
	)
}

registerPlugin( 'pressforward-nomination-keywords-control', {
	render: NominationKeywordsControl
} );

/**
 * Poor-man's redirect after publishing a nomination.
 *
 * PostPublishPanel doesn't appear properly after 'nomination' publication.
 *
 * @param {Object} wp
 */
( function( wp ) {
	const select = wp.data.select;
	const subscribe = wp.data.subscribe;
	const params = new URLSearchParams( document.location.search )
	const url = params.get( 'u' )

	const checkIfSavedAndRedirect = () => {
		const postType = select( 'core/editor' ).getEditedPostAttribute( 'type' );

		if ( 'nomination' !== postType ) {
			return;
		}

		const postStatus = select( 'core/editor' ).getEditedPostAttribute( 'status' );
		if ( 'publish' !== postStatus ) {
			return;
		}

		const isSaving = select( 'core/editor' ).isSavingPost();
		const didSaveSucceed = select( 'core/editor' ).didPostSaveRequestSucceed();
		const isAutoSaving = select( 'core/editor' ).isAutosavingPost();
		const isNewPost = select( 'core/editor' ).isEditedPostNew();

		if ( isSaving && ! isAutoSaving && didSaveSucceed && ! isNewPost ) {
			setTimeout( () => {
				window.location.href = `${ pfNominateThisBlockEditor.nominationSuccessUrl }&nominatedUrl=${ url }`;
			}, 1000 );
		}
	}

	subscribe( checkIfSavedAndRedirect );
} )( window.wp );
