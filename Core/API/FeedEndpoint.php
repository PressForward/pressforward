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
}
