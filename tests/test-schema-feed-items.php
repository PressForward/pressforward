<?php

/**
 * @group Feed_Items
 */
class PF_Tests_Schema_Feed_Items extends PF_UnitTestCase {
	public function test_get_existing_items_matching_item_id_match() {
		$feed_item_wp_post_id = $this->factory->feed_item->create();

		$feed_item_item_id = pressforward('controller.metas')->get_post_pf_meta( $feed_item_wp_post_id, 'item_id' );

		$feed_item_post = get_post( $feed_item_wp_post_id );

		$expected = [
			$feed_item_post,
		];

		// For compat with legacy query, we can't check the post objects in their entirety.
		$found = pressforward( 'schema.feed_item' )::get_existing_items_matching_item_id( $feed_item_item_id );

		$this->assertSame( count( $expected ), count( $found ) );
		$this->assertEquals( $expected[0]->ID, $found[0]->ID );
	}

	public function test_get_existing_items_matching_item_id_no_match() {
		$feed_item_wp_post_id = $this->factory->feed_item->create();

		// For compat with legacy query, we can't check the post objects in their entirety.
		$found = pressforward( 'schema.feed_item' )::get_existing_items_matching_item_id( 12345 );

		$this->assertSame( 0, count( $found ) );
	}

	public function test_get_existing_items_matching_item_link_match() {
		$link = 'https://example.com/foo/';

		$feed_item_wp_post_id = $this->factory->feed_item->create( [ 'item_link' => $link ] );

		$feed_item_item_link = pressforward('controller.metas')->get_post_pf_meta( $feed_item_wp_post_id, 'item_link' );

		$this->assertSame( $link, $feed_item_item_link );

		$feed_item_post = get_post( $feed_item_wp_post_id );

		$expected = [
			$feed_item_post,
		];

		// For compat with legacy query, we can't check the post objects in their entirety.
		$found = pressforward( 'schema.feed_item' )::get_existing_items_matching_item_link( $link );

		$this->assertSame( count( $expected ), count( $found ) );
		$this->assertEquals( $expected[0]->ID, $found[0]->ID );
	}

	public function test_get_existing_items_matching_item_link_no_match() {
		$link = 'https://example.com/foo/';

		$feed_item_wp_post_id = $this->factory->feed_item->create( [ 'item_link' => $link ] );

		// For compat with legacy query, we can't check the post objects in their entirety.
		$found = pressforward( 'schema.feed_item' )::get_existing_items_matching_item_link( 'https://example.com/bar/' );

		$this->assertSame( 0, count( $found ) );
	}
}
