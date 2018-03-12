<?php
namespace PressForward\Core\Schema;

use PressForward\Interfaces\Items as Items;
use PressForward\Controllers\Metas;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
/**
 * Classes and functions for dealing with feed items
 */

/**
 * Database class for manipulating feed items
 */
class Feed_Items implements HasActions, HasFilters {
	protected $filter_data = array();
	var $post_type;
	var $tag_taxonomy;

	public function __construct( Items $items, Metas $metas ) {
		$this->post_type    = 'pf_feed_item';
		$this->tag_taxonomy = 'pf_feed_item_tag';
		$this->items        = $items;
		$this->metas        = $metas;
		// Post types and taxonomies must be registered after 'init'
	}

	public function action_hooks() {
		$hooks = array(
			array(
				'hook'   => 'init',
				'method' => 'register_feed_item_post_type',
			),
			array(
				'hook'   => 'pf_feed_item_post_type_registered',
				'method' => 'register_feed_item_tag_taxonomy',
			),
			array(
				'hook'   => 'init',
				'method' => 'register_feed_item_removed_status',
			),
		);
		return $hooks;
	}

	public function filter_hooks() {
		return array(
			array(
				'hook'   => 'user_has_cap',
				'method' => 'alter_cap_on_fly',
			),
			array(
				'hook'     => 'map_meta_cap',
				'method'   => 'feeds_item_map_meta_cap',
				'priority' => 10,
				'args'     => 4,
			),
		);
	}

	public function register_feed_item_post_type() {
		$labels = array(
			'name'               => __( 'Feed Items', 'pf' ),
			'singular_name'      => __( 'Feed Item', 'pf' ),
			'add_new'            => _x( 'Add New', 'pf', 'add new feed item' ),
			'all_items'          => __( 'All Feed Items', 'pf' ),
			'add_new_item'       => __( 'Add New Feed Item', 'pf' ),
			'edit_item'          => __( 'Edit Feed Item', 'pf' ),
			'new_item'           => __( 'New Feed Item', 'pf' ),
			'view_item'          => __( 'View Feed Item', 'pf' ),
			'search_items'       => __( 'Search Feed Items', 'pf' ),
			'not_found'          => __( 'No feed items found', 'pf' ),
			'not_found_in_trash' => __( 'No feed items found in trash', 'pf' ),
		);

		$modules = pressforward( 'modules' )->modules;
		if ( isset( $modules['rss-out'] ) && ( 'yes' == get_option( PF_SLUG . '_' . 'rss-out_enable' ) ) ) {
			$rest_enabled = true;
		} else {
			$rest_enabled = false;
		}

		register_post_type(
			$this->post_type, apply_filters(
				'pf_register_feed_item_post_type_args', array(
					'label'                 => $labels['name'],
					'labels'                => $labels,
					'description'           => __( 'Feed items imported by PressForward&#8217;s RSS Importer', 'pf' ),
					'public'                => false,
					'show_ui'               => true,
					'show_in_admin_bar'     => false,
					'show_in_rest'          => true, // $rest_enabled,
					'rest_base'             => 'feed_items',
					'rest_controller_class' => 'PF_REST_Posts_Controller',
					'capability_type'       => $this->post_type,
					'capabilities'          => $this->map_feed_item_caps(),
				)
			)
		);

		do_action( 'pf_feed_item_post_type_registered' );
	}

	public function map_feed_item_caps() {
		return array(
			'publish_posts'       => 'publish_' . $this->post_type . 's',
			'edit_posts'          => 'edit_' . $this->post_type . 's',
			'edit_others_posts'   => 'edit_others_' . $this->post_type . 's',
			'delete_posts'        => 'delete_' . $this->post_type . 's',
			'delete_others_posts' => 'delete_others_' . $this->post_type . 's',
			'read_private_posts'  => 'read_private_' . $this->post_type . 's',
			'publish_pages'       => 'publish_' . $this->post_type . 's',
			'edit_pages'          => 'edit_' . $this->post_type . 's',
			'edit_others_pages'   => 'edit_others_' . $this->post_type . 's',
			'delete_pages'        => 'delete_' . $this->post_type . 's',
			'delete_others_pages' => 'delete_others_' . $this->post_type . 's',
			'read_private_pages'  => 'read_private_' . $this->post_type . 's',
			'edit_post'           => 'edit_' . $this->post_type,
			'delete_post'         => 'delete_' . $this->post_type,
			'read_post'           => 'read_' . $this->post_type,
			'edit_page'           => 'edit_' . $this->post_type,
			'delete_page'         => 'delete_' . $this->post_type,
			'read_page'           => 'read_' . $this->post_type,
		);
	}


	function alter_cap_on_fly( $caps ) {

		foreach ( $this->map_feed_item_caps() as $core_cap => $cap ) {
			if ( ! empty( $caps[ $core_cap ] ) ) { // user has edit capabilities
				$caps[ $cap ] = true;
			}
		}
		return $caps;
	}

	function feeds_item_map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( empty( $args ) ) {
			return $caps;
		}
		/* If editing, deleting, or reading a feed, get the post and post type object. */
		if ( 'edit_' . $this->post_type == $cap || 'delete_' . $this->post_type == $cap || 'read_' . $this->post_type == $cap ) {
			$post      = get_post( $args[0] );
			$post_type = get_post_type_object( $this->post_type );

			/* Set an empty array for the caps. */
			$caps = array();
		}

		/* If editing a feed, assign the required capability. */
		if ( 'edit_' . $this->post_type == $cap ) {
			if ( $user_id == $post->post_author ) {
				$caps[] = $post_type->cap->edit_posts;
			} else {
				$caps[] = $post_type->cap->edit_others_posts; }
		} // End if().

