<?php
/**
 * Items interface.
 *
 * @package PressForward
 */

namespace PressForward\Interfaces;

/**
 * Items interface.
 */
interface Items {
	/**
	 * Creates a post.
	 *
	 * @param array  $post    Update params. See wp_update_post().
	 * @param bool   $error   Whether to return a WP_Error object. Default fals.
	 * @param string $item_id PF item id.
	 * @return mixed
	 */
	public function insert_post( $post, $error = false, $item_id = '' );

	/**
	 * Updates a post.
	 *
	 * @param array $post  Update params. See wp_update_post().
	 * @param bool  $error Whether to return a WP_Error object. Default fals.
	 * @return mixed
	 */
	public function update_post( $post, $error = false );

	/**
	 * Deletes a post.
	 *
	 * @param int|\WP_Post $postid       Post.
	 * @param bool         $force_delete Whether to force delete and skip trash.
	 * @return mixed
	 */
	public function delete_post( $postid, $force_delete = false );

	/**
	 * Gets a post object.
	 *
	 * @param int|\WP_Post $post   Post.
	 * @param mixed        $output Output format.
	 * @param string       $filter Filter.
	 * @return mixed
	 */
	public function get_post( $post = null, $output = OBJECT, $filter = 'raw' );

	/**
	 * Checks whether a return value is an error.
	 *
	 * @param mixed $post Post object returned from wp_update_post().
	 * @return bool
	 */
	public function is_error( $post );
}
