<?php

/**
 * Classes and functions for dealing with feed items
 */

/**
 * Database class for manipulating feed items
 */
class PF_Feed_Item {
	protected $filter_data = array();

	public function __construct() {
		$this->post_type = pf_rss_import_schema()->feed_item_post_type;
		$this->tag_taxonomy = pf_rss_import_schema()->feed_item_tag_taxonomy;
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

		// Fetch some handy pf-specific data
		if ( ! empty( $posts ) ) {
			foreach ( $posts as &$post ) {
				$post->word_count = get_post_meta( $post->ID, 'pf_feed_item_word_count', true );
				$post->source     = get_post_meta( $post->ID, 'pf_feed_item_source', true );
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

		return update_post_meta( $post_id, 'pf_feed_item_word_count', $word_count );
	}

	public static function set_source( $post_id, $source ) {
		return update_post_meta( $post_id, 'pf_feed_item_source', $source );
	}

	# This function feeds items to our display feed function pf_reader_builder.
	# It is just taking our database of rssarchival items and putting them into a
	# format that the builder understands.
	public static function archive_feed_to_display($pageTop = 0) {
		global $wpdb, $post;
		//$args = array(
		//				'post_type' => array('any')
		//			);
		//$pageBottom = $pageTop + 20;
		$args = pf_feed_item_schema()->feed_item_post_type;
		//$archiveQuery = new WP_Query( $args );
		 $dquerystr = "
			SELECT $wpdb->posts.*, $wpdb->postmeta.*
			FROM $wpdb->posts, $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND $wpdb->posts.post_type = '" . pf_feed_item_schema()->feed_item_post_type . "'
			AND $wpdb->postmeta.meta_key = 'sortable_item_date'
			ORDER BY $wpdb->postmeta.meta_value DESC
			LIMIT $pageTop, 20
		 ";
		// print_r($dquerystr);
		 # DESC here because we are sorting by UNIX datestamp, where larger is later.
		 //Provide an alternative to load by feed date order.
		# This is how we do a custom query, when WP_Query doesn't do what we want it to.
		$archivalposts = $wpdb->get_results($dquerystr, OBJECT);
		//print_r(count($rssarchivalposts)); die();
		$feedObject = array();
		$c = 0;

		if ($archivalposts):

			foreach ($archivalposts as $post) :
			# This takes the $post objects and translates them into something I can do the standard WP functions on.
			setup_postdata($post);
			# I need this data to check against existing transients.
			$post_id = get_the_ID();
			$id = get_post_meta($post_id, 'item_id', true); //die();
			//Switch the delete on to wipe rss archive posts from the database for testing.
			//wp_delete_post( $post_id, true );
			//print_r($id);
			# If the transient exists than there is no reason to do any extra work.
			if ( false === ( $feedObject['rss_archive_' . $c] = get_transient( 'pf_archive_' . $id ) ) ) {

				$item_id = get_post_meta($post_id, 'item_id', true);
				$source_title = get_post_meta($post_id, 'source_title', true);
				$item_date = get_post_meta($post_id, 'item_date', true);
				$item_author = get_post_meta($post_id, 'item_author', true);
				$item_link = get_post_meta($post_id, 'item_link', true);
				$item_feat_img = get_post_meta($post_id, 'item_feat_img', true);
				$item_wp_date = get_post_meta($post_id, 'item_wp_date', true);
				$item_tags = get_post_meta($post_id, 'item_tags', true);
				$source_repeat = get_post_meta($post_id, 'source_repeat', true);

				$contentObj = new htmlchecker(get_the_content());
				$item_content = $contentObj->closetags(get_the_content());

				$feedObject['rss_archive_' . $c] = pf_feed_object(
											get_the_title(),
											$source_title,
											$item_date,
											$item_author,
											$item_content,
											$item_link,
											$item_feat_img,
											$item_id,
											$item_wp_date,
											$item_tags,
											//Manual ISO 8601 date for pre-PHP5 systems.
											get_the_date('o-m-d\TH:i:sO'),
											$source_repeat
											);
				set_transient( 'pf_archive_' . $id, $feedObject['rss_archive_' . $c], 60*10 );

			}
			$c++;
			endforeach;


		endif;
		wp_reset_postdata();
		return $feedObject;
	}

	# Method to manually delete rssarchival entries on user action.
	public static function reset_feed() {
		global $wpdb, $post;
		//$args = array(
		//				'post_type' => array('any')
		//			);
		$args = 'post_type=' . pf_feed_item_schema()->feed_item_post_type;
		//$archiveQuery = new WP_Query( $args );
		$dquerystr = "
			SELECT $wpdb->posts.*, $wpdb->postmeta.*
			FROM $wpdb->posts, $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND $wpdb->posts.post_type ='" . pf_feed_item_schema()->feed_item_post_type .
		 "'";
		# This is how we do a custom query, when WP_Query doesn't do what we want it to.
		$rssarchivalposts = $wpdb->get_results($dquerystr, OBJECT);
		//print_r(count($rssarchivalposts)); die();
		$feedObject = array();
		$c = 0;

		if ($rssarchivalposts):

			foreach ($rssarchivalposts as $post) :
			# This takes the $post objects and translates them into something I can do the standard WP functions on.
			setup_postdata($post);
			$post_id = get_the_ID();
			//Switch the delete on to wipe rss archive posts from the database for testing.
			wp_delete_post( $post_id, true );
			endforeach;


		endif;
		wp_reset_postdata();
		print_r(__('All archives deleted.', 'pf'));

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
//		return 'pf_feed_item_' .
	}
}
