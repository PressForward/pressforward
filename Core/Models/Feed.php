<?php
/**
 * Feed model.
 *
 * @package PressForward
 */

namespace PressForward\Core\Models;

/**
 * Feed class.
 */
class Feed extends BasicModel {
	/**
	 * Post type.
	 *
	 * @access protected
	 * @var string
	 */
	protected static $post_type = 'pf_feed';

	/**
	 * Data array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $data = array();

	/**
	 * Initalizes model.
	 *
	 * @param array  $item      Data about item.
	 * @param array  $handlers  Controllers.
	 * @param string $post_type Post type name.
	 */
	public function init( $item = array(), $handlers = array(), $post_type = '' ) {
		$this->type     = 'feed';
		$this->type_key = 'feed';
	}

	/**
	 * Fetches an instance based on post ID.
	 *
	 * @param int $id Post ID.
	 * @return Feed|null
	 */
	public static function get_instance_by_id( $id ) {
		$post = get_post( $id );
		if ( ! $post ) {
			return null;
		}

		if ( self::$post_type !== $post->post_type ) {
			return null;
		}

		$feed = new Feed();
		$feed->set( 'id', $post->ID );
		$feed->set( 'title', $post->post_title );

		$feed_url = get_post_meta( $post->ID, 'feedUrl', true );
		if ( ! $feed_url ) {
			$feed_url = get_post_meta( $post->ID, 'feed_url', true );
		}

		$feed->set( 'remote_feed_url', $feed_url );

		return $feed;
	}

	/**
	 * Gets the next scheduled retrieval for this feed.
	 *
	 * @return int|null Unix timestamp.
	 */
	public function get_next_scheduled_retrieval() {
		$next_retrieval = wp_next_scheduled(
			'pf_retrieve_feed',
			[
				'feed_id' => $this->get( 'id' ),
			]
		);

		if ( ! $next_retrieval ) {
			return null;
		}

		return $next_retrieval;
	}

	/**
	 * Schedules a feed retrieval.
	 *
	 * @param array $args {
	 *    Array of optional arguments.
	 *    @type int $interval Interval in seconds.
	 *    @type int $nextrun  Unix timestamp.
	 * }
	 * @return true|\WP_Error True if scheduled, WP_Error if not. See wp_schedule_event().
	 */
	public function schedule_retrieval( $args = [] ) {
		$r = array_merge(
			[
				'interval' => 'pf_interval',
				'nextrun'  => time() + MINUTE_IN_SECONDS,
			],
			$args
		);

		// Prevent duplicate schedules.
		$next_retrieval = $this->get_next_scheduled_retrieval();
		if ( $next_retrieval ) {
			wp_unschedule_event( $next_retrieval, 'pf_retrieve_feed', [ 'feed_id' => $this->get( 'id' ) ] );
		}

		$scheduled = wp_schedule_event(
			$r['nextrun'],
			$r['interval'],
			'pf_retrieve_feed',
			[
				'feed_id' => $this->get( 'id' ),
			],
			true
		);

		return $scheduled;
	}

	/**
	 * Retrieves the feed.
	 *
	 * @return void
	 */
	public function retrieve() {
		$module = $this->get_module();

		if ( ! $module ) {
			return;
		}

		$feed_id = $this->get( 'id' );

		$feed_post = get_post( $feed_id );

		$feed_data_object = $module->get_data_object( $feed_post );
		if ( ! $feed_data_object ) {
			return;
		}

		$feed_data_object['parent_feed_id'] = $feed_id;

		pressforward( 'schema.feed_item' )->assemble_feed_for_pull( $feed_data_object );
		pressforward( 'schema.feeds' )->set_feed_last_checked( $feed_id );
	}

	/**
	 * Gets the module to be used for this feed.
	 *
	 * @return \PF_Module|null Module object or null.
	 */
	public function get_module() {
		$feeds_schema = pressforward( 'schema.feeds' );
		$feed_type    = $feeds_schema->get_pf_feed_type( $this->get( 'id' ) );

		$module = null;
		foreach ( pressforward( 'modules' )->modules as $module ) {
			if ( $feed_type === $module->feed_type ) {
				break;
			}
		}

		return $module;
	}
}
