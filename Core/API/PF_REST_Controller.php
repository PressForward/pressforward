<?php
/**
 * PF_REST_Controller class.
 *
 * @package PressForward
 */

// Test with ngrok: ./ngrok http –host-header=rewrite local.WordPress.dev:80.

/**
 * PF_REST_Controller class.
 */
class PF_REST_Controller extends WP_REST_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'pf/v' . $version;
		$base      = 'status';
		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_pf_status' ),
					'priority'            => 10,
					'permission_callback' => [ $this, 'get_pf_status_permissions_check' ],
				),
			)
		);
	}

	/**
	 * Callback for status endpoint.
	 *
	 * @return WP_REST_Response
	 */
	public function get_pf_status() {
		$pf             = pressforward( 'modules' );
		$active_modules = array();

		foreach ( $pf->modules as $module_id => $module ) {
			$enabled = get_option( PF_SLUG . '_' . $module_id . '_enable' );
			if ( ! in_array( $enabled, array( 'yes', 'no' ), true ) ) {
				$enabled = 'yes';
			}
			if ( 'yes' === $enabled ) {
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

	/**
	 * Permissions callback for status endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function get_pf_status_permissions_check( $request ) {
		return true;
	}
}
