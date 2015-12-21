<?php

require_once(dirname(dirname(__FILE__)).'/interfaces/interface-meta_data.php');

class PF_to_WP_Meta implements System_Meta {

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

}
