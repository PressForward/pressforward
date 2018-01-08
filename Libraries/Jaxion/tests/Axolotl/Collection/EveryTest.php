<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class EveryTest extends \PHPUnit_Framework_TestCase {
	public function test_should_pass_if_every_element_passes() {
		$collection = new Collection( 'int', array( 2, 4, 6 ) );

		$result = $collection->every( function ( $element ) {
			return $element % 2 === 0;
		} );

		$this->assertTrue( $result );
	}

	public function test_should_fail_if_not_all_elements_pass() {
		$collection = new Collection( 'int', array( 2, 4, 6 ) );

		$result = $collection->every( function ( $element ) {
			return $element !== 2;
		} );

		$this->assertFalse( $result );
	}
}
