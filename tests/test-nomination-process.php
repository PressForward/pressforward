<?php

/**
 * @group PF_Nomination
 */
class PF_Tests_Nomination_Process extends PF_UnitTestCase {
	public function test_archive_feed_to_display_fromUnixTime() {
		$feed_id = $this->factory->feed->create();

		$feed_items = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$feed_items[ $i ] = $this->factory->feed_item->create( array(
				'sortable_item_date' => 10000 * ( $i + 1 ), // no zeroes
			) );
		}

		$found = pressforward( 'controller.loops' )->archive_feed_to_display( 1, 20, 25000 );

		$expected = array(
			$feed_items[5],
			$feed_items[4],
			$feed_items[3],
			$feed_items[2],
		);

		$this->assertEquals( $expected, array_values( wp_list_pluck( $found, 'post_id' ) ) );
	}

	public function test_feed_item_nom_create() {
		$feed_id = $this->factory->feed->create();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'test_feed_item_nom_create_middle' ) );
		wp_set_current_user( $user_id );
		$time = time();
		$item_id = md5('Test linkTest item1');
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => 'Test item1',
			'item_link' => 'http://aramzs.github.io/tools/humans/ux/2017/02/08/audience-behavior-jcarn.html',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'item_id'	=>	$item_id
		) );
		$item = get_post( $feed_item_id, ARRAY_A );
		$nominate = pressforward('controller.advancement')->to_nomination($item);
		$this->assertFalse( ($feed_item_id === $nominate) );
		$this->assertInternalType('int', $nominate);
		$nom = get_post( $nominate );
		$this->assertEquals( $nom->post_title, 'Test item1' );
		$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nominate);
		pressforward('controller.metas')->update_pf_meta( $nominate, 'nominator_array', $nominators );
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
	}

	public function test_feed_item_nom_create_middle() {
		$feed_id = $this->factory->feed->create();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'test_feed_item_nom_create_middle' ) );
		wp_set_current_user( $user_id );
		$time = time();
		$item_id = md5('Test linkTest item1');
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => 'Test item1',
			'item_link' => 'http://aramzs.github.io/tools/humans/ux/2017/02/08/audience-behavior-jcarn.html',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'item_id'	=>	$item_id
		) );
		$nominate = pressforward('utility.forward_tools')->transition_to_nomination($feed_item_id);
		$this->assertFalse( ($feed_item_id === $nominate) );
		$this->assertInternalType('int', $nominate);
		$nom = get_post( $nominate );
		$this->assertEquals( $nom->post_title, 'Test item1' );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
	}

	public function test_feed_item_meta_increment() {
		$feed_id = $this->factory->feed->create();
		$time = time();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment' ) );
		wp_set_current_user( $user_id );
		$item_id = md5('Test linkTest item2');
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => 'Test item2',
			'item_link' => 'http://aramzs.github.io/jekyll/social-media/2015/11/11/be-social-with-jekyll.html',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'item_id'	=>	$item_id
		) );
		$nomination = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom = get_post( $nomination );
		$this->assertEquals( $nom->post_title, 'Test item2' );
		$this->assertFalse( ($feed_item_id === $nomination) );
		$this->assertInternalType('int', $nomination);
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment2' ) );
		wp_set_current_user( $user_id_2 );
		$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nomination, $user_id_2);
		pressforward('controller.metas')->update_pf_meta( $nomination, 'nominator_array', $nominators );
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id_2, $nominators);
		$this->assertTrue( $exists );
	}

	public function test_feed_item_meta_increment_on_create() {
		$feed_id = $this->factory->feed->create();
		$time = time();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment' ) );
		wp_set_current_user( $user_id );
		$item_id = md5('Test linkTest item3');
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => 'Test item3',
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'item_id'	=>	$item_id
		) );
		$nomination = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom = get_post( $nomination );
		$this->assertEquals( $nom->post_title, 'Test item3' );
		$this->assertFalse( ($feed_item_id === $nomination) );
		$this->assertInternalType('int', $nomination);
		$fi_count = pressforward('controller.metas')->get_post_pf_meta( $feed_item_id, 'nomination_count' );
		$this->assertEquals($fi_count, 1);
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

	}
}
