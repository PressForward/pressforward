<?php
interface System_Meta
{
    public function add_meta($post_id, $meta_key, $meta_value, $unique);
    public function update_meta($post_id, $meta_key, $meta_value, $prev_value);
	public function delete_meta($post_id, $meta_key, $meta_value);
	public function get_meta( $post_id, $key = '', $single = false );
}
