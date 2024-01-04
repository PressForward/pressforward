<?php
/**
 * System interface.
 *
 * @package PressForward
 */

namespace PressForward\Interfaces;

/**
 * System interface.
 */
interface System {

	/**
	 * Adds option.
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    Option value.
	 * @param string $autoload Whether option should be autoloaded.
	 */
	public function add_option( $option, $value = '', $autoload = 'yes' );

	/**
	 * Deletes option.
	 *
	 * @param string $option Option name.
	 */
	public function delete_option( $option );

	/**
	 * Gets option.
	 *
	 * @param string $option        Option name.
	 * @param mixed  $default_value Default option value.
	 */
	public function get_option( $option, $default_value );

	/**
	 * Updates existing option.
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    Option value.
	 * @param string $autoload Whether option should be autoloaded.
	 */
	public function update_option( $option, $value = '', $autoload = 'yes' );

	/**
	 * Registers filter callback.
	 *
	 * @param string $tag    Filter hook.
	 * @param mixed  $values Values passed to callback.
	 */
	public function apply_filters( $tag, $values );

	/**
	 * Registers action callback.
	 *
	 * @param string $tag   Filter hook.
	 * @param mixed  $value Values passed to callback.
	 */
	public function add_action( $tag, $value );

	/**
	 * Gets a GUID for a given item ID.
	 *
	 * @param int $id Item ID.
	 */
	public function get_the_guid( $id );

	/**
	 * Gets a piece of site info.
	 *
	 * @param string $info_type Type of bloginfo being fetched.
	 */
	public function get_site_info( $info_type );
}
