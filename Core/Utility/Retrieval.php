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
	 * Steps through feedlist.
	 */
	public function step_through_feedlist() {
		// Log the beginning of this function.
		pf_iterate_cycle_state( 'retrieval_chunks_begun', true );
		pf_log( 'Beginning to step through feed list.' );

		// Retrieve the list of feeds.
		$feedlist = $this->pf_feedlist();

		// Move array internal pointer to end.
		end( $feedlist );

		// Because the internal pointer is now at the end
		// we can find the key for the last entry.
		$last_key = key( $feedlist );

		// Log the key we retrieved. This allows us to compare
		// to the iteration state in order to avoid multiple
		// processes spawning at the same time.
		pf_log( 'Beginning the retrieval process by determining the next feed that needs importing.' );
		pf_log( 'The key belonging to the last item in the feedlist is: ' . $last_key );

		// Get the option that stores the current step of iteration.
		$feeds_iteration = get_option( PF_SLUG . '_feeds_iteration' );

		// We want to insure that we are neither skipping ahead or
		// overlapping with a previous process. To do so we store two
		// options. The first tells us the current state of iteration.
		// We don't reset the pf_feeds_iteration option until we have actually
		// begun to retrieve the feed. The pf_prev_iteration stores the
		// last time it was set. When the feed retrieval checks begin, we set the
		// prev_iteration. When they are completed and ready to progress we
		// set the pf_feeds_iteration option.
		//
		// At this point the last run was started ( and, we assume,
		// completed, checks for that later. ) but we have not advanced
		// so the two options should match.
		$prev_iteration = get_option( PF_SLUG . '_prev_iteration', 0 );
		pf_log( 'Did the option properly iterate so that the previous iteration count of ' . $prev_iteration . ' is not equal to the current of ' . $feeds_iteration . '?' );

		/*
		 * @todo This appears to be reporting with the wrong messages.
		 * We need to resolve what is going on here with the right log.
		 */

		// This is the fix for the insanity caused by the planet money feed - http://www.npr.org/rss/podcast.php?id=510289.
		if ( (int) $prev_iteration === (int) $feeds_iteration && $feeds_iteration ) {

			// In some cases the option fails to update for reasons that are not
			// clear. In those cases, we will risk skipping a feed rather
			// than get caught in an endless loop.
			pf_log( 'Nope. Did the step_though_feedlist iteration option emergency update work here?' );

			// Make an attempt to update the option to its appropriate state.
			$this->update_option_w_check( '_feeds_iteration', $feeds_iteration + 1 );
			// Regardless of success, iterate this process forward.
			++$feeds_iteration;
		} elseif ( ! $feeds_iteration ) {
			pf_log( 'No, but we are at the beginning of the feed list, so this result is expected.' );
		} else {
			// No rest for the iterative, so on we go.
			pf_log( 'Yes' );
		}

		// If everything goes wrong, we need to know what the iterate state is.
		// At this point $feeds_iteration should be one greater than prev_iteration.
		pf_log( 'The current iterate state is: ' . $feeds_iteration );

		// Insure that the function hasn't gone rogue and is not past the
		// last element in the array.
		if ( $feeds_iteration <= $last_key ) {

			// The iteration state is not beyond the limit of the array
			// which means we can move forward.
			pf_log( 'The iteration is less than the last key, which means there are more feeds to process..' );

			// Get the basic URL for the feed.
			$a_feed = $feedlist[ $feeds_iteration ];
			pf_log( sprintf( 'Retrieving feed: %s (%s)', $a_feed->guid, $a_feed->ID ) );
			pf_log( 'Set last_checked for ' . $a_feed->ID );
			$result = pressforward( 'schema.feeds' )->set_feed_last_checked( $a_feed->ID );
			pf_log( $result );

			// @todo the above log may not work what what is being retrieved is an object.
			// Check the option to insure that we are currently inside the
			// iteration process. The 'going' switch is used elsewhere to check
			// if the iteration process is active or ended.
			$are_we_going = get_option( PF_SLUG . '_iterate_going_switch', 1 );
			pf_log( 'The flag indicating whether feed iteration is currently in progress is set to: ' . $are_we_going );
			$total_feed_count = 0;
			if ( is_array( $feedlist ) ) {
				$total_feed_count = count( $feedlist );
			}
			$feed_hb_state = array(
				'feed_id'         => $a_feed->ID,
				'feed_title'      => $a_feed->post_title,
				'last_key'        => $last_key,
				'feeds_iteration' => $feeds_iteration,
				'total_feeds'     => $total_feed_count,
			);
			$this->update_option_w_check( '_feeds_hb_state', $feed_hb_state );

			// The last key of the array is equal to our current key? Then we are
			// at the end of the feedlist. Set options appropriately to indicate to
			// other processes that the iterate state will soon be terminated.
			if ( (int) $last_key === (int) $feeds_iteration ) {
				pf_log( 'The last key is equal to the feeds_iteration. This is the last feed.' );

				// If we're restarting after this, we need to tell the system
				// to begin the next retrieve cycle at 0.
				$feeds_iteration = 0;
				$this->update_option_w_check( '_iterate_going_switch', 0 );

			} elseif ( $are_we_going ) {
				pf_log( 'No, we didn\'t start over.' );
				pf_log( 'Did we set the previous iteration option to ' . $feeds_iteration . '?' );

				// We should have advanced the feeds_iteration by now,
				// it is the active array pointer. To track this action for
				// future iterations, we store the current iteration state as
				// prev_iteration.
				$this->update_option_w_check( '_prev_iteration', $feeds_iteration );

				// Now we advance the feeds_iteration var to the array pointer
				// that represents the next feed we will need to retrieve.
				++$feeds_iteration;
				$this->update_option_w_check( '_iterate_going_switch', 1 );
				pf_log( 'We are set to a reiterate state.' );
			} else {
				// Oh noes, what has occurred!?
				pf_log( 'There is a problem with the iterate_going_switch and now the program does not know its state.' );
			}

			pf_log( 'Did the feeds_iteration option update to ' . $feeds_iteration . '?' );

			// Set and log the update that gives us the future feed retrieval.
			$this->update_option_w_check( '_feeds_iteration', $feeds_iteration );

			// Log a ( hopefully ) successful update.
			pf_log( 'The feed iteration option is now set to ' . $feeds_iteration );

			// If the feed retrieved is empty and we haven't hit the last feed item.
			if ( ( empty( $a_feed ) || 'publish' !== $a_feed->post_status ) && $feeds_iteration <= $last_key ) {
				pf_log( 'The feed is either an empty entry or un-retrievable, or not published AND the iteration is less than or equal to the last key.' );
				$the_feed = call_user_func( array( $this, 'step_through_feedlist' ) );
			} elseif ( empty( $a_feed ) && $feeds_iteration > $last_key ) {
				pf_log( 'The feed is either an empty entry or un-retrievable AND the iteration is greater than the last key.' );
				$this->update_option_w_check( '_feeds_iteration', 0 );

				$this->update_option_w_check( '_iterate_going_switch', 0 );

				pf_log( 'End of the update process. Return false.' );
				return false;
			}

			// If the feed isn't empty, attempt to retrieve it.
			$the_feed = self::feed_handler( $a_feed );
			if ( ! $the_feed ) {
				pf_log( 'This feed is empty, too old, or has had all items retrieved:' );
				pf_log( $a_feed->guid );
				$a_feed = '';
			}

			// If the array entry is empty and this isn't the end of the feedlist,
			// then get the next item from the feedlist while iterating the count.
			if ( ( empty( $a_feed ) || is_wp_error( $the_feed ) ) && $feeds_iteration <= $last_key ) {
				// Feed failure happening here.
				pf_log( 'The feed is either an empty entry or un-retrievable AND the iteration is less than or equal to the last key.' );

				// The feed is somehow bad, lets get the next one.
				$the_feed = call_user_func( array( $this, 'step_through_feedlist' ) );

			} elseif ( ( empty( $a_feed ) || is_wp_error( $the_feed ) ) && ( $feeds_iteration > $last_key ) ) {

				// The feed is somehow bad and we've come to the end of the array.
				// Now we switch all the indicators to show that the process is
				// over and log the process.
				pf_log( 'The feed is either an empty entry or un-retrievable AND the iteration is greater then the last key.' );
				$this->update_option_w_check( '_feeds_iteration', 0 );

				$this->update_option_w_check( '_iterate_going_switch', 0 );

				pf_log( 'End of the update process. Return false.' );
				return false;
			}

			return $the_feed;
		} else {
			// An error state that should never, ever, ever, ever, ever happen.
			pf_log( 'The iteration is now greater than the last key.' );
			$this->update_option_w_check( '_feeds_iteration', 0 );

			$this->update_option_w_check( '_iterate_going_switch', 0 );
			pf_log( 'End of the update process. Return false.' );
			return false;
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
	 * Handles the retrieval of a feed.
	 *
	 * Take the feed type and the feed id
	 * and apply filters so that we know which
	 * function to call to handle the feed
	 * and handle the item correctly.
	 * If check = true than this is just a validator for feeds.
	 *
	 * @param \WP_Post $obj   Post object.
	 * @param bool     $check For debugging.
	 */
	public function feed_handler( $obj, $check = false ) {
		global $pf;
		$feeds = pressforward( 'schema.feeds' );
		pf_log( 'Invoked: PF_Feed_retrieve::feed_handler()' );
		pf_log( 'Are we just checking?' );
		pf_log( $check );

		$id = $obj->ID;
		pf_log( 'Feed ID ' . $id );
		$type = $feeds->get_pf_feed_type( $id );
		pf_log( 'Checking for feed type ' . $type );

		$module_to_use = $this->does_type_exist( $type );
		if ( ! $module_to_use ) {
			// Be a better error.
			pf_log( 'The feed type does not exist.' );
			return false;
		}

		pf_log( 'Begin the process to retrieve the object full of feed items.' );

		$is_it_going = get_option( PF_SLUG . '_iterate_going_switch', 1 );
		if ( ! $is_it_going ) {
			// WE ARE? SHUT IT DOWN!!!
			update_option( PF_SLUG . '_feeds_iteration', 0 );
			update_option( PF_SLUG . '_iterate_going_switch', 0 );

			die();
		}

		// Maintaining support for 'rss-quick' legacy feed type.
		if ( ( 'rss-quick' === $type ) && ( 'publish' === $obj->post_status ) ) {
			// Let's update the RSS-Quick so it has real data.
			$rq_update = array(
				'type' => 'rss-quick',
				'ID'   => $id,
				'url'  => $obj->guid,
			);
			$feeds->update( $id, $rq_update );
		}

		// module function to return a set of standard pf feed_item object.
		// Like get_items in SimplePie.
		$feed_obj = $this->get_the_feed_object( $module_to_use, $obj );

		if ( $check ) {
			// Be a better error.
			if ( ! $feed_obj ) {
				return false;
			} else {
				return true;
			}
		} else {

			$prev_iteration   = get_option( PF_SLUG . '_prev_iteration', 0 );
			$iterate_op_check = get_option( PF_SLUG . '_feeds_iteration', 1 );

			pf_log( 'Did the option properly iterate so that the previous iteration count of ' . $prev_iteration . ' is not equal to the current of ' . $iterate_op_check . '?' );

			if ( $prev_iteration === $iterate_op_check ) {
				pf_log( 'Nope. Did the iteration option emergency update function here?' );
				$check_iteration = update_option( PF_SLUG . '_feeds_iteration', $iterate_op_check + 1 );
				pf_log( $check_iteration );
			} else {
				pf_log( 'Yes' );
			}

			if ( false !== $feed_obj && ! is_wp_error( $feed_obj ) && function_exists( 'the_alert_box' ) ) {
				$alert_box = pressforward( 'library.alertbox' );
				$ab_status = $alert_box->status();
				if ( $ab_status === $obj->post_status ) {
					// The feed has been retrieved, therefor this is a good feed. We can remove the alert.
					pressforward( 'library.alertbox' )->dismiss_alert( $obj->ID );
					// Assure the feed is back online.
					$argup  = array(
						'ID'          => $obj->ID,
						'post_status' => 'publish',
					);
					$result = wp_update_post( $argup );
				}
			}

			return $feed_obj;
		}
	}

	/**
	 * Checking whether an item is a feed.
	 *
	 * @param object $obj Feed object.
	 * @return bool
	 */
	public function is_feed( $obj ) {
		// By passing true, we're making it return a bool.
		return $this->feed_handler( $obj, true );
	}

	/**
	 * Fetches a feed by ID.
	 *
	 * @param int $id Feed ID.
	 */
	public function is_feed_by_id( $id ) {
		$obj = get_post( $id );
		return $this->feed_handler( $obj, true );
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
