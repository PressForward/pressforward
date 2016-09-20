<?php

/**
 * Extend the main WP_REST_Posts_Controller to a private endpoint controller.
 */
class PF_REST_Terms_Controller extends WP_REST_Terms_Controller {

	public function __construct( $post_type ) {
		parent::__construct($post_type);
		$this->namespace = 'pf/v1';
	}

	public function register_routes(){
		parent::register_routes();
	}

}

/**
 * Registers default REST API routes.
 *
 * @since 4.2.0
 */
function create_initial_pf_rest_term_routes() {

	// Terms.
	foreach ( get_taxonomies( array( 'show_in_rest' => true ), 'object' ) as $taxonomy ) {
		$class = ! empty( $post_type->rest_controller_class ) ? $post_type->rest_controller_class : false;

		if ( !$class ){
			continue;
		}

		if ( 'PF_REST_Terms_Controller' !== $class ) {
			continue;
		}

		if ( ! class_exists( $class ) ) {
			continue;
		}
		$controller = new $class( $taxonomy->name );
		if ( ! is_subclass_of( $controller, 'WP_REST_Controller' ) ) {
			continue;
		}

		$controller->register_routes();
	}
	//die();
}

add_action( 'rest_api_init', 'create_initial_pf_rest_term_routes', 11 );

//$controller = new PF_REST_Posts_Controller;
//$controller->register_routes();
