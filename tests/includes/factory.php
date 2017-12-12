<?php

class PF_UnitTest_Factory extends WP_UnitTest_Factory {
	public $activity = null;

	function __construct() {
		parent::__construct();

		$this->relationship = new PF_UnitTest_Factory_For_Relationship( $this );
		$this->feed = new PF_UnitTest_Factory_For_Feed( $this );
		$this->feed_item = new PF_UnitTest_Factory_For_Feed_Item( $this );
		$this->nomination = new PF_UnitTest_Factory_For_Nomination( $this );
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
		if ( ! isset( $args['user_id'] ) ) {
			$args['user_id'] = get_current_user_id(); }

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
		return pressforward( 'schema.feeds' )->feed_post_setup( $args );
	}

	function update_object( $activity_id, $fields ) {}

	function get_object_by_id( $item_id ) {}
}

class PF_UnitTest_Factory_For_Feed_Item extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'item_title' => new WP_UnitTest_Generator_Sequence( 'Feed Item Title %s' ),
			'item_link' => new WP_UnitTest_Generator_Sequence( 'Feed Item link %s' ),
			'item_content' => new WP_UnitTest_Generator_Sequence( 'Feed Item content %s' ),
			'source_title' => new WP_UnitTest_Generator_Sequence( 'Feed Item source_title %s' ),
			'item_wp_date' => time(),
			'sortable_item_date' => time(),
		);
	}

	function create_object( $args ) {
		$feed_item_id = pressforward( 'schema.feed_item' )->create( $args );

		$meta_keys = array(
			'item_id',
			'source_title',
			'item_date',
			'item_author',
			'item_link',
			'item_feat_img',
			'item_wp_date',
			'sortable_item_date',
			'item_tags',
			'source_repeat',
			'revertible_feed_text',
		);

		foreach ( $meta_keys as $mk ) {
			if ( isset( $args[ $mk ] ) ) {
				pressforward( 'controller.metas' )->update_pf_meta( $feed_item_id, $mk, $args[ $mk ] );
			}
		}

		return $feed_item_id;
	}

	function update_object( $activity_id, $fields ) {}

	function get_object_by_id( $item_id ) {}
}

class PF_UnitTest_Factory_For_Nomination extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'item_title' => new WP_UnitTest_Generator_Sequence( 'Nomination Item Title %s' ),
			'item_link' => new WP_UnitTest_Generator_Sequence( 'Nomination Item link %s' ),
			'item_content' => new WP_UnitTest_Generator_Sequence( 'Nomination Item content %s' ),
			'source_title' => new WP_UnitTest_Generator_Sequence( 'Nomination Item source_title %s' ),
			'item_wp_date' => time(),
			'sortable_item_date' => time(),
		);
	}

	function create_object( $args ) {
		$feed_item_id = pressforward( 'schema.feed_item' )->create( $args );

		$meta_keys = array(
			'item_id',
			'source_title',
			'item_date',
			'item_author',
			'item_link',
			'item_feat_img',
			'item_wp_date',
			'sortable_item_date',
			'item_tags',
			'source_repeat',
			'revertible_feed_text',
		);

		foreach ( $meta_keys as $mk ) {
			if ( isset( $args[ $mk ] ) ) {
				pressforward( 'controller.metas' )->update_pf_meta( $feed_item_id, $mk, $args[ $mk ] );
			}
		}

		return $feed_item_id;
	}

	function update_object( $activity_id, $fields ) {}

	function get_object_by_id( $item_id ) {}
}
