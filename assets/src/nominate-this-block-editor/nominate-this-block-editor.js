/* global pfNominateThisBlockEditor */
import { registerPlugin } from '@wordpress/plugins'

import {
	Button,
	CheckboxControl,
	PanelRow,
	TextControl
} from '@wordpress/components';

import { __, sprintf } from '@wordpress/i18n'

import {
	PluginDocumentSettingPanel ,
	PluginPrePublishPanel
} from '@wordpress/edit-post'

import { useDispatch, useSelect } from '@wordpress/data'

import './nominate-this-block-editor.scss'

import { assignTags } from '../util/tags'

const NominationSettingsControl = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const {
		dateNominated,
		itemAuthor,
		itemLink,
		keywords,
		nominationCount,
		postStatus,
		postType
	} = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )
		const editedPost = select( 'core/editor' ).getCurrentPost()

		const savedNominators = editedPost?.nominators ||  {}

		const savedDateNominated = editedPostMeta?.date_nominated || ''
		const savedItemAuthor = editedPostMeta?.item_author || ''
		const savedItemLink = editedPostMeta?.item_link || ''
		const savedNominationCount = Object.keys( savedNominators ).length
		const savedKeywords = editedPostMeta?.item_tags ? editedPostMeta.item_tags.split( ',' ) : []

		return {
			dateNominated: savedDateNominated,
			itemAuthor: savedItemAuthor,
			itemLink: savedItemLink,
			keywords: savedKeywords,
			nominationCount: savedNominationCount,
			postStatus: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
		}
	} )

	const { draftPostType, nominationPostType } = pfNominateThisBlockEditor

	// Only show on 'nomination' post type.
	if ( nominationPostType !== postType && draftPostType !== postType ) {
		return null
	}

	const isPublished = postStatus === 'publish'

	const editPostMeta = ( metaToUpdate ) => {
		editPost( { meta: metaToUpdate } );
	};

	// translators: %s: nomination count
	const nominationCountText = sprintf( __( 'Nomination Count: %s', 'pressforward' ), nominationCount )

	const viaBookmarkletTags = [ 'via bookmarklet' ]
	const allKeywords = [ ...keywords, ...viaBookmarkletTags ]

	return (
		<>
			<PluginDocumentSettingPanel
				icon="controls-forward"
				name="pressforward-nomination-settings-control"
				title={ __( 'Nomination Info', 'pressforward' ) }
			>
				<PanelRow>
					<TextControl
						label={ __( 'Author on Source', 'pressforward' ) }
						onChange={ ( newItemAuthor ) => {
							editPostMeta( { 'item_author': newItemAuthor } );
						} }
						value={ itemAuthor }
					/>
				</PanelRow>

				{ itemLink && (
					<PanelRow>
						<div className="panel-entry">
							<div className="panel-entry-label">
								<span className="components-base-control__label-text">{ __( 'Source Link', 'pressforward' ) }</span>
							</div>

							<div className="panel-entry-content">
								<a href={ itemLink } target="_blank" rel="noopener noreferrer">{ itemLink }</a>
							</div>
						</div>
					</PanelRow>
				) }

				{ isPublished && dateNominated && (
					<PanelRow>
						<div className="panel-entry">
							<div className="panel-entry-label">
								<span className="components-base-control__label-text">{ __( 'Date Nominated', 'pressforward' ) }</span>
							</div>

							<div className="panel-entry-content">
								{ dateNominated }
							</div>
						</div>
					</PanelRow>
				) }

				{ isPublished && (
						<PanelRow>
							{ nominationCountText }
						</PanelRow>
				) }
			</PluginDocumentSettingPanel>

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
		</>
	)
}

registerPlugin( 'pressforward-nomination-settings-control', {
	render: NominationSettingsControl,
} );

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
