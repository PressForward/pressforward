export const assignTags = async ( tagNames ) => {
	try {
		const tagMap = await ensureTagsExistAndGetIds( tagNames );
		wp.data.dispatch( 'core/editor' ).editPost( { tags: Object.values( tagMap ) } );
	} catch (error) {
		// eslint-disable-next-line no-console
		console.error('Error creating tags:', error);
	}
}

export const ensureTagsExistAndGetIds = async (tagNames) => {
	// Create a new collection instance for the tags
	const Tags = wp.api.collections.Tags;

	const tagPromises = tagNames.map((tagName) => {
		// Create a new promise that wraps the jQuery promise
		return new Promise((resolve, reject) => {
			const tagsCollection = new Tags();

			// HTML encode ampersands in tag names to avoid 400 errors
			const encodedTagName = tagName.replace(/&/g, '&amp;');
			tagsCollection.fetch({ data: { search: encodedTagName } })
				.done((tags) => {
					let tagData = {};

					if ( ! tags.length ) {
						// Create a new tag if one doesn't exist.
						const newTag = new wp.api.models.Tag({ name: tagName });
						newTag.save().done((tag) => {
							tagData[tagName] = tag.id;
							resolve(tagData);
						});
					} else {
						// Ensure that we are only matching exact tagName matches.
						tags = tags.filter((tag) => tag.name === encodedTagName);

						// Resolve with an object mapping tag names to tag IDs
						tagData = tags.reduce((acc, tag) => {
							acc[tag.name] = tag.id;
							return acc;
						}, {});

						resolve(tagData);
					}
				})
				.fail((error) => {
					// eslint-disable-next-line no-console
					console.error(`Failed to fetch tag for ${tagName}:`, error);
					reject(error); // Reject the promise on failure
				});
		});
	});

	const tagResults = await Promise.all(tagPromises);
	const tagMap = Object.assign({}, ...tagResults);

	return tagMap;
};
