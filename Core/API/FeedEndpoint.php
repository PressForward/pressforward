<?php
/**
 * Feed REST API utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use PressForward\Core\Models\Feed;

/**
 * Feed REST API utilities.
 */
class FeedEndpoint extends APIWithMetaEndpoints implements HasActions, HasFilters {
	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Level.
	 *
	 * @access public
	 * @var string
	 */
	public $level;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas $metas Metas object.
	 */
	public function __construct( Metas $metas ) {
		$this->metas     = $metas;
		$this->post_type = pressforward( 'schema.feeds' )->post_type;
		$this->level     = 'feed';
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		$actions = [
			[
				'hook'   => 'rest_api_init',
				'method' => 'register_rest_fields',
			],
		];

		return $actions;
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		$filter = array(
			array(
				'hook'     => 'rest_prepare_' . $this->post_type,
				'method'   => 'filter_wp_to_pf_in_terms',
				'priority' => 10,
				'args'     => 3,
			),
		);
		return $filter;
	}

	/**
	 * Not used.
	 *
	 * @param mixed $data    Not used.
	 * @param mixed $post    Not used.
	 * @param mixed $request Not used.
	 */
	public function filter_in_nomination_count( $data, $post, $request ) {
		return $data;
	}

	/**
	 * Registers meta fields for the feed endpoint.
	 *
	 * @return void
	 */
	public function register_rest_fields() {
		// Most REST fields are registered by the central meta schema.
		$this->register_rest_post_read_meta_fields();

		register_rest_field(
			$this->post_type,
			'last_checked',
			[
				'get_callback'    => function ( $post_object ) {
					$last = $this->metas->get_post_pf_meta( $post_object['id'], 'pf_feed_last_checked', true );
					return $this->format_time( $last );
				},

				'update_callback' => null,
				'schema'          => [
					'description' => __( 'The last time the feed was checked for new items', 'pressforward' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
			]
		);

		register_rest_field(
			$this->post_type,
			'last_retrieved',
			[
				'get_callback'    => function ( $post_object ) {
					$last = $this->metas->get_post_pf_meta( $post_object['id'], 'pf_feed_last_retrieved', true );
					return $this->format_time( $last );
				},

				'update_callback' => null,
				'schema'          => [
					'description' => __( 'The last time an item was fetched from the feed', 'pressforward' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
			]
		);

		register_rest_field(
			$this->post_type,
			'next_check',
			[
				'get_callback'    => function ( $post_object ) {
					$feed_object = Feed::get_instance_by_id( $post_object['id'] );
					$next        = $feed_object->get_next_scheduled_retrieval();

					return $this->format_time( gmdate( 'Y-m-d H:i:s', $next ) );
				},

				'update_callback' => null,
				'schema'          => [
					'description' => __( 'The next scheduled check for new feed items', 'pressforward' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
			]
		);

		register_rest_field(
			$this->post_type,
			'alert_message',
			[
				'get_callback'    => function ( $post_object ) {
					return pressforward( 'library.alertbox' )->get_bug_type( $post_object['id'] );
				},

				'update_callback' => null,
				'schema'          => [
					'description' => __( 'The error message for the feed', 'pressforward' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
			]
		);

		register_rest_field(
			$this->post_type,
			'feed_author',
			[
				'get_callback'    => function ( $post_object ) {
					$feed_object = Feed::get_instance_by_id( $post_object['id'] );
					return $feed_object->get_feed_author();
				},
				'update_callback' => function ( $value, $post ) {
					$feed_object = Feed::get_instance_by_id( $post->ID );
					$feed_object->set_feed_author( $value );
				},
				'schema'          => [
					'description' => __( 'The author of the feed', 'pressforward' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
			]
		);
	}

	/**
	 * Formats a saved timestamp into a human-readable string.
	 *
	 * @param string $timestamp The timestamp to format.
	 * @return string
	 */
	protected function format_time( $timestamp ) {
		if ( empty( $timestamp ) ) {
			return '';
		}

		$unix = strtotime( $timestamp . ' UTC' );

		$human_time_diff = human_time_diff( $unix );

		if ( $unix > time() ) {
			$relative = sprintf(
				// translators: %s is a human-readable time difference.
				__( 'in %s', 'pressforward' ),
				$human_time_diff
			);
		} else {
			$relative = sprintf(
				// translators: %s is a human-readable time difference.
				__( '%s ago', 'pressforward' ),
				$human_time_diff
			);
		}

		return sprintf(
			// translators: %1$s is a human-readable time difference, %2$s is a formatted date.
			__( '%1$s (%2$s)', 'pressforward' ),
			date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $unix ),
			$relative
		);
	}
}
