<?php

namespace PressForward\Controllers;

use PressForward\Interfaces\SystemMeta as SystemMeta;

class PF_to_WP_Meta implements SystemMeta {

	public function add_meta($post_id, $meta_key, $meta_value, $unique = false){
		return add_post_meta( $post_id, $meta_key, $meta_value, $unique );
	}

	public function update_meta($post_id, $meta_key, $meta_value, $prev_value = ''){
		return update_post_meta($post_id, $meta_key, $meta_value, $prev_value);
	}

	public function delete_meta($post_id, $meta_key, $meta_value = ''){
		return delete_post_meta($post_id, $meta_key, $meta_value);
	}

	public function get_meta( $post_id, $key = '', $single = false ){
		return get_post_meta( $post_id, $key, $single );
	}

	public function get_metas( $post_id ) {
		return get_post_meta( $post_id );
	}

}
