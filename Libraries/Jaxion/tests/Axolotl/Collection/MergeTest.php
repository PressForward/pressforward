<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use stdClass;

class MergeTest extends \PHPUnit_Framework_TestCase {
	public function test_should_merge_collection() {
		$c1 = new Collection( 'int', array( 1, 2, 3, 4 ) );
		$c2 = new Collection( 'int', array( 5, 6, 7, 8 ) );

		$result = $c1->merge( $c2 );

		$this->assertEquals( array( 1, 2, 3, 4, 5, 6, 7, 8 ), $result->to_array() );
	}

	public function test_should_merge_array() {
		$col   = new Collection( 'int' );
		$range = array( 0, 1 );

		$result = $col->merge( $range );

		$this->assertEquals( 2, $result->count() );
		$this->assertEquals( $range, $result->to_array() );
	}

	public function test_should_throw_with_incorrect_types() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$bad   = array( 'two' );

		$col = new Collection( 'int' );
		$col->merge( $bad );
	}

	public function test_should_throw_on_bad_types() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$col = new Collection( 'int' );
		$col->merge( new stdClass );
	}
}
