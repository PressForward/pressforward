<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class TakeWhileTest extends \PHPUnit_Framework_TestCase {
	public function test_take_while() {
		$collection = new Collection( 'int', array( 2, 4, 7, 9) );

		$c1 = $collection->take_while( function ( $item ) {
			return $item % 2 === 0;
		} );

		$this->assertEquals( 2, $c1->count() );

		$this->assertEquals( 2, $c1->at( 0 ) );
		$this->assertEquals( 4, $c1->at( 1 ) );

		$c2 = $collection->take_while( function ( $item ) {
			return true;
		} );
		$this->assertEquals( $collection, $c2 );

		$c3 = $collection->take_while( function ( $item ) {
			return false;
		} );
		$this->assertEquals( 0, $c3->count() );
	}
}
