<?php
namespace PressForward\Interfaces;
interface SystemUsers
{
	public function did_user_nominate($post_id, $user_id);
	public function get_user_option( $option );
	public function current_user_can( $capacity );
	public function user_level( $option_name, $role );
}
