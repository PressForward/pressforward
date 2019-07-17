<?php
namespace PressForward\Controllers;

use PressForward\Interfaces\System as System;

class PF_to_WP_System implements System {

	public function add_option( $option, $value = '', $autoload = 'yes' ) {
		return add_option( $option, $value, '', $autoload );
	}
	public function delete_option( $option ) {
		return delete_option( $option );
	}
	public function get_option( $option, $default ) {
		return get_option( $option, $default );
	}
	public function update_option( $option, $value = '', $autoload = 'yes' ) {
		return update_option( $option, $value, $autoload );
	}
	public function apply_filters( $tag, $values ) {
		return apply_filters( $tag, $value );
	}
	public function add_action( $tag, $value ) {
		return add_action( $tag, $value );
	}
	public function get_the_guid( $id ) {
		return get_the_guid( $id );
	}
	public function get_site_info( $info_type ){
		return get_bloginfo( $info_type );
	}

}
