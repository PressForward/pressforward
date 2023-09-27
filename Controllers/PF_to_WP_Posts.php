<?php
/**
 * Post utilities.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

/**
 * Wrappers for WP post functions.
 */
class PF_to_WP_Posts implements \PressForward\Interfaces\Items {

	/**
	 * Creates a post.
	 *
	 * @param array       $post    Update params. See wp_update_post().
	 * @param bool        $error   Whether to return a WP_Error object. Default fals.
	 * @param string|null $item_id PF item id.
	 * @return mixed
	 */
	public function insert_post( $post, $error = false, $item_id = null ) {
		if ( isset( $post['post_date'] ) && ! isset( $post['post_date_gmt'] ) ) {
			$post['post_date_gmt'] = get_gmt_from_date( $post['post_date'] );
		}
		if ( ( null !== $item_id ) && in_array( $post['post_type'], array( pressforward_draft_post_type(), 'pf_feed_item', 'nomination' ), true ) ) {
			$check = $this->check_not_existing( $item_id, $post['post_type'] );
			if ( true !== $check ) {
				return $check;
			}
		}
		return wp_insert_post( $post, $error );
	}

	/**
	 * Updates a post.
	 *
	 * @param array|\WP_Post $post  Update params. See wp_update_post().
	 * @param bool           $error Whether to return a WP_Error object. Default false.
	 * @return mixed
	 */
	public function update_post( $post, $error = false ) {
		if ( ! is_object( $post ) && ( isset( $post['post_date'] ) && ! isset( $post['post_date_gmt'] ) ) ) {
			$post['post_date_gmt'] = get_gmt_from_date( $post['post_date'] );
		} elseif ( $post instanceof \WP_Post ) {
			$post->post_date_gmt = get_gmt_from_date( $post->post_date );
		}
		return wp_update_post( $post, $error );
	}

	/**
	 * Deletes a post.
	 *
	 * @param int|\WP_Post $postid       Post.
	 * @param bool         $force_delete Whether to force delete and skip trash.
	 * @return mixed
	 */
	public function delete_post( $postid, $force_delete = false ) {
		return wp_delete_post( $postid, $force_delete );
	}

	/**
	 * Gets a post object.
	 *
	 * @param int|\WP_Post $post   Post.
	 * @param mixed        $output Output format.
	 * @param string       $filter Filter.
	 * @return mixed
	 */
	public function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
		return get_post( $post, $output, $filter );
	}

	/**
	 * Gets posts based on query vars.
	 *
	 * @param array $query See get_posts().
	 * @return array
	 */
	public function get_posts( $query ) {
		return get_posts( $query );
	}

	/**
	 * Checks whether a return value is an error.
	 *
	 * @param mixed $post Post object returned from wp_update_post().
	 * @return bool
	 */
	public function is_error( $post ) {
		return is_wp_error( $post );
	}

	/**
	 * Checks to see whether an item exists with this PF item ID.
	 *
	 * @param string $item_id   PF item ID.
	 * @param string $post_type Post type.
	 * @return int|bool
	 */
	public function check_not_existing( $item_id, $post_type ) {
		global $wpdb;
		$item_id_key = pressforward( 'controller.metas' )->get_key( 'item_id' );

		// Ignoring until we remove.
		// phpcs:disable
		if ( 3.2 >= get_bloginfo( 'version' ) ) {
			$querystr = $wpdb->prepare(
				"
			   SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
			   FROM {$wpdb->posts}, {$wpdb->postmeta}
			   WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
			   AND {$wpdb->postmeta}.meta_key = %s
			   AND {$wpdb->postmeta}.meta_value = %s
			   AND {$wpdb->posts}.post_type = %s
			   ORDER BY {$wpdb->posts}.post_date DESC
			",
				$item_id_key,
				$item_id,
				$post_type
			);
			// AND $wpdb->posts.post_date < NOW() <- perhaps by removing we can better prevent simultaneous duplications?
			// Since I've altered the query, I could change this to just see if there are any items in the query results
			// and check based on that. But I haven't yet.
			$checkposts = $wpdb->get_results( $querystr, OBJECT );
			if ( empty( $checkposts ) ) {
				return true;
			} else {
				pf_log( 'Checked with pre 3.2 method. Post already exists.' );
				return $checkposts[0]->ID;
			}
		}
		// phpcs:enable

		pf_log( 'Checking with post 3.2 method.' );

		// WP_Query arguments.
		$args = array(
			'post_type'  => $post_type,
			'meta_query' => array(
				array(
					'key'     => 'item_id',
					'value'   => $item_id,
					'compare' => '=',
				),
			),
		);

		// The Query.
		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return true;
		} else {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id = get_the_ID();
				wp_reset_postdata();
				return $id;
			}
		}

		return true;
	}
}
