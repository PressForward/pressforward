import { registerPlugin } from '@wordpress/plugins'

import {
	PanelRow,
	TextControl
} from '@wordpress/components';

import { __, sprintf } from '@wordpress/i18n'

import { PluginDocumentSettingPanel } from '@wordpress/edit-post'

import { useDispatch, useSelect } from '@wordpress/data'

import './nominate-this-block-editor.scss'

const NominationSettingsControl = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const {
		itemAuthor,
		itemLink,
		nominationCount,
		postStatus
	} = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		const savedItemAuthor = editedPostMeta?.item_author || ''
		const savedItemLink = editedPostMeta?.item_link || ''
		const savedNominationCount = editedPostMeta?.nomination_count || 0

		return {
			itemAuthor: savedItemAuthor,
			itemLink: savedItemLink,
			nominationCount: savedNominationCount,
			postStatus: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
		}
	} )

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

			{ isPublished && (
				<PanelRow>
					{ nominationCountText }
				</PanelRow>
			) }

		</PluginDocumentSettingPanel>
	)
}

registerPlugin( 'pressforward-nomination-settings-control', {
	icon: 'users',
	render: NominationSettingsControl,
} );
