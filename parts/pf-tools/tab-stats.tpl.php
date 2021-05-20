<div id="stats">
	<div style="width: 100%">
		<p><?php esc_html_e( 'About your PressForward Install (Requires the WordPress REST API.)', 'pf' ); ?></p>
		<p id="top-level"></p>
		<a href="#" id="build-authors" class="button" onclick="window.pf.stats.authors.getLeaderboard(true);"><?php esc_html_e( 'Assemble Author Stats', 'pf' ); ?></a>
		<a href="#" id="build-valid-posts" class="button" onclick="window.pf.stats.valid_posts.getLeaderboard(true);"><?php esc_html_e( 'Assemble Valid Posts for Stats', 'pf' ); ?></a>
	</div>
	<div id="stats-app"></div>
	<div id="author-leaderboard" style="width: 49%; float: left;">
		<ul>
		</ul>
	</div>
	<div id="sources-leaderboard" style="width: 49%; float: right;">
		<ul>
		</ul>
	</div>
</div>
