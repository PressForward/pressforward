<?php

class PF_Advance {
	var $post_interface;

	public static function init() {
		static $instance;

		if ( ! is_a( $instance, 'PF_Advance_Item' ) ) {
			$instance = new self();
		}

		return $instance;
	}

	private function includes(){
		//require_once(dirname(dirname(__FILE__)).'/controller/class-PF_to_WP_Meta.php');
	}

	private function __construct() {
		$this->includes();
		$this->post_interface = pressforward()->pf_item_interface;
	}

	public function to_item( $args = array() ){
		return $this->post_interface->insert_post( $args );
	}

	public function item_to_post( $item_ID ){
		$item = $this->post_interface->get_post( $item_ID , ARRAY_A );

		$post_id = $this->post_interface->insert_post( $item );
		$this->metas->transition_post_meta($item_ID, $post_id);
		return $post_id;
	}

	public function item_to_nomination( $item_ID ){
		$item = $this->post_interface->get_post( $item_ID , ARRAY_A );

		$nomination_id = $this->post_interface->insert_post( $item );
		$this->metas->transition_post_meta($item_ID, $nomination_id);
		return $nomination_id;
	}

	public function nomination_to_post( $nomination_ID ){
		$nomination = $this->post_interface->get_post( $nomination_ID , ARRAY_A );

		$post_id = $this->post_interface->insert_post( $nomination );
		$this->metas->transition_post_meta($nomination, $post_id);
		return $nomination_id;
	}
}
