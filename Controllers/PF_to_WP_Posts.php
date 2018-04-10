<?php

namespace PressForward\Controllers;

use PressForward\Interfaces\Items as Items;

// use WP_Query;
class PF_to_WP_Posts implements Items {

	public function insert_post( $post, $error = false, $item_id = false ) {
		if ( isset( $post['post_date'] ) && ! isset( $post['post_date_gmt'] ) ) {
			$post['post_date_gmt'] = get_gmt_from_date( $post['post_date'] );
		}
		if ( ( false !== $item_id ) && in_array( $post['post_type'], array( 'post', 'pf_feed_item', 'nomination' ) ) ) {
			$check = $this->check_not_existing( $item_id, $post['post_type'] );
			// var_dump($post, $item_id, $check); die();
			if ( true !== $check ) {
				return $check;
			}
		}
		return wp_insert_post( $post, $error );
	}

	public function update_post( $post, $error = false ) {
		if ( ! is_object( $post ) && ( isset( $post['post_date'] ) && ! isset( $post['post_date_gmt'] ) ) ) {
			$post['post_date_gmt'] = get_gmt_from_date( $post['post_date'] );
		} elseif ( is_object( $post ) && isset( $post->post_date ) && ! isset( $post->post_date_gmt ) ) {
			$post->post_date_gmt = get_gmt_from_date( $post->post_date );
		}
		return wp_update_post( $post, $error );
	}

	public function delete_post( $postid, $force_delete = false ) {
		return wp_delete_post( $postid, $force_delete );
	}

	public function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
		return get_post( $post, $output, $filter );
	}

	public function get_posts( $query ) {
		return get_posts( $query );
	}

	public function is_error( $post ) {
		return is_wp_error( $post );
	}

	public function check_not_existing( $item_id, $post_type ) {
		global $wpdb;
		$item_id_key = pressforward( 'controller.metas' )->get_key( 'item_id' );
		if ( 3.2 >= get_bloginfo( 'version' ) ) {
			$querystr = $wpdb->prepare(
				"
			   SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
			   FROM {$wpdb->posts}, {$wpdb->postmeta}
			   WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
			   AND {$wpdb->postmeta}.meta_key = '{$item_id_key}'
			   AND {$wpdb->postmeta}.meta_value = %s
			   AND {$wpdb->posts}.post_type = %s
			   ORDER BY {$wpdb->posts}.post_date DESC
			", $item_id, $post_type
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
		pf_log( 'Checking with post 3.2 method.' );
		// WP_Query arguments
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

		// var_dump($args);
		// The Query
		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {

			// var_dump($query); die();
			return true;
		} else {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id = get_the_ID();
				// var_dump($id); die();
				wp_reset_postdata();
				return $id;
			}
		}
	}

}
