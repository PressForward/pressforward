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
		$feed->set( 'description', $post->post_content );
		$feed->set( 'htmlUrl', get_post_meta( $post->ID, 'htmlUrl', true ) );

		$feed_url = get_post_meta( $post->ID, 'feedUrl', true );
		if ( ! $feed_url ) {
			$feed_url = get_post_meta( $post->ID, 'feed_url', true );
		}

		$feed->set( 'remote_feed_url', $feed_url );

		return $feed;
	}

	/**
	 * Saves feed to the database.
	 *
	 * @return int|false Post ID on success, false on failure.
	 */
	public function save() {
		$post_args = [
			'post_type'    => 'pf_feed',
			'post_status'  => 'publish',
			'post_content' => $this->get( 'description' ),
			'post_title'   => $this->get( 'title' ),
		];

		if ( $this->get( 'id' ) ) {
			$post_args['ID'] = $this->get( 'id' );
		}

		$post_id = wp_insert_post( $post_args );

		if ( ! $post_id ) {
			return false;
		}

		$this->set( 'id', $post_id );

		update_post_meta( $post_id, 'htmlUrl', $this->get( 'htmlUrl' ) );
		update_post_meta( $post_id, 'feedUrl', $this->get( 'remote_feed_url' ) );
		update_post_meta( $post_id, 'feed_url', $this->get( 'remote_feed_url' ) );
		update_post_meta( $post_id, 'feed_author', $this->get( 'feed_author' ) );

		return $post_id;
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
				'feed_id' => (string) $this->get( 'id' ),
			]
		);

		if ( ! $next_retrieval ) {
			return null;
		}

		return $next_retrieval;
	}

	/**
	 * Gets an element describing the next scheduled retrieval, in human-readable form.
	 *
	 * This uses the <abbr> element that's expected by the WP_List_Table column.
	 *
	 * @since 5.6.0
	 *
	 * @return string HTML.
	 */
	public function get_next_scheduled_retrieval_el() {
		$formatted_date = $this->get_next_scheduled_retrieval_date();

		$next_retrieval = $this->get_next_scheduled_retrieval();

		// Convert to WP timezone.
		$next_retrieval = $next_retrieval - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		$retval = '';
		if ( ! $next_retrieval ) {
			$retval = '-';
		} else {
			$time_diff = $next_retrieval - time();

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$in_text = $this->get_next_scheduled_retrieval_string();
			} else {
				$in_text = $formatted_date;
			}

			$retval = '<abbr title="' . esc_attr( $formatted_date ) . '">' . esc_html( $in_text ) . '</abbr>';
		}

		return $retval;
	}

	/**
	 * Gets the next scheduled retrieval as a WP-formatted date.
	 *
	 * @since 5.6.0
	 *
	 * @return string|false Formatted date, or false if not scheduled.
	 */
	public function get_next_scheduled_retrieval_date() {
		$next_retrieval = $this->get_next_scheduled_retrieval();
		if ( ! $next_retrieval ) {
			return false;
		}

		// Convert to WP timezone.
		$next_retrieval = $next_retrieval - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		return gmdate( 'Y/m/d g:i:s A', $next_retrieval );
	}

	/**
	 * Gets the next scheduled retrieval as a human-readable "In ..." string.
	 *
	 * @since 5.6.0
	 *
	 * @return string|false Formatted date, or false if not scheduled.
	 */
	public function get_next_scheduled_retrieval_string() {
		$next_retrieval = $this->get_next_scheduled_retrieval();
		if ( ! $next_retrieval ) {
			return false;
		}

		// translators: Time difference.
		return sprintf( __( 'In %s', 'pressforward' ), human_time_diff( $next_retrieval ) );
	}

	/**
	 * Schedules a feed retrieval.
	 *
	 * @param array $args {
	 *    Array of optional arguments.
	 *    @type int $interval Interval in seconds. Default is the 'pf_interval' of every 30 minutes.
	 *    @type int $nextrun  Unix timestamp. Default is a random time in the next 30 minutes.
	 * }
	 * @return true|\WP_Error True if scheduled, WP_Error if not. See wp_schedule_event().
	 */
	public function schedule_retrieval( $args = [] ) {
		$r = array_merge(
			[
				'interval' => 'pf_interval',
				'nextrun'  => time() + ( wp_rand( 0, 30 ) * MINUTE_IN_SECONDS ),
			],
			$args
		);

		// Prevent duplicate schedules.
		$next_retrieval = $this->get_next_scheduled_retrieval();
		if ( $next_retrieval ) {
			wp_unschedule_event( $next_retrieval, 'pf_retrieve_feed', [ 'feed_id' => (string) $this->get( 'id' ) ] );
		}

		$scheduled = wp_schedule_event(
			$r['nextrun'],
			$r['interval'],
			'pf_retrieve_feed',
			[
				'feed_id' => (string) $this->get( 'id' ),
			],
			true
		);

		return $scheduled;
	}

	/**
	 * Unschedules a feed retrieval.
	 *
	 * @return int|\WP_Error True if unscheduled, WP_Error if not. See wp_clear_scheduled_hook().
	 */
	public function unschedule_retrieval() {
		$next_retrieval = $this->get_next_scheduled_retrieval();
		if ( ! $next_retrieval ) {
			return 0;
		}

		$unscheduled = wp_clear_scheduled_hook(
			'pf_retrieve_feed',
			[ 'feed_id' => (string) $this->get( 'id' ) ],
			true
		);

		return $unscheduled;
	}

	/**
	 * Schedules a health check for this feed.
	 *
	 * @param array $args {
	 *   Array of optional arguments.
	 *   @type int  $nextrun     Unix timestamp. Default is a random time in the next 10 minutes.
	 *   @type bool $is_new_feed Whether this is a new feed. Default is false.
	 * }
	 * @return true|\WP_Error True if scheduled, WP_Error if not. See wp_schedule_event().
	 */
	public function schedule_health_check( $args = [] ) {
		$r = array_merge(
			[
				'nextrun'     => time() + ( wp_rand( 0, 5 ) * MINUTE_IN_SECONDS ),
				'is_new_feed' => false,
			],
			$args
		);

		$job_args = [
			'feed_id'     => (string) $this->get( 'id' ),
			'is_new_feed' => $r['is_new_feed'] ? '1' : '0',
		];

		// Prevent duplicate schedules.
		$next_health_check = wp_next_scheduled( 'pf_feed_health_check', $job_args );

		if ( $next_health_check ) {
			wp_unschedule_event( $next_health_check, 'pf_feed_health_check', $job_args );
		}

		$scheduled = wp_schedule_single_event( $r['nextrun'], 'pf_feed_health_check', $job_args, true );

		return $scheduled;
	}

	/**
	 * Unschedules a health check for this feed.
	 *
	 * @return int True if unscheduled, WP_Error if not. See wp_clear_scheduled_hook().
	 */
	public function unschedule_health_check() {
		$unscheduled_new = wp_clear_scheduled_hook(
			'pf_feed_health_check',
			[
				'feed_id'     => (string) $this->get( 'id' ),
				'is_new_feed' => '1',
			]
		);

		$unscheduled_old = wp_clear_scheduled_hook(
			'pf_feed_health_check',
			[
				'feed_id'     => (string) $this->get( 'id' ),
				'is_new_feed' => '1',
			]
		);

		$unscheduled = (int) $unscheduled_new + (int) $unscheduled_old;

		return $unscheduled;
	}

	/**
	 * Retrieves the feed.
	 *
	 * @return array See pressforward( 'schema.feed_item' )->assemble_feed_for_pull().
	 */
	public function retrieve() {
		$module = $this->get_module();

		$retval = [
			'date_retrieved' => null,
			'items_added'    => 0,
		];

		if ( ! $module ) {
			return $retval;
		}

		$feed_id = $this->get( 'id' );

		$feed_post = get_post( $feed_id );

		pressforward( 'schema.feeds' )->set_feed_last_checked( $feed_id );

		$feed_data_object = $module->get_data_object( $feed_post );
		if ( ! is_array( $feed_data_object ) ) {
			return $retval;
		}

		$feed_data_object['parent_feed_id'] = $feed_id;

		return pressforward( 'schema.feed_item' )->assemble_feed_for_pull( $feed_data_object );
	}

	/**
	 * Gets the module to be used for this feed.
	 *
	 * @return \PF_Module|null Module object or null.
	 */
	public function get_module() {
		$feeds_schema = pressforward( 'schema.feeds' );
		$feed_type    = $feeds_schema->get_pf_feed_type( $this->get( 'id' ) );

		// Special case for legacy 'rss-quick'.
		if ( 'rss-quick' === $feed_type ) {
			$feed_type = 'rss';
		}

		$module = null;
		foreach ( pressforward( 'modules' )->modules as $module ) {
			if ( $feed_type === $module->feed_type ) {
				break;
			}
		}

		return $module;
	}

	/**
	 * Performs a health check on the feed.
	 *
	 * @param bool $is_new_feed Whether this is a new feed. If true, any missing
	 *                          metadata about the feed (such as title) will be updated
	 *                          based on what is fetched from the remote feed.
	 * @return void
	 */
	public function health_check( $is_new_feed = false ) {
		$feed_url = $this->get( 'remote_feed_url' );

		$feed_urls_to_test = [
			$feed_url,
			trailingslashit( $feed_url ) . 'rss/',
			trailingslashit( $feed_url ) . 'rss/index.xml',
		];

		$feed_is_valid = false;
		while ( ! $feed_is_valid && ! empty( $feed_urls_to_test ) ) {
			$feed_url = array_shift( $feed_urls_to_test );
			$the_feed = pf_fetch_feed( $feed_url );
			if ( ! is_wp_error( $the_feed ) ) {
				$feed_is_valid = true;
			}
		}

		$alert_box = pressforward( 'library.alertbox' );
		if ( ! $feed_is_valid ) {
			if ( $alert_box ) {
				$alert_box->switch_post_type( $this->get( 'id' ) );
				$alert_box->add_bug_type_to_post( $this->get( 'id' ), __( 'Broken RSS feed.', 'pressforward' ) );
			}
			return;
		}

		if ( $alert_box ) {
			$alert_box->dismiss_alert( $this->get( 'id' ) );
		}

		if ( $is_new_feed ) {
			$this->set( 'title', $the_feed->get_title() );
			$this->set( 'description', $the_feed->get_description() );
			$this->set( 'htmlUrl', $the_feed->get_link( 0 ) );
			$this->set( 'feed_author', $the_feed->get_author() );
			$this->set( 'thumbnail', $the_feed->get_image_url() );

			$this->save();
		}
	}
}
