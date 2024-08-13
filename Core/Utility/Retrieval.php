<?php
/**
 * Retrieval utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\Utility;

use PressForward\Core\Models\Feed;

/**
 * Feed 'slurping' class.
 *
 * This class handles some aspects of retrieving items from feeds.
 * This class should only contain those functions that can be generalized
 * to work on multiple content retrieval methods (not just RSS).
 */
class Retrieval {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Schedule our cron actions for fetching feeds.
		add_action( 'init', array( $this, 'schedule_feed_out' ) );

		add_action( 'take_feed_out', array( pressforward( 'schema.feed_item' ), 'disassemble_feed_items' ) );

		add_action( 'pf_retrieve_feed', [ $this, 'retrieve_feed' ] );

		// Health check cron job callback.
		add_action( 'pf_feed_health_check', [ $this, 'trigger_feed_health_check' ], 10, 2 );

		// Use the legacy pull_feed_in cron event to trigger the 5.6.0 migration.
		add_action( 'pull_feed_in', [ $this, 'create_5_6_0_retrieval_events' ] );

		// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_filter( 'cron_schedules', array( $this, 'cron_add_short' ) );

		if ( is_admin() ) {
			add_action( 'wp_ajax_ajax_update_feed_handler', array( $this, 'ajax_update_feed_handler' ) );
			add_action( 'get_more_feeds', array( pressforward( 'schema.feed_item' ), 'assemble_feed_for_pull' ) );
		}
	}

	/**
	 * Adds our schedules to the array of cron schedules.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public function cron_add_short( $schedules ) {
		// Adds once weekly to the existing schedules.
		$pf_interval              = get_option( PF_SLUG . '_retrieval_frequency', 30 );
		$schedules['pf_interval'] = array(
			'interval' => $pf_interval * 60,
			'display'  => __( 'PressForward Retrieval Interval', 'pressforward' ),
		);
		return $schedules;
	}

	/**
	 * Schedules the monthly feed item cleanup.
	 */
	public function schedule_feed_out() {
		if ( ! wp_next_scheduled( 'take_feed_out' ) ) {
			wp_schedule_event( time(), 'hourly', 'take_feed_out' );
		}
	}

	/**
	 * Retrieves a feed.
	 *
	 * @since 5.6.0
	 *
	 * @param int $feed_id Feed ID.
	 * @return void
	 */
	public function retrieve_feed( $feed_id ) {
		$feed = Feed::get_instance_by_id( $feed_id );
		if ( ! $feed ) {
			return;
		}

		$feed->retrieve();
	}

	/**
	 * Triggers a health check for a feed.
	 *
	 * @since 5.6.0
	 *
	 * @param int  $feed_id     Feed ID.
	 * @param bool $is_new_feed Whether this is a new feed.
	 * @return void
	 */
	public function trigger_feed_health_check( $feed_id, $is_new_feed = false ) {
		$feed = Feed::get_instance_by_id( $feed_id );
		if ( ! $feed ) {
			return;
		}

		$feed->health_check( $is_new_feed );
	}

	/**
	 * Where we store a list of feeds to check.
	 *
	 * We need this to handle some sort of subsets of feeds
	 * Eventually it should be going through these queries
	 * as pages to decrease server load from giant query
	 * results.
	 */
	public function pf_feedlist() {
		// @TODO Not this way.
		$args = array(
			'posts_per_page' => -1,
			'post_status'    => array( 'publish' ),
		);

		$args      = apply_filters( 'pf_feedlist_args', $args );
		$the_feeds = pressforward( 'schema.feeds' )->get( $args );
		$feedlist  = array();

		if ( ! isset( $the_feeds ) ) {
			// @todo a better error report.
			return false;
		} elseif ( is_wp_error( $the_feeds ) ) {
			return $the_feeds;
		} else {
			foreach ( $the_feeds as $a_feed ) {
				$feedlist[] = $a_feed;
			}
		}

		$all_feeds_array         = apply_filters( 'imported_rss_feeds', $feedlist );
		$ordered_all_feeds_array = array_values( $all_feeds_array );

		return $ordered_all_feeds_array;
	}

	/**
	 * Check if the requested feed_type exists.
	 *
	 * @param  string $type feed_type to check against.
	 * @return string|bool id of matching module, false if no match.
	 */
	public function does_type_exist( $type ) {
		// 'rss-quick' is a legacy type.
		if ( 'rss-quick' === $type ) {
			$type = 'rss';
		}

		foreach ( pressforward( 'modules' )->modules as $module ) {
			if ( $module->feed_type === $type ) {
				// id and slug should be the same right?
				return $module->id;
			}
		}

		return false;
	}

	/**
	 * This will attempt to retrieve the feed based on an available module function.
	 *
	 * @param string   $module_to_use Module name.
	 * @param \WP_Post $a_feed_obj    A feed object.
	 */
	public function get_the_feed_object( $module_to_use, $a_feed_obj ) {
		$module   = pressforward( 'modules' )->modules[ $module_to_use ];
		$feed_obj = $module->get_data_object( $a_feed_obj );
		if ( empty( $feed_obj ) ) {
			return false;
		} else {
			$feed_obj['parent_feed_id'] = $a_feed_obj->ID;
			return $feed_obj;
		}
	}

	/**
	 * AJAX handler for wp_ajax_ajax_update_feed_handler action.
	 */
	public function ajax_update_feed_handler() {
		$post_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;

		if ( ! $post_id ) {
			pf_log( 'No feed ID was passed to the ajax handler.' );
			die();
		}

		pf_log( 'Starting ajax_update_feed_handler with ID of ' . $post_id );

		$feed = Feed::get_instance_by_id( $post_id );
		if ( ! $feed ) {
			pf_log( 'The feed object was not retrieved.' );
			return;
		}

		$retrieved_status = $feed->retrieve();

		$retval = [
			'dateRetrieved'       => $retrieved_status['date_retrieved'],
			'itemsAdded'          => $retrieved_status['items_added'],
			'nextRetrievalDate'   => $feed->get_next_scheduled_retrieval_date(),
			'nextRetrievalString' => $feed->get_next_scheduled_retrieval_string(),
			'feedItemCount'       => pressforward( 'schema.feeds' )->count_feed_items_collected( $post_id )->publish,
		];

		if ( ! empty( $retval['dateRetrieved'] ) ) {
			wp_send_json_success( $retval );
		} else {
			wp_send_json_error( $retval );
		}
	}

	/**
	 * Create cron events for individual feeds.
	 *
	 * @since 5.6.0
	 *
	 * @return void
	 */
	public function create_5_6_0_retrieval_events() {
		$migration_complete = get_option( 'pf_retrieval_migration_5_6_0' );
		if ( $migration_complete ) {
			return;
		}

		$feedlist = $this->pf_feedlist();
		foreach ( $feedlist as $feed_post ) {
			$feed = Feed::get_instance_by_id( $feed_post->ID );
			if ( ! $feed ) {
				continue;
			}

			// If the retrieval is already scheduled, there's nothing more to do.
			$next_scheduled = $feed->get_next_scheduled_retrieval();
			if ( $next_scheduled ) {
				continue;
			}

			// We don't want the retrieval for all items to happen simultaneously,
			// so we set a random offset of up to 15 minutes.
			$nextrun = time() + wp_rand( 0, 900 );
			$feed->schedule_retrieval(
				[
					'nextrun' => $nextrun,
				]
			);
		}

		// No more need for the existing 'pull_feed_in' cron event.
		wp_clear_scheduled_hook( 'pull_feed_in' );
		update_option( 'pf_retrieval_migration_5_6_0', '1' );
	}

	/**
	 * Gets a count of feeds.
	 *
	 * @since 5.7.0
	 *
	 * @return int
	 */
	public function get_feed_count() {
		$args = array(
			'post_type'      => 'pf_feed',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		$feeds = pressforward( 'schema.feeds' )->get( $args );

		return count( $feeds );
	}

	/**
	 * Gets a count of feed items.
	 *
	 * @since 5.7.0
	 *
	 * @param bool $exclude_promoted Whether to exclude promoted items.
	 * @param int  $since            Unix timestamp.
	 * @return int
	 */
	public function get_feed_item_count( $exclude_promoted = false, $since = 0 ) {
		$args = array(
			'post_type'      => 'pf_feed_item',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'date_query'     => array(
				'after' => gmdate( 'Y-m-d H:i:s', $since ),
			),
		);

		if ( $exclude_promoted ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'pf_is_promoted',
					'value'   => '1',
					'compare' => '!=',
				),
			);
		}

		$feed_items = pressforward( 'schema.feed_item' )->get( $args );

		return count( $feed_items );
	}
}
