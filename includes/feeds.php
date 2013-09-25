<?php 

/**
 * Feeds data schema
 *
 * The RSS Import module stores several different types of data. The schemas
 * are described below.
 *
 * (1) FEEDS
 *     Feeds are stored in a custom post type ('pf_feed'). Data
 *     is mapped as follows:
 *
 *     [data type]       -> [WP location]
 *     feed title   	 -> wp_posts.post_title
 *     feed notes 		 -> wp_posts.post_content
 *     xmlUrl        	 -> wp_postmeta ('pf_feed_xmlUrl')
 *     htmlUrl           -> wp_postmeta ('pf_feed_htmlUrl') 
 *     type           	 -> wp_postmeta ('pf_feed_type') 
 *     created           -> wp_posts.post_date_gmt
 */
 
class PF_Feeds_Schema {
	var $feed_post_type;
	var $feed_tag_taxonomy;

	public function init() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}	
	
	public function __construct() {
		$this->feed_post_type = 'pf_feed';
		$this->feed_tag_taxonomy = 'pf_feed_category';

		// Post types and taxonomies must be registered after 'init'
		add_action( 'init', array( $this, 'register_feed_post_type' ) );
		add_action( 'pf_feed_post_type_registered', array( $this, 'register_feed_tag_taxonomy' ) );
	
	}
	
	/**
	 * Feed items are stored in a CPT, which is registered here
	 */
	public function register_feed_item_post_type() {
		$labels = array(
			'name'               => __( 'Feeds', 'pf' ),
			'singular_name'      => __( 'Feed', 'pf' ),
			'add_new'            => _x( 'Add New', 'pf', 'add new feed' ),
			'all_items'          => __( 'All Feeds', 'pf' ),
			'add_new_item'       => __( 'Add New Feed', 'pf' ),
			'edit_item'          => __( 'Edit Feed', 'pf' ),
			'new_item'           => __( 'New Feed', 'pf' ),
			'view_item'          => __( 'View Feed', 'pf' ),
			'search_items'       => __( 'Search Feeds', 'pf' ),
			'not_found'          => __( 'No feeds found', 'pf' ),
			'not_found_in_trash' => __( 'No feeds found in trash', 'pf' ),
		);

		register_post_type( $this->feed_item_post_type, apply_filters( 'pf_register_feed_post_type_args', array(
			'label'       => $labels['name'],
			'labels'      => $labels,
			'description' => __( 'Feeds imported by PressForward&#8217;s Feed Importer', 'pf' ),
			'public'      => false,
			'show_ui'     => false, // for testing only
		) ) );

		do_action( 'pf_feed_item_post_type_registered' );
	}

	public function register_feed_tag_taxonomy() {
		$labels = array(
			'name'          => __( 'Feed Tags', 'pf' ),
			'singular_name' => __( 'Feed Tag', 'pf' ),
			'all_items'     => __( 'All Feed Tags', 'pf' ),
			'edit_item'     => __( 'Edit Feed Tag', 'pf' ),
			'update_item'   => __( 'Update Feed Tag', 'pf' ),
			'add_new_item'  => __( 'Add New Feed Tag', 'pf' ),
			'new_item_name' => __( 'New Feed Tag', 'pf' ),
			'search_items'  => __( 'Search Feed Tags', 'pf' ),
		);

		register_taxonomy( $this->feed_tag_taxonomy, $this->feed_post_type, apply_filters( 'pf_register_feed_tag_taxonomy_args', array(
			'labels' => $labels,
			'public' => true,
			'show_admin_columns' => true,
			'rewrite' => false,
		) ) );
	}
	
}