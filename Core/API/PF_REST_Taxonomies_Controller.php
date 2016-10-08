<?php

/**
 * Extend the main WP_REST_Posts_Controller to a private endpoint controller.
 */
class PF_REST_Taxonomies_Controller extends WP_REST_Taxonomies_Controller {

	public function __construct(  ) {
		parent::__construct();
		$this->namespace = 'pf/v1';
	}

	public function register_routes(){
		parent::register_routes();
	}

	/**
	 * Get all public taxonomies
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_items( $request ) {
		if ( ! empty( $request['type'] ) ) {
			$taxonomies = get_object_taxonomies( $request['type'], 'objects' );
		} else {
			$taxonomies = get_taxonomies( '', 'objects' );
		}
		$data = array();
		foreach ( $taxonomies as $tax_type => $value ) {
			//var_dump($value);
			if ( 'pf_feed_category' !== $value->name ){
				continue;
			}
			if ( empty( $value->show_in_rest ) || ( 'edit' === $request['context'] && ! current_user_can( $value->cap->manage_terms ) ) ) {
				continue;
			}
			$tax = $this->prepare_item_for_response( $value, $request );
			$tax = $this->prepare_response_for_collection( $tax );
			$data[ $tax_type ] = $tax;
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Get a specific taxonomy
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function get_item( $request ) {
		$tax_obj = get_taxonomy( $request['taxonomy'] );
		if ( empty( $tax_obj ) || ( 'pf_feed_category' != $tax_obj->name ) ) {
			return new WP_Error( 'rest_taxonomy_invalid', __( 'Invalid PF resource.' ), array( 'status' => 404 ) );
		}
		$data = $this->prepare_item_for_response( $tax_obj, $request );
		return rest_ensure_response( $data );
	}

}

function activate_pf_taxonomies_controller(){
	$controller = new WP_REST_Taxonomies_Controller;
	return $controller->register_routes();
}

add_action( 'rest_api_init', 'activate_pf_taxonomies_controller', 11 );

//$controller = new PF_REST_Posts_Controller;
//$controller->register_routes();
