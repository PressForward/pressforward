<?php
namespace PressForward\Interfaces;
interface System {

	public function add_option( $option, $value = '', $autoload = 'yes' );
	public function delete_option( $option);
	public function get_option( $option, $default);
	public function update_option( $option, $value = '', $autoload = 'yes' );
	public function apply_filters( $tag, $values );
	public function add_action( $tag, $value );
	public function get_the_guid( $id );
	public function get_site_info( $info_type );
}
