// WordPress dependencies.
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, PanelRow, ToggleControl } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

// Internal dependencies.
import metadata from './block.json';

import './editor-style.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const {
		backgroundColor,
		prefixAuthorName,
		prefixSourceLink ,
		showAuthorName,
		showSourceLink,
	} = attributes;

	const { authorName, sourceLink } = useSelect( ( select ) => {
		const editedPostMeta = select( 'core/editor' ).getEditedPostAttribute( 'meta' )

		const savedAuthorName = editedPostMeta?.item_author || ''
		const savedSourceLink = editedPostMeta?.item_link || ''

		return {
			authorName: savedAuthorName,
			sourceLink: savedSourceLink,
		}
	} )

	const blockProps = useBlockProps({
		style: {
			backgroundColor
		},
	});

	return (
		<div { ...blockProps }>
			{ showAuthorName && (
				<div className="pf-credits-line">
					<RichText
						className="pf-credits-prefix"
						tagName="p"
						value={ prefixAuthorName }
						onChange={ ( newPrefixAuthorName ) => setAttributes( { prefixAuthorName: newPrefixAuthorName } ) }
						placeholder={ __( 'Author: ', 'pressforward' ) }
					/>

					<p className="pf-credits-author-name">
						{ authorName }
					</p>
				</div>
			) }

			{ showSourceLink && (
				<div className="pf-credits-line">
					<RichText
						className="pf-credits-prefix"
						tagName="p"
						value={ prefixSourceLink }
						onChange={ ( newPrefixSourceLink ) => setAttributes( { prefixSourceLink: newPrefixSourceLink } ) }
						placeholder={ __( 'Source: ', 'pressforward' ) }
					/>

					<p className="pf-credits-source-link">
						<a href={ sourceLink }>{ sourceLink }</a>
					</p>
				</div>
			) }

			<InspectorControls>
				<Panel title={ __( 'PressForward Credits', 'pressforward' ) }>
					<PanelBody title={ __( 'Settings', 'pressforward' ) }>
						<PanelRow>
							<ToggleControl
								label={ __( 'Show Author Name', 'pressforward' ) }
								checked={ showAuthorName }
								onChange={ () => setAttributes( { showAuthorName: ! showAuthorName } ) }
							/>
						</PanelRow>

						<PanelRow>
							<ToggleControl
								label={ __( 'Show Source Link', 'pressforward' ) }
								checked={ showSourceLink }
								onChange={ () => setAttributes( { showSourceLink: ! showSourceLink } ) }
							/>
						</PanelRow>
					</PanelBody>
				</Panel>
			</InspectorControls>
		</div>
	);
};

registerBlockType( metadata, {
	edit: Edit
} );
