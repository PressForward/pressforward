<?php
/**
 * SystemMeta interface.
 *
 * @package PressForward
 */

namespace PressForward\Interfaces;

/**
 * SystemMeta interface.
 */
interface SystemMeta {

	/**
	 * Adds metadata for a post.
	 *
	 * @param int    $post_id    ID of the post.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Meta value.
	 * @param bool   $unique     Optional. Whether to force uniqueness. Default fals.
	 * @return int|bool
	 */
	public function add_meta( $post_id, $meta_key, $meta_value, $unique );

	/**
	 * Updates metadata for a post.
	 *
	 * @param int    $post_id    ID of the post.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Meta value.
	 * @param mixed  $prev_value Optional. Previous value to match.
	 * @return int|bool
	 */
	public function update_meta( $post_id, $meta_key, $meta_value, $prev_value = '' );

	/**
	 * Deletes metadata for a post.
	 *
	 * @param int    $post_id    ID of the post.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Optional. Meta value to match.
	 * @return int|bool
	 */
	public function delete_meta( $post_id, $meta_key, $meta_value );

	/**
	 * Gets a single piece of metadata for a post.
	 *
	 * @param int    $post_id ID of the post.
	 * @param string $key     Metadata key.
	 * @param bool   $single  Whether to fetch a single row.
	 * @return mixed
	 */
	public function get_meta( $post_id, $key = '', $single = false );
}
