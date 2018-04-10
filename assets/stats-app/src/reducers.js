import { combineReducers } from 'redux';

function authorsSet( state = [], action ){
	switch ( action.type ){
		case 'ADD_AUTHORS':
			return [
				...state,
				{
					id: action.id,
					authorObj: action.author
				}
			]
		case 'FILL_AUTHORS':
			let added = false;
			let newState = state.map(
				author => {
						if ( author.id === action.id ){
						 author.count += action.author.count;
						 added = true;
					} else {
						author = author;
					}
				}
			);
			if ( false === added ){
				newState.push({
					id: action.id,
					authorObj: action.author
				});
			}
			return newState;
		default:
			return state;
	}
}

function pageSet( state = 1, action ){
	switch (action.type) {
		case 'SET_PAGE':
			if ( state != action.page ){
				return action.page;
			} else {
				return state;
			}
			break;
		default:
			return state;

	}
};

const statsApp = combineReducers({
	authorsSet,
	pageSet
});

//export { authorsSet, pageSet };
export default statsApp
