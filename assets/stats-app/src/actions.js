//let authorsSet = {};
export const fillAuthors = ( key, object ) => {
	return {
		type: 'FILL_AUTHORS',
		id: key,
		author: object
	}
}

export const pageCheck = ( pageNumber ) => {
	return {
		type: 'SET_PAGE',
		page: pageNumber
	}
}
