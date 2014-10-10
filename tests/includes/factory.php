<?php

class PF_UnitTest_Factory extends WP_UnitTest_Factory {
	public $activity = null;

	function __construct() {
		parent::__construct();

		$this->relationship = new PF_UnitTest_Factory_For_Relationship( $this );
		$this->feed = new PF_UnitTest_Factory_For_Feed( $this );
		$this->feed_item = new PF_UnitTest_Factory_For_Feed_Item( $this );
	}
}

class PF_UnitTest_Factory_For_Relationship extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'user_id' => 0,
			'item_id' => 0,
			'type'    => 'star',
			'value'   => '',
		);
	}

	function create_object( $args ) {
		if ( ! isset( $args['user_id'] ) )
			$args['user_id'] = get_current_user_id();

		return pf_set_relationship( $args['type'], $args['item_id'], $args['user_id'], $args['value'] );
	}

	function update_object( $activity_id, $fields ) {}

	function get_object_by_id( $feed_id ) {}
}

class PF_UnitTest_Factory_For_Feed extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'title' => new WP_UnitTest_Generator_Sequence( 'Feed Title %s' ),
			'url' => new WP_UnitTest_Generator_Sequence( 'Feed URL %s' ),
			'description' => new WP_UnitTest_Generator_Sequence( 'Feed Item description %s' ),
			'tags' => '',
			'type' => 'rss',
		);
	}

	function create_object( $args ) {
		$pf_feed_schema = new PF_Feeds_Schema();
		return $pf_feed_schema->feed_post_setup( $args );
	}

	function update_object( $activity_id, $fields ) {}

	function get_object_by_id( $item_id ) {}
}

class PF_UnitTest_Factory_For_Feed_Item extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'item_title' => new WP_UnitTest_Generator_Sequence( 'Feed Item Title %s' ),
			'item_title' => new WP_UnitTest_Generator_Sequence( 'Feed Item link %s' ),
			'item_content' => new WP_UnitTest_Generator_Sequence( 'Feed Item content %s' ),
			'source_title' => new WP_UnitTest_Generator_Sequence( 'Feed Item source_title %s' ),
			'item_wp_date' => time(),
		);
	}

	function create_object( $args ) {
		$feed_item = new PF_Feed_Item();
		return $feed_item->create( $args );
	}

	function update_object( $activity_id, $fields ) {}

	function get_object_by_id( $item_id ) {}
}
