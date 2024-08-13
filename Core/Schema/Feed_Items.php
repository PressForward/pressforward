<?php
/**
 * Feed Items data control.
 *
 * @package PressForward
 */

namespace PressForward\Core\Schema;

use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Controllers\Metas;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

/**
 * Database class for manipulating feed items
 */
class Feed_Items implements HasActions, HasFilters {
	/**
	 * Filter data.
	 *
	 * @access protected
	 * @var array
	 */
	protected $filter_data = array();

	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Tag taxonomy.
	 *
	 * @access public
	 * @var string
	 */
	public $tag_taxonomy;

	/**
	 * Items object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PF_to_WP_Posts
	 */
	public $items;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\PF_to_WP_Posts $items Items object.
	 * @param \PressForward\Controllers\Metas          $metas Metas object.
	 */
	public function __construct( PF_to_WP_Posts $items, Metas $metas ) {
		$this->post_type    = 'pf_feed_item';
		$this->tag_taxonomy = 'pf_feed_item_tag';
		$this->items        = $items;
		$this->metas        = $metas;
	}

	/**
	 * {@inheritdoc}
	 */
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

	/**
	 * {@inheritdoc}
	 */
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

	/**
	 * Registers the feed item post type.
	 */
	public function register_feed_item_post_type() {
		$labels = array(
			'name'               => __( 'Feed Items', 'pressforward' ),
			'singular_name'      => __( 'Feed Item', 'pressforward' ),
			'add_new'            => _x( 'Add New', 'pf', 'add new feed item' ),
			'all_items'          => __( 'All Feed Items', 'pressforward' ),
			'add_new_item'       => __( 'Add New Feed Item', 'pressforward' ),
			'edit_item'          => __( 'Edit Feed Item', 'pressforward' ),
			'new_item'           => __( 'New Feed Item', 'pressforward' ),
			'view_item'          => __( 'View Feed Item', 'pressforward' ),
			'search_items'       => __( 'Search Feed Items', 'pressforward' ),
			'not_found'          => __( 'No feed items found', 'pressforward' ),
			'not_found_in_trash' => __( 'No feed items found in trash', 'pressforward' ),
		);

		$modules = pressforward( 'modules' )->modules;
		if ( isset( $modules['rss-out'] ) && ( 'yes' === get_option( PF_SLUG . '_rss-out_enable' ) ) ) {
			$rest_enabled = true;
		} else {
			$rest_enabled = false;
		}

		register_post_type(
			$this->post_type,
			apply_filters(
				'pf_register_feed_item_post_type_args',
				array(
					'label'                 => $labels['name'],
					'labels'                => $labels,
					'description'           => __( 'Feed items imported by PressForward&#8217;s RSS Importer', 'pressforward' ),
					'public'                => false,
					'show_ui'               => true,
					'show_in_admin_bar'     => false,
					'show_in_rest'          => true, // $rest_enabled,
					'rest_base'             => 'feed_items',
					'rest_namespace'        => 'pf/v1',
					'rest_controller_class' => 'PF_REST_Posts_Controller',
					'capability_type'       => $this->post_type,
					'capabilities'          => $this->map_feed_item_caps(),
				)
			)
		);

		do_action( 'pf_feed_item_post_type_registered' );
	}

	/**
	 * Gets cap list for feed item post type.
	 *
	 * @return array
	 */
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

	/**
	 * Callback for on-the-fly filtering of feed-item capabilities.
	 *
	 * @param array $caps Passed from 'user_has_cap' filter.
	 * @return array
	 */
	public function alter_cap_on_fly( $caps ) {

		foreach ( $this->map_feed_item_caps() as $core_cap => $cap ) {
			if ( ! empty( $caps[ $core_cap ] ) ) { // user has edit capabilities.
				$caps[ $cap ] = true;
			}
		}
		return $caps;
	}

	/**
	 * Meta cap mapping for feed items.
	 *
	 * @param array  $caps    Relevant caps for user.
	 * @param string $cap     Capability to check against $caps array.
	 * @param int    $user_id ID of user.
	 * @param array  $args    Arguments.
	 * @return array
	 */
	public function feeds_item_map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( empty( $args ) ) {
			return $caps;
		}

		$caps_to_map = [
			'edit_' . $this->post_type,
			'delete_' . $this->post_type,
			'read_' . $this->post_type,
		];

		if ( ! in_array( $cap, $caps_to_map, true ) ) {
			return $caps;
		}

		$post      = get_post( $args[0] );
		$post_type = get_post_type_object( $this->post_type );

		// Set an empty array for the caps.
		$caps = array();

