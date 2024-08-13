/* global pfBlocks */

import { registerPlugin } from '@wordpress/plugins'
import { useDispatch, useSelect } from '@wordpress/data'
import { __, sprintf } from '@wordpress/i18n'
import { PluginDocumentSettingPanel } from '@wordpress/edit-post'
import { PanelRow, SelectControl, TextControl } from '@wordpress/components'

// Load blocks.
import './bookmarklet-code';
import './item-credits';
import './item-nominators';

// Other miscellaneous Block Editor-related modifications.
const PostSettingsControl = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const { draftPostType, linkToSource } = pfBlocks

	const {
		forwardToOrigin,
		itemLink,
		postType
	} = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		const savedForwardToOrigin = editedPostMeta?.pf_forward_to_origin ? editedPostMeta.pf_forward_to_origin : []
		const savedItemLink = editedPostMeta?.item_link ? editedPostMeta.item_link : ''

		return {
			forwardToOrigin: savedForwardToOrigin,
			itemLink: savedItemLink,
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
		}
	} )

	// Only show on 'draft' post type (default 'post').
	if ( postType !== draftPostType ) {
		return null
	}

	// If there's no itemLink, this is probably not a PressForward item.
	if ( ! itemLink ) {
		return null
	}

	const editPostMeta = ( metaToUpdate ) => {
		editPost( { meta: metaToUpdate } );
	};

	const sourceLink = <a href={ itemLink } target="_blank" rel="noopener noreferrer">{ itemLink }</a>

	const linkToSourceInt = parseInt( linkToSource )

	return (
		<>
			<PluginDocumentSettingPanel
				icon="controls-forward"
				name="pressforward-post-settings-control"
				title={ __( 'PressForward', 'pressforward' ) }
			>
				<SelectControl
					label={ __( "Forward to item's original URL?", 'pressforward' ) }
					description={ __( "If you want to forward this nomination to the original URL, select 'Forward'.", 'pressforward' ) }
					onChange={ ( newForwardToOrigin ) => {
						editPostMeta( { 'pf_forward_to_origin': newForwardToOrigin } );
					} }
					value={ forwardToOrigin }
					options={ [
						{ label: __( "Don't Forward", 'pressforward' ), value: 'no-forward' },
						{ label: __( 'Forward', 'pressforward' ), value: 'forward' },
					] }
				/>

				<p className="description">
					{ __( 'If set to "Forward", visitors to this item will be forwarded to the source item URL.', 'pressforward' ) }
					{ 0 === linkToSourceInt && (
						<>
							<br />
							<span>
								{ __( 'Note that the redirect-to-source feature is currently disabled at the site level. To enable, visit Dashboard > Preferences > Site Options and increase "Seconds to redirect…" to a value greater than 0.', 'pressforward' ) }
							</span>
						</>
					) }
				</p>

				<PanelRow>
					{ sourceLink }
				</PanelRow>
			</PluginDocumentSettingPanel>
		</>
	)
}

registerPlugin(
	'pressforward-post-settings-control',
	{ render: PostSettingsControl }
);

const NominationSettingsControl = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const {
		dateNominated,
		itemAuthor,
		itemLink,
		nominationCount,
		postStatus,
		postType,
		sourcePublicationName,
		sourcePublicationUrl
	} = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )
		const editedPost = select( 'core/editor' ).getCurrentPost()

		const savedNominators = editedPost?.nominators ||  {}

		const savedDateNominated = editedPostMeta?.date_nominated || ''
		const savedItemAuthor = editedPostMeta?.item_author || ''
		const savedItemLink = editedPostMeta?.item_link || ''
		const savedNominationCount = Object.keys( savedNominators ).length

		const savedSourcePublicationName = editedPostMeta?.source_publication_name || ''
		const savedSourcePublicationUrl = editedPostMeta?.source_publication_url || ''

		return {
			dateNominated: savedDateNominated,
			itemAuthor: savedItemAuthor,
			itemLink: savedItemLink,
			nominationCount: savedNominationCount,
			postStatus: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
			postType: select( 'core/editor' ).getEditedPostAttribute( 'type' ),
			sourcePublicationName: savedSourcePublicationName,
			sourcePublicationUrl: savedSourcePublicationUrl
		}
	} )

	const { draftPostType, nominationPostType } = pfBlocks

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

				{ sourcePublicationName && (
					<PanelRow>
						<div className="panel-entry">
							<div className="panel-entry-label">
								<span className="components-base-control__label-text">{ __( 'Source Publication', 'pressforward' ) }</span>
							</div>

							<div className="panel-entry-content">
								{ sourcePublicationUrl ? (
									<a href={ sourcePublicationUrl } target="_blank" rel="noopener noreferrer">{ sourcePublicationName }</a>
								) : (
									sourcePublicationName
								) }
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
		</>
	)
}

registerPlugin( 'pressforward-nomination-settings-control', {
	render: NominationSettingsControl,
} );

