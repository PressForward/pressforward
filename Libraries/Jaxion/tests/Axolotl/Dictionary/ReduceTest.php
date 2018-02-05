<?php

namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class ReduceTest extends PHPUnit_Framework_TestCase {
	public function test_adding_with_okay_types_adds_to_dictionary() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		) );

		$flat = $d->reduce( function ( $carry, $value, $key ) {
			$carry[] = $key;
			$carry[] = $value;

			return $carry;
		}, array() );

		$this->assertEquals( array( 'a', 1, 'b', 2, 'c', 3, 'd', 4 ), $flat );

		$lt10 = function ( $carry, $value ) {
			if ( ! $carry ) {
				return false;
			}

			return $value < 10;

		};

		$valuesLessThanTen = $d->reduce( $lt10, true );

		$this->assertTrue( $valuesLessThanTen );

		$d = $d->add( 'e', 99 );

		$valuesLessThanTen = $d->reduce( $lt10, true );

		$this->assertFalse( $valuesLessThanTen );
	}

}
