<?php
/**
 * Loop tools.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

/**
 * PF_Loops class.
 */
class PF_Loops {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {}

	// This function feeds items to our display feed function pf_reader_builder.
	// It is just taking our database of rssarchival items and putting them into a
	// format that the builder understands.

	/**
	 * Fetch a collection of feed items and format for use in the reader.
	 *
	 * @param array $args {
	 *   Array of optional arguments.
	 *   @var int    $pageTop      First item to display on the page. Note that it
	 *                             is decremented by 1, so should not be 0.
	 *   @var int    $pagefull     Number of items to show per page.
	 *   @var int    $fromUnixTime Feed items will only be returned when their
	 *                             publish date is later than this. Must be in
	 *                             UNIX format.
	 *   @var bool   $limitless    True to show all feed items. Skips pagination,
	 *                             but obeys $fromUnixTime. Default: false.
	 *   @var string $limit        Limit to feed items with certain relationships
	 *                             set. Note that relationships are relative to
	 *                             logged-in user (starred|nominated).
	 * }
	 * @return array
	 */
	public static function archive_feed_to_display( $args = array() ) {

		// Backward compatibility.
		$func_args = func_get_args();
		if ( ! is_array( $func_args[0] ) || 1 < count( $func_args ) ) {
			$args = array(
				'start' => $func_args[0],
			);

			if ( isset( $func_args[1] ) ) {
				$args['posts_per_page'] = $func_args[1];
			}

			if ( isset( $func_args[2] ) ) {
				$args['from_unix_time'] = $func_args[2];
			}

			if ( isset( $func_args[3] ) ) {
				$args['no_limit'] = $func_args[3];
			}

			if ( isset( $func_args[4] ) ) {
				$args['relationship'] = $func_args[4];
			}
		} else {
			$args = $func_args[0];
		}

		// Make sure default values are set.
		$r = array_merge(
			array(
				'start'            => 0,
				'posts_per_page'   => 20,
				'from_unix_time'   => 0,
				'no_limit'         => false,
				'relationship'     => false,
				'search_terms'     => '',
				'exclude_archived' => false,
				'count_total'      => false,
				'orderby'          => '',
			),
			$args
		);

		if ( empty( $r['from_unix_time'] ) || ( $r['from_unix_time'] < 100 ) ) {
			$r['from_unix_time'] = 0;
		}

		$r['start'] = $r['start'] - 1;

		if ( ! $r['posts_per_page'] ) {
			$user_obj = wp_get_current_user();
			$user_id  = $user_obj->ID;

			// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			$r['posts_per_page'] = get_user_option( 'pf_pagefull', $user_id );
			if ( $r['posts_per_page'] > 100 ) {
				$r['posts_per_page'] = 100;
			}

			if ( empty( $r['posts_per_page'] ) ) {
				$r['posts_per_page'] = 20;
			}
		}

		$post_args = array(
			'post_type'      => pf_feed_item_post_type(),

			// Ordering by 'sortable_item_date' > 0.
			// phpcs:disable WordPress.DB.SlowDBQuery
			'meta_key'       => 'sortable_item_date',
			'meta_value'     => $r['from_unix_time'],
			'meta_type'      => 'SIGNED',
			'meta_compare'   => '>',
			'orderby'        => $r['orderby'],

			// Pagination.
			'posts_per_page' => $r['posts_per_page'],
			'offset'         => $r['start'],
		);

		if ( empty( $post_args['orderby'] ) ) {
			$post_args['orderby'] = [ 'meta_value' => 'DESC' ];
		}

		if ( $r['no_limit'] ) {
			$post_args['posts_per_page'] = -1;
		}

		if ( ! empty( $r['relationship'] ) ) {
			switch ( $r['relationship'] ) {
				case 'starred':
					$rel_items = pf_get_relationships_for_user( 'star', get_current_user_id() );
					break;

				case 'nominated':
					$rel_items = pf_get_relationships_for_user( 'nominate', get_current_user_id() );
					break;
			}

			if ( ! empty( $rel_items ) ) {
				$post_args['post__in'] = wp_list_pluck( $rel_items, 'item_id' );
			}
		}

		if ( ! empty( $r['reveal'] ) ) {
			switch ( $r['reveal'] ) {
				case 'no_hidden':
					$rel_items = pf_get_relationships_for_user( 'archive', get_current_user_id() );
					break;

				case 'unread':
					$rel_not_items = pf_get_relationships_for_user( 'read', get_current_user_id() );
					break;

				case 'drafted':
					$drafted_items = pf_get_drafted_items();
					if ( empty( $drafted_items ) ) {
						$drafted_items = array( 0 );
					}
					break;
			}

			if ( ! empty( $rel_items ) ) {
				$posts_in = wp_list_pluck( $rel_items, 'item_id' );
				if ( ! empty( $post_args['post__in'] ) ) {
					$post_args['post__in'] = array_merge( $post_args['post__in'], $posts_in );
				} else {
					$post_args['post__in'] = $posts_in;
				}
			}

			if ( ! empty( $rel_not_items ) ) {
				$posts_not_in              = wp_list_pluck( $rel_not_items, 'item_id' );
				$post_args['post__not_in'] = $posts_not_in;
			}

			if ( isset( $drafted_items ) ) {
				// Intersect to match only those items that have drafts.
				if ( ! empty( $post_args['post__in'] ) && array( 0 ) !== $drafted_items ) {
					$post_args['post__in'] = array_intersect( $post_args['post__in'], $drafted_items );
				} else {
					$post_args['post__in'] = $drafted_items;
				}
			}
		}

		if ( ! empty( $r['exclude_archived'] ) ) {
			$archived                  = pf_get_relationships_for_user( 'archive', get_current_user_id() );
			$post_args['post__not_in'] = wp_list_pluck( $archived, 'item_id' );
		}

		if ( ! empty( $r['search_terms'] ) ) {
			/*
			 * Quote so as to get only exact matches. This is for
			 * backward compatibility - might want to remove it for
			 * a more flexible search.
			 */
			$post_args['s'] = '"' . $r['search_terms'] . '"';
		}

		$post_args['post_status'] = 'publish';

		if ( isset( $_GET['feed'] ) ) {
			$post_args['post_parent'] = intval( $_GET['feed'] );
		} elseif ( isset( $_GET['folder'] ) ) {
			$parents_in_folder = new \WP_Query(
				array(
					'post_type'              => pressforward( 'schema.feeds' )->post_type,
					'fields'                 => 'ids',
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,

					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'tax_query'              => array(
						array(
							'taxonomy' => pressforward( 'schema.feeds' )->tag_taxonomy,
							'field'    => 'term_id',
							// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
							'terms'    => wp_unslash( $_GET['folder'] ),
						),
					),
				)
			);

			$post_args['post_parent__in'] = $parents_in_folder->posts;
			if ( empty( $post_args['post_parent__in'] ) ) {
				return array();
			}
		}

		$feed_items = new \WP_Query( $post_args );

		$feed_object = array();
		$c           = 0;

		foreach ( $feed_items->posts as $post ) {
			$post_id = $post->ID;

			$item_id         = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_id', true );
			$source_title    = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'source_title', true );
			$item_date       = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_date', true );
			$item_author     = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_author', true );
			$item_link       = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_link', true );
			$item_feat_img   = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_feat_img', true );
			$item_wp_date    = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_wp_date', true );
			$item_tags       = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_tags', true );
			$source_repeat   = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'source_repeat', true );
			$readable_status = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'readable_status', true );

			$content_obj  = pressforward( 'library.htmlchecker' );
			$item_content = $content_obj->closetags( $post->post_content );

			// Remove image src attributes and replace with data-src attributes.
			$doc = new \DOMDocument();
			libxml_use_internal_errors( true );
			$doc->loadHTML( '<?xml version="1.0" encoding="UTF-8"?>' . $item_content );
			$imgs = $doc->getElementsByTagName( 'img' );
			foreach ( $imgs as $img ) {
				$img_src = $img->getAttribute( 'src' );
				$img->removeAttribute( 'src' );
				$img->setAttribute( 'data-src', $img_src );
			}

			$item_content = '';
			$bodies       = $doc->getElementsByTagName( 'body' );
			if ( $bodies->length > 0 ) {
				foreach ( $bodies->item( 0 )->childNodes as $child ) {
					$item_content .= $doc->saveHTML( $child );
				}
			}

			$feed_object[ 'rss_archive_' . $c ] = pf_feed_object(
				$post->post_title,
				$source_title,
				$item_date,
				$item_author,
				$item_content,
				$item_link,
				$item_feat_img,
				$item_id,
				$item_wp_date,
				$item_tags,
				// Manual ISO 8601 date for pre-PHP5 systems.
				gmdate( 'o-m-d\TH:i:sO', strtotime( $post->post_date ) ),
				$source_repeat,
				(string) $post_id,
				$readable_status
			);

			++$c;
		}

		if ( $r['count_total'] ) {
			$retval = [
				'items'         => $feed_object,
				'max_num_pages' => $feed_items->max_num_pages,
			];
		} else {
			$retval = $feed_object;
		}

		return $retval;
	}
}
