<?php

namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;

class RejectTest extends \PHPUnit_Framework_TestCase {
	public function test_filter() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		) );

		$subset = $d->reject( function ( $v ) {
			return $v % 2 === 0;
		} );

		//original is unchanged
		$this->assertEquals( 4, $d->count() );

		//new has 2
		$this->assertEquals( 2, $subset->count() );

		//new does not have b and d
		$this->assertFalse( $subset->exists( 'b' ) );
		$this->assertFalse( $subset->exists( 'd' ) );

		//new has a and c
		$this->assertTrue( $subset->exists( 'a' ) );
		$this->assertTrue( $subset->exists( 'c' ) );

		$this->assertEquals( 1, $subset->get( 'a' ) );
		$this->assertEquals( 3, $subset->get( 'c' ) );
	}
}
