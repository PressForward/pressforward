<?php
/**
 * Template for stats tools tab.
 *
 * @package PressForward
 */

wp_enqueue_script( 'pf-api' );

?>
<div id="stats">
	<div style="width: 100%">
		<p><?php esc_html_e( 'About your PressForward Install (Requires the WordPress REST API.)', 'pressforward' ); ?></p>
		<p id="top-level"></p>
		<button id="build-authors" class="button button-secondary"><?php esc_html_e( 'Assemble Author Stats', 'pressforward' ); ?></button>
		<button id="build-valid-posts" class="button button-secondary"><?php esc_html_e( 'Assemble Valid Posts for Stats', 'pressforward' ); ?></button>
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
