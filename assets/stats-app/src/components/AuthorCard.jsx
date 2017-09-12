import { h, render, Component } from 'preact';

export default class AuthorCard extends Component {
    render( { key, author } ) {
        <li>
            <div id="{key}" class="pf-stats__author-card">
                <ul>
                    <li><strong>Name:</strong>{author.name}</li>
                    <li><strong>Count:</strong>{author.count}</li>
                    <li><strong>Author Gender:</strong>{author.gender}</li>
                    <li><strong>Gender Confidence:</strong>{author.gender_confidence}</li>
                </ul>
            </div>
        </li>
    }
};
