<?php
/**
 * Integration with archive.org.
 *
 * @since 5.8.0
 *
 * @package PressForward
 */

namespace PressForward\Core\Utility;

/**
 * Integration with archive.org.
 *
 * @since 5.8.0
 */
class ArchiveOrg {
	/**
	 * Constructor.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'pf_transition_to_last_step', [ $this, 'maybe_submit_to_archive_org' ] );
		add_action( 'pressforward_submit_to_archive_org', [ $this, 'cron_callback' ] );
	}

	/**
	 * Is archive.org integration enabled for the site?
	 *
	 * @since 5.8.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = get_option( 'pressforward_archive_org_enabled', false );
		return 'on' === $enabled;
	}

	/**
	 * Conditionally submit an item to archive.org.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The WP post ID of the item to submit.
	 * @return void
	 */
	public function maybe_submit_to_archive_org( $post_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$status = self::get_post_status( $post_id );
		if ( in_array( $status['status'], [ 'pending_send', 'pending_response', 'success' ], true ) ) {
			return;
		}

		// If there's no item link, we can't submit to archive.org.
		$item_link = pressforward( 'controller.metas' )->retrieve_meta( $post_id, 'item_link' );
		if ( ! $item_link ) {
			return;
		}

		wp_schedule_single_event(
			time(),
			'pressforward_submit_to_archive_org',
			[ $post_id ]
		);

		update_post_meta(
			$post_id,
			'archive_org_status',
			[
				'status'    => 'pending_send',
				'timestamp' => time(),
			]
		);
	}

	/**
	 * Cron callback for an item that must be sent to archive.org.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The WP post ID.
	 */
	public function cron_callback( $post_id ) {
		$status = self::get_post_status( $post_id );
		if ( 'pending_send' !== $status['status'] ) {
			return;
		}

		$item_link = pressforward( 'controller.metas' )->retrieve_meta( $post_id, 'item_link' );
		if ( ! $item_link ) {
			return;
		}

		update_post_meta(
			$post_id,
			'archive_org_status',
			[
				'status'    => 'pending_response',
				'timestamp' => time(),
			]
		);

		$response = wp_remote_get(
			'https://web.archive.org/save/' . $item_link,
			[
				'timeout' => 60, // archive.org can be very slow.
			]
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $response_code ) {
			update_post_meta(
				$post_id,
				'archive_org_status',
				[
					'status'    => 'success',
					'timestamp' => time(),
				]
			);
		} else {
			update_post_meta(
				$post_id,
				'archive_org_status',
				[
					'status'    => 'error',
					'timestamp' => time(),
				]
			);
		}
	}

	/**
	 * Gets the archive.org status for a post.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The WP post ID.
	 * @return array
	 */
	public static function get_post_status( $post_id ) {
		$default = [
			'status'    => '',
			'timestamp' => '',
		];

		$status = get_post_meta( $post_id, 'archive_org_status', true );

		if ( ! is_array( $status ) ) {
			$status = [];
		}

		$status = array_merge( $default, $status );

		return $status;
	}
}
