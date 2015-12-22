<?php

/**
 * Feed item data schema
 *
 * The RSS Import module stores several different types of data. The schemas
 * are described below.
 *
 * (1) FEED ITEMS
 *     Feed items are stored in a custom post type ('pf_feed_item'). Data
 *     is mapped as follows:
 *
 *     [data type]       -> [WP location]
 *     feed item title   -> wp_posts.post_title
 *     feed item URL     -> wp_posts.guid
 *     feed item content -> wp_posts.post_content
 *     word count        -> wp_postmeta ('pf_feed_item_word_count')
 *     source feed       -> wp_postmeta ('pf_feed_item_source') May move to post_parent if feeds move to CPT
 *     date              -> wp_posts.post_date_gmt
 */
class PF_Feed_Item_Schema {
	var $feed_item_post_type;
	var $feed_item_tag_taxonomy;

	public function init() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}

	public function __construct() {
		$this->feed_item_post_type = 'pf_feed_item';
		$this->feed_item_tag_taxonomy = 'pf_feed_item_tag';

		// Post types and taxonomies must be registered after 'init'
		add_action( 'init', array( $this, 'register_feed_item_post_type' ) );
		add_action( 'pf_feed_item_post_type_registered', array( $this, 'register_feed_item_tag_taxonomy' ) );

		// Maybe install custom table for relationships
		add_action( 'admin_init', array( $this, 'maybe_install_relationship_table' ) );

		add_filter('user_has_cap', array( $this, 'alter_cap_on_fly' ) );
		add_filter( 'map_meta_cap', array( $this, 'feeds_item_map_meta_cap'), 10, 4 );
	}

	/**
	 * Feed items are stored in a CPT, which is registered here
	 */
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

		register_post_type( $this->feed_item_post_type, apply_filters( 'pf_register_feed_item_post_type_args', array(
			'label'       => $labels['name'],
			'labels'      => $labels,
			'description' => __( 'Feed items imported by PressForward&#8217;s RSS Importer', 'pf' ),
			'public'      => false,
			'show_ui'     => true, // for testing only
			'show_in_admin_bar' => false,
			'show_ui'     => true, // for testing only
			'capability_type' => $this->feed_item_post_type,
			'capabilities' => $this->map_feed_item_caps()
		) ) );

		do_action( 'pf_feed_item_post_type_registered' );
	}

	public function map_feed_item_caps(){
		return array(
			'publish_posts' => 'publish_'.$this->feed_item_post_type.'s',
			'edit_posts' => 'edit_'.$this->feed_item_post_type.'s',
			'edit_others_posts' => 'edit_others_'.$this->feed_item_post_type.'s',
			'delete_posts' => 'delete_'.$this->feed_item_post_type.'s',
			'delete_others_posts' => 'delete_others_'.$this->feed_item_post_type.'s',
			'read_private_posts' => 'read_private_'.$this->feed_item_post_type.'s',
			'publish_pages' => 'publish_'.$this->feed_item_post_type.'s',
			'edit_pages' => 'edit_'.$this->feed_item_post_type.'s',
			'edit_others_pages' => 'edit_others_'.$this->feed_item_post_type.'s',
			'delete_pages' => 'delete_'.$this->feed_item_post_type.'s',
			'delete_others_pages' => 'delete_others_'.$this->feed_item_post_type.'s',
			'read_private_pages' => 'read_private_'.$this->feed_item_post_type.'s',
			'edit_post' => 'edit_'.$this->feed_item_post_type,
			'delete_post' => 'delete_'.$this->feed_item_post_type,
			'read_post' => 'read_'.$this->feed_item_post_type,
			'edit_page' => 'edit_'.$this->feed_item_post_type,
			'delete_page' => 'delete_'.$this->feed_item_post_type,
			'read_page' => 'read_'.$this->feed_item_post_type,
		);
	}


	function alter_cap_on_fly( $caps ){

		foreach ($this->map_feed_item_caps() as $core_cap => $cap){
			if (! empty( $caps[$core_cap] ) ) { // user has edit capabilities
				$caps[$cap] = true;
			}
		}
		return $caps;
	}

	function feeds_item_map_meta_cap( $caps, $cap, $user_id, $args ) {
		if (  empty($args) ){
			return $caps;
		}
		/* If editing, deleting, or reading a feed, get the post and post type object. */
		if ( 'edit_'.$this->feed_item_post_type == $cap || 'delete_'.$this->feed_item_post_type == $cap || 'read_'.$this->feed_item_post_type == $cap ) {
			$post = get_post( $args[0] );
			$post_type = get_post_type_object( $post->feed_item_post_type );

			/* Set an empty array for the caps. */
			$caps = array();
		}

		/* If editing a feed, assign the required capability. */
		if ( 'edit_'.$this->feed_item_post_type == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->edit_posts;
			else
				$caps[] = $post_type->cap->edit_others_posts;
		}

		/* If deleting a feed, assign the required capability. */
		elseif ( 'delete_'.$this->feed_item_post_type == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->delete_posts;
			else
				$caps[] = $post_type->cap->delete_others_posts;
		}

		/* If reading a private feed, assign the required capability. */
		elseif ( 'read_'.$this->feed_item_post_type == $cap ) {

			if ( 'private' != $post->post_status )
				$caps[] = 'read';
			elseif ( $user_id == $post->post_author )
				$caps[] = 'read';
			else
				$caps[] = $post_type->cap->read_private_posts;
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

		register_taxonomy( $this->feed_item_tag_taxonomy, $this->feed_item_post_type, apply_filters( 'pf_register_feed_item_tag_taxonomy_args', array(
			'labels' => $labels,
			'public' => true,
			'show_admin_columns' => true,
			'rewrite' => false,
		) ) );
	}

	public function register_folders_for_items(){
		#add_action( 'plugins_loaded', pressforward()->nominations->post_type );
		#register_taxonomy_for_object_type( pressforward()->nominations->post_type, 'post_type_name');
	}

	/**
	 * Checks to see whether the relationship table needs to be installed, and installs if so
	 *
	 * A regular activation hook won't work correctly given where how
	 * this file is loaded. Might change this in the future
	 */
	public function maybe_install_relationship_table() {
		if ( ! is_super_admin() ) {
			return;
		}

		global $wpdb;
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . 'pf_relationships' ) );

		if ( ! $table_exists ) {
			self::install_relationship_table();
		}
	}

	/**
	 * Defines the relationship table schema and runs dbDelta() on it
	 */
	public static function install_relationship_table() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pf_relationships (
	                    id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		            user_id bigint(20) NOT NULL,
		            item_id bigint(20) NOT NULL,
			    relationship_type smallint(5) NOT NULL,
			    value varchar(255),

			    KEY user_id (user_id),
			    KEY item_id (item_id),
			    KEY relationship_type (relationship_type)
			)";

		dbDelta( $sql );
	}
}
