<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class FilterTest extends PHPUnit_Framework_TestCase {
	public function test_filter() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		) );

		$subset = $d->filter( function ( $v ) {
			return $v % 2 === 0;
		} );

		//original is unchanged
		$this->assertEquals( 4, $d->count() );

		//new has 2
		$this->assertEquals( 2, $subset->count() );

		//new contains b and d
		$this->assertEquals( 2, $subset->get( 'b' ) );
		$this->assertEquals( 4, $subset->get( 'd' ) );

		//new is missing a and c
		$this->assertFalse( $subset->exists( 'a' ) );
		$this->assertFalse( $subset->exists( 'c' ) );
	}
}
