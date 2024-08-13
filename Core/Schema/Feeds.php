<?php
/**
 * Feeds data control.
 *
 * @package PressForward
 */

namespace PressForward\Core\Schema;

use stdClass;
use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use PressForward\Controllers\Metas;
use PressForward\Core\Models\Feed;

/**
 * Database class for manipulating feed.
 */
class Feeds implements HasActions, HasFilters {
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
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas $metas Metas object.
	 */
	public function __construct( Metas $metas ) {
		$this->post_type    = 'pf_feed';
		$this->tag_taxonomy = 'pf_feed_category';
		$this->metas        = $metas;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		$hooks = array(
			array(
				'hook'     => 'init',
				'method'   => 'register_feed_post_type',
				'priority' => 10,
			),
			array(
				'hook'   => 'admin_init',
				'method' => 'disallow_add_new',
			),
			array(
				'hook'   => 'save_post',
				'method' => 'save_submitbox_pf_actions',
			),
			[
				'hook'   => 'save_post',
				'method' => 'set_up_feed_retrieval_cron_job',
			],
			[
				'hook'   => 'before_delete_post',
				'method' => 'delete_feed_cron_jobs',
			],
			array(
				'hook'   => 'pf_feed_post_type_registered',
				'method' => 'under_review_post_status',
			),
			array(
				'hook'     => 'manage_pf_feed_posts_custom_column',
				'method'   => 'last_retrieved_date_column_content',
				'priority' => 10,
				'args'     => 2,
			),
		);

		if ( is_admin() ) {
			$admin_hooks = array(
				array(
					'hook'   => 'wp_ajax_deal_with_old_feedlists',
					'method' => 'deal_with_old_feedlists',
				),
				array(
					'hook'   => 'post_submitbox_misc_actions',
					'method' => 'feed_submitbox_pf_actions',
				),
			);
			$hooks       = array_merge( $hooks, $admin_hooks );
		}
		return $hooks;
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		$filters = array(
			array(
				'hook'     => 'ab_alert_specimens_update_post_type-add-feeds',
				'method'   => 'make_alert_return_to_publish',
				'priority' => 10,
				'args'     => 1,
			),
			array(
				'hook'     => 'views_edit-' . $this->post_type,
				'method'   => 'modify_post_views',
				'priority' => 10,
				'args'     => 1,
			),
			array(
				'hook'     => 'map_meta_cap',
				'method'   => 'feeds_map_meta_cap',
				'priority' => 10,
				'args'     => 4,
			),
			array(
				'hook'   => 'user_has_cap',
				'method' => 'alter_cap_on_fly',
			),
			array(
				'hook'   => 'option_page_capability_pf_feedlist_group',
				'method' => 'feed_option_page_cap',
			),
			array(
				'hook'   => 'manage_edit-' . $this->post_type . '_columns',
				'method' => 'custom_feed_column_name',
			),
		);

		if ( is_admin() ) {
			$admin_filters = array(
				array(
					'hook'     => 'page_row_actions',
					'method'   => 'url_feed_row_action',
					'priority' => 10,
					'args'     => 2,
				),
				array(
					'hook'     => 'page_row_actions',
					'method'   => 'refresh_feed_row_action',
					'priority' => 10,
					'args'     => 2,
				),
				array(
					'hook'   => 'post_updated_messages',
					'method' => 'feed_save_message',
				),
				array(
					'hook'     => 'parent_file',
					'method'   => 'move_feed_tags_submenu',
					'priority' => 10,
					'args'     => 1,
				),
			);
			$filters       = array_merge( $filters, $admin_filters );
		}
		return $filters;
	}

	/**
	 * Feeds are stored in a CPT, which is registered here.
	 */
	public function register_feed_post_type() {
		$labels = array(
			'name'               => __( 'Subscribed Feeds', 'pressforward' ),
			'singular_name'      => __( 'Feed', 'pressforward' ),
			'add_new'            => _x( 'Add New', 'pf', 'add new feed' ),
			'all_items'          => __( 'All Feeds', 'pressforward' ),
			'add_new_item'       => __( 'Add New Feed', 'pressforward' ),
			'edit_item'          => __( 'Edit Feed', 'pressforward' ),
			'new_item'           => __( 'New Feed', 'pressforward' ),
			'view_item'          => __( 'View Feed', 'pressforward' ),
			'search_items'       => __( 'Search Feeds', 'pressforward' ),
			'not_found'          => __( 'No feeds found', 'pressforward' ),
			'not_found_in_trash' => __( 'No feeds found in trash', 'pressforward' ),
		);

		register_post_type(
			$this->post_type,
			apply_filters(
				'pf_register_feed_post_type_args',
				array(
					'label'                 => $labels['name'],
					'labels'                => $labels,
					'description'           => __( 'Feeds imported by PressForward&#8217;s Feed Importer', 'pressforward' ),
					'public'                => false,
					'hierarchical'          => true,
					'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ),
					'taxonomies'            => array( 'post_tag' ),
					'show_in_menu'          => PF_MENU_SLUG,
					'show_in_admin_bar'     => true,
					'show_in_rest'          => true,
					'rest_namespace'        => 'pf/v1',
					'rest_base'             => 'feeds',
					'rest_controller_class' => 'PF_REST_Posts_Controller',
					'show_ui'               => true, // for testing only.
					'capability_type'       => $this->post_type,
					'capabilities'          => $this->map_feed_caps(),
				)
			)
		);

		do_action( 'pf_feed_post_type_registered' );
	}

