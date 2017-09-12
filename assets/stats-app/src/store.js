import { createStore } from 'redux';
import statsApp from './reducers'

let startingStore = function(){
	let startingStore = {
		authorsSet: [],
		pageSet: 0
	}

	return startingStore;
}();
//console.log(statsApp);
let store = createStore( statsApp, startingStore, window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__() );
