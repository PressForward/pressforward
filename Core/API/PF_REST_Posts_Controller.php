<?php
/**
 * Posts controller for PF content.
 *
 * @package PressForward
 */

/**
 * Extend the main WP_REST_Posts_Controller to a private endpoint controller.
 */
class PF_REST_Posts_Controller extends WP_REST_Posts_Controller {
	/**
	 * Constructor.
	 *
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {
		parent::__construct( $post_type );
		$this->namespace = 'pf/v1';
	}
}
