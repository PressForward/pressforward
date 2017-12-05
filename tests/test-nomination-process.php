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

	function check_feed_nominations_incremented($post_id, $count = 1){
		$parent_id = wp_get_post_parent_id( $post_id );
		$this->assertGreaterThan(0, $parent_id);
		if ( false != $parent_id ){
			$post = get_post($parent_id);
			if ( 'pf_feed' !== $post->post_type){
				return true;
			}
			$feedNomCount = pressforward('controller.metas')->get_post_pf_meta( $parent_id, 'pf_nominations_in_feed', true );
			$this->assertGreaterThan(0, $feedNomCount);
			$this->assertEquals($count, $feedNomCount);
		}
	}

	function check_feed_nominations_deincremented($post_id, $count = 0){
		$parent_id = wp_get_post_parent_id( $post_id );
		$this->assertGreaterThan(0, $parent_id);
		if ( false != $parent_id ){
			$feedNomCount = pressforward('controller.metas')->get_post_pf_meta( $parent_id, 'pf_nominations_in_feed', true );
			$this->assertEquals($count, $feedNomCount);
		}
	}

	public function check_standard_metrics($feed_item_id, $nominate_id, $title){
		$this->assertFalse( ($feed_item_id === $nominate_id) );
		$this->assertGreaterThan(0, $nominate_id);
		$nomination_post = get_post( $nominate_id );
		$this->assertEquals( $nomination_post->post_title, $title );
	}

	public function check_standard_nomination_metrics($nominate_id, $user_id, $count = 1, $denominate = false){
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nominate_id, 'nominator_array' );

		$exists = array_key_exists($user_id, $nominators);
		if ($denominate){
			$this->assertFalse( $exists );
		} else {
			$this->assertTrue( $exists );
		}
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nominate_id, 'nomination_count' );
		$this->assertEquals($nomination_count, $count);
	}

	public function check_standard_user_metrics($nominate_id, $user_id, $count = 1, $denominate = false){
		$nom_stats = get_user_meta( $user_id, 'nom_stats', true );
		$exists = array_key_exists($nominate_id, $nom_stats);
		if ($denominate){
			$this->assertFalse( $exists );
		} else {
			$this->assertTrue( $exists );
		}
		$noms_counted = get_user_meta(  $user_id, 'nom_count', true  );
		$this->assertEquals($noms_counted, $count);
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
		$this->check_standard_metrics($feed_item_id, $nominate, 'Test item1');

		$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nominate);
		pressforward('controller.metas')->update_pf_meta( $nominate, 'nominator_array', $nominators );

		$this->check_standard_nomination_metrics($nominate, $user_id, 1);

		$this->check_feed_nominations_incremented($nominate);
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
		$this->check_standard_metrics($feed_item_id, $nominate, 'Test item1');
		$this->check_standard_nomination_metrics($nominate, $user_id, 1);
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
		$this->check_standard_metrics($feed_item_id, $nomination, 'Test item2');

		// Did it apply the current user's nomination?
		$this->check_standard_nomination_metrics($nomination, $user_id, 1);

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
		pressforward('controller.metas')->update_pf_meta( $nomination, 'nominator_array', $nominators['nominators'] );
		$this->check_standard_user_metrics($nomination, $user_id_2, 1);

		$this->check_standard_nomination_metrics($nomination, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination, 2);

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
		$this->check_standard_metrics($feed_item_id, $nomination, $title);

		// Does the feed_item count increment?
		$fi_count = pressforward('controller.metas')->get_post_pf_meta( $feed_item_id, 'nomination_count' );
		$this->assertEquals($fi_count, 1);

		// Does the nomination cound increment?
		$this->check_standard_nomination_metrics($nomination, $user_id, 1);

		// Can we add another user properly?
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment4' ) );
		wp_set_current_user( $user_id_2 );
		$nominators = pressforward('utility.forward_tools')->apply_nomination_data($nomination, $user_id_2);
		pressforward('controller.metas')->update_pf_meta( $nomination, 'nominator_array', $nominators );
		$this->check_standard_nomination_metrics($nomination, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination, 2);

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
		$this->check_standard_user_metrics($nomination, $user_id_2, 0, true);

		pressforward('controller.metas')->update_pf_meta( $nomination, 'nominator_array', $nominators['nominators'] );
		$this->check_standard_nomination_metrics($nomination, $user_id_2, 1, true);
		$this->check_standard_nomination_metrics($nomination, $user_id, 1);

		$this->check_feed_nominations_deincremented($nomination, 1);
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
		$this->check_standard_metrics($feed_item_id, $nomination, $title);

		// Does the nomination cound increment?
		$this->check_standard_nomination_metrics($nomination, $user_id, 1);

		// Does the feed_item count increment?
		$fi_count = pressforward('controller.metas')->get_post_pf_meta( $feed_item_id, 'nomination_count' );
		$this->assertEquals($fi_count, 1);

		// Can we add another user properly?
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment4' ) );
		wp_set_current_user( $user_id_2 );
		$nomination_two = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom_two = get_post( $nomination_two );
		$this->assertEquals( $nom_two->ID, $nomination );

		$this->check_standard_metrics($feed_item_id, $nomination_two, $title);

		$this->check_standard_nomination_metrics($nomination_two, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination, 2);

		// Can we remove a nomination
		$nomination_three = pressforward('utility.forward_tools')->item_to_nomination($item_id, $feed_item_id);
		$nom_three = get_post( $nomination_three );
		$this->assertEquals( $nom_three->ID, $nomination );
		$this->assertEquals( $nom_three->post_title, $title );
		$this->assertFalse( ($feed_item_id === $nomination_three) );
		$this->assertGreaterThan( 0,  $nomination_three);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_two, 'nominator_array' );

		$this->check_standard_user_metrics($nomination_three, $user_id_2, 0, true);

		$this->check_standard_nomination_metrics($nomination, $user_id_2, 1, true);

		$this->check_feed_nominations_deincremented($nomination, 1);

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

		$post_exists = pressforward('utility.forward_tools')->is_a_pf_type($item_id, $post_type);
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
		$nom_and_post_check = pressforward('utility.forward_tools')->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
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

	public function test_sequential_bookmarklet_to_nomination_followed_by_feed_item(){
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
		$nom_and_post_check = pressforward('utility.forward_tools')->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
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


		$feed_id = $this->factory->feed->create();
		$time = time();
		$user_id_3 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type' ) );
		wp_set_current_user( $user_id_3 );
		$post['post_parent'] = $feed_id;
		$feed_item_id = $this->factory->feed_item->create( $post );

		$by_feed_nomination_id = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertEquals( $nomination_id, $by_feed_nomination_id );

		// Does the nomination count increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 3);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nominator_array' );
		$exists = array_key_exists($user_id_3, $nominators);
		$this->assertTrue( $exists );

	}

	public function test_sequential_feed_item_to_nomination_followed_by_bookmarklet(){
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test";
		$time = time();
		$post = array(
			'post_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=239',
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
		$feed_id = $this->factory->feed->create();
		$post['post_parent'] = $feed_id;

		$post['item_title'] = $title;
		$feed_item_id = $this->factory->feed_item->create( $post );

		$nomination_id = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertGreaterThan( 0,  $nomination_id);
		// Does the nomination count increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		$nom = get_post( $nomination_id );
		$this->assertEquals( $nom->post_title, $title );

		$item_id_via_meta = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'item_id' );
		$this->assertEquals( $item_id_via_meta, $item_id );

		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		// Switch users
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination2' ) );
		wp_set_current_user( $user_id_2 );

		$post_type = array( 'post', pressforward( 'schema.nominations' )->post_type );

		// Check by item_id.
		$nom_and_post_check = pressforward('utility.forward_tools')->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		$this->assertTrue( ($nom_and_post_check !== false) );

		// Attempt to nominate via bookmarklet
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

		$time = time();
		$user_id_3 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type' ) );
		wp_set_current_user( $user_id_3 );

		$nomination_id_three = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertGreaterThan( 0,  $nomination_id_three);
		$this->assertEquals( $nomination_attempt_two_id, $nomination_id_three );
		$this->assertEquals( $nomination_id, $nomination_id_three );

		$item_id_three_via_meta = pressforward('controller.metas')->get_post_pf_meta( $nomination_id_three, 'item_id' );
		$this->assertEquals( $item_id_three_via_meta, $item_id_via_meta );

		// Does the nomination count increment?
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 3);
		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_id, 'nominator_array' );
		$exists = array_key_exists($user_id_3, $nominators);
		$this->assertTrue( $exists );


	}

	/**
	 * Test item to final step
	 *  - Does the `to_nomination` function successfully move a post to nomination
	 *  - Does `apply_nomination_data` function successfully add user data.
	 * @return {[type]} [description]
	 */
	public function test_feed_item_to_last_step_middle() {
		$feed_id = $this->factory->feed->create();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'test_feed_item_nom_create_middle' ) );
		wp_set_current_user( $user_id );
		$time = time();
		$link = 'http://aramzs.github.io/tools/humans/ux/2017/02/08/audience-behavior-jcarn.html?cb=20';
		$title = 'Test item20';
		$item_id = md5($link.$title);
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => $title,
			'item_link' => $link,
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
		$nomination_id = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$final_id = pressforward('utility.forward_tools')->nomination_to_last_step( $item_id, $nomination_id );

		$this->assertFalse( ($feed_item_id === $final_id) );
		$this->assertGreaterThan(0, $final_id);
		$final = get_post( $final_id );
		$this->assertEquals( $final->post_title, $title );

		$nominate = pressforward('controller.metas')->get_post_pf_meta($final_id, 'nom_id');
		$this->assertEquals($nominate, $nomination_id);

		pressforward('controller.metas')->transition_post_meta( $feed_item_id, $nominate, true );
		$this->assertFalse( ($feed_item_id === $nominate) );
		$this->assertGreaterThan(0, $nominate);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $final_id, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $final_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
	}

	/**
	 * Test item to final step
	 *  - Does the `to_nomination` function successfully move a post to nomination
	 *  - Does `apply_nomination_data` function successfully add user data.
	 * @return {[type]} [description]
	 */
	public function test_feed_item_to_last_step() {
		$feed_id = $this->factory->feed->create();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'test_feed_item_nom_create_middle' ) );
		wp_set_current_user( $user_id );
		$time = time();
		$link = 'http://aramzs.github.io/tools/humans/ux/2017/02/08/audience-behavior-jcarn.html?cb=29';
		$title = 'Test item29';
		$item_id = md5($link.$title);
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => $title,
			'item_link' => $link,
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
		$final_id = pressforward('utility.forward_tools')->item_to_last_step( $item_id, $feed_item_id );

		$this->assertFalse( ($feed_item_id === $final_id) );
		$this->assertGreaterThan(0, $final_id);
		$final = get_post( $final_id );
		$this->assertEquals( $final->post_title, $title );

		$nominate = pressforward('controller.metas')->get_post_pf_meta($final_id, 'nom_id');

		$this->assertFalse( ($feed_item_id === $nominate) );
		$this->assertGreaterThan(0, $nominate);
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $final_id, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $final_id, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $nominate, 'nomination_count' );
		$this->assertEquals($nomination_count, 1);
	}

	public function test_feed_item_to_last_step_followed_by_nomination() {
		$feed_id = $this->factory->feed->create(array( 'post_title' => 'feed_for_test_feed_item_to_last_step_followed_by_nomination' ));
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'test_feed_item_nom_create_middle' ) );
		wp_set_current_user( $user_id );
		$time = time();
		$link = 'http://aramzs.github.io/tools/humans/ux/2017/02/08/audience-behavior-jcarn.html?cb=29';
		$title = 'Test item29';
		$item_id = md5($link.$title);
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => $title,
			'item_link' => $link,
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
		$final_id = pressforward('utility.forward_tools')->item_to_last_step( $item_id, $feed_item_id );

		$this->check_standard_metrics($feed_item_id, $final_id, $title);

		$final_post = get_post($final_id);
		$this->assertEquals($final_post->post_type, 'post');

		$nominate = pressforward('controller.metas')->get_post_pf_meta($final_id, 'nom_id');

		$this->check_standard_metrics($feed_item_id, $nominate, $title);

		$this->check_standard_nomination_metrics($nominate, $user_id, 1);

		$this->check_feed_nominations_incremented($nominate);

		// Now nominate
		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'nomination_after_draft' ) );
		wp_set_current_user( $user_id_2 );
		//sleep(2);

		$nomination_two_id = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		//var_dump( $nomination_two_id ); die();

		$nomination_post = get_post($nomination_two_id);

		$this->assertEquals('nomination', $nomination_post->post_type);

		$this->assertEquals($nomination_two_id, $nominate);

		$this->check_standard_metrics($feed_item_id, $nomination_two_id, $title);

		$this->check_standard_nomination_metrics($nomination_two_id, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination_two_id, 2);

		//var_dump(get_post_meta($final_id)); die();
		$this->check_standard_metrics($feed_item_id, $final_id, $title);

		$nomination_and_post_check = pressforward('utility.forward_tools')->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		$this->assertEquals($nomination_and_post_check, $nomination_two_id);
		$this->assertEquals( get_option( PF_SLUG . '_draft_post_type', 'post' ), 'post' );
		$final_post = get_post($final_id);
		$this->assertEquals( get_option( PF_SLUG . '_draft_post_type', 'post' ), $final_post->post_type );
		$this->assertEquals( get_post_meta( $final_id, 'item_id', true ), $item_id );

