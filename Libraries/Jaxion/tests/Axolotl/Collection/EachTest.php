<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class EachTest extends \PHPUnit_Framework_TestCase {
	public function test_should_call_callback_for_each_element() {
		$collection = new Collection( 'int', array( 1, 2, 3, 4 ) );

		$results = array();

		$collection->each( function ( $a ) use ( &$results ) {
			$results[] = $a;
		} );

		$this->assertEquals( array( 1, 2, 3, 4 ), $results );
	}
}
