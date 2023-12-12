// WordPress dependencies.
import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Internal dependencies.
import metadata from './block.json';

import './editor-style.scss';

const Edit = () => {
	return (
		<div { ...useBlockProps() }>
			<div className="nominate-this-bookmarklet-code">
				<span className="nominate-this-bookmarklet">
					<span>{ __( 'Nominate This', 'pressforward' ) }</span>
				</span>

				<button className="nominate-this-js-toggle">
					<span className="dashicons dashicons-clipboard"></span>
					<span className="screen-reader-text">{ __( 'Show code', 'pressforward' ) }</span>
				</button>
			</div>
		</div>
	);
};

registerBlockType( metadata, {
	edit: Edit
} );
