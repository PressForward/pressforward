// WordPress dependencies.
import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';

// Internal dependencies.
import metadata from './block.json';

const Edit = () => {
	return (
		<div { ...useBlockProps() }>
			Placeholder
		</div>
	);
};

registerBlockType( metadata, {
	edit: Edit
} );
