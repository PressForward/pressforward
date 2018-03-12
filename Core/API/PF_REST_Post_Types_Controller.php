<?php

/**
 * Extend the main WP_REST_Posts_Controller to a private endpoint controller.
 */
class PF_REST_Post_Types_Controller extends WP_REST_Post_Types_Controller {

	public function __construct() {
		parent::__construct();
		$this->namespace = 'pf/v1';
		$this->terms     = array(
			'feeds',
			'feed_items',
			'nominations',
		);
		$this->types     = array(
			'pf_feed',
			'pf_feed_item',
			'nomination',
		);
		// Why is this post_types instead of using the basename like elsewhere?
		// Something to PR later mby.
		add_filter( 'rest_prepare_post_type', array( $this, 'filter_post_types' ), 11, 3 );
	}

	public function register_routes() {
		parent::register_routes();
	}

	public function filter_post_types( $data, $post, $request ) {
		$links    = $data->get_links();
		$pf_terms = $this->terms;
		foreach ( $pf_terms as $term ) {
			$data = pressforward( 'api.pf_endpoint' )->filter_an_api_data_link( $data, $links, 'https://api.w.org/items', $term );
		}
		// var_dump($data); die();
		return $data;
	}

	/**
	 * Get all public post types
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$data = array();
		foreach ( get_post_types( array(), 'object' ) as $obj ) {
			if ( empty( $obj->show_in_rest ) || ( 'edit' === $request['context'] && ! current_user_can( $obj->cap->edit_posts ) ) || ! in_array( $obj->name, $this->types ) ) {
				continue;
			}
			$post_type          = $this->prepare_item_for_response( $obj, $request );
			$data[ $obj->name ] = $this->prepare_response_for_collection( $post_type );
		}
		return rest_ensure_response( $data );
	}

	public function get_item( $request ) {
		$obj = get_post_type_object( $request['type'] );
		if ( empty( $obj ) || ! in_array( $obj->name, $this->types ) ) {
			return new WP_Error( 'rest_type_invalid', __( 'Invalid PF resource.' ), array( 'status' => 404 ) );
		} else {
			return parent::get_item( $request );
		}
	}

}

function activate_pf_post_types_controller() {
	$controller = new PF_REST_Post_Types_Controller();
	return $controller->register_routes();
}

add_action( 'rest_api_init', 'activate_pf_post_types_controller', 11 );

// $controller = new PF_REST_Posts_Controller;
// $controller->register_routes();
