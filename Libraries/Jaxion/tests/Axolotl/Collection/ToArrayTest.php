<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class ToArrayTest extends \PHPUnit_Framework_TestCase {
	public function test_to_array() {
		$items = array( 1, 2, 3 );

		$col = new Collection( 'int', $items );

		$this->assertEquals( $items, $col->to_array() );
	}
}
