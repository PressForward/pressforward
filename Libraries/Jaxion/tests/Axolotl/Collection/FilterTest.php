<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use PHPUnit_Framework_TestCase;

class FilterTest extends PHPUnit_Framework_TestCase {
	public function test_should_filter_matching_elements() {
		$collection = new Collection( 'int', array( 54, 32, 32, 32 ) );

		$filtered = $collection->filter( function ( $element ) {
			return $element !== 32;
		} );

		$this->assertEquals( 1, $filtered->count() );
		$this->assertEquals( $collection = new Collection( 'int', array( 54 ) ), $filtered );
	}

}
