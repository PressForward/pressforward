<?php
/**
 * Terms controller for PF content.
 *
 * @package PressForward
 */

/**
 * Extend the main WP_REST_Posts_Controller to a private endpoint controller.
 */
class PF_REST_Terms_Controller extends WP_REST_Terms_Controller {
	/**
	 * Constructor.
	 *
	 * @param string $taxonomy Taxonomy.
	 */
	public function __construct( $taxonomy ) {
		parent::__construct( $taxonomy );
		$this->taxonomy  = $taxonomy;
		$this->namespace = 'pf/v1';
		$tax_obj         = get_taxonomy( $taxonomy );
		$this->rest_base = ! empty( $tax_obj->rest_base ) ? $tax_obj->rest_base : $tax_obj->name;
	}
}
