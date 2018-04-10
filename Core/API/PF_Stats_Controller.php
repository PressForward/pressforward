<?php

// Test with ngrok: ./ngrok http –host-header=rewrite local.WordPress.dev:80
class PF_Stats_Controller extends WP_REST_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'pf/v' . $version;
		$base      = 'stats';
		register_rest_route(
			$namespace, '/' . $base, array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_items' ),
					'priority' => 10,
				),
			)
		);

	}

	public function get_items( $request ) {
		$data = array(
			'available_stats' => array(
				'overview'  => array(
					'description' => 'Basic stats about PressForward on this site',
					'_links'      => array(
						'self' => get_rest_url() . 'pf/v1/stats/overview',
					),
				),
				'authors'   => array(
					'description' => 'Statistics about authors',
					'_links'      => array(
						'self' => get_rest_url() . 'pf/v1/stats/authors',
					),
				),
				'pf_posted' => array(
					'description' => 'The set of all posts published through PressForward with attached statistics.',
					'_links'      => array(
						'self' => get_rest_url() . 'pf/v1/stats/pf_posted',
					),
				),
			),
		);
		return new WP_REST_Response( $data, 200 );
	}

}

function activate_pf_stats_controller() {
	$controller = new PF_Stats_Controller();
	$controller->register_routes();
}


add_action( 'rest_api_init', 'activate_pf_stats_controller', 11 );
