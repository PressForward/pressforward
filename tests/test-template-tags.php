<?php
// /srv/www/wordpress-default/public_html/wp-content/plugins/pressforward$
/**
 * @group PF_Nomination
 */
class PF_Tests_Template_Tags extends PF_UnitTestCase {
	public function test_get_the_nominator_ids() {
		$feed_id = $this->factory->feed->create();
		$time = time();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'feed_item_meta_increment' ) );
		wp_set_current_user( $user_id );
		$title = 'Test item';
		$url = 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=25';
		$item_id = md5($url.$title);
		$feed_item_id = $this->factory->feed_item->create( array(
			'post_parent' => $feed_id,
			'item_title' => $title,
			'item_link' => 'http://aramzs.github.io/notes/wordpress/wordpressus2015/2015/12/04/wordcamp-us.html?t=25',
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

		// Does the user enter the array?
		$nominators = pressforward('controller.metas')->get_post_pf_meta( $nomination, 'nominator_array' );
		$exists = array_key_exists($user_id, $nominators);
		$this->assertTrue( $exists );

		$nominator_ids = get_the_nominator_ids($nomination);
		$is_in_array = in_array($user_id, $nominator_ids);
		$this->assertTrue($is_in_array);
	}

}
