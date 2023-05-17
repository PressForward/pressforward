<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class PF_UnitTestCase extends TestCase {
	public $factory;

	public function set_up() {
		$this->factory = new PF_UnitTest_Factory;
	}

	public function tear_down() {
		global $wpdb;

		$user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE user_login != 'admin'" );
		foreach ( $user_ids as $user_id ) {
			wp_delete_user( $user_id );
		}

		$pf_types = array_map(
			function( $type ) use ( $wpdb ) {
				return $wpdb->prepare( '%s', $type );
			},
			[ 'nomination', 'pf_feed', 'pf_feed_item', 'attachment', 'post' ]
		);
		$pf_types_str = implode( ',', $pf_types );

		$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$pf_types_str})" );
		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		delete_option( 'pf_delete_queue' );
	}

	/**
	 * Borrowed from WP.
	 */
	public function assertEqualSets( $expected, $actual, $message = '' ) {
		$this->assertIsArray( $expected, $message . ' Expected value must be an array.' );
		$this->assertIsArray( $actual, $message . ' Value under test is not an array.' );

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual, $message );
	}
}
