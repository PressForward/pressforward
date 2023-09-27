<?php
/**
 * Advance_System interface.
 *
 * @package PressForward
 */

namespace PressForward\Interfaces;

/**
 * Advance_System interface.
 */
interface Advance_System {

	/**
	 * Gets the post status for the "last step" in the advancement process.
	 *
	 * @return string
	 */
	public function last_step_state();

	/**
	 * Gets the post type for the "last step" in the advancement process.
	 *
	 * @return string
	 */
	public function last_step_post_type();

	/**
	 * Transitions an old post to a new one.
	 *
	 * @param int|\WP_Post $old_post Source post.
	 * @param int|\WP_Post $new_post Destination post.
	 * @return void
	 */
	public function transition( $old_post, $new_post );

	/**
	 * Migrates a featured image from one post to another.
	 *
	 * @param int|\WP_Post $old_post Source post.
	 * @param int|\WP_Post $new_post Destination post.
	 * @return void
	 */
	public function transition_post_image( $old_post, $new_post );

	/**
	 * Prepares a post sent from the bookmarklet.
	 *
	 * @param int $post_id ID of the post.
	 * @return void
	 */
	public function prep_bookmarklet( $post_id );

	/**
	 * Transitions a nomination to the last step, ie becoming a post draft.
	 *
	 * @param array $post Post args.
	 * @return int ID of the newly created post draft.
	 */
	public function to_last_step( $post = array() );

	/**
	 * Transitions a post to a nomination.
	 *
	 * @param array $post Post args.
	 * @return int ID of the new nomination post.
	 */
	public function to_nomination( $post = array() );

	/**
	 * Checks for the existence of posts in previous PF states.
	 *
	 * @param string $item_id   ID of the item.
	 * @param string $post_type Post type.
	 */
	public function get_pf_type_by_id( $item_id, $post_type );
}
