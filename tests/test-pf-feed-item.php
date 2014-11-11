<?php

/**
 * @group PF_Feed_Item
 */
class PF_Tests_Feed_Item extends PF_UnitTestCase {
	public function test_archive_feed_to_display_translate_params() {
		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found1 = PF_Feed_Item::archive_feed_to_display( 5, 7, 5050, true, false );
		$found2 = PF_Feed_Item::archive_feed_to_display( array(
			'start' => 5,
			'posts_per_page' => 7,
			'from_unix_time' => 5050,
			'no_limit' => true,
			'relationship' => false,
		) );

		$this->assertSame( $found1, $found2 );
	}

	public function test_archive_feed_to_display_limitless() {
		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 0, 20, 0, true );

		// Expect newest first.
		$expected = array_reverse( $feed_items );

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );
	}

	public function test_archive_feed_to_display_starred() {
		$old_current_user = get_current_user_id();
		$u = $this->factory->user->create();
		wp_set_current_user( $u );

		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		// Set stars on two of the items.
		foreach ( array( 1, 3 ) as $key ) {
			$this->factory->relationship->create( array(
				'user_id' => $u,
				'item_id' => $feed_items[ $key ],
				'type'    => 'star',
				'value'   => '1',
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 1, 20, 0, false, 'starred' );

		$expected = array(
			$feed_items[3],
			$feed_items[1],
		);

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );

		wp_set_current_user( $old_current_user );
	}

	public function test_archive_feed_to_display_nominated() {
		$old_current_user = get_current_user_id();
		$u = $this->factory->user->create();
		wp_set_current_user( $u );

		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		// Set nominate on two of the items.
		foreach ( array( 1, 3 ) as $key ) {
			$this->factory->relationship->create( array(
				'user_id' => $u,
				'item_id' => $feed_items[ $key ],
				'type'    => 'nominate',
				'value'   => '1',
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 1, 20, 0, false, 'nominated' );

		$expected = array(
			$feed_items[3],
			$feed_items[1],
		);

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );

		wp_set_current_user( $old_current_user );
	}

	public function test_archive_feed_to_display_search_terms() {
		$old_current_user = get_current_user_id();
		$u = $this->factory->user->create();
		wp_set_current_user( $u );

		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'item_content' => 'This is content ' . $i,
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( array(
			'start' => 1,
			'search_terms' => 'content 3',
		) );

		$expected = array(
			$feed_items[3],
		);

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );

		wp_set_current_user( $old_current_user );
	}

	public function test_archive_feed_to_display_search_terms_when_items_are_archived() {
		$old_current_user = get_current_user_id();
		$u = $this->factory->user->create();
		wp_set_current_user( $u );

		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'item_content' => 'This is content ' . $i,
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		// Set archive on all of the items.
		foreach ( $feed_items as $feed_item ) {
			$this->factory->relationship->create( array(
				'user_id' => $u,
				'item_id' => $feed_item,
				'type'    => 'archive',
				'value'   => '1',
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( array(
			'start' => 1,
			'search_terms' => 'content 3',
			'exclude_archived' => true,
		) );

		$this->assertEmpty( $found );

		wp_set_current_user( $old_current_user );
	}

	public function test_archive_feed_to_display_reveal_no_hidden() {
		// Fake it.
		$old_get = $_GET;
		$_GET['reveal'] = 'no_hidden';

		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 1 );
		$expected = array_reverse( $feed_items );

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );

		$_GET = $old_get;
	}

	public function test_archive_feed_to_display_non_logged_in_no_reveal() {
		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 1 );
		$expected = array_reverse( $feed_items );

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );
	}

	public function test_archive_feed_to_display_pageTop() {
		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 3 );

		$expected = array(
			$feed_items[3],
			$feed_items[2],
			$feed_items[1],
			$feed_items[0],
		);

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );
	}

	public function test_archive_feed_to_display_pagefull() {
		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 1, 3 );

		$expected = array(
			$feed_items[5],
			$feed_items[4],
			$feed_items[3],
		);

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );
	}

	public function test_archive_feed_to_display_fromUnixTime() {
		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = PF_Feed_Item::archive_feed_to_display( 1, 20, 25000 );

		$expected = array(
			$feed_items[5],
			$feed_items[4],
			$feed_items[3],
			$feed_items[2],
		);

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );
	}

	public function test_archive_feed_to_display_return_format() {
		$feed_id = $this->factory->feed->create();
		$time = time();
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => 'Test item',
			'item_link' => 'Test link',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
		) );

		$found = PF_Feed_Item::archive_feed_to_display( 1 );

		$expected = array(
			'item_title' => 'Test item',
			'source_title' => 'Test source title',
			'item_date' => '20000',
			'item_author' => 'foo',
			'item_content' => 'Test content',
			'item_link' => 'Test link',
			'item_feat_img' => 'Test feat img',
			'item_id' => '',
			'item_wp_date' => (string) $time,
			'item_tags' => '',
			'item_added_date' => date( 'o-m-d\TH:i:sO', $time ),
			'source_repeat' => '',
			'post_id' => $feed_item_id,
			'readable_status' => '',
		);

		// Check each key individually because format may change in the future.
		$this->assertNotEmpty( $found['rss_archive_0'] );

		$keys = array(
			'item_title',
			'source_title',
			'item_date',
			'item_author',
			'item_content',
			'item_link',
			'item_feat_img',
			'item_id',
			'item_wp_date',
			'item_tags',
			'item_added_date',
			'source_repeat',
			'post_id',
			'readable_status',
		);

		foreach ( $keys as $key ) {
			$this->assertEquals( $expected[ $key ], $found['rss_archive_0'][ $key ] );
		}
	}
}
