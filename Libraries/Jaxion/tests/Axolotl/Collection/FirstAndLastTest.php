<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class FirstAndLastTest extends \PHPUnit_Framework_TestCase {
	public function test_can_get_first_item() {
		$collection = new Collection( 'int', array( 1, 2, 3, 4, 5 ) );
		$this->assertSame( $collection->first(), 1 );
	}

	public function test_can_not_get_first_if_collection_is_empty() {
		$this->setExpectedException( 'RuntimeException' );

		$collection = new Collection( 'int' );
		$collection->first();
	}

	public function test_can_get_last_item() {
		$collection = new Collection( 'int', array( 1, 2, 3, 4, 5 ) );
		$this->assertSame( $collection->last(), 5 );
	}

	public function test_can_not_get_last_if_collection_is_empty() {
		$this->setExpectedException( 'RuntimeException' );

		$collection = new Collection( 'int' );
		$collection->last();
	}
}
