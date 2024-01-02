/* global pfBlocks */

import { registerPlugin } from '@wordpress/plugins'
import { useDispatch, useSelect } from '@wordpress/data'
import { __, sprintf } from '@wordpress/i18n'
import { PluginDocumentSettingPanel } from '@wordpress/edit-post'
import { PanelRow, SelectControl } from '@wordpress/components'

// Load blocks.
import './bookmarklet-code';
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
