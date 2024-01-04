<?php
/**
 * Item REST API utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

/**
 * Item REST API utilities.
 */
class ItemEndpoint extends APIWithMetaEndpoints implements HasActions, HasFilters {
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
		$this->post_type = pressforward( 'schema.feed_item' )->post_type;
		$this->level     = 'item';
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		$actions = array(
			array(
				'hook'   => 'rest_api_init',
				'method' => 'register_rest_post_read_meta_fields',
			),
		);
		return $actions;
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		$filter = array(
			array(
				'hook'     => 'rest_prepare_' . $this->post_type,
				'method'   => 'add_rest_post_links',
				'priority' => 10,
				'args'     => 3,
			),
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
	 * Adds post links to feed item with a parent.
	 *
	 * @param \WP_REST_Response $data    Response object.
	 * @param \WP_Post          $post    Post object.
	 * @param \WP_REST_Request  $request Request object.
	 * @return \WP_REST_Response
	 */
	public function add_rest_post_links( $data, $post, $request ) {
		// http://v2.wp-api.org/extending/linking/.
		// https://1fix.io/blog/2015/06/26/adding-fields-wp-rest-api/.
		$feed_id = 'false';
		if ( ! empty( $post->post_parent ) ) {
			$feed_id = $post->post_parent;
		}

		$data->add_links(
			array(
				'feed' => array(
					'href'       => rest_url( '/pf/v1/feeds/' . $feed_id ),
					'embeddable' => true,
				),
			)
		);

		return $data;
	}
}
