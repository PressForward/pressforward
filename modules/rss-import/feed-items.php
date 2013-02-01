<?php

/**
 * Classes and functions for dealing with feed items
 */

/**
 * Database class for manipulating feed items
 */
class RSSPF_RSS_Import_Feed_Item {
	protected $filter_data = array();

	public function __construct() {
		$this->post_type = rsspf_rss_import_schema()->feed_item_post_type;
		$this->tag_taxonomy = rsspf_rss_import_schema()->feed_item_tag_taxonomy;
	}

	public function get( $args = array() ) {
		$wp_args = array(
			'post_type'        => $this->post_type,
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);

		$query_filters = array();

		// WP_Query does not accept a 'guid' param, so we filter hackishly
		if ( isset( $args['url'] ) ) {
			$this->filter_data['guid'] = $args['url'];
			unset( $args['url'] );
			$query_filters['posts_where'][] = '_filter_where_guid';
		}

		foreach ( $query_filters as $hook => $filters ) {
			foreach ( $filters as $f ) {
				add_filter( $hook, array( $this, $f ) );
			}
		}

		// Other WP_Query args pass through
		$wp_args = wp_parse_args( $args, $wp_args );

		$posts = get_posts( $wp_args );

		foreach ( $query_filters as $hook => $filters ) {
			foreach ( $filters as $f ) {
				remove_filter( $hook, array( $this, $f ) );
			}
		}

		// Fetch some handy rsspf-specific data
		if ( ! empty( $posts ) ) {
			foreach ( $posts as &$post ) {
				$post->word_count = get_post_meta( $post->ID, 'rsspf_feed_item_word_count', true );
				$post->source     = get_post_meta( $post->ID, 'rsspf_feed_item_source', true );
				$post->tags       = wp_get_post_terms( $post->ID, $this->tag_taxonomy );
			}
		}

		return $posts;
	}

	public function create( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'title'   => '',
			'url'     => '',
			'content' => '',
			'source'  => '',
			'date'    => '',
			'tags'    => array(),
		) );

		// Sanitization
		// Conversion should be done upstream
		if ( ! is_numeric( $r['date'] ) ) {
			return new WP_Error( 'Date should be in UNIX format' );
		}

		$wp_args = array(
			'post_type'    => $this->post_type,
			'post_status'  => 'publish',
			'post_title'   => $r['title'],
			'post_content' => wp_specialchars_decode( $r['content'], ENT_COMPAT ), // todo
			'guid'         => $r['url'],
			'post_date'    => date( 'Y-m-d H:i:s', $r['date'] ),
			'tax_input'    => array( $this->tag_taxonomy => $r['tags'] ),
		);

		$post_id = wp_insert_post( $wp_args );

		if ( $post_id ) {
			self::set_word_count( $post_id, $r['content'] );
			self::set_source( $post_id, $r['source'] );

		}

		return $post_id;
	}

	public function _filter_where_guid( $where ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.guid = %s ", $this->filter_data['guid'] );
		return $where;
	}

	// STATIC UTILITY METHODS

	public static function set_word_count( $post_id, $content = false ) {
		if ( false === $content ) {
			$post = get_post( $post_id );
			$content = $post->post_content;
		}

		$content_array = explode( ' ', strip_tags( $content ) );
		$word_count = count( $content_array );

		return update_post_meta( $post_id, 'rsspf_feed_item_word_count', $word_count );
	}

	public static function set_source( $post_id, $source ) {
		return update_post_meta( $post_id, 'rsspf_feed_item_source', $source );
	}

	/**
	 * Set a feed item's tags
	 *
	 * @param int $post_id
	 * @param array $tags
	 * @param bool $append True if you want to append rather than replace
	 */
	public static function set_tags( $post_id, $tags, $append = false ) {
		return wp_set_object_terms( $post_id, $tags, $this->tag_taxonomy, $append );
	}

	/**
	 * Converts a raw tag array to a list appropriate for a tax_query
	 *
	 * Will create the necessary tags if they're not found
	 */
	public static function convert_raw_tags( $tags ) {
		$retval = array( $this->tag_taxonomy => $tags );
		return $retval;
	}

	public static function get_term_slug_from_tag( $tag ) {
//		return 'rsspf_feed_item_' .
	}
}
