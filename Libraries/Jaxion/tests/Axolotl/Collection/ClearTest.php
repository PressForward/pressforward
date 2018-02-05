<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use PHPUnit_Framework_TestCase;

class ClearTest extends PHPUnit_Framework_TestCase {
	public function test_should_returns_an_empty_collection() {
		$col = new Collection( 'int', array(  1 ) );

		//col will have one
		$this->assertEquals( 1, $col->count() );

		//empty should have no items
		$empty = $col->clear();
		$this->assertEquals( 0, $empty->count() );

		//col should remain unchanged
		$this->assertEquals( 1, $col->count() );
	}
}
