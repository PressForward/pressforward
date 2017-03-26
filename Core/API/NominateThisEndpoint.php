<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Core\Admin\PFTemplater;

use WP_Ajax_Response;
use WP_Error;

class NominateThisEndpoint implements HasActions {

	protected $api_base;

	function __construct( $api_base, PFTemplater $templates ) {
		$this->api_base = $api_base;
		$this->api_base['endpoint'] = 'nominatethis';
		$this->templates = $templates;
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
		$namespace = $this->api_base['base_namespace'] . $this->api_base['version'];
		$base = $this->api_base['endpoint'];
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_nominate_this_template' ),
				'args' => array(
			      'context' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Supplies the Nominate This template for building the bookmarklet.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => 'view'
			      ),
			    ),
				'permission_callback' => function () {
			      return true; //$this->templates->users->pf_get_defining_capability_by_role( 'contributor' );
			  	},
				'priority'  => 10,
			),
		));

	}

	public function get_nominate_this_template($request){
		define( 'IFRAME_REQUEST' , true );
		define( 'WP_ADMIN', false );
		global $pagenow;
		return rest_ensure_response(
				include(PF_ROOT.'/includes/nominate-this-core.php')
			);
	}


}
