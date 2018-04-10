import { h, render } from 'preact';
import { Provider } from 'preact-redux';
import store from './store';
import App from './components/App';
//import './style';
/** @jsx h */

const container = document.getElementById('stats-app');

render((
	<div id="outer">
		<Provider store={store}>
			<App />
		</Provider>
	</div>
), container);
