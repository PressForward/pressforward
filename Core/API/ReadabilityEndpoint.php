<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class ReadabilityEndpoint extends WP_REST_Controller  implements HasActions {

	protected $api_base;

	function __construct( $api_base, PF_Readability $readability, Forward_Tools $forward_tools, HTMLChecker $html_checker ) {
		$this->api_base = $api_base;
		$this->api_base['endpoint'] = 'readability';
		$this->readability = $readability;
		$this->forward_tools = $forward_tools;
		$this->html_checker = $html_checker;
	}

	public function action_hooks() {
		//add_action( 'rest_api_init', 'activate_pf_rest_controller', 11 );
		$actions = array(
			array(
				'hook' => 'rest_api_init',
				'method' => 'activate',
				'priority'	=> 11
			),
		);
		return $actions;
	}

	function activate() {
		$controller = $this;
		$controller->register_routes();
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version = '1';
		$namespace = $this->api_base['base_namespace'] . $this->api_base['version'];
		$base = $this->api_base['endpoint'];
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_pf_status' ),
				'priority'  => 10,
			),
		));

	}


}
