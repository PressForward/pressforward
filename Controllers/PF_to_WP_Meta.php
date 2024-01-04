<?php
/**
 * Post meta utilities.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

/**
 * Metadata wrappers.
 */
class PF_to_WP_Meta implements \PressForward\Interfaces\SystemMeta {

	/**
	 * Adds metadata for a post.
	 *
	 * @param int    $post_id    ID of the post.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Meta value.
	 * @param bool   $unique     Optional. Whether to force uniqueness. Default fals.
	 * @return int|bool
	 */
	public function add_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
		return add_post_meta( $post_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Updates metadata for a post.
	 *
	 * @param int    $post_id    ID of the post.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Meta value.
	 * @param mixed  $prev_value Optional. Previous value to match.
	 * @return int|bool
	 */
	public function update_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Deletes metadata for a post.
	 *
	 * @param int    $post_id    ID of the post.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Optional. Meta value to match.
	 * @return int|bool
	 */
	public function delete_meta( $post_id, $meta_key, $meta_value = '' ) {
		return delete_post_meta( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Gets a single piece of metadata for a post.
	 *
	 * @param int    $post_id ID of the post.
	 * @param string $key     Metadata key.
	 * @param bool   $single  Whether to fetch a single row.
	 * @return mixed
	 */
	public function get_meta( $post_id, $key = '', $single = false ) {
		return get_post_meta( $post_id, $key, $single );
	}

	/**
	 * Gets all metadata for a post.
	 *
	 * @param int $post_id ID of the post.
	 * @return array
	 */
	public function get_metas( $post_id ) {
		return get_post_meta( $post_id );
	}
}
