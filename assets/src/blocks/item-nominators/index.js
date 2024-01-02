// WordPress dependencies.
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

// Internal dependencies.
import metadata from './block.json';

import './editor-style.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const { prefix } = attributes;

	const { nominators } = useSelect( ( select ) => {
		const editedPost = select( 'core/editor' ).getCurrentPost()

		const savedNominators = editedPost.nominators ? editedPost.nominators : {}

//		const editorSettings = select( 'core/block-editor' ).getSettings()

		// Loop through the savedNominators object and fetch the user objects.
		const nominatorUsers = []
		for ( const index in savedNominators ) {
			const nominatorUser = select( 'core' ).getEntityRecord( 'root', 'user', savedNominators[ index ].user_id )
			nominatorUsers.push( nominatorUser )
		}


		return {
			nominators: nominatorUsers,
		}
	} )

	/**
	 * Generates a string of the nominators' names from the `name` property of the nominators object.
	 */
	const generateNominatorString = () => {
		if ( ! nominators ) {
			return ''
		}

		const nominatorNames = nominators.map( ( nominator ) => {
			if ( 'undefined' === typeof nominator ) {
				return null
			}

			return nominator.name
		} )

		// Remove empty values.
		nominatorNames.filter( ( name ) => name )

		return nominatorNames.join( ', ' )
	}

	return (
		<div { ...useBlockProps() }>
			<RichText
				className="pf-nominators-prefix"
				tagName="p"
				value={ prefix }
				onChange={ ( newPrefix ) => setAttributes( { prefix: newPrefix } ) }
				placeholder={ __( 'Nominated by: ', 'pressforward' ) }
			/>

			<p className="pf-nominators">
				{ generateNominatorString() }
			</p>
		</div>
	);
};

registerBlockType( metadata, {
	edit: Edit
} );
