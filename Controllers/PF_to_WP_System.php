<?php
/**
 * System utilities.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

/**
 * System utilities.
 */
class PF_to_WP_System implements \PressForward\Interfaces\System {
	/**
	 * Wrapper for add_option().
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    Option value.
	 * @param string $autoload Whether option should be autoloaded.
	 */
	public function add_option( $option, $value = '', $autoload = 'yes' ) {
		return add_option( $option, $value, '', $autoload );
	}

	/**
	 * Wrapper for delete_option().
	 *
	 * @param string $option Option name.
	 */
	public function delete_option( $option ) {
		return delete_option( $option );
	}

	/**
	 * Wrapper for get_option().
	 *
	 * @param string $option        Option name.
	 * @param mixed  $default_value Default option value.
	 */
	public function get_option( $option, $default_value ) {
		return get_option( $option, $default_value );
	}

	/**
	 * Wrapper for update_option().
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    Option value.
	 * @param string $autoload Whether option should be autoloaded.
	 */
	public function update_option( $option, $value = '', $autoload = 'yes' ) {
		return update_option( $option, $value, $autoload );
	}

	/**
	 * Wrapper for apply_filters().
	 *
	 * @param string $tag    Filter hook.
	 * @param mixed  $values Values passed to callback.
	 */
	public function apply_filters( $tag, $values ) {
		return apply_filters( $tag, $values );
	}

	/**
	 * Wrapper for add_action().
	 *
	 * @param string $tag   Filter hook.
	 * @param mixed  $value Values passed to callback.
	 */
	public function add_action( $tag, $value ) {
		return add_action( $tag, $value );
	}

	/**
	 * Wrapper for get_the_guid().
	 *
	 * @param int $id Item ID.
	 */
	public function get_the_guid( $id ) {
		return get_the_guid( $id );
	}

	/**
	 * Wrapper for get_bloginfo().
	 *
	 * @param string $info_type Type of bloginfo being fetched.
	 */
	public function get_site_info( $info_type ) {
		return get_bloginfo( $info_type );
	}
}
