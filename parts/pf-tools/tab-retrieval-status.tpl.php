<div id="status_check">
	<span id="retrieving_feeds">
	<?php
		/**
		 * $feed_hb_state = array(
		 * 'feed_id'	=>	$aFeed->ID,
		 * 'feed_title'	=> $aFeed->post_title,
		 * 'last_key'	=> $last_key,
		 * 'feeds_iteration'	=>	$feeds_iteration,
		 * 'total_feeds'	=>	count($feedlist)
		 * );
		 **/

		$feed_hb_state = get_option( PF_SLUG . '_feeds_hb_state' );
		$iteration = $feed_hb_state['feeds_iteration'] + 1;
		print_r(
			esc_html__( 'Retrieving feeds. Currently at %1$s feed number %2$s of %3$s', 'pf' ),
			'<span id="rf-feed-title">' . esc_html( $feed_hb_state['feed_title'] ) . '</span>',
			'<span id="rf-iteration">' . esc_html( $iteration ) . '</span>',
			'<span id="rf-total-feeds">' . esc_html( $feed_hb_state['total_feeds'] ) . '</span>'
		);
	?>
	</span>
	<div id="rf-progressbar"></div>
</div>
