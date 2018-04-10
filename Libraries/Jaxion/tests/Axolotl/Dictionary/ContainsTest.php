<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class ContainsTest extends PHPUnit_Framework_TestCase {
	public function test_adding_with_okay_types_adds_to_dictionary() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		) );

		$c3 = $d->contains( function ( $value, $key ) {
			return $key === 'c' && $value === 3;
		} );

		$this->assertTrue( $c3 );

		$c4 = $d->contains( function ( $value, $key ) {
			return $key === 'c' && $value === 4;
		} );

		$this->assertFalse( $c4 );
	}

}
