<?php
/**
 * Retrieval utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\Utility;

use PressForward\Core\Models\Feed;

/**
 * Feed 'slurping' class
 *
 * This class handles the functions for iterating through
 * a feed list and retrieving the items in those feeds.
 * This class should only contain those functions that
 * can be generalized to work on multiple content
 * retrieval methods (not just RSS).
 */
class Retrieval {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'alter_for_retrieval' ), 999 );

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
			add_action( 'wp_ajax_nopriv_feed_retrieval_reset', array( $this, 'feed_retrieval_reset' ) );
			add_action( 'wp_ajax_feed_retrieval_reset', array( $this, 'feed_retrieval_reset' ) );
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
	 * Creates a custom nonce in order to secure feed retrieval requests.
	 *
	 * @return string
	 */
	public function get_chunk_nonce() {
		$nonce = wp_hash( time() );
		pf_log( sprintf( 'Created and saved retrieval nonce: %s', $nonce ) );
		update_option( 'chunk_nonce', $nonce );
		return $nonce;
	}

	/**
	 * A function to make absolutely sure options update.
	 *
	 * @param string $option_name  Option name.
	 * @param mixed  $option_value Option value.
	 */
	public function update_option_w_check( $option_name, $option_value ) {
		pf_log( 'Did the ' . $option_name . ' option update?' );
		$option_result = update_option( PF_SLUG . $option_name, $option_value );
		pf_log( $option_result );

		if ( ! $option_result ) {
			// Occasionally WP refuses to set an option.
			// In these situations we will take more drastic measures
			// and attempt to set it again.
			pf_log( 'For no apparent reason, the option did not update. Delete and try again.' );
			pf_log( 'Did the option delete?' );
			$delete_check = delete_option( PF_SLUG . $option_name );
			pf_log( $delete_check );
			$second_check = update_option( PF_SLUG . $option_name, $option_value );
			pf_log( 'Did the new option setup work?' );
			pf_log( $second_check );
		}
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
		global $pf;
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

		$feed->retrieve();
	}

	/**
	 * Listens for ?press=forward request and initiates retrieval process.
	 */
	public function alter_for_retrieval() {
		if ( ! isset( $_GET['press'] ) || 'forward' !== sanitize_text_field( wp_unslash( $_GET['press'] ) ) ) {
			return;
		}

		$nonce       = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		$nonce_check = get_option( 'chunk_nonce' );
		pf_log( 'Nonce is:' );
		pf_log( $nonce_check );
		if ( ! $nonce || ! $nonce_check ) {
			pf_log( 'Nonce check failed.' );
		}

		pf_log( 'Pressing forward.' );
		pf_log( 'Beginning import chunk' );

		pressforward( 'schema.feed_item' )->assemble_feed_for_pull();

		delete_option( 'chunk_nonce' );

		pf_log( "Import chunk completed.\n\n\n" );

		exit;
	}

	/**
	 * Resets feed retrieval status.
	 */
	public function feed_retrieval_reset() {
		$feed_iteration  = update_option( PF_SLUG . '_feeds_iteration', 0 );
		$retrieval_state = update_option( PF_SLUG . '_iterate_going_switch', 0 );
	}

	/**
	 * Trigger source data.
	 *
	 * @deprecated 5.6.0 Feeds are now pulled in individually.
	 *
	 * @param bool $do_return For debugging only.
	 */
	public function trigger_source_data( $do_return = false ) {
		$message         = array();
		$feed_iteration  = get_option( PF_SLUG . '_feeds_iteration', 0 );
		$retrieval_state = get_option( PF_SLUG . '_iterate_going_switch', 0 );

		pf_log( 'Invoked: PF_Feed_Retrieve::trigger_source_data()' );

		$message['iteration']       = pf_message( 'Feed iteration: ' . $feed_iteration );
		$message['iterating_check'] = pf_message( 'Retrieval state: ' . $retrieval_state );

		if ( 0 === (int) $feed_iteration && 0 === (int) $retrieval_state ) {
			$status = update_option( PF_SLUG . '_iterate_going_switch', 1 );
			// Echo to the user.

			$message['action_taken'] = pf_message( __( 'Beginning the retrieval process', 'pressforward' ), true, true );

			pf_iterate_cycle_state( 'retrieval_cycles_begun', true );

			if ( $status ) {
				pf_log( __( 'Iterate switched to going.', 'pressforward' ) );
			} else {
				pf_log( __( 'Iterate option not switched.', 'pressforward' ) );
			}

			// phpcs:disable
			if ( $do_return ) {
				@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
				print_r( json_encode( $message ) );
			}
			// phpcs:enable

			pressforward( 'schema.feed_item' )->assemble_feed_for_pull();
		} else {
			$feeds_meta_state = get_option( PF_SLUG . '_feeds_meta_state', array() );
			if ( empty( $feeds_meta_state ) ) {
				$feeds_meta_state = array(
					'feed_iteration'  => $feed_iteration,
					'retrieval_state' => $retrieval_state,
					'retrigger'       => time() + ( 2 * 60 * 60 ),
				);

				update_option( PF_SLUG . '_feeds_meta_state', $feeds_meta_state );
				$message['action_taken'] = pf_message( __( 'Created new metastate to check on next retrieval step.', 'pressforward' ), true );
			} else {
				$message['action_taken'] = pf_message( __( 'Metastate is already saved and active for next check.', 'pressforward' ), true );
				pf_log( $feeds_meta_state );
			}

			if ( $feeds_meta_state['retrigger'] > time() ) {
				$message['action_taken'] = pf_message( __( 'The sources are already being retrieved.', 'pressforward' ), true );
			} elseif ( ( (int) $feed_iteration === (int) $feeds_meta_state['feed_iteration'] ) && ( (int) $retrieval_state === (int) $feeds_meta_state['retrieval_state'] ) ) {
				$message['action_taken'] = pf_message( __( 'The sources are stuck, clearing system to activate on next retrieve.', 'pressforward' ), true );

				// Wipe the checking option for use next time.
				update_option( PF_SLUG . '_feeds_meta_state', array() );
				update_option( PF_SLUG . '_iterate_going_switch', 1 );
				pressforward( 'schema.feed_item' )->assemble_feed_for_pull();
			} elseif ( $feeds_meta_state['retrigger'] < ( time() + 86400 ) ) {
				// If it has been more than 24 hours and retrieval has been frozen in place
				// and the retrieval state hasn't been reset, reset the check values and reset
				// the meta state. If it is actually mid-process things should progress.
				// Otherwise next meta-state check will iterate forward.
				update_option( PF_SLUG . '_feeds_meta_state', array() );
				update_option( PF_SLUG . '_iterate_going_switch', 0 );
				update_option( PF_SLUG . '_feeds_iteration', 0 );
				$double_check = array(
					'feed_iteration'  => 0,
					'retrieval_state' => 0,
					'retrigger'       => $feeds_meta_state['retrigger'],
				);
				update_option( PF_SLUG . '_feeds_meta_state', $double_check );
				$message['action_taken'] = pf_message( __( 'The meta-state is too old. It is now reset. Next time, we will start retrieval over.', 'pressforward' ), true );
			} else {
				$double_check = array(
					'feed_iteration'  => $feed_iteration,
					'retrieval_state' => $feeds_meta_state['retrieval_state'],
					'retrigger'       => $feeds_meta_state['retrigger'],
				);
				update_option( PF_SLUG . '_feeds_meta_state', $double_check );
				pf_log( $double_check );
				$message['action_taken'] = pf_message( __( 'The sources are already being retrieved.', 'pressforward' ), true );
			}

			if ( $do_return ) {
				return $message;
			}
		}
		return '';
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
}
