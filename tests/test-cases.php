<?php

/**
 * @group relationships
 */
class PF_Tests extends PF_UnitTestCase {

	function test_pf_get_relationship_value_blank_string() {
		$user_id = $this->factory->user->create();
		$item_id = $this->factory->post->create();
		$type = 'star';
		$relationship_id = $this->factory->relationship->create( array(
			'user_id' => $user_id,
			'item_id' => $item_id,
			'type'    => $type,
			'value'   => '',
		) );

		$value = pf_get_relationship_value( $type, $item_id, $user_id );
		$this->assertSame( 0, $value );
	}

	function test_pf_get_relationship_value_doesnt_exist() {
		$user_id = $this->factory->user->create();
		$item_id = $this->factory->post->create();
		$type = 'star';

		$value = pf_get_relationship_value( $type, $item_id, $user_id );
		$this->assertSame( false, $value );
	}

	function test_pf_get_relationship_value_no_relationship_by_this_type() {
		$user_id = $this->factory->user->create();
		$item_id = $this->factory->post->create();
		$type = 'star';
		$relationship_id = $this->factory->relationship->create( array(
			'user_id' => $user_id,
			'item_id' => $item_id,
			'type'    => $type,
			'value'   => '',
		) );

		// Try a different type of relationship. Should return false
		$value = pf_get_relationship_value( 'read', $item_id, $user_id );

		$this->assertSame( false, $value );
	}

	function test_pf_get_relationship_value_no_relationship_by_this_type_with_integers() {
		$user_id = $this->factory->user->create();
		$item_id = $this->factory->post->create();
		$type = 2;
		$relationship_id = $this->factory->relationship->create( array(
			'user_id' => $user_id,
			'item_id' => $item_id,
			'type'    => $type,
			'value'   => 1,
		) );

		// Try a different type of relationship. Should return false
		$value = pf_get_relationship_value( 1, $item_id, $user_id );

		$this->assertSame( false, $value );
	}

	public function test_pf_get_relationship_value_should_be_cached() {
		global $wpdb;

		$user_id = $this->factory->user->create();
		$item_id = $this->factory->post->create();
		$type = 'star';
		$rel_value = 12345;
		$relationship_id = $this->factory->relationship->create( array(
			'user_id' => $user_id,
			'item_id' => $item_id,
			'type'    => $type,
			'value'   => $rel_value,
		) );

		$value = pf_get_relationship_value( $type, $item_id, $user_id );
		$this->assertSame( $rel_value, $value );

		$num_queries = $wpdb->num_queries;

		$value = pf_get_relationship_value( $type, $item_id, $user_id );
		$this->assertSame( $rel_value, $value );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	public function test_pf_get_relationship_value_cache_invalidation() {
		$user_id = $this->factory->user->create();
		$item_id = $this->factory->post->create();
		$type = 'star';
		$rel_value = 12345;
		$relationship_id = $this->factory->relationship->create( array(
			'user_id' => $user_id,
			'item_id' => $item_id,
			'type'    => $type,
			'value'   => $rel_value,
		) );

		$value = pf_get_relationship_value( $type, $item_id, $user_id );
		$this->assertSame( $rel_value, $value );

		$new_rel_value = 23456;
		$t = pf_set_relationship( $type, $item_id, $user_id, $new_rel_value );

		$value = pf_get_relationship_value( $type, $item_id, $user_id );
		$this->assertSame( $new_rel_value, $value );
	}
}
