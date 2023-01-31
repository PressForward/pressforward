<?php
/**
 * Loader for many of PF's endpoints.
 *
 * @package PressForward
 */

namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

/**
 * Loader for many of PF's endpoints.
 */
class PFEndpoint extends APIWithMetaEndpoints implements HasActions {

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
	 * @var PressForward\Controllers\Metas
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
	 * @param PressForward\Controllers\Metas $metas Metas object.
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
				'method' => 'callback_init',
			),
		);
		return $actions;
	}

	/**
	 * Loads PF endpoint classes.
	 */
	public function callback_init() {
		require_once 'PF_REST_Controller.php';
		require_once 'PF_Stats_Controller.php';
		require_once 'PF_REST_Taxonomies_Controller.php';
		require_once 'PF_REST_Post_Types_Controller.php';
		require_once 'PF_REST_Posts_Controller.php';
		require_once 'PF_REST_Terms_Controller.php';
	}

	/**
	 * Adds 'meta_key' and 'meta_value' to array of allowed args.
	 *
	 * @todo Unused.
	 *
	 * @param array $valid_vars Array of valid vars.
	 * @return array
	 */
	public function allow_meta_query( $valid_vars ) {
		$valid_vars = array_merge( $valid_vars, array( 'meta_key', 'meta_value' ) );
		return $valid_vars;
	}
}