		/* If editing a feed, assign the required capability. */
		if ( 'edit_' . $this->post_type === $cap ) {
			if ( $post && $user_id === (int) $post->post_author ) {
				$caps[] = $post_type->cap->edit_posts;
			} else {
				$caps[] = $post_type->cap->edit_others_posts;
			}
		} elseif ( 'delete_' . $this->post_type === $cap ) {
			if ( $post && $user_id === (int) $post->post_author ) {
				$caps[] = $post_type->cap->delete_posts;
			} else {
				$caps[] = $post_type->cap->delete_others_posts;
			}
		} elseif ( 'read_' . $this->post_type === $cap ) {
			/* If reading a private feed, assign the required capability. */
			if ( $post && 'private' !== $post->post_status ) {
				$caps[] = 'read';
			} elseif ( $post && $user_id === (int) $post->post_author ) {
				$caps[] = 'read';
			} else {
				$caps[] = $post_type->cap->read_private_posts;
			}
		}

		/* Return the capabilities required by the user. */
		return $caps;
	}

	/**
	 * Registers the taxonomy for feed item tags.
	 */
	public function register_feed_item_tag_taxonomy() {
		$labels = array(
			'name'          => __( 'Feed Item Tags', 'pressforward' ),
			'singular_name' => __( 'Feed Item Tag', 'pressforward' ),
			'all_items'     => __( 'All Feed Item Tags', 'pressforward' ),
			'edit_item'     => __( 'Edit Feed Item Tag', 'pressforward' ),
			'update_item'   => __( 'Update Feed Item Tag', 'pressforward' ),
			'add_new_item'  => __( 'Add New Feed Item Tag', 'pressforward' ),
			'new_item_name' => __( 'New Feed Item Tag', 'pressforward' ),
			'search_items'  => __( 'Search Feed Item Tags', 'pressforward' ),
		);

		register_taxonomy(
			$this->tag_taxonomy,
			$this->post_type,
			apply_filters(
				'pf_register_feed_item_tag_taxonomy_args',
				array(
					'labels'             => $labels,
					'public'             => true,
					'show_admin_columns' => true,
					'rewrite'            => false,
				)
			)
		);
	}

	/**
	 * Not used.
	 */
	public function register_folders_for_items() {}

	/**
	 * Gets a list of feed items.
	 *
	 * @param array $args Query arguments.
	 */
	public function get( $args = array() ) {
		$wp_args = array(
			'post_type'        => pf_feed_item_post_type(),
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);

		$query_filters = array();

		// WP_Query does not accept a 'guid' param, so we filter hackishly.
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

		// Other WP_Query args pass through.
		$wp_args = wp_parse_args( $args, $wp_args );

		$posts = $this->items->get_posts( $wp_args );

		foreach ( $query_filters as $hook => $filters ) {
			foreach ( $filters as $f ) {
				remove_filter( $hook, array( $this, $f ) );
			}
		}

		// Fetch some handy pf-specific data.
		if ( ! empty( $posts ) ) {
			foreach ( $posts as &$post ) {
				$post->word_count = $this->metas->get_post_pf_meta( $post->ID, 'pf_feed_item_word_count', true );
				$post->source     = $this->metas->get_post_pf_meta( $post->ID, 'source_title', true );
				$post->tags       = wp_get_post_terms( $post->ID, pf_feed_item_tag_taxonomy() );
			}
		}

		return $posts;
	}

	/**
	 * Gets a post by a PF item ID.
	 *
	 * @param string $item_id PF item ID.
	 * @return bool|\WP_Post
	 */
	public function get_by_item_id( $item_id ) {
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

	/**
	 * Creates a new feed item.
	 *
	 * @param array $args Creation arguments.
	 * @return int|\WP_Error
	 */
	public function create( $args = array() ) {
		$r = wp_parse_args(
			$args,
			array(
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

		// Sanitization.
		// Conversion should be done upstream.
		if ( ! is_numeric( $r['item_wp_date'] ) ) {
			$r['item_wp_date'] = strtotime( $r['item_wp_date'] );
			if ( ! $r['item_wp_date'] ) {
				return new \WP_Error( __( 'Date should be in UNIX format', 'pressforward' ) );
			}
		}

		$wp_args = array(
			'post_type'    => pf_feed_item_post_type(),
			'post_status'  => $r['post_status'],
			'post_title'   => $r['item_title'],
			'post_content' => wp_specialchars_decode( $r['item_content'], ENT_COMPAT ), // todo.
			'guid'         => $r['item_link'],
			'post_date'    => gmdate( 'Y-m-d H:i:s', $r['item_wp_date'] ),
			'tax_input'    => array(
				pf_feed_item_tag_taxonomy() => $r['item_tags'],
			),
			'post_parent'  => $r['post_parent'],
		);

		$item_id = pressforward_create_feed_item_id( $r['item_link'], $r['item_title'] );
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


	/**
	 * 'posts_where' filter callback for 'guid' query parameter.
	 *
	 * @param string $where SQL string.
	 * @return string
	 */
	public function _filter_where_guid( $where ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.guid = %s ", $this->filter_data['guid'] );
		return $where;
	}

	/**
	 * Set word count for an item.
	 *
	 * @since 2.0.0
	 *
	 * @param int         $post_id ID of the post.
	 * @param string|bool $content Optional. Post content. Falls back on content of post $post_id.
	 */
	public function set_word_count( $post_id, $content = false ) {
		if ( false === $content ) {
			$post    = get_post( $post_id );
			$content = $post->post_content;
		}

		$content_array = explode( ' ', wp_strip_all_tags( $content ) );
		$word_count    = count( $content_array );

		return pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_feed_item_word_count', $word_count );
	}


	/**
	 * Sets source title.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $post_id ID of the post.
	 * @param string $source  Source.
	 */
	public function set_source( $post_id, $source ) {
		return pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'source_title', $source );
	}

	/**
	 * Sets source URL.
	 *
	 * This function is meant to find and set the true source URL on an item,
	 * it seeks to fully resolve URLs from known aggregation services.
	 *
	 * @since 3.4.5
	 *
	 * @param int    $post_id  ID of the post.
	 * @param string $item_url URL of the item.
	 */
	public function set_source_link( $post_id, $item_url ) {
		$url       = pressforward( 'controller.http_tools' )->resolve_a_url( $item_url );
		$url_array = wp_parse_url( $url );
		if ( empty( $url_array['host'] ) ) {
			return;
		}

		$source_url = 'http://' . $url_array['host'];
		return pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_source_link', $source_url );
	}

	/**
	 * Retrieves the item source's link.
	 *
	 * Retrieve the link for the item's source. Attempt to fully
	 * resolve the URL for known aggregation services.
	 *
	 * @since 3.4.5
	 *
	 * @param int  $post_id ID of the post.
	 * @param bool $force   Whether to force a re-check. Default false.
	 */
	public function get_source_link( $post_id, $force = false ) {
		$source_url = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'pf_source_link' );

		if ( empty( $source_url ) || $force ) {
			$url        = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_link' );
			$source_url = pressforward( 'controller.http_tools' )->resolve_a_url( $url );
			pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_source_link', $source_url );
		}

		return $source_url;
	}

	/**
	 * Sets the last_retrieved value for the parent feed.
	 *
	 * @since 3.4.0
	 *
	 * @param int $feed_item_id ID of the feed item.
	 * @return bool
	 */
	public function set_parent_last_retrieved( $feed_item_id ) {
		$feed_item = get_post( $feed_item_id );

		if ( ! is_a( $feed_item, '\WP_Post' ) || empty( $feed_item->post_parent ) ) {
			return false;
		}

		$feed_id = intval( $feed_item->post_parent );

		return pressforward( 'controller.metas' )->update_pf_meta( $feed_id, 'pf_feed_last_retrieved', gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Cleans up PF content on feed item deletion.
	 */
	public function disassemble_feed_items() {
		pf_log( 'Disassemble Feed Items Activated' );
		$retain = get_option( 'pf_retain_time', 2 );

		if ( 0 === (int) $retain ) {
			return;
		}

		/**
		 * Filters the max number of items that should be deleted during trash collection.
		 *
		 * The number can be adjusted up or down, depending on server resources.
		 *
		 * @since 5.5.0
		 *
		 * @param int $batch_size Default 25.
		 */
		$batch_size = apply_filters( 'pressforward_delete_expired_feed_items_batch_size', 25 );

		// Delete rss feed items with a date past a certain point.
		$query_for_del = new \WP_Query(
			array(
				'post_type'              => $this->post_type,
				'posts_per_page'         => $batch_size,
				'fields'                 => 'ids',
				'update_post_term_cache' => false,
				'date_query'             => array(
					'before' => $retain . ' months ago',
				),
			)
		);

		pf_log( 'Cleaning up feed items.' );
		pf_log( $query_for_del );

		// The Loop.
		if ( empty( $query_for_del->posts ) ) {
			return '';
		}

		/**
		 * Pre-delete action for old feed item clean-up.
		 *
		 * This allows plugins to disable clean-up, or to add their own clean-up routine
		 * (such as offloading into a separate, non-WP system).
		 *
		 * Return a value other than `null` to disable PF's own deletion.
		 *
		 * @since 5.5.0
		 *
		 * @param null  $pre_delete Default null. Return non-null value to bail from deletion.
		 * @param array $post_ids   IDs of feed items older than the specified date.
		 */
		$pre_delete = apply_filters( 'pressforward_pre_delete_expired_feed_items', null, $query_for_del->posts );
		if ( null !== $pre_delete ) { // @phpstan-ignore-line
			return true;
		}

		foreach ( $query_for_del->posts as $key => $post_id ) {
			pf_log( 'Cleaning up ' . $post_id );
			pf_delete_item_tree( $post_id );
		}

		return true;
	}

	/**
	 * Method to manually delete rssarchival entries on user action.
	 */
	public function reset_feed() {
		global $wpdb, $post;

		$count     = wp_count_posts( pf_feed_item_post_type() );
		$pub_count = $count->publish;
		$pages     = $pub_count / 100;
		if ( ( $pages < 1 ) && ( $pages > 0 ) ) {
			$pages = 1;
		} else {
			$pages = ceil( $pages );
		}

		while ( $pages > 0 ) {
			$args = array(
				'post_type'              => $this->post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'paged'                  => $pages,
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
			);

			$archive_query = new \WP_Query( $args );
			foreach ( $archive_query->posts as $key => $post_id ) {
				// Switch the delete on to wipe rss archive posts from the database for testing.
				pf_delete_item_tree( $post_id );
			}

			--$pages;
		}
	}

	/**
	 * Takes the items from a fetched feed and converts them into PF Feed Items.
	 *
	 * @param array $feed_obj Feed object data.
	 * @return array
	 */
	public function assemble_feed_for_pull( $feed_obj ) {
		pf_log( 'Invoked: PF_Feed_Item::assemble_feed_for_pull()' );

		ignore_user_abort( true );
		set_time_limit( 0 );

		// We need to init $source_repeat so it can be if 0 if nothing is happening.
		$source_repeat = 0;

		// We'll need this for our fancy query.
		global $wpdb;

		// Since rss_object places all the feed items into an array of arrays whose structure is standardized throughout,
		// We can do stuff with it, using the same structure of items as we do everywhere else.
		pf_log( 'Now beginning check and processing for entering items into the database.' );
		$parent = $feed_obj['parent_feed_id'];
		unset( $feed_obj['parent_feed_id'] );

		$retval = [
			'date_retrieved' => gmdate( 'Y-m-d H:i:s' ),
			'items_added'    => 0,
		];

		foreach ( $feed_obj as $item ) {
			$item['item_link'] = pressforward( 'controller.http_tools' )->resolve_a_url( $item['item_link'] );

			$thepostscheck          = 0;
			$the_posts_double_check = 0;
			$item_id                = $item['item_id'];
			$source_repeat          = 0;

			// Originally this query tried to get every archive post earlier than 'now' to check.
			// But it occured to me that, since I'm doing a custom query anyway, I could just query for items with the ID I want.
			// Less query results, less time.
			// Perhaps I should do this outside of the foreach? One query and search it for each item_id and then return those not in?
			$item_id_key = pressforward( 'controller.metas' )->get_key( 'item_id' );
			$querystr    = $wpdb->prepare(
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
				pf_feed_item_post_type()
			);

			// Since I've altered the query, I could change this to just see if there are any items in the query results
			// and check based on that. But I haven't yet.
			// phpcs:ignore WordPress.DB
			$checkposts = $wpdb->get_results( $querystr, OBJECT );
			if ( $checkposts ) {
				foreach ( $checkposts as $check_post ) {
					setup_postdata( $check_post );
					if ( ( pressforward( 'controller.metas' )->get_post_pf_meta( $check_post->ID, 'item_id', $item_id, true ) ) === $item_id ) {
						++$thepostscheck;
						$post_id_to_pass = $check_post->ID;
						pf_log( 'We already have post ' . $post_id_to_pass . ' for ' );
						pf_log( $item );
						do_action(
							'already_a_feed_item',
							array(
								'item'    => $item,
								'post_id' => $post_id_to_pass,
							)
						);
					}
				}
			}

			wp_reset_postdata();

			if ( 0 === $thepostscheck ) {
				$query_more_str = $wpdb->prepare(
					"
						SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
						FROM {$wpdb->posts}, {$wpdb->postmeta}
						WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
						AND {$wpdb->postmeta}.meta_key = 'item_link'
						AND {$wpdb->postmeta}.meta_value = %s
						AND {$wpdb->posts}.post_type = %s
						ORDER BY {$wpdb->posts}.post_date DESC
					 ",
					$item['item_link'],
					pf_feed_item_post_type()
				);

				// phpcs:ignore WordPress.DB
				$checkpoststwo = $wpdb->get_results( $query_more_str, OBJECT );

				if ( $checkpoststwo ) {
					pf_log( 'Check for posts with the same link.' );
					foreach ( $checkpoststwo as $check_post ) {
						setup_postdata( $check_post );

						// Post comparative values.
						$the_title = $check_post->post_title;
						$post_id   = $check_post->ID;

						$post_date      = strtotime( $check_post->post_date );
						$post_item_link = pressforward( 'controller.metas' )->get_post_pf_meta( $check_post->ID, 'item_link', true );

						// Item comparative values.
						$item_date  = strtotime( $item['item_date'] );
						$item_title = $item['item_title'];
						$item_link  = $item['item_link'];

						// First check if it more recent than the currently stored item.
						if ( ( ( $the_title === $item_title ) || ( $post_item_link === $item_link ) ) ) {
							++$the_posts_double_check;
							pf_log( 'We already have the post ' . $the_title . ' with the link ' . $item_link );
							$source_repeat = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'source_repeat', true );
							if ( ( $item_date > $post_date ) ) {
								// If it is more recent, than this is the new dominant post.
								// @TODO: Allow the feed process to check for updated content.
								// The idea is that if the retrieved item's date is newer than
								// the internal date it will update. But the retrieved item date
								// is not always available, and is not currently being processed
								// correctly when it is, so we're not doing this for now.
								++$source_repeat;
								$thepostscheck = 1;
							} elseif ( ( $item_date <= $post_date ) ) {
								// if it is less recent, then we need to increment the source count.
								++$source_repeat;
								if ( $the_posts_double_check > $source_repeat ) {
									pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'source_repeat', $source_repeat );
								}
								// The idea is that if the retrieved item's date is newer than
								// the internal date it will update. But the retrieved item date
								// is not always available, and is not currently being processed
								// correctly when it is, so we're not doing this for now.
								$thepostscheck = 1;
							} else {
								// The idea is that if the retrieved item's date is newer than
								// the internal date it will update. But the retrieved item date
								// is not always available, and is not currently being processed
								// correctly when it is, so we're not doing this for now.
								$thepostscheck = 1;
							}
						} else {
							// If it isn't duplicated at all, then we need to give it a source repeat count of 0.
							$source_repeat = 0;
						}
					}
				}
			}

			wp_reset_postdata();

			// Why an increment here instead of a bool?
			// If I start getting errors, I can use this to check how many times an item is in the database.
			// Potentially I could even use this to clean the database from duplicates that might occur if
			// someone were to hit the refresh button at the same time as another person.

			if ( 0 === $thepostscheck ) {
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
				$source_repeat = $source_repeat;

				// Trying to prevent bad or malformed HTML from entering the database.
				$item_title   = wp_strip_all_tags( $item_title );
				$item_content = strip_tags( $item_content, '<p> <strong> <bold> <i> <em> <emphasis> <del> <h1> <h2> <h3> <h4> <h5> <a> <img>' );

				// Need to get rid of some weird characters that prevent inserting posts into the database.
				// From: http://www.alexpoole.name/web-development/282/remove-smart-quotes-bullets-dashes-and-other-junky-characters-from-a-string-with-php
				// And: http://www.enghiong.com/wp_insert_post-could-not-insert-post-into-the-database.html.
				// If we use the @ to prevent showing errors, everything seems to work. But it is still dedicating crap to the database...
				// Perhaps sanitize_post isn't the cause? What is then?
				// Do we want or need the post_status to be published?
				$data = array(
					'post_status'  => 'publish',
					'post_type'    => pf_feed_item_post_type(),
					'item_title'   => $item_title,
					'post_parent'  => $feed_obj_id,
					'item_content' => $item_content,
					'item_link'    => $item_link,
					'source_title' => $source_title,
					'item_wp_date' => $item_wp_date,
					'item_tags'    => $item_tags,

				);

				// RIGHT HERE is where the content is getting assigned a bunch of screwed up tags.
				// The content is coming in from the rss_object assembler a-ok. But something here saves them to the database screwy.
				// It looks like sanitize post is screwing them up terribly. But what to do about it without removing the security measures which we need to apply?
				$worked = 1;

				do_action( 'about_to_insert_pf_feed_items', $item );

				// The post gets created here, the $new_nom_id variable contains the new post's ID.
				$new_nom_id         = $this->create( $data );
				$post_inserted_bool = $this->post_inserted( $new_nom_id, $data );

				if ( ! $post_inserted_bool ) {
					// It's the end of the world! Let's throw everything at this.
					pf_log( 'Post will not go into the database. We will try again.' );
					$item_content         = htmlentities( wp_strip_all_tags( $item_content ), ENT_QUOTES, 'UTF-8' );
					$item_content         = wp_kses( stripslashes( $item_content ), 'post' );
					$item_content         = $this->extra_special_sanatize( $item_content, true );
					$item_content         = wpautop( $item_content );
					$item_title           = $this->extra_special_sanatize( $item_title, true );
					$data['item_content'] = $item_content;
					$new_nom_id           = $this->create( $data );
					$post_inserted_bool   = $this->post_inserted( $new_nom_id, $data );
				}

				pf_log( 'End of wp_insert_post process.' );

				++$retval['items_added'];

				// Somewhere in the process links with complex queries at the end (joined by ampersands) are getting encoded.
				// I don't want that, so I turn it back here.
				// For some reason this is only happening to the ampersands, so that's the only thing I'm changing.
				$item_link = str_replace( '&amp;', '&', $item_link );

				// If it doesn't have a featured image assigned already, I use the set_ext_as_featured function to try and find one.
				// It also, if it finds one, sets it as the featured image for that post.
				if ( ! empty( $_POST['item_feat_img'] ) ) {
					// Turned off set_ext_as_featured here, as that should only occur when items are nominated.
					// Before nominations, the featured image should remain a meta field with an external link.
					$item_feat_img = get_transient( 'feed_img_' . $new_nom_id );
					if ( false === $item_feat_img ) {
						set_time_limit( 0 );
						// if it forces the issue when we try and get the image, there's nothing we can do.
						$item_link = str_replace( '&amp;', '&', $item_link );
						if ( pressforward( 'library.opengraph' )->fetch( $item_link ) ) {
							// If there is no featured image passed, let's try and grab the opengraph image.
							$node = pressforward( 'library.opengraph' )->fetch( $item_link );

							$item_feat_img = $node->image;
						}

						// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
						if ( ! $item_feat_img ) {
							// Thinking of starting a method here to pull the first image from the body of a post.
							// http://stackoverflow.com/questions/138313/how-to-extract-img-src-title-and-alt-from-html-using-php.
							// http://stackoverflow.com/questions/1513418/get-all-images-url-from-string.
							// http://stackoverflow.com/questions/7479835/getting-the-first-image-in-string-with-php.
						}

						// Most RSS feed readers don't store the image locally. Should we?
						set_transient( 'feed_img_' . $new_nom_id, $item_feat_img, 60 * 60 * 24 );
					}
				}

				// Adding the meta info about the feed item to the post's meta.
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
				pressforward( 'controller.metas' )->establish_post( $new_nom_id, $pf_meta_args );
				$parent_id = $feed_obj_id;
				do_action( 'pf_post_established', $new_nom_id, $item_id, $parent_id );
			} else {
				pf_log( 'The post was a repeat, so we are not adding it.' );
			}
		}

		return $retval;
	}

	/**
	 * Logs debug data after inserting a post.
	 *
	 * @param int   $post_attempt Results of insert attempt.
	 * @param mixed $data         Data.
	 * @return bool
	 */
	public static function post_inserted( $post_attempt, $data ) {
		$worked      = 1;
		$worked_bool = true;

		if ( 0 === $post_attempt ) {
			pf_log( 'The following post did not go into the database correctly.' );
			pf_log( $data );
			$worked = 0;
		} else {
			pf_log( 'Create post in the database with the title ' . $data['item_title'] . ' and id of ' );
			pf_log( $post_attempt );
		}

		if ( 0 === $worked ) {
			$worked_bool = false;
		}

		return $worked_bool;
	}

	/**
	 * Alternate function title - 'stop_pasting_junk_from_word'.
	 *
	 * @param string $the_string String to sanitize.
	 * @param bool   $severe     Triggers extra-extra special sanitization.
	 * @return string
	 */
	public static function extra_special_sanatize( $the_string, $severe = false ) {
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

		$the_string = str_replace( $search, $replace, $the_string );
		pf_log( 'String run through specified str_replace.' );

		$the_string = mb_convert_encoding( $the_string, 'UTF-8', mb_detect_encoding( $the_string, 'auto' ) );

		if ( $severe ) {
			/*
			 * Remove MS Word Special Characters.
			 * From: https://gist.github.com/gcoop/701814.
			 */
			$search  = array( '&acirc;��', '&acirc;��', '&acirc;��', '&acirc;��', '&Acirc;&pound;', '&Acirc;&not;', '&acirc;�&cent;', '&Acirc;&nbsp;', '&Acirc;', '&amp;nbsp;', '&#8230;' );
			$replace = array( '-', '&ldquo;', '&lsquo;', '&rsquo;', '&pound;', '&not;', '&#8482;', '', '', '', '...' );

			$the_string = str_replace( $search, $replace, $the_string );
			$the_string = str_replace( '&acirc;�', '&rdquo;', $the_string );

			$search  = array( '&#39;', "\xc3\xa2\xc2\x80\xc2\x99", "\xc3\xa2\xc2\x80\xc2\x93", "\xc3\xa2\xc2\x80\xc2\x9d", "\xc3\xa2\x3f\x3f", '&#8220;', '&#8221;', '#8217;', '&not;', '&#8482;' );
			$replace = array( "'", "'", ' - ', '"', "'", '"', '"', "'", '-', '(TM)' );

			$the_string = str_replace( $search, $replace, $the_string );

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

			$the_string = strtr( $the_string, $quotes );
		}

		return $the_string;
	}

	/**
	 * Get the content of a URL, using various fallbacks.
	 *
	 * @param string $url URL.
	 * @return string|bool
	 */
	public static function get_content_through_aggregator( $url ) {
		set_time_limit( 0 );

		// First run it through Readability.
		$descrip = pressforward( 'controller.readability' )->readability_object( $url );

		// If that doesn't work...
		if ( ! $descrip ) {
			$url = str_replace( '&amp;', '&', $url );

			// Try and get the OpenGraph description.
			if ( pressforward( 'library.opengraph' )->fetch( $url ) ) {
				$node    = pressforward( 'library.opengraph' )->fetch( $url );
				$descrip = $node->description;
			} else {
				$content_html = get_meta_tags( $url );
				if ( $content_html ) {
					// Try and get the HEAD > META DESCRIPTION tag.
					$descrip = isset( $content_html['description'] ) ? $content_html['description'] : '';
					if ( ! $descrip ) {
						pf_log( $url . ' has no meta OpenGraph description we can find.' );
					}
				} else {
					// Ugh... we can't get anything huh?
					pf_log( $url . ' has no description we can find.' );
					// We'll want to return a false to loop with.
					return false;
				}
			}
		}

		return $descrip;
	}

	/**
	 * Gets extension for an image path.
	 *
	 * @param string $img_url URL of image.
	 * @return string
	 */
	public function resolve_image_type( $img_url ) {
		$img_url = strtok( $img_url, '?' );
		$type    = wp_check_filetype( $img_url );
		return $type['ext'];
	}

	/**
	 * Enforces scheme for URLs.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public function assert_url_scheme( $url ) {
		$url_parts   = wp_parse_url( $url );
		$slash_check = substr( $url, 0, 2 );
		if ( empty( $url_parts['scheme'] ) && ( '//' === $slash_check ) ) {
			$url = 'http:' . $url;
		} elseif ( empty( $url_parts['scheme'] ) && ( '//' !== $slash_check ) ) {
			$url = 'http://' . $url;
		}

		return $url;
	}

	/**
	 * Gets image URL from OpenGraph.
	 *
	 * @param string $link URL.
	 * @return string|bool
	 */
	public static function get_ext_og_img( $link ) {
		$node = pressforward( 'library.opengraph' )->fetch( $link );
		if ( ! empty( $node ) ) {
			$item_feat_img = $node->image;
		} else {
			return false;
		}

		return $item_feat_img;
	}

	/**
	 * Checks whether a filepath represents a real image.
	 *
	 * @param string $filepath File path.
	 * @return bool
	 */
	public function assure_image( $filepath ) {
		$img_info = getimagesize( $filepath );
		// Your 1x1 tracking or dummy images have no domain here!
		if ( ( 2 > $img_info[0] ) || ( 2 > $img_info[1] ) ) {
			// I assure you this is not an image.
			return false;
		} else {
			// This is an image I assure you.
			return true;
		}
	}

	/**
	 * Set post featured image from OG image tag.
	 *
	 * @param int    $post_id  ID of the post.
	 * @param string $og_image Image URL from OpenGraph tag.
	 */
	public static function set_ext_as_featured( $post_id, $og_image ) {
		if ( strlen( $og_image ) <= 5 ) {
			return;
		}

		// Remove query args from the URL.
		$og_image = pressforward( 'schema.feed_item' )->assert_url_scheme( $og_image );

		if ( ! filter_var( $og_image, FILTER_VALIDATE_URL ) ) {
			pf_log( 'It looked like we received a file, but PHP could not understand it as a URL: ' . $og_image );
			return '';
		}

		$img_parts        = pathinfo( $og_image );
		$img_ext          = $img_parts['extension'];
		$img_title        = $img_parts['filename'];
		$resolved_img_ext = pressforward( 'schema.feed_item' )->resolve_image_type( $og_image );
		$resolved_img_ext = strtolower( $resolved_img_ext );

		if ( ! in_array( $resolved_img_ext, array( 'jpg', 'png', 'jrpg', 'bmp', 'gif', 'jpeg' ), true ) && ! in_array( $img_ext, array( 'jpg', 'png', 'jrpg', 'bmp', 'gif', 'jpeg' ), true ) ) {
			return;
		}

		$img_title = sanitize_file_name( $img_title );
		// Let's not get crazy here.
		$img_title = substr( $img_title, 0, 100 );

		if ( strpos( $img_title, '.' ) !== false ) {
			$img_title = 'retrieved-featured-image';
		} else {
			$img_title = $img_title;
		}

		$upload_dir   = wp_upload_dir();
		$og_cache_img = $upload_dir['path'] . '/' . $post_id . '-' . $img_title . '.' . $resolved_img_ext;

		if ( ! file_exists( $og_cache_img ) ) {
			$result = copy( $og_image, $og_cache_img );

			if ( ! $result ) {
				return;
			}
		}

		if ( false === pressforward( 'schema.feed_item' )->assure_image( $og_cache_img ) ) {
			return;
		}

		// Methods within sourced from http://codex.wordpress.org/Function_Reference/wp_insert_attachment
		// and http://wordpress.stackexchange.com/questions/26138/set-post-thumbnail-with-php
		// Get the type of the image file. .jpg, .gif, or whatever.
		$filetype = wp_check_filetype( $og_cache_img );

		// Set the identifying variables for the about to be featured image.
		$img_data = array(
			'guid'           => $og_cache_img,
			// tell WordPress what the filetype is.
			'post_mime_type' => $filetype['type'],
			// set the image title to the title of the site you are pulling from.
			'post_title'     => get_the_title( $post_id ),
			// WordPress tells us we must set this and set it to empty. Why? Dunno.
			'post_content'   => $img_title,
			// Now we set the status of the image. It will inheret that of the post.
			// If the post is published, then the image will be to.
			'post_status'    => 'inherit',
		);

		// WordPress needs an absolute path to the image, as opposed to the relative path we used before.
		// I'm hoping that by using the upload_dir function (above) I can make this function work with multisite.

		// Now we insert the image as a WordPress attachement, and associate it with the current post.
		$thumbid = wp_insert_attachment( $img_data, $og_cache_img, $post_id );

		// To set a thumbnail, you need metadata associated with an image.
		// To get that we need to call the image.php file.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $thumbid, $og_cache_img );

		// Now we attach the meta data to the image.
		wp_update_attachment_metadata( $thumbid, $metadata );

		// Now that we have a correctly meta-ed and attached image we can finally turn it into a post thumbnail.
		pressforward( 'controller.metas' )->update_pf_meta( $post_id, '_thumbnail_id', $thumbid );
	}

	/**
	 * Filters 'posts_where' to return only posts older than sixty days.
	 *
	 * Can be modified with user setting for retention.
	 *
	 * @param string $where SQL clause.
	 * @return string
	 */
	public static function filter_where_older( $where = '' ) {
		$retain        = get_option( 'pf_retain_time', 2 );
		$retain_months = $retain * 30;
		$str           = '-' . $retain_months . ' days';

		// Posts before the last 60 days.
		$where .= " AND post_date < '" . gmdate( 'Y-m-d', strtotime( $str ) ) . "'";
		return $where;
	}

	/**
	 * Sets a feed item's tags.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $tags    Array of tags.
	 * @param bool  $append  True if you want to append rather than replace.
	 */
	public function set_tags( $post_id, $tags, $append = false ) {
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
		// Loop through each module to get its source data.
		$source_data_object = array();
		foreach ( pressforward( 'modules' )->modules as $module ) {
			$source_data_object = array_merge( $source_data_object, $module->get_data_object() );
		}
		return $source_data_object;
	}

	/**
	 * Converts a raw tag array to a list appropriate for a tax_query.
	 *
	 * Will create the necessary tags if they're not found.
	 *
	 * @param mixed $tags Tags.
	 * @return array
	 */
	public function convert_raw_tags( $tags ) {
		$retval = array(
			$this->tag_taxonomy => $tags,
		);
		return $retval;
	}

	/**
	 * Not used.
	 */
	public static function get_term_slug_from_tag() {}

	/**
	 * Register "Removed feed item" post status.
	 */
	public function register_feed_item_removed_status() {
		$args = array(
			'label'                     => _x( 'Removed Feed Item', 'pf', 'pressforward' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,

			// translators: Removed feed item count.
			'label_count'               => _n_noop( 'Removed <span class="count">(%s)</span>', 'Removed <span class="count">(%s)</span>', 'pressforward' ),
		);

		register_post_status( 'removed_feed_item', $args );
	}

	/**
	 * Returns list of oEmbed-capable domains.
	 *
	 * @return array
	 */
	public function oembed_capables() {
		return array(
			'youtube.com',
		);
	}
}