	/**
	 * Gets cap list for feed post type.
	 *
	 * @return array
	 */
	public function map_feed_caps() {
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
		foreach ( $this->map_feed_caps() as $core_cap => $cap ) {
			if ( ! empty( $caps[ $core_cap ] ) ) { // user has edit capabilities.
				$caps[ $cap ] = true;
			}
		}
		return $caps;
	}

	/**
	 * Ensure that 'Feed Tags' stays underneath the PressForward top-level item.
	 *
	 * @param string $pf The $parent_file value passed to the 'parent_file' filter.
	 * @return string
	 */
	public function move_feed_tags_submenu( $pf ) {
		global $typenow, $pagenow;
		// phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
		if ( ( 'term.php' === $pagenow || 'edit-tags.php' === $pagenow ) && ! empty( $_GET['taxonomy'] ) && $this->tag_taxonomy === sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) ) {
			$pf = 'pf-menu';
		}

		return $pf;
	}

	/**
	 * Meta cap mapping for feeds.
	 *
	 * @param array  $caps    Relevant caps for user.
	 * @param string $cap     Capability to check against $caps array.
	 * @param int    $user_id ID of user.
	 * @param array  $args    Arguments.
	 * @return array
	 */
	public function feeds_map_meta_cap( $caps, $cap, $user_id, $args ) {
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
		$post_type = get_post_type_object( $post->post_type );

		// Set an empty array for the caps.
		$caps = array();

		if ( 'edit_' . $this->post_type === $cap ) {
			/* If editing a feed, assign the required capability. */
			if ( $post && $user_id === (int) $post->post_author ) {
				$caps[] = $post_type->cap->edit_posts;
			} else {
				$caps[] = $post_type->cap->edit_others_posts;
			}
		} elseif ( 'delete_' . $this->post_type === $cap ) {
			/* If deleting a feed, assign the required capability. */
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
	 * Gets caps for a feed role option.
	 *
	 * @param string $cap Cap name.
	 * @return array
	 */
	public function feed_option_page_cap( $cap ) {
		$caps = $this->map_feed_caps();
		return $caps['edit_posts'];
	}

	/**
	 * Registers the 'Under Review' post status.
	 */
	public function under_review_post_status() {
		register_post_status(
			'under_review',
			array(
				'label'                     => _x( 'Under Review', 'pf', 'pressforward' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,

				// Translators: Under Review feed count.
				'label_count'               => _n_noop( 'Under Review <span class="count">(%s)</span>', 'Under Review <span class="count">(%s)</span>', 'pressforward' ),
			)
		);
	}

	/**
	 * Adds actions to the submit box for Feeds.
	 */
	public function feed_submitbox_pf_actions() {
		global $post;

		if ( $post->post_type !== $this->post_type ) {
			return;
		}

		$value = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'pf_no_feed_alert', true );

		if ( '' === $value ) {
			// If the user does not want to forward all things this setting is 0,
			// which evaluates to empty.
			$value = 0;
		}

		echo '<div class="misc-pub-section misc-pub-section-last">
	         <span id="pf_no_feed_alert_single">'
			. '<label><input type="checkbox"' . ( ! empty( $value ) ? ' checked="checked" ' : null ) . 'value="1" name="pf_no_feed_alert" /> ' . esc_html__( 'No alerts, never let feed go inactive.', 'pressforward' ) . '</label>'
		. '</span></div>';
	}

	/**
	 * Sets up retrieval cron job on feed save.
	 *
	 * @since 5.6.0
	 *
	 * @param int $post_id ID of the post being saved.
	 * @return void
	 */
	public function set_up_feed_retrieval_cron_job( $post_id ) {
		$feed = Feed::get_instance_by_id( $post_id );
		if ( ! $feed ) {
			return;
		}

		// If the retrieval is already scheduled, there's nothing more to do.
		$next_scheduled = $feed->get_next_scheduled_retrieval();
		if ( $next_scheduled ) {
			return;
		}

		$feed->schedule_retrieval();
	}

	/**
	 * Deletes cron jobs on feed delete.
	 *
	 * @since 5.6.0
	 *
	 * @param int $post_id ID of the post being deleted.
	 * @return void
	 */
	public function delete_feed_cron_jobs( $post_id ) {
		$feed = Feed::get_instance_by_id( $post_id );
		if ( ! $feed ) {
			return;
		}

		$feed->unschedule_retrieval();
		$feed->unschedule_health_check();
	}

	/**
	 * Handles submitbox actions on save.
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function save_submitbox_pf_actions( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return false;
		}

		if ( empty( $_POST['pf_no_feed_alert'] ) ) {
			pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_no_feed_alert', 0 );
		} else {
			$no_feed_alert = isset( $_POST['pf_no_feed_alert'] ) ? sanitize_text_field( wp_unslash( $_POST['pf_no_feed_alert'] ) ) : '';
			pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_no_feed_alert', $no_feed_alert );
		}

		return $post_id;
	}

	/**
	 * Adds column names for custom columns on Feeds list table.
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function custom_feed_column_name( $posts_columns ) {
		$posts_columns['author']          = __( 'Added by', 'pressforward' );
		$posts_columns['items_retrieved'] = __( 'Items', 'pressforward' );
		$posts_columns['date']            = __( 'Date Added', 'pressforward' );
		return $posts_columns;
	}

	/**
	 * Content of the Items Retrieved column.
	 *
	 * We also hide the feed URL in this column, so we can reveal it on Quick Edit.
	 *
	 * @since 3.7.0
	 *
	 * @param string $column_name Column ID.
	 * @param int    $post_id ID of the post for the current row in the table.
	 */
	public function last_retrieved_date_column_content( $column_name, $post_id ) {
		if ( 'items_retrieved' !== $column_name ) {
			return;
		}
		$counts = $this->count_feed_items_collected( $post_id );
		echo (int) $counts->publish;
	}

	/**
	 * Count number of published items that are children of a feed and more if
	 * user has permissions to view.
	 *
	 * This function provides an efficient method of finding the amount of feed
	 * items a feed post has as children. Another method is to count the amount
	 * of items in get_posts(), but that method has a lot of overhead with doing
	 * so. Therefore, use this function instead. Based on WP4.3 wp_count_posts.
	 *
	 * The $perm parameter checks for 'readable' value and if the user can read
	 * private posts, it will display that for the user that is signed in.
	 *
	 * @since 3.7.0
	 *
	 * @global wpdb $wpdb
	 *
	 * @param int    $parent_id Parent feed post ID.
	 * @param string $perm      Optional. 'readable' or empty. Default empty.
	 * @return object Number of posts for each status.
	 */
	public function count_feed_items_collected( $parent_id, $perm = '' ) {
		global $wpdb;

		$type = pressforward( 'schema.feed_item' )->post_type;
		if ( ! post_type_exists( $type ) ) {
			return new stdClass();
		}

		$counts = wp_cache_get( $type . '_' . $parent_id, 'pf_counts' );
		if ( false !== $counts ) {
			/** This filter is documented in wp-includes/post.php */
			return apply_filters( 'pf_count_items', $counts, $parent_id, $perm );
		}

		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d";
		if ( 'readable' === $perm && is_user_logged_in() ) {
			$post_type_object = get_post_type_object( $type );
			if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
				$query .= $wpdb->prepare(
					" AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
					get_current_user_id()
				);
			}
		}
		$query .= ' GROUP BY post_status';

		// phpcs:ignore WordPress.DB
		$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type, $parent_id ), ARRAY_A );
		$counts  = array_fill_keys( get_post_stati(), 0 );

		foreach ( $results as $row ) {
			$counts[ $row['post_status'] ] = $row['num_posts'];
		}

		$counts = (object) $counts;
		wp_cache_set( $type . '_' . $parent_id, $counts, 'pf_counts', 1740 );

		/**
		 * Modify returned post counts by status for the current post type.
		 *
		 * @since 3.7.0
		 *
		 * @param object $counts    An object containing the current post_type's post
		 *                          counts by status.
		 * @param int    $parent_id ID of the parent item.
		 * @param string $perm      The permission to determine if the posts are 'readable'
		 *                          by the current user.
		 */
		return apply_filters( 'pf_count_items', $counts, $parent_id, $perm );
	}

	/**
	 * Checks whether a feed tag exists with the given ID.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public function is_feed_term( $id ) {
		$termcheck = term_exists( (int) $id, $this->tag_taxonomy );
		if ( empty( $termcheck ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Adds list table row actions for feeds.
	 *
	 * @param array    $actions Action links.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function url_feed_row_action( $actions, $post ) {
		if ( $post->post_type !== $this->post_type ) {
			return $actions;
		}

		$url = $this->metas->get_post_pf_meta( $post->ID, 'feed_url' );
		if ( isset( $actions['edit'] ) ) {
			$edit_actions = $actions['edit'];
		} else {
			$edit_actions = '';
		}

		$actions['edit'] = '<span class="inline pf-url" style="visibility:visible;color:grey;">' . esc_attr( $url ) . '</span><br/>';
		$ab_msg          = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'ab_alert_msg', true );

		if ( ! empty( $ab_msg ) ) {
			$actions['edit'] .= '<span class="inline pf-alert-msg" style="">' . pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'ab_alert_msg', true ) . '</span><br/>';
		}

		$actions['edit'] .= $edit_actions;

		return $actions;
	}

	/**
	 * Adds 'Refresh' list table row action for feeds.
	 *
	 * @param array    $actions Action links.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function refresh_feed_row_action( $actions, $post ) {
		if ( $post->post_type !== $this->post_type ) {
			return $actions;
		}

		$actions['refresh_feed'] = '<span class="inline hide-if-no-js pf-refresh"><a href="#" class="refresh-feed" data-pf-feed="' . esc_attr( (string) $post->ID ) . '" title="' . esc_attr__( 'Refresh this feed', 'pressforward' ) . '">' . esc_html__( 'Refresh Feed Items', 'pressforward' ) . '</a> | ';

		return $actions;
	}

	/**
	 * Redirect away from WP's native Add New Feed page.
	 */
	public function disallow_add_new() {
		global $pagenow;
		/* Check current admin page. */
		if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && $_GET['post_type'] === $this->post_type ) {
			wp_safe_redirect( admin_url( '/admin.php?page=pf-feeder', 'http' ), 301 );
			exit;
		}
	}

	/**
	 * Attempts to migrate feedlits.
	 */
	public function deal_with_old_feedlists() {
		$feedlist = get_option( PF_SLUG . '_feedlist' );
		if ( ! get_option( PF_SLUG . '_feedlist_backup' ) ) {
			$feedlist = add_option( PF_SLUG . '_feedlist_backup', $feedlist );
		}

		if ( empty( $feedlist ) ) {
			return true;
		}

		$all_feeds_array = apply_filters( 'imported_rss_feeds', $feedlist );
		pf_log( 'Preparing to transform feedlist into a list of feed posts.' );
		$ordered_all_feeds_array = array_values( $all_feeds_array );
		$tidy_all_feeds_array    = array_filter( $ordered_all_feeds_array );
		foreach ( $tidy_all_feeds_array as $key => $feed ) {
			$feedlist = $this->progressive_feedlist_transformer( $tidy_all_feeds_array, $feed, $key );
		}
		$check_up = update_option( PF_SLUG . '_feedlist', $feedlist );
		if ( ! $check_up ) {
			wp_die( 'Unable to update feedlist option with new smaller feedlist.' );
		}
	}

	/**
	 * Set the last_checked value for the parent feed.
	 *
	 * @since 3.5.0
	 *
	 * @param int $feed_id ID of the feed item.
	 * @return bool
	 */
	public function set_feed_last_checked( $feed_id ) {
		if ( empty( $feed_id ) ) {
			$feed_id = get_the_ID();
		}

		if ( ! $feed_id ) {
			return false;
		}

		return pressforward( 'controller.metas' )->update_pf_meta( $feed_id, 'pf_feed_last_checked', gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Migrates a feedlist.
	 *
	 * Not only is this moving feeds over into feed CPT posts, but this methodology will insure a time-out won't force the process to restart.
	 * There should probably be a AJAX interface for this, same as the AB subscribe method.
	 *
	 * @param array  $feedlist Feed list.
	 * @param string $xml_url  XML URL.
	 * @param int    $key      Key.
	 * @param array  $args     Arguments.
	 * @return array
	 */
	public function progressive_feedlist_transformer( $feedlist = array(), $xml_url = '', $key = 0, $args = array() ) {
		$post_args = array_merge( array( 'type' => 'rss' ), $args );
		$check     = $this->create( $xml_url, $post_args );
		if ( is_numeric( $check ) && ( 0 < $check ) ) {
			unset( $feedlist[ $key ] );
		}
		return $feedlist;
	}

	/**
	 * Deletes all feeds.
	 */
	public function kill_all_feeds() {
		$mycustomposts = get_posts(
			array(
				'post_type'      => $this->post_type,
				'posts_per_page' => -1,
			)
		);
		foreach ( $mycustomposts as $mypost ) {
			// Delete each post.
			wp_delete_post( $mypost->ID, true );
			// Set to False if you want to send them to Trash.
		}
	}

	/**
	 * Registers the "Removed Feed" post status.
	 */
	public function dead_feed_status() {
		register_post_status(
			'removed_' . $this->post_type,
			array(
				'label'                  => _x( 'Removed Feed', 'pf', 'pressforward' ),
				'public'                 => false,
				'exclude_from_search'    => true,
				'show_in_admin_all_list' => false,
			)
		);
	}

	/**
	 * Pre-flight actions when saving a feed CPT.
	 *
	 * @param array  $r            Args passed to wp_insert_post().
	 * @param string $insert_type 'insert' or 'update'.
	 * @return array
	 */
	public function feed_post_setup_inital( $r, $insert_type = 'insert' ) {
		pf_log( 'Invoked: feed_post_setup' );
		foreach ( $r as $k => $e ) {
			if ( ! $e ) {
				$r[ $k ] = '';
			}
		}

		pf_log( 'Replaced false meta with empty strings.' );

		if ( empty( $r['post_parent'] ) ) {
			$r['post_parent'] = 0;
		}

		if ( empty( $r['post_status'] ) ) {
			pf_log( 'Post status will be set to published because none supplied.' );
			pf_log( $r );
			$r['post_status'] = 'publish';
		}

		$wp_args = array(
			'post_type'    => $this->post_type,
			'post_status'  => $r['post_status'],
			'post_title'   => $r['title'],
			'post_content' => $r['description'],
			'guid'         => $r['url'],
			'post_parent'  => $r['post_parent'],
			'tax_input'    => array( $this->tag_taxonomy => $r['tags'] ),
		);

		// Duplicate the function of WordPress where creating a pre-existing
		// post results in an update to that post.
		pf_log( 'We have initially formed the following post args:' );
		pf_log( $wp_args );

		if ( ! $this->has_feed( $r['url'] ) ) {
			$insert_type = 'insert';
		} else {
			$insert_type = 'update';
		}

		if ( empty( $wp_args['post_title'] ) ) {
			pf_log( 'Missing a title, assigning the URL' );
			$wp_args['post_title'] = $r['url'];
		}

		pf_log( 'Will now ' . $insert_type . ' a post with the following args:' );
		pf_log( $wp_args );

		if ( 'update' === $insert_type ) {
			if ( ! isset( $r['ID'] ) ) {
				$post_obj = $this->get_feed( $r['url'] );
				$r['ID']  = $post_obj->ID;
			}

			unset( $wp_args['post_status'] );
			$wp_args['ID'] = $r['ID'];
			wp_update_post( $wp_args );
		}

		$r['wp_args'] = $wp_args;

		return $r;
	}

	/**
	 * Inserts a new feed into the database.
	 *
	 * @param array  $r            Args passed to wp_insert_post().
	 * @param string $insert_type 'insert' or 'update'.
	 * @return bool|int
	 */
	public function feed_post_setup( $r, $insert_type = 'insert' ) {
		pf_log( 'Invoked: feed_post_setup' );
		$r       = $this->feed_post_setup_inital( $r, $insert_type );
		$wp_args = $r['wp_args'];
		unset( $r['wp_args'] );

		$post_id = null;

		if ( 'update' === $insert_type ) {
			$post_id = $r['ID'];
		}

		if ( 'insert' === $insert_type ) {
			$post_id = pressforward( 'controller.items' )->insert_post( $wp_args );
		}

		pf_log( 'Posting process resulted in:' );
		pf_log( $post_id );
		pf_log( 'for' );
		pf_log( $wp_args );

		if ( is_numeric( $post_id ) && ( 0 < $post_id ) ) {
			pf_log( 'The post_id is numeric and greater than 0, complete the ' . $insert_type . ' process' );
			$this->set_pf_feed_type( $post_id, $r['type'] );

			if ( isset( $r['feed_folders'] ) && null !== empty( $r['feed_folders'] ) ) {
				$set = wp_set_object_terms( $post_id, $r['feed_folders'], $this->tag_taxonomy );
			}

			$unsetables = array( 'title', 'description', 'tags', 'type', 'url', 'post_status', 'ID', 'post_type', 'post_title', 'post_content', 'guid', 'post_parent', 'tax_input' );
			foreach ( $unsetables as $k => $a ) {
				unset( $r[ $a ] );
			}

			$this->set_feed_meta( $post_id, $r );
			return $post_id;
		} else {
			return false;
		}
	}

	/**
	 * Filters the views on the Feeds list table.
	 *
	 * @param array $views Views.
	 * @return array
	 */
	public function modify_post_views( $views ) {
		if ( isset( $views['publish'] ) ) {
			$views['publish'] = str_replace( 'Published ', 'Active ', $views['publish'] );
		}

		if ( isset( $views['draft'] ) ) {
			$views['draft'] = str_replace( 'Drafts ', 'Inactive ', $views['draft'] );
		}

		return $views;
	}

	/**
	 * Not used.
	 */
	public function modify_post_edit_status() {}

	/**
	 * A function to pull feed meta out of the SimplePie object and into the argument array.
	 *
	 * @param array  $r        Arguments.
	 * @param object $the_feed Feed object.
	 * @return array
	 */
	public function setup_rss_meta( $r, $the_feed ) {
		if ( empty( $r ) ) {
			return $r;
		}

		if ( ! $r['htmlUrl'] ) {
			$r['htmlUrl'] = $the_feed->get_link( 0 );
		}

		if ( ! $r['title'] ) {
			$r['title'] = $the_feed->get_title();
		}

		if ( ! $r['description'] ) {
			$r['description'] = $the_feed->get_description();
		}

		if ( ! $r['feed_author'] ) {
			$r['feed_author'] = $the_feed->get_author();
		}

		if ( ! $r['thumbnail'] ) {
			$r['thumbnail'] = $the_feed->get_image_url();
		}

		return $r;
	}

	/**
	 * A function to create a new feed entry.
	 *
	 * Arguments:
	 *      $htmlUrl = false, $type = false, $title = false, $tags = false, $thumbnail = false, $description = false, $added_by_user = false, $added_by_module = false
	 *
	 *  @param string $feed_url Feed URL.
	 *  @param array  $args     Arguments.
	 */
	public function create( $feed_url, $args = array() ) {
		$r = wp_parse_args(
			$args,
			array(
				'title'        => $feed_url,
				'url'          => $feed_url,
				'htmlUrl'      => false,
				'type'         => 'rss',
				'feed_url'     => $feed_url,
				'description'  => false,
				'feed_author'  => false,
				'feed_icon'    => false,
				'feed_folders' => null,
				'copyright'    => false,
				'thumbnail'    => false,
				'user_added'   => false,
				'post_parent'  => 0,
				'post_status'  => 'publish',
				'module_added' => 'rss-import',
				'tags'         => array(),
			)
		);

		pf_log( 'Creating a feed with the following parameters:' );
		pf_log( $r );

		if ( ! $r['user_added'] ) {
			$current_user    = wp_get_current_user();
			$r['user_added'] = $current_user->user_login;
		}

		$existing = false;
		if ( $this->has_feed( $feed_url ) ) {
			pf_log( 'This is an existing feed, and will be updated.' );
			$check    = $this->feed_post_setup( $r, 'update' );
			$existing = true;
		} else {
			pf_log( 'This is a new feed.' );
			$check = $this->feed_post_setup( $r );
		}

		pf_log( 'Feed creation result:' );
		pf_log( $check );

		if ( ! $check ) {
			return false;
		}

		$feed = Feed::get_instance_by_id( $check );
		if ( ! $feed ) {
			return false;
		}

		/*
		 * Schedule an initial health check for the feed.
		 *
		 * Health checks should come before the next scheduled retrieval.
		 * If a retrieval is scheduled, we will reschedule it for after the health check.
		 */
		$health_check_timestamp = time() + MINUTE_IN_SECONDS;
		$retrieval_timestamp    = $feed->get_next_scheduled_retrieval();

		if ( $retrieval_timestamp && $retrieval_timestamp < $health_check_timestamp ) {
			$new_retrieval_timestamp = $health_check_timestamp + ( wp_rand( 5, 30 ) * MINUTE_IN_SECONDS );
			$feed->schedule_retrieval(
				[
					'nextrun' => $new_retrieval_timestamp,
				]
			);
		}

		$feed->schedule_health_check(
			[
				'nextrun'     => $health_check_timestamp,
				'is_new_feed' => ! $existing,
			]
		);

		/**
		 * Fires after a feed is inserted into the database.
		 *
		 * @param int $check Feed ID.
		 */
		do_action( 'pf_feed_inserted', $check );

		return $check;
	}

	/**
	 * Gets a feed object corresponding to a URL.
	 *
	 * @param string $url URL.
	 * @return \WP_Post
	 */
	public function get_feed( $url ) {
		$posts = $this->has_feed( $url );
		return $posts[0];
	}

	/**
	 * A function to pull feeds from the database.
	 *
	 * @param array $args Arguments for the query.
	 * @return array
	 */
	public function get( $args = array() ) {
		if ( ! post_type_exists( 'pf_feed' ) ) {
			$this->register_feed_post_type();
		}

		$post_status   = array( 'publish', 'under_review', 'draft' );
		$post_status[] = pressforward( 'library.alertbox' )->status();

		$defaults = array(
			'post_type'        => $this->post_type,
			'post_status'      => $post_status,
			'suppress_filters' => false,
		);

		$query_filters = array();

		// WP_Query does not accept a 'guid' param, so we filter hackishly.
		if ( isset( $args['url'] ) ) {
			$parts = substr_count( $args['url'], '&' );

			if ( $parts > 0 ) {
				// Apparently WP query can't deal with more than one part in a URL query. So we need another way.
				$args['meta_key']   = 'feed_url';
				$args['meta_value'] = $args['url'];
			} else {
				$this->filter_data['guid'] = $args['url'];
				unset( $args['url'] );
				$query_filters['posts_where'][] = '_filter_where_guid';
			}
		}

		foreach ( $query_filters as $hook => $filters ) {
			foreach ( $filters as $f ) {
				add_filter( $hook, array( $this, $f ) );
			}
		}

		// Other WP_Query args pass through.
		$wp_args = wp_parse_args( $args, $defaults );

		$posts = get_posts( $wp_args );

		foreach ( $query_filters as $hook => $filters ) {
			foreach ( $filters as $f ) {
				remove_filter( $hook, array( $this, $f ) );
			}
		}

		// Fetch some handy pf-specific data.
		return $posts;
	}

	/**
	 * Check if a post or posts exists with get, if it does not  return false. If it does, return the array of posts.
	 *
	 * @param string $url URL.
	 * @return array|bool
	 */
	public function has_feed( $url ) {
		$parsed = wp_parse_url( $url );
		if ( ! isset( $parsed['scheme'] ) ) {
			$url = 'http://' . $url;
		}
		$posts = $this->get( array( 'url' => $url ) );
		pf_log( 'Checked for feed ' . $url );
		pf_log( $posts );
		if ( is_array( $posts ) && count( $posts ) > 0 ) {
			return $posts;
		} else {
			return false;
		}
	}

	/**
	 * A function to retrieve a list of feeds for walking, display.
	 *
	 * Specifically by type.
	 *
	 * @todo - This will be important for plugging in non-RSS modules.
	 *
	 * @param string $type Type.
	 * @param array  $args Arguments.
	 */
	public function get_feeds_by_type( $type = 'all', $args = array() ) {

		// Eventually we'll want to use the 'retrieve_by'
		// to allow devs to pass different types of queries
		// using the data passed in the argument.
		$r = wp_parse_args(
			$args,
			array(
				'title'        => false,
				'url'          => false,
				'htmlUrl'      => false,
				'type'         => $type,
				'description'  => false,
				'feed_author'  => false,
				'feed_icon'    => false,
				'copyright'    => false,
				'thumbnail'    => false,
				'user_added'   => false,
				'module_added' => false,
				'tags'         => array(),
				'page'         => false,
				'retrieve_by'  => false,
			)
		);
	}

	/**
	 * Updates a feed based on URL.
	 *
	 * When walking through the feedlist, if it is an old entry,
	 * call this function to renew the feed post with better data.
	 *
	 * @param string $url URL.
	 */
	public function update_url( $url ) {
		pf_log( 'Invoked: PF_Feeds_Schema::update_url' );
		$posts = $this->has_feed( $url );
		if ( ! $posts ) {
			$check = $this->create( $url );
			return $check;
		}

		$c = 0;
		foreach ( $posts as $the_post ) {
			$post_id = $the_post->ID;
			if ( is_numeric( $post_id ) ) {
				if ( 0 === $c ) {
					$feed_post        = get_post( $post_id, ARRAY_A );
					$feed_post['url'] = $url;
					$this->update( $post_id, $feed_post );
				} else {
					$url = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'feed_url', true );
					if ( $url ) {
						wp_delete_post( $post_id, true );
					}
				}

				++$c;
			} else {
				// Let's duplicate WordPress's mechanic of 'update' creating a new post if it doesn't exist.
				$id = $this->create( $url );
				return $id;
			}
		}
	}

	/**
	 * A function to update an existing feed CPT entry.
	 *
	 * @param int   $post_id ID of the post.
	 * @param array $args    Update args.
	 */
	public function update( $post_id, $args ) {
		pf_log( 'Invoked: PF_FEEDS_SCHEMA::update' );
		$r = wp_parse_args(
			$args,
			array(
				'ID'           => $post_id,
				'title'        => false,
				'url'          => false,
				'htmlUrl'      => false,
				'type'         => 'rss',
				'description'  => false,
				'feed_author'  => false,
				'feed_icon'    => false,
				'copyright'    => false,
				'thumbnail'    => false,
				'module_added' => 'rss-import',
				'tags'         => array(),
			)
		);

		if ( ! $r['url'] ) {
			$feed_url = get_the_guid( $post_id );
			if ( empty( $feed_url ) ) {
				return false;
			}
		} else {
			$feed_url = $r['url'];
		}

		if ( 'rss' === $r['type'] ) {
			$the_feed = pf_fetch_feed( $feed_url );
			if ( is_wp_error( $the_feed ) ) {
				return new \WP_Error( 'badfeed', __( 'The feed fails verification.', 'pressforward' ) );
			} else {
				$r = $this->setup_rss_meta( $r, $the_feed );
			}
		}

		$check = $this->feed_post_setup( $r, 'update' );
		return $check;
	}

	/**
	 * Updates feed.
	 *
	 * @todo What is the difference between this and the update() method above?
	 *
	 * @param int   $post_id ID of the post.
	 * @param array $args    Arguments.
	 */
	public function update_title( $post_id, $args ) {
		pf_log( 'Invoked: PF_FEEDS_SCHEMA::update' );
		$r = wp_parse_args(
			$args,
			array(
				'ID'           => $post_id,
				'title'        => false,
				'url'          => false,
				'htmlUrl'      => false,
				'type'         => 'rss',
				'description'  => false,
				'feed_author'  => false,
				'feed_icon'    => false,
				'copyright'    => false,
				'thumbnail'    => false,
				'module_added' => 'rss-import',
				'tags'         => array(),
			)
		);

		if ( ! $r['url'] ) {
			$feed_url = get_the_guid( $post_id );
			if ( empty( $feed_url ) ) {
				return false;
			}
		} else {
			$feed_url = $r['url'];
		}

		$check = $this->feed_post_setup_inital( $r, 'update' );

		return $check['ID'];
	}

	/**
	 * This function makes it easy to set the type of 'feed', which is important when we move to using something other than RSS.
	 *
	 * @param int    $id   ID of the feed.
	 * @param string $type Feed type.
	 */
	public function set_pf_feed_type( $id, $type = 'rss' ) {
		pf_log( 'Invoked: PF_Feed_Schema::set_pf_feed_type for ' . $id );
		$update_result = pressforward( 'controller.metas' )->update_pf_meta( $id, 'feed_type', $type );
		pf_log( 'Attempted to update to type ' . $type . ' with results of: ' );
		pf_log( $update_result );
		if ( is_wp_error( $update_result ) ) {
			return $update_result->get_error_message();
		} else {
			return true;
		}
	}

	/**
	 * Gets the feed type of a feed.
	 *
	 * @param int $id ID of the feed.
	 * @return string|false
	 */
	public function get_pf_feed_type( $id ) {
		pf_log( 'Invoked: PF_Feed_Schema::get_pf_feed_type(' . $id . ')' );
		$update_result = pressforward( 'controller.metas' )->get_post_pf_meta( $id, 'feed_type', true );
		if ( is_wp_error( $update_result ) ) {
			return $update_result->get_error_message();
		} elseif ( ! $update_result ) {
			return false;
		} else {
			return $update_result;
		}
	}

	/**
	 * This function processes the meta data passed to the create or update feed object and turns it into post_meta.
	 *
	 * Note that the goal with all feed meta is to describe it in a way as similar
	 * to OPML as possible for accurate output later.
	 *
	 * @param int   $post_id ID of the post.
	 * @param array $args    Arguments.
	 */
	public function set_feed_meta( $post_id, $args ) {
		pf_log( 'Invoked: PF_Feeds_Schema::set_feed_meta' );
		$c = 1;
		foreach ( $args as $k => $a ) {
			pf_log( 'Setting ' . $post_id . ' Feed Meta: ' . $k . ' - ' . $a );
			if ( $a ) {
				pressforward( 'controller.metas' )->update_pf_meta( $post_id, $k, $a );
			}
			++$c;
		}

		if ( ( $c + 1 ) === count( $args ) ) {
			pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_meta_data_check', 'complete' );
		}
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
	 * Filter callback to modify alert status to 'publish'.
	 *
	 * @param array $status_data Status data.
	 * @return array
	 */
	public function make_alert_return_to_publish( $status_data ) {
		if ( ( ! empty( $status_data['type'] ) ) && ( $this->post_type === $status_data['type'] ) ) {
			$status_data['status'] = 'publish';
		}
		return $status_data;
	}

	/**
	 * Gets a message to display after feed save.
	 *
	 * @param array $messages Messages to be displayed.
	 * @return array
	 */
	public function feed_save_message( $messages ) {
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		$messages[ $this->post_type ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Feed updated.', 'pressforward' ),
			2  => __( 'Custom field updated.', 'pressforward' ),
			3  => __( 'Custom field deleted.', 'pressforward' ),
			4  => __( 'Feed updated.', 'pressforward' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Feed restored to revision from %s', 'pressforward' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'The feed was made successfully active.', 'pressforward' ),
			7  => __( 'The feed was saved successfully.', 'pressforward' ),
			8  => __( 'Feed submitted.', 'pressforward' ),
			9  => sprintf(
				// translators: Publish box date format, see http://php.net/date.
				__( 'Feed scheduled for: <strong>%1$s</strong>.', 'pressforward' ),
				date_i18n( __( 'M j, Y @ G:i', 'pressforward' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Feed draft updated.', 'pressforward' ),
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link                        = ' ';
			$messages[ $this->post_type ][1] .= $view_link;
			$messages[ $this->post_type ][6] .= $view_link;
			$messages[ $this->post_type ][9] .= $view_link;

			$preview_permalink                 = add_query_arg( 'preview', 'true', $permalink );
			$preview_link                      = ' ';
			$messages[ $this->post_type ][8]  .= $preview_link;
			$messages[ $this->post_type ][10] .= $preview_link;
		}

		return $messages;
	}
}
