<?php
/**
 * FeedSource interface.
 *
 * @since 5.9.0
 *
 * @package PressForward
 */

namespace PressForward\Interfaces;

/**
 * Interface FeedSource
 *
 * @package PressForward\Interfaces
 */
interface FeedSource {
	/**
	 * Fetches data from a feed.
	 *
	 * @param \PressForward\Core\Models\Feed $feed Feed object.
	 * @return array|\WP_Error
	 */
	public function fetch( $feed );

	/**
	 * Performs a health check on the feed.
	 *
	 * @param \PressForward\Core\Models\Feed $feed        Feed object.
	 * @param bool                           $is_new_feed Whether the feed is new.
	 */
	public function health_check( \PressForward\Core\Models\Feed $feed, $is_new_feed = false );
}
