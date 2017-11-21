<?php
// /srv/www/wordpress-default/public_html/wp-content/plugins/pressforward$
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
	/**
	 * Test one:
	 *  - Does the `to_nomination` function successfully move a post to nomination
	 *  - Does `apply_nomination_data` function successfully add user data.
	 * @return {[type]} [description]
	 */
	public function test_feed_item_to_nomination_create() {
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
		$this->assertGreaterThan(0, $nominate);
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

	/**
	 * Does `transition_to_nomination` work
	 * @return {[type]} [description]
	 */
	public function test_feed_item_create_middle_with_transition_to_nomination() {
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
		$this->assertGreaterThan(0, $nominate);
		$nom = get_post( $nominate );
		$this->assertEquals( $nom->post_title, 'Test item1' );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
	}

	/**
	 * Does
	 *  - `apply_nomination_data` increment with a new user properly.
	 * @return {[type]} [description]
	 */
	public function test_feed_item_meta_increment_with_item_to_nomination() {
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
		// First item to nomination.
		$nomination = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom = get_post( $nomination );
		$this->assertEquals( $nom->post_title, 'Test item2' );
		$this->assertFalse( ($feed_item_id === $nomination) );
		$this->assertGreaterThan(0, $nomination);

		// Did it apply the current user's nomination?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment2' ) );
		// Will it apply the 2nd user's nomination?
		wp_set_current_user( $user_id_2 );
		//$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nomination, $user_id_2);
		$user_id_2 = pressforward('utility.forward_tools')->assure_user_id($user_id_2);
		$nominators = pressforward('utility.forward_tools')->apply_nomination_array( $nomination, $user_id_2 );
		$this->assertTrue( $nominators['applied'] );
		if ( $nominators['applied'] ){
			$check_u_count = pressforward('utility.forward_tools')->apply_nomination_user_data( $nomination, $user_id_2 );
			$check_nom_count = pressforward('utility.forward_tools')->apply_nomination_count( $nomination, $user_id_2 );
		} else {
			pressforward('utility.forward_tools')->revoke_nomination_user_data( $nomination, $user_id_2 );
			pressforward('utility.forward_tools')->revoke_nomination_count( $nomination, $user_id_2 );
		}
		//$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nomination, $user_id_2);
		$nom_stats = get_user_meta( $user_id_2, 'nom_stats', true );
		$exists = array_key_exists($nomination, $nom_stats);
		$this->assertTrue( $exists );
		$noms_counted = get_user_meta(  $user_id_2, 'nom_count', true  );
		$this->assertEquals($noms_counted, 1);
		$check_nom_count = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nomination_count', true );
		$this->assertEquals($check_nom_count, 2);
		pressforward('controller.metas')->update_pf_meta( $nomination, 'nominator_array', $nominators['nominators'] );
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id_2, $nominators);
		$this->assertTrue( $exists );
	}

	public function test_feed_item_deincrement() {
		$feed_id = $this->factory->feed->create();
		$time = time();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment' ) );
		wp_set_current_user( $user_id );
		$title = 'Test item4';
		$url = 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=3';
		$item_id = md5($url.$title);
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=3',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'item_id'	=>	$item_id
		) );
		// Does the item create proprly
		$nomination = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom = get_post( $nomination );
		$this->assertEquals( $nom->post_title, $title );
		$this->assertFalse( ($feed_item_id === $nomination) );
		$this->assertGreaterThan(0, $nomination);

		// Does the feed_item count increment?
		$fi_count = pressforward('controller.metas')->get_post_pf_meta( $feed_item_id, 'nomination_count' );
		$this->assertEquals($fi_count, 1);
		// Does the nomination cound increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		// Can we add another user properly?
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment4' ) );
		wp_set_current_user( $user_id_2 );
		$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nomination, $user_id_2);
		pressforward('controller.metas')->update_pf_meta( $nomination, 'nominator_array', $nominators );
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id_2, $nominators);
		$this->assertTrue( $exists );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nomination_count' );
		$this->assertEquals($nomination_count, 2);

		// Can we remove a nomination
		$user_id_2 = pressforward('utility.forward_tools')->assure_user_id($user_id_2);
		$nominators = pressforward('utility.forward_tools')->apply_nomination_array( $nomination, $user_id_2 );
		$this->assertFalse( $nominators['applied'] );
		if ( $nominators['applied'] ){
			pressforward('utility.forward_tools')->apply_nomination_user_data( $nomination, $user_id_2 );
			pressforward('utility.forward_tools')->apply_nomination_count( $nomination, $user_id_2 );
		} else {
			$check_u_count = pressforward('utility.forward_tools')->revoke_nomination_user_data( $nomination, $user_id_2 );
			$check_nom_count = pressforward('utility.forward_tools')->revoke_nomination_count( $nomination, $user_id_2 );
		}
		//$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nomination, $user_id_2);
		$nom_stats = get_user_meta( $user_id_2, 'nom_stats', true );
		$exists = array_key_exists($nomination, $nom_stats);
		$this->assertFalse( $exists );
		$noms_counted = get_user_meta(  $user_id_2, 'nom_count', true  );
		$this->assertEquals($noms_counted, 0);
		$check_nom_count = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nomination_count', true );
		$this->assertEquals($check_nom_count, 1);

		pressforward('controller.metas')->update_pf_meta( $nomination, 'nominator_array', $nominators['nominators'] );
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id_2, $nominators);
		$this->assertFalse( $exists );
	}

	public function test_feed_item_nomination() {
		$feed_id = $this->factory->feed->create();
		$time = time();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment' ) );
		wp_set_current_user( $user_id );
		$title = 'Test item5';
		$url = 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=23';
		$item_id = md5($url.$title);
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=23',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'item_id'	=>	$item_id
		) );
		// Does the item create proprly
		$nomination = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom = get_post( $nomination );
		$this->assertEquals( $nom->post_title, $title );
		$this->assertFalse( ($feed_item_id === $nomination) );
		$this->assertGreaterThan( 0,  $nomination);

		// Does the feed_item count increment?
		$fi_count = pressforward('controller.metas')->get_post_pf_meta( $feed_item_id, 'nomination_count' );
		$this->assertEquals($fi_count, 1);
		// Does the nomination cound increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		// Can we add another user properly?
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment4' ) );
		wp_set_current_user( $user_id_2 );
		$nomination_two = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom_two = get_post( $nomination_two );
		$this->assertEquals( $nom_two->ID, $nomination );
		$this->assertEquals( $nom_two->post_title, $title );
		$this->assertFalse( ($feed_item_id === $nomination_two) );
		$this->assertGreaterThan( 0,  $nomination_two);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_two, 'nominator_array' );

		$exists = array_key_exists($user_id_2, $nominators);
		$this->assertTrue( $exists );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_two, 'nomination_count' );
		$this->assertEquals($nomination_count, 2);

		// Can we remove a nomination
		$nomination_three = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom_three = get_post( $nomination_three );
		$this->assertEquals( $nom_three->ID, $nomination );
		$this->assertEquals( $nom_three->post_title, $title );
		$this->assertFalse( ($feed_item_id === $nomination_three) );
		$this->assertGreaterThan( 0,  $nomination_three);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_two, 'nominator_array' );

		$exists = array_key_exists($user_id_2, $nominators);
		$this->assertFalse( $exists );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_three, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
	}

	public function test_is_a_pf_type(){
		$post_exists = pressforward('utility.forward_tools')->is_a_pf_type(3344);
		$this->assertFalse( $post_exists );
		$feed_id = $this->factory->feed->create();
		$time = time();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment' ) );
		wp_set_current_user( $user_id );
		$title = 'Test item4';
		$url = 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=3';
		$item_id = md5($url.$title);
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=3',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'item_id'	=>	$item_id
		) );
		// Does the item create proprly
		$nomination = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$this->assertGreaterThan( 0,  $nomination);

		$nom = get_post( $nomination );
		$this->assertGreaterThan( 0,  $nom->ID);
		$post_type = pressforward( 'schema.nominations' )->post_type;
		$this->assertEquals( $nom->post_type, $post_type );
		$test_of_item_id = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'item_id' );
		$this->assertEquals( $test_of_item_id, $item_id );
		// Check the Query.
		$r = array(
								'meta_key' => pressforward('controller.metas')->get_key('item_id'),
								'meta_value' => $item_id,
						);

		if ( false != $post_type ) {
			$r['post_type'] = $post_type;
		}
			$r['no_found_rows'] = true;
			$r['cache_results'] = false;
			$r['post_status'] = array('publish', 'alert_specimen', 'under_review', 'future', 'draft', 'pending', 'private' );

		$postsAfter = new \WP_Query( $r );
		pf_log( ' Checking for posts with item ID ' . $item_id . ' returned query with ' . $postsAfter->post_count . ' items.' );
		$this->assertEquals($postsAfter->query['meta_key'], 'item_id');
		$this->assertEquals($postsAfter->query_vars['meta_value'], $item_id);
		$request = "SELECT   wptests_posts.ID FROM wptests_posts  INNER JOIN wptests_postmeta ON ( wptests_posts.ID = wptests_postmeta.post_id ) WHERE 1=1  AND (
  ( wptests_postmeta.meta_key = 'item_id' AND wptests_postmeta.meta_value = '".$item_id."' )
) AND wptests_posts.post_type = 'nomination' AND (wptests_posts.post_status = 'publish' OR wptests_posts.post_status = 'alert_specimen' OR wptests_posts.post_status = 'under_review' OR wptests_posts.post_status = 'future' OR wptests_posts.post_status = 'draft' OR wptests_posts.post_status = 'pending' OR wptests_posts.post_status = 'private') GROUP BY wptests_posts.ID ORDER BY wptests_posts.post_date DESC LIMIT 0, 10";
		//$this->assertEquals($postsAfter->request, $request);
		$this->assertTrue( isset($postsAfter->posts[0]) );
		$this->assertEquals($postsAfter->posts[0]->ID, $nom->ID);
		$this->assertTrue( ($postsAfter->post_count > 0) );

		$query_check = pressforward('controller.advancement')->pf_get_posts_by_id_for_check($post_type, $item_id, true);
		$this->assertTrue( 0 < $query_check->post_count );

		$q = pressforward('controller.advancement')->pf_get_posts_by_id_for_check( $post_type, $item_id );
		$this->assertTrue( 0 < $q->post_count );

		$attempt = pressforward('controller.advancement')->get_pf_type_by_id( $item_id, $post_type );
		$this->assertTrue( ($attempt !== false) );

		$post_exists = pressforward('utility.forward_tools')->is_a_pf_type($item_id);
		$this->assertTrue( ($post_exists !== false) );
		$this->assertGreaterThan( 0,  $post_exists);
		$this->assertEquals( $post_exists, $nomination );
	}

	public function test_bookmarklet_to_nomination(){
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test";
		$time = time();
		$post = array(
			'post_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=9',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'post_tags'	=> 'test'
		);
		$_POST = array_merge($_POST, $post);
		$nomination_id = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertGreaterThan( 0,  $nomination_id);

		$nom = get_post( $nomination_id );
		$this->assertEquals( $nom->post_title, $title );

		// Does the nomination cound increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
	}

	public function test_sequential_bookmarklet_to_nomination(){
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test";
		$time = time();
		$post = array(
			'post_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=9',
			'item_content' => 'Test content',
			'source_title' => 'Test source title',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'item_wp_date' => $time,
			'post_tags'	=> 'test'
		);
		$_POST = array_merge($_POST, $post);
		$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		$nomination_id = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertGreaterThan( 0,  $nomination_id);

		$nom = get_post( $nomination_id );
		$this->assertEquals( $nom->post_title, $title );

		$item_id_via_meta = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'item_id' );
		$this->assertEquals( $item_id_via_meta, $item_id );

		// Does the nomination count increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		// Switch users
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination2' ) );
		wp_set_current_user( $user_id_2 );

		$post_type = array( 'post', pressforward( 'schema.nominations' )->post_type );

		// Check by item_id.
		$nom_and_post_check = pressforward('utility.forward_tools')->is_a_pf_type( $item_id );
		$this->assertTrue( ($nom_and_post_check !== false) );

		// Attempt to nominate twice
		$nomination_attempt_two_id = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertGreaterThan( 0,  $nomination_attempt_two_id);
		$this->assertEquals( $nomination_attempt_two_id, $nomination_id );

		$nom_attempt_two = get_post($nomination_attempt_two_id);
		$this->assertEquals( $nom_attempt_two->post_title, $title );

		$item_id_two_via_meta = pressforward('controller.metas')->get_post_pf_meta( $nomination_attempt_two_id, 'item_id' );
		$this->assertEquals( $item_id_two_via_meta, $item_id_via_meta );

		// Does the nomination count increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 2);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nominator_array' );
		$exists = array_key_exists($user_id_2, $nominators);
		$this->assertTrue( $exists );

	}
}
