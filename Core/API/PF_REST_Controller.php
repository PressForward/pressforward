<?php

// Test with ngrok: ./ngrok http –host-header=rewrite local.WordPress.dev:80
class PF_REST_Controller extends WP_REST_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'pf/v' . $version;
		$base      = 'status';
		register_rest_route(
			$namespace, '/' . $base, array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_pf_status' ),
					'priority' => 10,
				),
			)
		);

	}

	public function get_pf_status() {
		$pf             = pressforward( 'modules' );
		$active_modules = array();
		foreach ( $pf->modules as $module_id => $module ) {
			$enabled = get_option( PF_SLUG . '_' . $module_id . '_enable' );
			if ( ! in_array( $enabled, array( 'yes', 'no' ) ) ) {
				$enabled = 'yes';
			}
			if ( 'yes' == $enabled ) {
				$active_modules[] = $module_id;
			}
		}
		$data = array(
			'status'         => 'PressForward running.',
			'version'        => PF_VERSION,
			'active_modules' => $active_modules,
		);
		return new WP_REST_Response( $data, 200 );
	}

}

function activate_pf_rest_controller() {
	$controller = new PF_REST_Controller();
	$controller->register_routes();
}


add_action( 'rest_api_init', 'activate_pf_rest_controller', 11 );
