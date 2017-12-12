import { h, render, Component } from 'preact';
import { connect } from 'preact-redux';
import { bindActions } from '../utils';
import statsApp from '../reducers';
import * as actions from '../actions';
import AuthorCard from './AuthorCard';

@connect((state) => { return { authorsSet: state.authorsSet } })
export default class App extends Component {
    render(a, b, c, d) {
        console.log(a, b, c, d);
        (<div id="pf-stats__author-leaderboard">
			<ul>
                { authorsSet.map( author => (
                    <AuthorCard
                        key={author.id}
                        author={author.authorObj}
                    />
                )) }
			</ul>
        </div>)
    }
};

function mapStateToProps(state) {
   return { authorsSet: state.authorsSet };
}
