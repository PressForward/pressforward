<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class InsertTest extends \PHPUnit_Framework_TestCase {
	public function test_should_insert_value() {
		$collection = new Collection( 'int', array( 1, 2 ) );
		$result = $collection->insert( 1, 3 );

		$this->assertEquals( 3, $result->at( 1 ) );

		$this->setExpectedException( 'OutOfRangeException' );
		$collection->insert( 100, 5 );

		$this->setExpectedException( 'InvalidArgumentException' );

		$collection->insert( - 1, 5 );
	}
}
