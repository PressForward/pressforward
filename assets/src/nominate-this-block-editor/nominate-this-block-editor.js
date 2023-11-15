import { registerPlugin } from '@wordpress/plugins'

import {
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

const NominationSettingsControl = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const {
		dateNominated,
		itemAuthor,
		itemLink,
		nominationCount,
		postStatus,
		postType
	} = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		const savedDateNominated = editedPostMeta?.date_nominated || ''
		const savedItemAuthor = editedPostMeta?.item_author || ''
		const savedItemLink = editedPostMeta?.item_link || ''
		const savedNominationCount = editedPostMeta?.nomination_count || 0

		return {
			dateNominated: savedDateNominated,
			itemAuthor: savedItemAuthor,
			itemLink: savedItemLink,
			nominationCount: savedNominationCount,
			postStatus: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
		}
	} )

	// Only show on 'nomination' post type.
	if ( postType !== 'nomination' ) {
		return null
	}

	const isPublished = postStatus === 'publish'

	const editPostMeta = ( metaToUpdate ) => {
		editPost( { meta: metaToUpdate } );
	};

	// translators: %s: nomination count
	const nominationCountText = sprintf( __( 'Nomination Count: %s', 'pressforward' ), nominationCount )

	return (
		<PluginDocumentSettingPanel
			icon="controls-forward"
			name="pressforward-nomination-settings-control"
			title={ __( 'Nomination Settings', 'pressforward' ) }
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
	)
}

registerPlugin( 'pressforward-nomination-settings-control', {
	render: NominationSettingsControl,
} );

const NominationPrePublishPanel = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const { sendToDraft } = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		const savedSendToDraft = editedPostMeta?.send_to_draft || false

		return {
			sendToDraft: savedSendToDraft,
		}
	} )

	return (
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
	)
}

registerPlugin( 'pressforward-nomination-pre-publish-panel', {
	render: NominationPrePublishPanel,
} );
