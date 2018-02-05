<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class InsertRangeTest extends \PHPUnit_Framework_TestCase {
	public function test_should_insert_elements() {
		$collection = new Collection( 'int', array( 1, 2 ) );
		$elements   = array( 3, 4 );

		$result = $collection->insert_range( 1, $elements );

		$this->assertEquals( array ( 1, 3, 4, 2 ), $result->to_array() );
	}
}
