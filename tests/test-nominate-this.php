<?php
// /srv/www/wordpress-default/public_html/wp-content/plugins/pressforward$
/**
 * @group PF_Nomination
 */
class PF_Tests_Nominate_This_Process extends PF_UnitTestCase {

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

	public function a_feed_item( $title, $time ){
		return array(
			'post_title' => $title,
			'item_link' => 'https://www.washingtonpost.com/news/politics/wp/2017/12/05/naacp-says-that-trump-should-not-attend-opening-of-civil-rights-museum-in-mississippi/',
			'item_content' => 'Ellie Dahmer, wife of Vernon Dahmer, who was killed in 1966 by the Ku Klux Klan, views some of the artifacts in the Mississippi Civil Rights Museum during a private preview in Jackson. The galleries at the museum focus on the period between 1945 and 1976 when the civil rights movement gained footing in the state. (Rogelio V. Solis/AP)

The NAACP is urging President Trump to skip the opening celebration for a civil rights museum in Mississippi that he had planned to attend, with the organization’s leader sharply criticizing the president’s record on civil rights.

The Mississippi Civil Rights Museum, a project of the state’s Department of Archives and History, is set to open with a ribbon-cutting ceremony on Saturday in Jackson, the state capital. The event will feature speeches from civil rights leaders and elected officials, including Mississippi Gov. Phil Bryant (R), who extended the invitation to the president.

But NAACP President Derrick Johnson said Trump’s attendance would be an “affront” to the movement commemorated by the museum.

“President Trump’s statements and policies regarding the protection and enforcement of civil rights have been abysmal, and his attendance is an affront to the veterans of the civil rights movement,” Johnson said. “He has created a commission to reinforce voter suppression, refused to denounce white supremacists, and overall, has created a racially hostile climate in this nation.”

White House press secretary Sarah Huckabee Sanders called Johnson’s stance “honestly very sad,” during her afternoon briefing on Tuesday.

“I think this is something that should bring the country together,” she said. “And I would hope that those individuals would join in that celebration instead of protesting it.”

The NAACP, founded in 1909, is one of the country’s oldest and most prestigious civil rights organizations. The organization’s rebuke is likely to touch off a new round of debate about the president’s views on racial minorities and civil rights.

Some of the president’s actions, including his refusal at times to disavow white supremacists and his vocal support for Confederate monuments, have been the subject of fierce debate.

The opening, which will celebrate the Civil Rights Museum as well as a new Museum of Mississippi History, is expected to feature speeches from the governor, Bryant; former NAACP chairman Myrlie Evers; and Rep. John Lewis (D-Ga.), a civil rights leader whom Trump disparaged as “All talk, talk, talk — no action or results” after Lewis said he would not attend the president’s inauguration.

NAACP board member Amos Brown, the president of the organization’s San Francisco branch and a native of Jackson, called Trump’s potential visit “an insult.”

“He has never been a supporter of civil rights or equal opportunity or justice,” Brown said in a statement. “He’s been silent on civil rights issues, and his silence speaks volumes.',
			'source_title' => 'The Washington Post',
			'sortable_item_date' => 10000,
			'item_date' => 20000,
			'item_author' => 'foo',
			'item_feat_img' => 'Test feat img',
			'post_date'	=>	'2017-12-06 00:28:51',
			'post_date_gmt'	=>	'2017-12-06 00:28:51',
			'item_wp_date' => $time,
			'post_tags'	=> 'test'
		);
	}

