import { registerPlugin } from '@wordpress/plugins'

import {
	PanelRow,
	TextControl
} from '@wordpress/components';

import { __, sprintf } from '@wordpress/i18n'

import { PluginDocumentSettingPanel } from '@wordpress/edit-post'

import { useDispatch, useSelect } from '@wordpress/data'

const NominationSettingsControl = ( {} ) => {
	const { editPost } = useDispatch( 'core/editor' )

	const { itemAuthor, nominationCount, postId, postStatus } = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		const savedItemAuthor = editedPostMeta?.item_author || ''
		const savedNominationCount = editedPostMeta?.nomination_count || 0

		return {
			itemAuthor: savedItemAuthor,
			nominationCount: savedNominationCount,
			postId: select( 'core/editor' ).getCurrentPostId(),
			postStatus: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
		}
	} )

	const isPublished = postStatus === 'publish'

	const editPostMeta = ( metaToUpdate ) => {
		editPost( { meta: metaToUpdate } );
	};

	return (
		<PluginDocumentSettingPanel
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

			{ isPublished && (
				<PanelRow>
					{ sprintf( __( 'Nomination Count: %s', 'pressforward' ), nominationCount ) }
				</PanelRow>
			) }

		</PluginDocumentSettingPanel>
	)
}

registerPlugin( 'pressforward-nomination-settings-control', {
	icon: 'users',
	render: NominationSettingsControl,
} );
