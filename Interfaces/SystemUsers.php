<?php
/**
 * SystemUsers interface.
 *
 * @package PressForward
 */

namespace PressForward\Interfaces;

/**
 * SystemUsers interface.
 */
interface SystemUsers {

	/**
	 * Checks whether a user nominated a given post.
	 *
	 * @param int $post_id ID of the nominated item.
	 * @param int $user_id ID of the user. Optional. Defaults to current user.
	 * @return bool
	 */
	public function did_user_nominate( $post_id, $user_id );

	/**
	 * Gets an option for a user.
	 *
	 * @param string $option Option name.
	 * @return mixed
	 */
	public function get_user_option( $option );

	/**
	 * Checks whether the current user can perform a certain action.
	 *
	 * @param string $capability Capability to check.
	 * @return bool
	 */
	public function current_user_can( $capability );

	/**
	 * Gets the user capability for a given PF action type.
	 *
	 * @param string $option_name Option name.
	 * @param string $role        Role name.
	 * @return string
	 */
	public function user_level( $option_name, $role );

	/**
	 * Gets the current user.
	 *
	 * @return \WP_User
	 */
	public function get_current_user();

	/**
	 * Gets a piece of user meta.
	 *
	 * @param int    $user_id  ID of the user.
	 * @param string $meta_key Meta key.
	 * @param bool   $single   Whether to return a single row.
	 */
	public function get_user_meta( $user_id, $meta_key, $single = true );

	/**
	 * Updates a piece of user meta.
	 *
	 * @param int    $user_id    ID of the user.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param mixed  $prev_value Optional. Previous value.
	 */
	public function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' );
}
