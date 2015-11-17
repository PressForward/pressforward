<?php
class PF_Subscribed_Feeds {

	public static function init() {
		static $instance;

		if ( ! is_a( $instance, 'PF_Subscribed_Feeds' ) ) {
			$instance = new self();
		}

		return $instance;
	}

	private function __construct() {
		#add_filter( 'manage_pf_feed_posts_columns', array($this, 'manage_feeds_titles') );
		#add_filter( 'manage_pf_feed_posts_column', array($this, 'manage_feeds_titles') );
		#add_filter( 'manage_posts_custom_columns', array($this, 'manage_feeds_titles') );
		#add_filter( 'manage_posts_columns', array( $this, 'manage_feed_titles' ) );
		#add_filter( 'manage_pf_feed_posts_custom_columns', array( $this, 'manage_feed_titles' ) );
		add_filter( 'manage_posts_custom_columns', array( $this, 'manage_feed_titles' ), 10, 2 );
	}

	public function manage_feed_titles( $column_name = '', $post_id = 0 ){
		#@trigger_error($columns, E_USER_NOTICE);
		#var_dump($columns); die();
		#return array_merge( $columns,
        #      array('sticky' => __('Sticky')) );
        #if ( 'title' == $column_name ){
		#	echo 'Stufff' ;
		#}
		#
		pf_log($column_name);
		return $column_name;
	}



}
