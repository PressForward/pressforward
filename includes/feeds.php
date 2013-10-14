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
	var $post_type;
	var $tag_taxonomy;

	public function init() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}	
	
	public function __construct() {
		$this->post_type = 'pf_feed';
		$this->tag_taxonomy = 'pf_feed_category';

		// Post types and taxonomies must be registered after 'init'
		add_action( 'init', array( $this, 'register_feed_post_type' ) );
		#add_action('admin_init', array($this, 'deal_with_old_feedlists') );
		add_action( 'pf_feed_post_type_registered', array( $this, 'register_feed_tag_taxonomy' ) );
	
	}
	
	/**
	 * Feed items are stored in a CPT, which is registered here
	 */
	public function register_feed_post_type() {
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

		register_post_type( $this->post_type, apply_filters( 'pf_register_feed_post_type_args', array(
			'label'       => $labels['name'],
			'labels'      => $labels,
			'description' => __( 'Feeds imported by PressForward&#8217;s Feed Importer', 'pf' ),
			'public'      => false,
			'hierarchical' => true,
			'supports' 	=> array('title','editor','author','thumbnail','excerpt','custom-fields','page-attributes'),
			'taxonomies' => array('post_tag'),
			'show_ui'     => true, // for testing only
		) ) );

		do_action( 'pf_feed_post_type_registered' );
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

		register_taxonomy( $this->tag_taxonomy, $this->post_type, apply_filters( 'pf_register_feed_tag_taxonomy_args', array(
			'labels' => $labels,
			'public' => true,
			'show_admin_columns' => true,
			'rewrite' => false
		) ) );
	}
	
	public function get( $args = array() ) {
		$wp_args = array(
			'post_type'        => $this->post_type,
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);

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

	}
	
	public function deal_with_old_feedlists() {
		
		if ( false == (get_option( PF_SLUG . '_feedlist' )) ){
			return true;
		} else {
			$feedlist = get_option( PF_SLUG . '_feedlist' );
		}
		$all_feeds_array = apply_filters( 'imported_rss_feeds', $feedlist );
		pf_log('Preparing to transform feedlist into a list of feed posts.');
		$ordered_all_feeds_array = array_values($all_feeds_array);
		$tidy_all_feeds_array = array_filter( $ordered_all_feeds_array, 'strlen' );
		foreach ($tidy_all_feeds_array as $key => $feed){
			$this->progressive_feedlist_transformer($tidy_all_feeds_array, $feed, $key);
		}
		
	}
	
	# Not only is this moving feeds over into feed CPT posts, but this methodology will insure a time-out won't force the process to restart.
	# There should probably be a AJAX interface for this, same as the AB subscribe method. 
	public function progressive_feedlist_transformer($feedlist, $xmlUrl, $key) {
		$check = self::create($xmlUrl, array('type' => 'rss-old'));
		if ($check){
			unset($feedlist[$key]);
			update_option( PF_SLUG . '_feedlist', $feedlist );
		}
	}
	
	public function feed_post_setup($r, $insert_type = 'insert'){
		
		foreach ($r as $k=>$e){
			if (!$e)
				$r[$k] = '';
		}
		
		$wp_args = array(
			'post_type' 	=> $this->post_type,
			'post_status' 	=> 'publish',
			'post_title'	=> $r['title'],
			'post_content'	=> $r['description'],
			'tax_input' 	=> array($this->tag_taxonomy => $r['tags'])
		);
		
		if ($insert_type == 'insert'){
			$wp_args['guid'] = $r['url'];
			$wp_args['post_date'] = date( 'Y-m-d H:i:s', time());
			$post_id = wp_insert_post($wp_args);
		} elseif ($insert_type == 'update') {
			$wp_args['ID'] = $r['ID'];
			wp_update_post( $wp_args );
		}
		if ( is_numeric($post_id) ){
			self::set_pf_feed_type($post_id, $r['type']);
			foreach ($r as $k=>$a){
				if ($k == ('title'||'description'||'url'||'tags'||'type'))
					unset($r[$k]);
			}
			self::set_feed_meta($post_id, $r);
			return true;
		} else {
			return false;
		}	
	}
	
	public function setup_rss_meta($r, $theFeed){
	
		if(empty($r)){
			
		} else {
			if (!$r['htmlUrl']){
				$r['htmlUrl'] = $theFeed->get_permalink();
			}
			if (!$r['title']){
				$r['title'] = $theFeed->get_title();
			}
			if (!$r['description']){
				$r['description'] = $theFeed->get_description();
			}
			if (!$r['feed_icon']){
				$r['feed_icon'] = $theFeed->get_favicon();
			}	
			if (!$r['feed_author']){
				$r['feed_author'] = $theFeed->get_author();
			}	
			if (!$r['thumbnail']){
				$r['thumbnail'] = $theFeed->get_image_url();
			}				
			if (empty($r['tags'])){
				$r['tags'] = $theFeed->get_feed_tags();
			}
		}
		return $r;
	
	}
	
	/*
	 * Arguments:
	 * 		$htmlUrl = false, $type = false, $title = false, $tags = false, $thumbnail = false, $description = false, $added_by_user = false, $added_by_module = false 
	 */
	
	public function create($feedUrl, $args = array()){
	
		$r = wp_parse_args( $args, array(
			'title'   		=> false,
			'url'     		=> $feedURL,
			'htmlUrl' 		=> false,
			'type'	  		=> 'rss',
			'description' 	=> false,
			'feed_author' 	=> false,
			'feed_icon'  	=> false,
			'copyright'		=> false,			
			'thumbnail'  	=> false,
			'user_added'    => false,
			'module_added' 	=> 'rss-import',
			'tags'    => array(),
		) );
		
		if ($r['type'] == 'rss'){
		
			if (is_wp_error($theFeed = fetch_feed($feedURL))){
				return new WP_Error('badfeed', __('The feed fails verification.'));
			} else {
				$r = self::setup_rss_meta($r, $theFeed);
			}		
		}
		if (!$r['user_added']){
			$current_user = wp_get_current_user();
			$r['user_added'] = $current_user->user_login;
		}
		if ($r['type'] == 'rss-old'){
			$r['title'] = $r['url'];
		}
		
		self::feed_post_setup($r);
		

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


		return $posts;
	}	
	
	# When walking through the feedlist, if it is an old entry,
	# call this function to renew the feed post with better data.
	public function update_url($url){
		$posts = self::get(array('url' => $url));
		$c = 0;
		foreach ($posts as $post){
			setup_postdata($post);
			$post_id = get_the_ID();
			if ($c == 0){
				self::update($post_id, array('url' => $url));
			} else {
				if ($url == get_the_guid($post_id)){
					wp_delete_post( $post_id, true );
				}
			}
			$c++;
		}
		wp_reset_postdata();
	}
	
	public function update($post_id, $args){
		$r = wp_parse_args( $args, array(
			'ID'			=> $post_id,
			'title'   		=> false,
			'url'     		=> false,
			'htmlUrl' 		=> false,
			'type'	  		=> 'rss',
			'description' 	=> false,
			'feed_author' 	=> false,
			'feed_icon'  	=> false,
			'copyright'		=> false,			
			'thumbnail'  	=> false,
			'module_added' 	=> 'rss-import',
			'tags'    		=> array(),
		) );
		if (!$r['url']){
			return false;
		}
		if ($r['type'] == 'rss'){
		
			if (is_wp_error($theFeed = fetch_feed($feedURL))){
				return new WP_Error('badfeed', __('The feed fails verification.'));
			} else {
				$r = self::setup_rss_meta($r, $theFeed);
			}		
		}
		
		self::feed_post_setup($r);

		
	}
	
	# This function makes it easy to set the type of 'feed', which is important when we move to using something other than RSS.
	
	public function set_pf_feed_type($id, $type = "rss") {
	
		$updateResult = update_post_meta($id, 'feed_type', $type);
		
		if (is_wp_error($updateResult)){
			return $updateResult->get_error_message();
		} else {
			return true;
		}
	
	}
	
	public function set_feed_meta($post_id, $args){
		$c = 1;
		foreach ($args as $k=>$a){
		
			if(!$a){
	
			} else {
				update_post_meta($post_id, $k, $a);
			}
			$c++;
		
		}
		
		if ($c >= count($args)){
			update_post_meta($post_id, 'meta_data', 'complete');

		}
	}
	
	public function _filter_where_guid( $where ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.guid = %s ", $this->filter_data['guid'] );
		return $where;
	}	
	
}