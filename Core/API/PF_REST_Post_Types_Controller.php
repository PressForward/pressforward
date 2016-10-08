<?php

/**
 * Extend the main WP_REST_Posts_Controller to a private endpoint controller.
 */
class PF_REST_Post_Types_Controller extends WP_REST_Post_Types_Controller {

	public function __construct(  ) {
		parent::__construct();
		$this->namespace = 'pf/v1';
		// Why is this post_types instead of using the basename like elsewhere?
		// Something to PR later mby.
		add_filter('rest_prepare_post_type', array( $this, 'filter_post_types' ), 11, 3 );
	}

	public function register_routes(){
		parent::register_routes();
	}

	public function filter_post_types( $data, $post, $request ){
		$links = $data->get_links();
		$pf_terms = array(
			'feeds',
			'feed_items',
			'nominations'
		);
		foreach ( $pf_terms as $term ){
			$data = pressforward('api.pf_endpoint')->filter_an_api_data_link($data, $links, 'https://api.w.org/items', $term);
		}
		//var_dump($data); die();
		return $data;
	}

}

function activate_pf_post_types_controller(){
	$controller = new PF_REST_Post_Types_Controller;
	return $controller->register_routes();
}

add_action( 'rest_api_init', 'activate_pf_post_types_controller', 11 );

//$controller = new PF_REST_Posts_Controller;
//$controller->register_routes();
