<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class ReduceTest extends \PHPUnit_Framework_TestCase {
	public function test_should_reduce_collection() {
		$collection = new Collection( 'int', array( 1, 2, 3 ) );

		$result = $collection->reduce( function ( $total, $item ) {
			return $total + $item;
		} );

		$this->assertEquals( 6, $result );

		$result = $collection->reduce( function ( $total, $item ) {
			return $total + $item;
		}, 2 );

		$this->assertEquals( 8, $result );
	}
}
