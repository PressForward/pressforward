<?php

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
		$this->assertSame( $value, '' );
	}

	function test_pf_get_relationship_value_doesnt_exist() {
		$user_id = $this->factory->user->create();
		$item_id = $this->factory->post->create();
		$type = 'star';

		$value = pf_get_relationship_value( $type, $item_id, $user_id );
		$this->assertSame( $value, false );
	}

}