//		$nominators = pressforward('utility.forward_tools')->apply_nomination_data($final_id. false, true);
//		pressforward('controller.metas')->update_pf_meta( $final_id, 'nominator_array', $nominators );

		//$final_step_parent = pf_is_drafted( $item_id );
		//$this->assertEquals($final_step_parent, $final_id);
		$nom_nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination_two_id, 'nominator_array' );
		//var_dump($nom_nominators);
		//var_dump($nominators = pressforward('controller.metas')->get_post_pf_meta( $final_id, 'nominator_array' )); var_dump($user_id_2, array_key_exists($user_id_2, $nominators)); die();
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $final_id, 'nominator_array' );
		$this->assertTrue(array_key_exists($user_id_2, $nominators));
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		$nomination_count = pressforward('controller.metas')->get_post_pf_meta( $final_id, 'nomination_count' );
		$this->assertEquals(2, $nomination_count);
		$this->check_standard_nomination_metrics($final_id, $user_id_2, 2);

		$this->check_feed_nominations_incremented($final_id, 2);

	}

	/**
	 * [test_nominate_then_bookmarklet description]
	 *
	 * Issue #961
	 * @return {[type]} [description]
	 */
	public function test_nominate_then_bookmarklet_sequentials() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination8' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test";
		$time = time();
		$post = array(
			'post_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=2389',
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
		$feed_id = $this->factory->feed->create();
		$post['post_parent'] = $feed_id;

		$post['item_title'] = $title;
		$feed_item_id = $this->factory->feed_item->create( $post );

		$nomination_id = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );

		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type' ) );
		wp_set_current_user( $user_id_2 );

		$nomination_id_two = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertEquals($nomination_id, $nomination_id_two);

		$this->check_standard_metrics($feed_item_id, $nomination_id_two, $title);

		$this->check_standard_nomination_metrics($nomination_id_two, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination_id_two, 2);

		$user_id_3 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type3' ) );
		wp_set_current_user( $user_id_3 );

		$nomination_id_three = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertEquals($nomination_id, $nomination_id_three);

		$this->check_standard_metrics($feed_item_id, $nomination_id_three, $title);

		$this->check_standard_nomination_metrics($nomination_id_three, $user_id_3, 3);

		$this->check_feed_nominations_incremented($nomination_id_three, 3);


		$user_id_4 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type4' ) );
		wp_set_current_user( $user_id_4 );
		$nomination_id_four = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertEquals($nomination_id, $nomination_id_four);

		$this->check_standard_metrics($feed_item_id, $nomination_id_four, $title);

		$this->check_standard_nomination_metrics($nomination_id_four, $user_id_3, 4);

		$this->check_feed_nominations_incremented($nomination_id_four, 4);

	}

	/**
	 * [test_nominate_then_bookmarklet description]
	 *
	 * Issue #961
	 * @return {[type]} [description]
	 */
	public function test_bookmarklet_then_nominate_sequentials() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination8' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test";
		$time = time();
		$post = array(
			'post_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=2389',
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
		$feed_id = $this->factory->feed->create();
		$post['post_parent'] = $feed_id;

		$post['item_title'] = $title;
		$feed_item_id = $this->factory->feed_item->create( $post );

		$nomination_id = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);

		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type' ) );
		wp_set_current_user( $user_id_2 );

		$nomination_id_two = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertEquals($nomination_id, $nomination_id_two);

		$this->check_standard_metrics($feed_item_id, $nomination_id_two, $title);

		$this->check_standard_nomination_metrics($nomination_id_two, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination_id_two, 2);

		$user_id_3 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type3' ) );
		wp_set_current_user( $user_id_3 );

		$nomination_id_three = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertEquals($nomination_id, $nomination_id_three);

		$this->check_standard_metrics($feed_item_id, $nomination_id_three, $title);

		$this->check_standard_nomination_metrics($nomination_id_three, $user_id_3, 3);

		$this->check_feed_nominations_incremented($nomination_id_three, 3);


		$user_id_4 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type4' ) );
		wp_set_current_user( $user_id_4 );
		$nomination_id_four = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertEquals($nomination_id, $nomination_id_four);

		$this->check_standard_metrics($feed_item_id, $nomination_id_four, $title);

		$this->check_standard_nomination_metrics($nomination_id_four, $user_id_3, 4);

		$this->check_feed_nominations_incremented($nomination_id_four, 4);

	}

	/**
	 * [test_nominate_then_bookmarklet description]
	 *
	 * Issue #961
	 * @return {[type]} [description]
	 */
	public function test_bookmarklet_to_publish_then_nominate_sequentials() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_to_nomination8' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test";
		$time = time();
		$post = array(
			'post_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=2389',
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
		$feed_id = $this->factory->feed->create();
		$post['post_parent'] = $feed_id;

		$post['item_title'] = $title;
		$feed_item_id = $this->factory->feed_item->create( $post );

		$last_step_id = pressforward('utility.forward_tools')->bookmarklet_to_last_step(false, $post);

		$nomination_id = $feedNomCount = pressforward('controller.metas')->get_post_pf_meta( $last_step_id, 'nom_id', true );

		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type' ) );
		wp_set_current_user( $user_id_2 );

		$nomination_id_two = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertEquals($nomination_id, $nomination_id_two);

		$this->check_standard_metrics($feed_item_id, $nomination_id_two, $title);

		$this->check_standard_nomination_metrics($nomination_id_two, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination_id_two, 2);

		$user_id_3 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type3' ) );
		wp_set_current_user( $user_id_3 );

		$nomination_id_three = pressforward('utility.forward_tools')->bookmarklet_to_nomination(false, $post);
		$this->assertEquals($nomination_id, $nomination_id_three);

		$this->check_standard_metrics($feed_item_id, $nomination_id_three, $title);

		$this->check_standard_nomination_metrics($nomination_id_three, $user_id_3, 3);

		$this->check_feed_nominations_incremented($nomination_id_three, 3);


		$user_id_4 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type4' ) );
		wp_set_current_user( $user_id_4 );
		$nomination_id_four = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertEquals($nomination_id, $nomination_id_four);

		$this->check_standard_metrics($feed_item_id, $nomination_id_four, $title);

		$this->check_standard_nomination_metrics($nomination_id_four, $user_id_3, 4);

		$this->check_feed_nominations_incremented($nomination_id_four, 4);

	}
}
