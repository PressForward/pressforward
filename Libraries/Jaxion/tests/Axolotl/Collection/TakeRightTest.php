<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class TakeRightTest extends \PHPUnit_Framework_TestCase {
	public function test_should_take_right_correctly() {
		$collection = new Collection( 'int', array( 2, 4, 6 ) );

		$c1 = $collection->take_right( 1 );
		$c2 = $collection->take_right( 2 );
		$c3 = $collection->take_right( 3 );

		$this->assertEquals( 1, $c1->count() );
		$this->assertEquals( 2, $c2->count() );
		$this->assertEquals( 3, $c3->count() );

		$this->assertEquals( 6, $c1->at( 0 ) );

		$this->assertEquals( 4, $c2->at( 0 ) );
		$this->assertEquals( 6, $c2->at( 1 ) );

		$this->assertEquals( 2, $c3->at( 0 ) );
		$this->assertEquals( 4, $c3->at( 1 ) );
		$this->assertEquals( 6, $c3->at( 2 ) );
	}
}
