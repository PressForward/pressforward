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

		pressforward( 'schema.feeds' )->set_feed_last_checked( $feed_id );

		$feed_results = $module->get_feed_items( $this );

		if ( is_wp_error( $feed_results ) ) {
			$retval['error'] = $feed_results->get_error_message();
			return $retval;
		}

		$feed_results['parent_feed_id'] = $feed_id;

		return pressforward( 'schema.feed_item' )->assemble_feed_for_pull( $feed_results );
	}

	/**
	 * Gets the feed author for the feed.
	 *
	 * @since 5.8.0
	 *
	 * @return string
	 */
	public function get_feed_author() {
		$feed_author_meta = get_post_meta( $this->get( 'id' ), 'feed_author', true );

		if ( is_string( $feed_author_meta ) ) {
			return $feed_author_meta;
		}

		// Legacy items have feed_author saved as a SimplePie Author object.
		if ( is_object( $feed_author_meta ) ) {
			// May be an incomplete class.
			$feed_author_meta = (array) $feed_author_meta;
			if ( isset( $feed_author_meta['name'] ) ) {
				update_post_meta( $this->get( 'id' ), 'feed_author', $feed_author_meta['name'] );
				return $feed_author_meta['name'];
			}
		}

		return '';
	}

	/**
	 * Sets the feed author for the feed.
	 *
	 * @since 5.8.0
	 *
	 * @param string $author Author name.
	 * @return void
	 */
	public function set_feed_author( $author ) {
		update_post_meta( $this->get( 'id' ), 'feed_author', $author );
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

		// Both google-scholar-keyword and google-scholar-profile are handled by 'google-scholar'.
		if ( 'google-scholar-keyword' === $feed_type || 'google-scholar-profile' === $feed_type ) {
			$feed_type = 'google-scholar';
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

		$module = $this->get_module();
		$module->health_check( $this, $is_new_feed );
	}

	/**
	 * Gets a count of nominations associated with items belonging to this feed.
	 *
	 * @param bool $force_refresh Whether to force a refresh of the count.
	 * @return int
	 */
	public function get_nomination_count( $force_refresh = false ) {
		if ( $force_refresh ) {
			$nomination_count = 0;

			$items_belonging_to_feed = get_posts(
				[
					'post_type'              => [ 'nomination', 'pf_feed_item' ],
					'posts_per_page'         => -1,
					'post_parent'            => $this->get( 'id' ),
					'update_meta_cache'      => true,
					'update_post_term_cache' => false,
					'post_status'            => 'any',
				]
			);

			$nominators_of_feed_items = [];

			$nomination_count = 0;

			/*
			 * An item may be nominated by a user at different points in its
			 * lifecycle. Don't double-count a given user's nomination for an item.
			 */
			foreach ( $items_belonging_to_feed as $item_belonging_to_feed ) {
				$item_nominators = pressforward( 'utility.forward_tools' )->get_nomination_nominator_array( $item_belonging_to_feed->ID );

				$pf_item_id = get_post_meta( $item_belonging_to_feed->ID, 'item_id', true );

				if ( ! $pf_item_id ) {
					continue;
				}

				if ( ! isset( $nominators_of_feed_items[ $pf_item_id ] ) ) {
					$nominators_of_feed_items[ $pf_item_id ] = [];
				}

				foreach ( $item_nominators as $nominator ) {
					$nom_user_id = $nominator['user_id'];

					// Keying ensures uniqueness.
					$nominators_of_feed_items[ $pf_item_id ][ $nom_user_id ] = $nom_user_id;
				}
			}

			foreach ( $nominators_of_feed_items as $nominators ) {
				$nomination_count += count( $nominators );
			}

			update_post_meta( $this->get( 'id' ), 'pf_nominations_in_feed', $nomination_count );
		}

		$cached = get_post_meta( $this->get( 'id' ), 'pf_nominations_in_feed', true );
		return (int) $cached;
	}

	/**
	 * Gets whether the 'do_import_tags' flag is set for this feed.
	 *
	 * Defaults to true when no meta value is present.
	 *
	 * @since 5.9.0
	 *
	 * @return bool
	 */
	public function get_do_import_tags() {
		$do_import_tags = get_post_meta( $this->get( 'id' ), 'do_import_tags', true );

		if ( '' === $do_import_tags ) {
			return true;
		}

		return (bool) $do_import_tags;
	}

	/**
	 * Sets the 'do_import_tags' flag for this feed.
	 *
	 * @since 5.9.0
	 *
	 * @param bool $do_import_tags Whether to import tags.
	 * @return void
	 */
	public function set_do_import_tags( $do_import_tags ) {
		if ( $do_import_tags ) {
			update_post_meta( $this->get( 'id' ), 'do_import_tags', '1' );
		} else {
			update_post_meta( $this->get( 'id' ), 'do_import_tags', '0' );
		}
	}

	/**
	 * Get the "default author" string for this feed.
	 *
	 * @since 5.9.0
	 *
	 * @return string
	 */
	public function get_default_author() {
		return pressforward( 'controller.metas' )->get_post_pf_meta( $this->get( 'id' ), 'pf_feed_default_author', true );
	}
}
