<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class RemoveAtTest extends \PHPUnit_Framework_TestCase {
	public function test_should_remove_element_at_index() {
		$collection = new Collection( 'int', array( 3, 2, 1 ) );

		$collection = $collection->remove_at( 1 );

		$this->assertEquals( 2, $collection->count() );
		$this->assertEquals( 3, $collection->at( 0 ) );
		$this->assertEquals( 1, $collection->at( 1 ) );
	}

}
