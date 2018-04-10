<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class RejectTest extends \PHPUnit_Framework_TestCase {
	public function test_reject_returns_items_that_do_not_match_criteria() {
		$col = new Collection( 'int', array( 1, 2, 3, 4, 5 ) );

		$odds = $col->reject( function ( $item ) {
			return $item % 2 === 0;
		} );

		$this->assertEquals( new Collection( 'int', array( 1, 3, 5 ) ), $odds );
	}
}