	public function query_check( $item_id, $post_type = false, $ids_only = false ){
		global $wpdb;
		// If the item is less than 24 hours old on nomination, check the whole database.
		// $theDate = getdate();
		// $w = date('W');
		$r = array(
								'meta_key' => pressforward('controller.metas')->get_key('item_id'),
								'meta_value' => $item_id,
								'post_type'	=> array( 'post', pf_feed_item_post_type() ),
							);

		if ( $ids_only ) {
			$r['fields'] = 'ids';
			$r['no_found_rows'] = true;
			$r['cache_results'] = false;

		}

		$r['post_status'] = array('publish', 'alert_specimen', 'under_review', 'future', 'draft', 'pending', 'private' );

		if ( false != $post_type ) {
			$r['post_type'] = $post_type;
		}

		$postsAfter = new \WP_Query( $r );
		return $postsAfter;
	}


	public function test_nominate_this_to_feed_item_create() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_feed_item_to_nomination_this' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test 2";
		$time = time();
		$post = $this->a_feed_item($title, $time);
		$_POST = array_merge($_POST, $post);
		$_POST['publish'] = 'nominate';
		$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		$feed_id = $this->factory->feed->create();
		$post['post_parent'] = $feed_id;

		$post['item_title'] = $title;

		$feed_item_id = $this->factory->feed_item->create( $post );
		$this->assertGreaterThan(0, $feed_item_id);
		$is_a_type_check =  pressforward('utility.forward_tools')->is_a_pf_type( $item_id, pressforward( 'schema.feed_item' )->post_type );
		$this->assertEquals($is_a_type_check, $feed_item_id);

		$nomination_id = pressforward('bookmarklet.core')->nominate_it();

		$this->check_standard_metrics($feed_item_id, $nomination_id, $title);

		$this->check_standard_nomination_metrics($nomination_id, $user_id, 1);

		$this->check_feed_nominations_incremented($nomination_id, 1);

		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type' ) );
		wp_set_current_user( $user_id_2 );

		$nomination_id_two = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );
		$this->assertEquals($nomination_id, $nomination_id_two);

		$this->check_standard_metrics($feed_item_id, $nomination_id_two, $title);

		$this->check_standard_nomination_metrics($nomination_id_two, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination_id_two, 2);

		$user_id_3 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type3' ) );
		wp_set_current_user( $user_id_3 );

		$post['post_title'] = $post['post_title'].' | Test Case';
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

	public function test_nominate_to_nominate_this_from_feed_item_create() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'bookmarklet_feed_item_to_nomination' ) );
		wp_set_current_user( $user_id );
		$_POST = array();
		$title = "bookmarklet test 2";
		$time = time();
		$post = $this->a_feed_item($title, $time);
		$_POST = array_merge($_POST, $post);
		$_POST['publish'] = 'nominate';
		$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		$feed_id = $this->factory->feed->create();
		$post['post_parent'] = $feed_id;

		$post['item_title'] = $title;

		$feed_item_id = $this->factory->feed_item->create( $post );
		$this->assertGreaterThan(0, $feed_item_id);
		$is_a_type_check =  pressforward('utility.forward_tools')->is_a_pf_type( $item_id, pressforward( 'schema.feed_item' )->post_type );
		$this->assertEquals($is_a_type_check, $feed_item_id);

		$nomination_id = pressforward('utility.forward_tools')->item_to_nomination( $item_id, $feed_item_id );

		$this->check_standard_metrics($feed_item_id, $nomination_id, $title);

		$this->check_standard_nomination_metrics($nomination_id, $user_id, 1);

		$this->check_feed_nominations_incremented($nomination_id, 1);

		$user_id_2 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type' ) );
		wp_set_current_user( $user_id_2 );

		$nomination_id_two = pressforward('bookmarklet.core')->nominate_it();
		$this->assertEquals($nomination_id, $nomination_id_two);

		$this->check_standard_metrics($feed_item_id, $nomination_id_two, $title);

		$this->check_standard_nomination_metrics($nomination_id_two, $user_id_2, 2);

		$this->check_feed_nominations_incremented($nomination_id_two, 2);

		$user_id_3 = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'next_nomination_by_type3' ) );
		wp_set_current_user( $user_id_3 );

		$post['post_title'] = $post['post_title'].' | Test Case';
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
