<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class ReduceRightTest extends \PHPUnit_Framework_TestCase {
	public function test_reduce_right_add() {
		$col = new Collection( 'int', array( 1, 3, 10, 4 ) );

		$c1 = $col->reduce_right( function ( $carry, $inc ) {
			return $carry + $inc;
		} );

		$this->assertEquals( 18, $c1 );
	}

	public function test_reduce_right_sub() {
		$col = new Collection( 'int', array( 1, 3, 5, 10 ) );

		$c1 = $col->reduce_right( function ( $carry, $inc ) {
			return $carry - $inc;
		} );

		$this->assertEquals( -19, $c1 );
	}

	public function test_reduce_right_sub_non_zero_default() {
		$col = new Collection( 'int', array( 1, 3, 10, 4 ) );

		$c1 = $col->reduce_right( function ( $carry, $inc ) {
			return $carry - $inc;
		}, 20 );

		$this->assertEquals( 2, $c1 );
	}

	public function test_reduce_right_string() {
		$col = new Collection( 'string', array( 'a', 'b', 'c', 'd' ) );

		$c1 = $col->reduce_right( function ( $carry, $inc ) {
			return $carry . $inc;
		} );

		$this->assertEquals( 'dcba', $c1 );
	}

	public function test_reduce_right_string_with_default() {
		$col = new Collection( 'string', array( 'a', 'b', 'c', 'd' ) );

		$c1 = $col->reduce_right( function ( $carry, $inc ) {
			return $carry . $inc;
		}, 'z' );

		$this->assertEquals( 'zdcba', $c1 );
	}
}
