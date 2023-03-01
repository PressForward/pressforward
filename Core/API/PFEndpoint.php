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
				'method' => 'callback_init',
			),
		);
		return $actions;
	}

	/**
	 * Loads PF endpoint classes.
	 */
	public function callback_init() {
		$class_names = [
			'PF_REST_Controller',
			'PF_Stats_Controller',
			'PF_REST_Taxonomies_Controller',
			'PF_REST_Post_Types_Controller',
		];

		foreach ( $class_names as $class_name ) {
			require $class_name . '.php';
			$controller = new $class_name();
			$controller->register_routes();
		}

		require 'PF_REST_Posts_Controller.php';
		foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
			$class_name = ! empty( $post_type->rest_controller_class ) ? $post_type->rest_controller_class : false;

			if ( ! $class_name ) {
				continue;
			}

			if ( 'PF_REST_Posts_Controller' !== $class_name ) {
				continue;
			}

			if ( ! class_exists( $class_name ) ) {
				continue;
			}

			$controller = new $class_name( $post_type->name );
			if ( ! is_subclass_of( $controller, 'WP_REST_Controller' ) ) {
				continue;
			}

			$controller->register_routes();
		}

		// Terms.
		require 'PF_REST_Terms_Controller.php';
		foreach ( get_taxonomies( array( 'show_in_rest' => true ), 'object' ) as $taxonomy ) {
			$class_name = ! empty( $taxonomy->rest_controller_class ) ? $taxonomy->rest_controller_class : false;

			if ( ! $class_name ) {
				continue;
			}

			if ( 'PF_REST_Terms_Controller' !== $class_name ) {
				continue;
			}

			if ( ! class_exists( $class_name ) ) {
				continue;
			}

			$controller = new $class_name( $taxonomy->name );
			if ( ! is_subclass_of( $controller, 'WP_REST_Controller' ) ) {
				continue;
			}

			$controller->register_routes();
		}
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
