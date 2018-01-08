<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class ReverseTest extends \PHPUnit_Framework_TestCase {

	public function test_should_reverse_collection() {
		$c = new Collection( 'int', array( 1, 2, 3 ) );
		$r = $c->reverse();

		$this->assertEquals( array( 3, 2, 1 ), $r->to_array() );
		$this->assertEquals( array( 1, 2, 3 ), $c->to_array() );
	}
}
