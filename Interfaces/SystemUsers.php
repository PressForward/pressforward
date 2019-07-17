<?php
namespace PressForward\Interfaces;
interface SystemUsers {

	public function did_user_nominate( $post_id, $user_id);
	public function get_user_option( $option );
	public function current_user_can( $capacity );
	public function user_level( $option_name, $role );
	public function get_current_user();
	public function get_user_meta($user_id, $meta_key, $single = true);
	public function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value );
}
