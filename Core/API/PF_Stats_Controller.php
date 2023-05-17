<?php
/**
 * Controller for PF stats endpoint.
 *
 * @package PressForward
 */

// Test with ngrok: ./ngrok http –host-header=rewrite local.WordPress.dev:80.

/**
 * Controller for PF stats endpoint.
 */
class PF_Stats_Controller extends WP_REST_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'pf/v' . $version;
		$base      = 'stats';
		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'priority'            => 10,
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				),
			)
		);
	}

	/**
	 * Callback for stats endpoint read request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$data = array(
			'available_stats' => array(
				'overview'  => array(
					'description' => __( 'Basic stats about PressForward on this site', 'pressforward' ),
					'_links'      => array(
						'self' => get_rest_url() . 'pf/v1/stats/overview',
					),
				),
				'authors'   => array(
					'description' => __( 'Statistics about authors', 'pressforward' ),
					'_links'      => array(
						'self' => get_rest_url() . 'pf/v1/stats/authors',
					),
				),
				'pf_posted' => array(
					'description' => __( 'The set of all posts published through PressForward with attached statistics.', 'pressforward' ),
					'_links'      => array(
						'self' => get_rest_url() . 'pf/v1/stats/pf_posted',
					),
				),
			),
		);
		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Permissions callback for stats endpoint read request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}
}