		elseif ( 'delete_' . $this->post_type == $cap ) {
			if ( $user_id == $post->post_author ) {
				$caps[] = $post_type->cap->delete_posts;
			} else {
				$caps[] = $post_type->cap->delete_others_posts; }
		} /* If reading a private feed, assign the required capability. */
		elseif ( 'read_' . $this->post_type == $cap ) {

			if ( 'private' != $post->post_status ) {
				$caps[] = 'read'; } elseif ( $user_id == $post->post_author ) {
				$caps[] = 'read';
				} else {
					$caps[] = $post_type->cap->read_private_posts; }
		}

		/* Return the capabilities required by the user. */
		return $caps;
	}


	public function register_feed_item_tag_taxonomy() {
		$labels = array(
			'name'          => __( 'Feed Item Tags', 'pf' ),
			'singular_name' => __( 'Feed Item Tag', 'pf' ),
			'all_items'     => __( 'All Feed Item Tags', 'pf' ),
			'edit_item'     => __( 'Edit Feed Item Tag', 'pf' ),
			'update_item'   => __( 'Update Feed Item Tag', 'pf' ),
			'add_new_item'  => __( 'Add New Feed Item Tag', 'pf' ),
			'new_item_name' => __( 'New Feed Item Tag', 'pf' ),
			'search_items'  => __( 'Search Feed Item Tags', 'pf' ),
		);

		register_taxonomy(
			$this->tag_taxonomy, $this->post_type, apply_filters(
				'pf_register_feed_item_tag_taxonomy_args', array(
					'labels'             => $labels,
					'public'             => true,
					'show_admin_columns' => true,
					'rewrite'            => false,
				)
			)
		);
	}

	public function register_folders_for_items() {
		// add_action( 'plugins_loaded', pressforward('admin.nominated')->post_type );
		// register_taxonomy_for_object_type( pressforward('admin.nominated')->post_type, 'post_type_name');
	}

	public static function get( $args = array() ) {
		$wp_args = array(
			'post_type'        => pf_feed_item_post_type(),
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

		$posts = $this->items->get_posts( $wp_args );

		foreach ( $query_filters as $hook => $filters ) {
			foreach ( $filters as $f ) {
				remove_filter( $hook, array( $this, $f ) );
			}
		}

		// Fetch some handy pf-specific data
		if ( ! empty( $posts ) ) {
			foreach ( $posts as &$post ) {
				$post->word_count = $this->metas->get_post_pf_meta( $post->ID, 'pf_feed_item_word_count', true );
				$post->source     = $this->metas->get_post_pf_meta( $post->ID, 'source_title', true );
				$post->tags       = wp_get_post_terms( $post->ID, pf_feed_item_tag_taxonomy() );
			}
		}

		return $posts;
	}

	public static function get_by_item_id( $item_id ) {
		$args = array(
			'meta_key'   => pressforward( 'controller.metas' )->get_key( 'item_id' ),
			'meta_value' => $item_id,
		);
		$post = $this->get( $args );
		if ( empty( $post ) ) {
			return false;
		} else {
			return $post[0];
		}
	}

	public function create( $args = array() ) {
		$r = wp_parse_args(
			$args, array(
				'item_title'   => '',
				'item_link'    => '',
				'item_content' => '',
				'source_title' => '',
				'item_wp_date' => '',
				'post_parent'  => '',
				'item_tags'    => array(),
				'post_status'  => 'publish',
			)
		);

		// Sanitization
		// Conversion should be done upstream
		if ( ! is_numeric( $r['item_wp_date'] ) ) {
			$r['item_wp_date'] = strtotime( $r['item_wp_date'] );
			if ( ! $r['item_wp_date'] ) {
				return new \WP_Error( 'Date should be in UNIX format' );
			}
		}

		$wp_args = array(
			'post_type'    => pf_feed_item_post_type(),
			'post_status'  => $r['post_status'],
			'post_title'   => $r['item_title'],
			'post_content' => wp_specialchars_decode( $r['item_content'], ENT_COMPAT ), // todo
			'guid'         => $r['item_link'],
			'post_date'    => date( 'Y-m-d H:i:s', $r['item_wp_date'] ),
			'tax_input'    => array(
				pf_feed_item_tag_taxonomy() => $r['item_tags'],
			),
			'post_parent'  => $r['post_parent'],
		);
		// pf_log($r);
		$item_id = create_feed_item_id( $r['item_link'], $r['item_title'] );
		$post_id = pressforward( 'controller.items' )->insert_post( $wp_args, false, $item_id );
		pf_log( 'Post created with ID of ' . $post_id );

		if ( is_numeric( $post_id ) ) {
			$this->set_word_count( $post_id, $r['item_content'] );
			$this->set_source( $post_id, $r['source_title'] );
			$this->set_source_link( $post_id, $r['item_link'] );
			$this->set_parent_last_retrieved( $post_id );
			pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'item_id', $item_id );
		}

		return $post_id;
	}

	public function _filter_where_guid( $where ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.guid = %s ", $this->filter_data['guid'] );
		return $where;
	}

	// STATIC UTILITY METHODS
	/**
	 * Set word count for an item
	 *
	 * @since 2.0.0
	 */
	public function set_word_count( $post_id, $content = false ) {
		if ( false === $content ) {
			$post    = get_post( $post_id );
			$content = $post->post_content;
		}

		$content_array = explode( ' ', strip_tags( $content ) );
		$word_count    = count( $content_array );

		return pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_feed_item_word_count', $word_count );
	}


	/**
	 * Set source title.
	 *
	 * @since 2.0.0
	 */
	public function set_source( $post_id, $source ) {
		return pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'source_title', $source );
	}

	/**
	 * Set source URL
	 *
	 * This function is meant to find and set the true source URL on an item,
	 * it seeks to fully resolve URLs from known aggregation services.
	 *
	 * @since 3.4.5
	 */
	public function set_source_link( $post_id, $item_url ) {
		$url       = pressforward( 'controller.http_tools' )->resolve_a_url( $item_url );
		$url_array = parse_url( $url );
		if ( empty( $url_array['host'] ) ) {
			return;
		}
		$source_url = 'http://' . $url_array['host'];
		return pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_source_link', $source_url );
	}


	/**
	 * Retrieve the item source's link.
	 *
	 * Retrieve the link for the item's source. Attempt to fully
	 * resolve the URL for known aggregation services.
	 *
	 * @since 3.4.5
	 */
	public function get_source_link( $post_id ) {
		$url = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'pf_source_link' );
		if ( empty( $url ) ) {
			$url = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_link' );
			// pf_log($url);
		}
		$source_url = pressforward( 'controller.http_tools' )->resolve_a_url( $url );
		pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_source_link', $source_url );
		return $source_url;
	}

	/**
	 * Set the last_retrieved value for the parent feed.
	 *
	 * @since 3.4.0
	 *
	 * @param int $feed_item_id ID of the feed item.
	 * @return bool
	 */
	public function set_parent_last_retrieved( $feed_item_id ) {
		$feed_item = get_post( $feed_item_id );

		if ( ! is_a( $feed_item, 'WP_Post' ) || empty( $feed_item->post_parent ) ) {
			return false;
		}

		$feed_id = intval( $feed_item->post_parent );

		if ( ! $feed_id ) {
			return false;
		}

		return pressforward( 'controller.metas' )->update_pf_meta( $feed_id, 'pf_feed_last_retrieved', date( 'Y-m-d H:i:s' ) );
	}

	// via http://wordpress.stackexchange.com/questions/109793/delete-associated-media-upon-page-deletion
	public function disassemble_feed_item_media( $post_id ) {

		$attachments = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'posts_per_page'         => -1,
				'post_status'            => 'any',
				'post_parent'            => $post_id,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $attachments ) || empty( $attachments->posts ) ) {
			return '';
		}

		foreach ( $attachments->posts as $key => $ID ) {
			if ( false === wp_delete_attachment( $ID ) ) {
				pf_log( 'Failed to delete attachment for ' . $post_id );
			}
		}
	}


	// The function we add to the action to clean our database.
	public function disassemble_feed_items() {
		pf_log( 'Disassemble Feed Items Activated' );
		$retain = get_option( 'pf_retain_time', 2 );
		// delete rss feed items with a date past a certain point.
		// add_filter( 'posts_where', array( $this, 'filter_where_older') );
		$queryForDel = new \WP_Query(
			array(
				'post_type'              => $this->post_type,
				'posts_per_page'         => '150',
				'fields'                 => 'ids',
				'update_post_term_cache' => false,
				'date_query'             => array(
					'before' => $retain . ' months ago',
				),
			)
		);
		pf_log( 'Cleaning up feed items.' );
		pf_log( $queryForDel );

		// remove_filter( 'posts_where', array( $this, 'filter_where_older') );
		// pf_log( $queryForDel );
		// The Loop
		if ( empty( $queryForDel ) || empty( $queryForDel->posts ) ) {
			return '';
		}
		foreach ( $queryForDel->posts as $key => $post_id ) {
			// All the posts in this loop are older than 60 days from 'now'.
			// Delete them all.
			pf_log( 'Cleaning up ' . $post_id );
			pf_delete_item_tree( $post_id );
		}

		return true;

	}
	// Method to manually delete rssarchival entries on user action.
	public function reset_feed() {
		global $wpdb, $post;

		$count     = wp_count_posts( pf_feed_item_post_type() );
		$pub_count = $count->publish;
		$pages     = $pub_count / 100;
		// var_dump($pages);
		if ( ( $pages < 1 ) && ( $pages > 0 ) ) {
			$pages = 1;
		} else {
			$pages = ceil( $pages );
		}
		while ( $pages > 0 ) {
			$args         = array(
				'post_type'              => $this->post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'paged'                  => $pages,
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
			);
			$archiveQuery = new \WP_Query( $args );
			// var_dump($archiveQuery);
			if ( ! empty( $archiveQuery ) ) {
				foreach ( $archiveQuery->posts as $key => $post_id ) {
					// Switch the delete on to wipe rss archive posts from the database for testing.
					pf_delete_item_tree( $post_id );
				}
			}

			$pages--;
		}

	}

	public function get_the_feed_object() {
		pf_log( 'Invoked: PF_Feed_Item::get_the_feed_object()' );
		// $PF_Feed_Retrieve = new PF_Feed_Retrieve();
		// This pulls the RSS feed into a set of predetermined objects.
		// The rss_object function takes care of all the feed pulling and item arraying so we can just do stuff with the feed output.
		$theFeed = pressforward( 'utility.retrieval' )->step_through_feedlist();
		if ( ( ! $theFeed ) || is_wp_error( $theFeed ) ) {
			pf_log( 'The feed is false, exit process. [THIS SHOULD NOT OCCUR except at the conclusion of feeds retrieval.]' );
			pf_iterate_cycle_state( 'retrieval_cycles_ended', true );
			// Wipe the checking option for use next time.
			update_option( PF_SLUG . '_feeds_meta_state', array() );
			$chunk_state = update_option( PF_SLUG . '_ready_to_chunk', 1 );
			exit;
		}

		return $theFeed;
	}

	public function assemble_feed_for_pull( $feedObj = 0 ) {
		pf_log( 'Invoked: PF_Feed_Item::assemble_feed_for_pull()' );

		ignore_user_abort( true );
		set_time_limit( 0 );
		// Chunking control, the goal here is to ensure that no feed assembly occurs while the feed assembly is already occuring.
		// Option: If true (1), the system is ready to assemble a chunk. If false (0), the system is already assembling a chunk.
		$ready_for_chunk_assembly = get_option( PF_SLUG . '_ready_to_chunk', 1 );

		if ( $ready_for_chunk_assembly === 0 ) {
			pf_log( 'Chunk already in progress.' );
			return;
		} else {
			pf_log( 'Beginning next import chunk.' );
			pf_log( 'The chunk state is set?' );
			$chunk_state = update_option( PF_SLUG . '_ready_to_chunk', 0 );
			pf_log( $chunk_state );
		}

		if ( $feedObj == 0 ) {
			$theFeed = $this->get_the_feed_object();
			$feedObj = $theFeed;
		}

		// We need to init $sourceRepeat so it can be if 0 if nothing is happening.
		$sourceRepeat = 0;
		// We'll need this for our fancy query.
		global $wpdb;
		// Since rss_object places all the feed items into an array of arrays whose structure is standardized throughout,
		// We can do stuff with it, using the same structure of items as we do everywhere else.
		pf_log( 'Now beginning check and processing for entering items into the database.' );
		$parent = $feedObj['parent_feed_id'];
		unset( $feedObj['parent_feed_id'] );
		foreach ( $feedObj as $item ) {
			$thepostscheck       = 0;
			$thePostsDoubleCheck = 0;
			$item_id             = $item['item_id'];
			$sourceRepeat        = 0;
			// $queryForCheck = new WP_Query( array( 'post_type' => 'rssarchival', 'meta_key' => 'item_id', 'meta_value' => $item_id ) );
			 // Originally this query tried to get every archive post earlier than 'now' to check.
			 // But it occured to me that, since I'm doing a custom query anyway, I could just query for items with the ID I want.
			 // Less query results, less time.
			 // Perhaps I should do this outside of the foreach? One query and search it for each item_id and then return those not in?
			 $item_id_key = pressforward( 'controller.metas' )->get_key( 'item_id' );
			$querystr     = $wpdb->prepare(
				"
				SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
				FROM {$wpdb->posts}, {$wpdb->postmeta}
				WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				AND {$wpdb->postmeta}.meta_key = '{$item_id_key}'
				AND {$wpdb->postmeta}.meta_value = %s
				AND {$wpdb->posts}.post_type = %s
				ORDER BY {$wpdb->posts}.post_date DESC
			 ", $item_id, pf_feed_item_post_type()
			);
			 // AND $wpdb->posts.post_date < NOW() <- perhaps by removing we can better prevent simultaneous duplications?
			 // Since I've altered the query, I could change this to just see if there are any items in the query results
			 // and check based on that. But I haven't yet.
			$checkposts = $wpdb->get_results( $querystr, OBJECT );
			// print_r($checkposts);
			if ( $checkposts ) :
				global $post;
				foreach ( $checkposts as $post ) :
					setup_postdata( $post );
					// print_r(get_the_ID());
					// print_r('< the ID');
					if ( ( pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'item_id', $item_id, true ) ) === $item_id ) {
						$thepostscheck++;
						$post_id_to_pass = $post->ID;
						pf_log( 'We already have post ' . $post_id_to_pass . ' for ' );
						pf_log( $item );
						do_action(
							'already_a_feed_item', array(
								'item'    => $item,
								'post_id' => $post_id_to_pass,
							)
						);
					}

					endforeach;
				endif;
				wp_reset_query();
			if ( $thepostscheck === 0 ) {
				$queryMoreStr  = $wpdb->prepare(
					"
						SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
						FROM {$wpdb->posts}, {$wpdb->postmeta}
						WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
						AND {$wpdb->postmeta}.meta_key = 'item_link'
						AND {$wpdb->postmeta}.meta_value = %s
						AND {$wpdb->posts}.post_type = %s
						ORDER BY {$wpdb->posts}.post_date DESC
					 ", $item['item_link'], pf_feed_item_post_type()
				);
				$checkpoststwo = $wpdb->get_results( $queryMoreStr, OBJECT );
				if ( $checkpoststwo ) :
					pf_log( 'Check for posts with the same link.' );
					foreach ( $checkpoststwo as $post ) :
						setup_postdata( $post );

							// Post comparative values.
							$theTitle = $post->post_title;
							$postID   = $post->ID;

							$postDate     = strtotime( $post->post_date );
							$postItemLink = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'item_link', true );
							// Item comparative values.
							$itemDate = strtotime( $item['item_date'] );
							// pf_log( 'Item time '. $itemDate . ' post date is '. $postDate );
							$itemTitle = $item['item_title'];
							$itemLink  = $item['item_link'];

							// First check if it more recent than the currently stored item.
						if ( ( ( $theTitle == $itemTitle ) || ( $postItemLink == $itemLink ) ) ) {
							$thePostsDoubleCheck++;
							pf_log( 'We already have the post ' . $theTitle . ' with the link ' . $itemLink );
							$sourceRepeat = pressforward( 'controller.metas' )->get_post_pf_meta( $postID, 'source_repeat', true );
							if ( ( $itemDate > $postDate ) ) {
								// If it is more recent, than this is the new dominant post.
								// @TODO: Allow the feed process to check for updated content.
								// The idea is that if the retrieved item's date is newer than
								// the internal date it will update. But the retrieved item date
								// is not always available, and is not currently being processed
								// correctly when it is, so we're not doing this for now.
								$sourceRepeat++;
								$thepostscheck = 1;
							} elseif ( ( $itemDate <= $postDate ) ) {
								// if it is less recent, then we need to increment the source count.
								$sourceRepeat++;
								if ( $thePostsDoubleCheck > $sourceRepeat ) {
									pressforward( 'controller.metas' )->update_pf_meta( $postID, 'source_repeat', $sourceRepeat );
								}
								// The idea is that if the retrieved item's date is newer than
								// the internal date it will update. But the retrieved item date
								// is not always available, and is not currently being processed
								// correctly when it is, so we're not doing this for now.
								// $thepostscheck++;
								$thepostscheck = 1;
							} else {
								// The idea is that if the retrieved item's date is newer than
								// the internal date it will update. But the retrieved item date
								// is not always available, and is not currently being processed
								// correctly when it is, so we're not doing this for now.
								$thepostscheck = 1;
							}
						} else {
							// If it isn't duplicated at all, then we need to give it a source repeat count of 0
							$sourceRepeat = 0;
						}// End if().

						endforeach;
					endif;
			}// End if().
				wp_reset_query();
			// Why an increment here instead of a bool?
			// If I start getting errors, I can use this to check how many times an item is in the database.
			// Potentially I could even use this to clean the database from duplicates that might occur if
			// someone were to hit the refresh button at the same time as another person.
			// $fo = fopen(PF_ROOT . "/modules/rss-import/rss-import.txt", 'a') or print_r('Can\'t open log file.');
			// if ($fo != false){
			// fwrite($fo, "\nSending " . $item['item_title'] . " to post table.");
			// fclose($fo);
			// }
			if ( $thepostscheck === 0 ) {
				$item_title    = $item['item_title'];
				$item_content  = $item['item_content'];
				$item_feat_img = $item['item_feat_img'];
				$source_title  = $item['source_title'];
				$item_date     = $item['item_date'];
				$item_author   = $item['item_author'];
				$item_link     = $item['item_link'];
				$item_wp_date  = $item['item_wp_date'];
				$item_tags     = $item['item_tags'];
				if ( ! isset( $item['parent_feed_id'] ) || ! $item['parent_feed_id'] ) {
					$item['parent_feed_id'] = $parent;
				}
				$feed_obj_id   = $item['parent_feed_id'];
				$source_repeat = $sourceRepeat;

				// Trying to prevent bad or malformed HTML from entering the database.
				$item_title   = strip_tags( $item_title );
				$item_content = strip_tags( $item_content, '<p> <strong> <bold> <i> <em> <emphasis> <del> <h1> <h2> <h3> <h4> <h5> <a> <img>' );
				// Need to get rid of some weird characters that prevent inserting posts into the database.
				// From: http://www.alexpoole.name/web-development/282/remove-smart-quotes-bullets-dashes-and-other-junky-characters-from-a-string-with-php
				// And: http://www.enghiong.com/wp_insert_post-could-not-insert-post-into-the-database.html
				// $item_content = self::extra_special_sanatize($item_content);
				// $item_title = self::extra_special_sanatize($item_title);
				// $item_content = wpautop($item_content);
				// $postcontent = sanitize_post($item_content);
				// If we use the @ to prevent showing errors, everything seems to work. But it is still dedicating crap to the database...
				// Perhaps sanitize_post isn't the cause? What is then?
				// Do we want or need the post_status to be published?
				$data = array(
					'post_status'    => 'publish',
					'post_type'      => pf_feed_item_post_type(),
					// 'post_date' => $_SESSION['cal_startdate'],
						'item_title' => $item_title,
					'post_parent'    => $feed_obj_id,
					'item_content'   => $item_content,
					'item_link'      => $item_link,
					'source_title'   => $source_title,
					'item_wp_date'   => $item_wp_date,
					'item_tags'      => $item_tags,

				);

				// RIGHT HERE is where the content is getting assigned a bunch of screwed up tags.
				// The content is coming in from the rss_object assembler a-ok. But something here saves them to the database screwy.
				// It looks like sanitize post is screwing them up terribly. But what to do about it without removing the security measures which we need to apply?
				$worked = 1;
				do_action( 'about_to_insert_pf_feed_items', $item );
				// The post gets created here, the $newNomID variable contains the new post's ID.
				$newNomID           = $this->create( $data );
				$post_inserted_bool = $this->post_inserted( $newNomID, $data );

				if ( ! $post_inserted_bool ) {
					// It's the end of the world! Let's throw everything at this.
					pf_log( 'Post will not go into the database. We will try again.' );
					$item_content         = htmlentities( strip_tags( $item_content ), ENT_QUOTES, 'UTF-8' );
					$item_content         = wp_kses( stripslashes( $item_content ), array( 'p', 'a', 'b', 'em', 'strong' ) );
					$item_content         = $this->extra_special_sanatize( $item_content, true );
					$item_content         = wpautop( $item_content );
					$item_title           = $this->extra_special_sanatize( $item_title, true );
					$data['item_content'] = $item_content;
					$newNomID             = $this->create( $data );
					$post_inserted_bool   = $this->post_inserted( $newNomID, $data );
				}
				pf_log( 'End of wp_insert_post process.' );
				// $posttest = get_post($newNomID);
				// print_r($posttest->post_content);
				// Somewhere in the process links with complex queries at the end (joined by ampersands) are getting encoded.
				// I don't want that, so I turn it back here.
				// For some reason this is only happening to the ampersands, so that's the only thing I'm changing.
				$item_link = str_replace( '&amp;', '&', $item_link );

				// If it doesn't have a featured image assigned already, I use the set_ext_as_featured function to try and find one.
				// It also, if it finds one, sets it as the featured image for that post.
				if ( ! empty( $_POST['item_feat_img'] ) ) {
					// Turned off set_ext_as_featured here, as that should only occur when items are nominated.
					// Before nominations, the featured image should remain a meta field with an external link.
					if ( false === ( $itemFeatImg = get_transient( 'feed_img_' . $itemUID ) ) ) {
						set_time_limit( 0 );
						// if it forces the issue when we try and get the image, there's nothing we can do.
						$itemLink = str_replace( '&amp;', '&', $itemLink );
						if ( pressforward( 'library.opengraph' )->fetch( $itemLink ) ) {
							// If there is no featured image passed, let's try and grab the opengraph image.
							$node        = pressforward( 'library.opengraph' )->fetch( $itemLink );
							$itemFeatImg = $node->image;

						}

						if ( $itemFeatImg == '' ) {
							// Thinking of starting a method here to pull the first image from the body of a post.
							// http://stackoverflow.com/questions/138313/how-to-extract-img-src-title-and-alt-from-html-using-php
							// http://stackoverflow.com/questions/1513418/get-all-images-url-from-string
							// http://stackoverflow.com/questions/7479835/getting-the-first-image-in-string-with-php
							// preg_match_all('/<img[^>]+>/i',$itemContent, $imgResult);
							// $imgScript = $imgResult[0][0];
						}
						// Most RSS feed readers don't store the image locally. Should we?
						set_transient( 'feed_img_' . $itemUID, $itemFeatImg, 60 * 60 * 24 );
					}
				}

				// adding the meta info about the feed item to the post's meta.
				$pf_meta_args = array(
					pressforward( 'controller.metas' )->meta_for_entry( 'item_id', $item_id ),
					pressforward( 'controller.metas' )->meta_for_entry( 'source_title', $source_title ),
					pressforward( 'controller.metas' )->meta_for_entry( 'item_date', $item_date ),
					pressforward( 'controller.metas' )->meta_for_entry( 'item_author', $item_author ),
					pressforward( 'controller.metas' )->meta_for_entry( 'item_link', $item_link ),
					pressforward( 'controller.metas' )->meta_for_entry( 'item_feat_img', $item_feat_img ),
					// The item_wp_date allows us to sort the items with a query.
					pressforward( 'controller.metas' )->meta_for_entry( 'item_wp_date', $item_wp_date ),
					// We can't just sort by the time the item came into the system (for when mult items come into the system at once)
					// So we need to create a machine sortable date for use in the later query.
					pressforward( 'controller.metas' )->meta_for_entry( 'sortable_item_date', strtotime( $item_date ) ),
					pressforward( 'controller.metas' )->meta_for_entry( 'item_tags', $item_tags ),
					pressforward( 'controller.metas' )->meta_for_entry( 'source_repeat', $source_repeat ),
					pressforward( 'controller.metas' )->meta_for_entry( 'revertible_feed_text', $item_content ),

				);
				pressforward( 'controller.metas' )->establish_post( $newNomID, $pf_meta_args );
				$parent_id = $feed_obj_id;
				do_action( 'pf_post_established', $newNomID, $item_id, $parent_id );
			} else {
				pf_log( 'The post was a repeat, so we are not adding it.' );
			}// End if().
		}// End foreach().
		update_option( PF_SLUG . '_ready_to_chunk', 1 );
		// $Feed_Retrieve = new PF_Feed_Retrieve();
		pressforward( 'utility.retrieval' )->advance_feeds();
		// die('Refreshing...');
	}

	public static function post_inserted( $postAttempt, $data ) {
			$worked     = 1;
			$workedBool = true;
		if ( $postAttempt === 0 ) {
			pf_log( 'The following post did not go into the database correctly.' );
			pf_log( $data );
			$worked = 0;
		} elseif ( is_wp_error( $postAttempt ) ) {
			pf_log( 'Attempting to add ' . $data['item_title'] . ' to the database caused this error:' );
			pf_log( $postAttempt );
			pf_log( 'The following post caused the above error.' );
			pf_log( $data );
			$worked = 0;
		} else {
			pf_log( 'Create post in the database with the title ' . $data['item_title'] . ' and id of ' );
			pf_log( $postAttempt );
		}
		if ( $worked === 0 ) {
			$workedBool = false; }
		return $workedBool;
	}

	// Alternate function title - 'stop_pasting_junk_from_word'
	public static function extra_special_sanatize( $string, $severe = false ) {

		$search  = array(
			chr( 145 ),
			chr( 146 ),
			chr( 147 ),
			chr( 148 ),
			chr( 151 ),
			chr( 150 ),
			chr( 133 ),
			chr( 149 ),
			chr( 189 ),
		);
		$replace = array(
			"'",
			"'",
			'"',
			'"',
			'--',
			'-',
			'...',
			'&bull;',
			'1/2',
		);
		$string  = str_replace( $search, $replace, $string );
		pf_log( 'String run through specified str_replace.' );
		$string = utf8_encode( $string );
		pf_log( 'String run through utf8_encode' );
		pf_log( 'String returned.' );
		if ( $severe ) {
			// ============
			// Remove MS Word Special Characters
			// From: https://gist.github.com/gcoop/701814
			// ============
				$search  = array( '&acirc;��', '&acirc;��', '&acirc;��', '&acirc;��', '&Acirc;&pound;', '&Acirc;&not;', '&acirc;�&cent;', '&Acirc;&nbsp;', '&Acirc;', '&amp;nbsp;', '&#8230;' );
				$replace = array( '-', '&ldquo;', '&lsquo;', '&rsquo;', '&pound;', '&not;', '&#8482;', '', '', '', '...' );

				$string = str_replace( $search, $replace, $string );
				$string = str_replace( '&acirc;�', '&rdquo;', $string );

				$search  = array( '&#39;', "\xc3\xa2\xc2\x80\xc2\x99", "\xc3\xa2\xc2\x80\xc2\x93", "\xc3\xa2\xc2\x80\xc2\x9d", "\xc3\xa2\x3f\x3f", '&#8220;', '&#8221;', '#8217;', '&not;', '&#8482;' );
				$replace = array( "'", "'", ' - ', '"', "'", '"', '"', "'", '-', '(TM)' );

				$string = str_replace( $search, $replace, $string );

			$quotes = array(
				"\xC2\xAB"     => '"',
				"\xC2\xBB"     => '"',
				"\xE2\x80\x98" => "'",
				"\xE2\x80\x99" => "'",
				"\xE2\x80\x9A" => "'",
				"\xE2\x80\x9B" => "'",
				"\xE2\x80\x9C" => '"',
				"\xE2\x80\x9D" => '"',
				"\xE2\x80\x9E" => '"',
				"\xE2\x80\x9F" => '"',
				"\xE2\x80\xB9" => "'",
				"\xE2\x80\xBA" => "'",
				"\xe2\x80\x93" => '-',
				"\xc2\xb0"     => '�',
				"\xc2\xba"     => '�',
				"\xc3\xb1"     => '&#241;',
				"\x96"         => '&#241;',
				"\xe2\x81\x83" => '&bull;',
				"\xd5"         => "'",
			);

			$string = strtr( $string, $quotes );
			$string = utf8_encode( $string );
			// From: http://stackoverflow.com/questions/657643/how-to-remove-html-special-chars
			// $string = preg_replace("/&#?[a-z0-9]+;/i","", $string);
		}// End if().

		return $string;
	}


	/**
	 * Get the content of a URL, using various fallbacks
	 */
	public static function get_content_through_aggregator( $url ) {

		set_time_limit( 0 );
		// $this->set_error_handler("customError");
		$descrip = '';
		// $url = http_build_url($urlParts, HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT);
		// print_r($url);
		// First run it through Readability.
		$descrip = pressforward( 'controller.readability' )->readability_object( $url );
		// print_r($url);
		// If that doesn't work...
		if ( ! $descrip ) {
			$url = str_replace( '&amp;', '&', $url );
			// Try and get the OpenGraph description.
			if ( pressforward( 'library.opengraph' )->fetch( $url ) ) {
				$node    = pressforward( 'library.opengraph' )->fetch( $url );
				$descrip = $node->description;
			} // End if().
			elseif ( '' != ( $contentHtml = @get_meta_tags( $url ) ) ) {
				// Try and get the HEAD > META DESCRIPTION tag.
				$descrip = $contentHtml['description'];
				print_r( $url . ' has no meta OpenGraph description we can find.' );

			} else {
				// Ugh... we can't get anything huh?
				print_r( $url . ' has no description we can find.' );
				// We'll want to return a false to loop with.
				return false;
			}
		}
		return $descrip;

	}

	public function resolve_image_type( $img_url ) {
		$img_url = strtok( $img_url , '?' );
		$type = wp_check_filetype( $img_url );
		return $type['ext'];
	}

	public function assert_url_scheme( $url ) {
		$url_parts   = parse_url( $url );
		$slash_check = substr( $url, 0, 2 );
		if ( empty( $url_parts['scheme'] ) && ( '//' == $slash_check ) ) {
			$url = 'http:' . $url;
		} elseif ( empty( $url_parts['scheme'] ) && ( '//' != $slash_check ) ) {
			$url = 'http://' . $url;
		}

		return $url;

	}

	public static function get_ext_og_img( $link ) {
		$node = pressforward( 'library.opengraph' )->fetch( $link );
		if ( ! empty( $node ) ) {
			$itemFeatImg = $node->image;
		} else {
			return false;
		}

		return $itemFeatImg;
	}

	public function assure_image( $filepath ) {
		$img_info = getimagesize( $filepath );
		// Your 1x1 tracking or dummy images have no domain here!
		if ( ( 2 > $img_info[0] ) || ( 2 > $img_info[1] ) ) {
			// I assure you this is not an image
			return false;
		} else {
			// This is an image I assure you.
			return true;
		}
	}

	public static function set_ext_as_featured( $postID, $ogImage ) {

		if ( 5 < ( strlen( $ogImage ) ) ) {

				// Remove Queries from the URL
				// $ogImage = preg_replace('/\?.*/', '', $ogImage);
				$ogImage = pressforward( 'schema.feed_item' )->assert_url_scheme( $ogImage );
			if ( ! filter_var( $ogImage, FILTER_VALIDATE_URL ) ) {
				pf_log( 'It looked like we received a file, but PHP could not understand it as a URL: ' . $ogImage );
				return '';
			}
				$imgParts         = pathinfo( $ogImage );
				$imgExt           = $imgParts['extension'];
				$imgTitle         = $imgParts['filename'];
				$resolved_img_ext = pressforward( 'schema.feed_item' )->resolve_image_type( $ogImage );
			if ( ( $resolved_img_ext != ( 'jpg' || 'png' || 'jrpg' || 'bmp' || 'gif' || 'jpeg' ) ) || ( $imgExt != ( 'jpg' || 'png' || 'jrpg' || 'bmp' || 'gif' || 'jpeg' ) ) ) {
				// var_dump($resolved_img_ext); die();
				return;
			}

				$imgTitle = sanitize_file_name( $imgTitle );
				// Let's not get crazy here.
				$imgTitle = substr( $imgTitle, 0, 100 );
			if ( strpos( $imgTitle, '.' ) !== false ) {
				$imgTitle = 'retrieved-featured-image';
			} else {
				$imgTitle = $imgTitle;
			}

				// '/' . get_option(upload_path, 'wp-content/uploads') . '/' . date("o")
				$uploadDir  = wp_upload_dir();
				$ogCacheImg = $uploadDir['path'] . '/' . $postID . '-' . $imgTitle . '.' . $resolved_img_ext;
				// var_dump($ogCacheImg); die();
			if ( ! file_exists( $ogCacheImg ) ) {

				$result = copy( $ogImage, $ogCacheImg );

				if ( ! $result ) {
					return;
				}
			}

			if ( false == pressforward( 'schema.feed_item' )->assure_image( $ogCacheImg ) ) {
				return;
			}

			// Methods within sourced from http://codex.wordpress.org/Function_Reference/wp_insert_attachment
			// and http://wordpress.stackexchange.com/questions/26138/set-post-thumbnail-with-php
			// Get the type of the image file. .jpg, .gif, or whatever
			$filetype = wp_check_filetype( $ogCacheImg );

			// Set the identifying variables for the about to be featured image.
			$imgData = array(
				'guid'           => $ogCacheImg,
				// tell WordPress what the filetype is.
				'post_mime_type' => $filetype['type'],
				// set the image title to the title of the site you are pulling from
				'post_title'     => get_the_title( $postID ),
				// WordPress tells us we must set this and set it to empty. Why? Dunno.
				'post_content'   => $imgTitle,
				// Now we set the status of the image. It will inheret that of the post.
				// If the post is published, then the image will be to.
				'post_status'    => 'inherit',
			);
			// WordPress needs an absolute path to the image, as opposed to the relative path we used before.
			// I'm hoping that by using the upload_dir function (above) I can make this function work with multisite.
			// $pathedImg = $uploadDir['url'] . $img;
			// Now we insert the image as a WordPress attachement, and associate it with the current post.
			$thumbid = wp_insert_attachment( $imgData, $ogCacheImg, $postID );

			// To set a thumbnail, you need metadata associated with an image.
			// To get that we need to call the image.php file
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$metadata = wp_generate_attachment_metadata( $thumbid, $ogCacheImg );
			// Now we attach the meta data to the image.
			wp_update_attachment_metadata( $thumbid, $metadata );

			// Now that we have a correctly meta-ed and attached image we can finally turn it into a post thumbnail.
			pressforward( 'controller.metas' )->update_pf_meta( $postID, '_thumbnail_id', $thumbid );

		}// End if().
	}



	/**
	 * Filter 'posts_where' to return only posts older than sixty days.
	 * Can be modified with user setting for retention.
	 */
	public static function filter_where_older( $where = '' ) {
		$retain       = get_option( 'pf_retain_time', 2 );
		$retainMonths = $retain * 30;
		$str          = '-' . $retainMonths . ' days';
		// posts before the last 60 days
		$where .= " AND post_date < '" . date( 'Y-m-d', strtotime( $str ) ) . "'";
		return $where;
	}

	/**
	 * Set a feed item's tags
	 *
	 * @param int   $post_id
	 * @param array $tags
	 * @param bool  $append True if you want to append rather than replace
	 */
	public static function set_tags( $post_id, $tags, $append = false ) {
		return wp_set_object_terms( $post_id, $tags, $this->tag_taxonomy, $append );
	}

	/**
	 * Get the source data object, in a standardized format
	 *
	 * For the moment, all this data comes from the RSS_Import module. In
	 * the future, other modules can hook in to provide their own data
	 * sources.
	 */
	public function source_data_object() {
		// Loop through each module to get its source data
		$source_data_object = array();
		foreach ( pressforward( 'modules' )->modules as $module ) {
			$source_data_object = array_merge( $source_data_object, $module->get_data_object() );
		}
		return $source_data_object;
	}

	/**
	 * Converts a raw tag array to a list appropriate for a tax_query
	 *
	 * Will create the necessary tags if they're not found
	 */
	public static function convert_raw_tags( $tags ) {
		$retval = array(
			$this->tag_taxonomy => $tags,
		);
		return $retval;
	}

	public static function get_term_slug_from_tag( $tag ) {
		// return 'pf_feed_item_' .
	}

	public function dead_post_status() {
		register_post_status(
			'removed_feed_item', array(
				'label'                  => _x( 'Removed Feed Item', 'pf' ),
				'public'                 => false,
				'exclude_from_search'    => true,
				'show_in_admin_all_list' => false,
			)
		);
	}


	public function register_feed_item_removed_status() {

		$args = array(
			'label'                     => _x( 'Removed Feed Item', 'pf' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
			'label_count'               => _n_noop( 'Removed <span class="count">(%s)</span>', 'Removed <span class="count">(%s)</span>' ),
		);

		register_post_status( 'removed_feed_item', $args );

	}

	public function oembed_capables() {
		return array(
			'youtube.com',
		);
	}

}
