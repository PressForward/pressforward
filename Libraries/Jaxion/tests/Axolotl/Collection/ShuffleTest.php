<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class ShuffleTest extends \PHPUnit_Framework_TestCase {
	public function test_shuffle() {
		$col = new Collection( 'int', array( 1, 2, 3, 4, 5, 6 ) );

		$shuffled = $col->shuffle();

		$this->assertTrue( $shuffled->contains( function ( $a ) {
			return $a == 1;
		} ) );
		$this->assertTrue( $shuffled->contains( function ( $a ) {
			return $a == 2;
		} ) );
		$this->assertTrue( $shuffled->contains( function ( $a ) {
			return $a == 3;
		} ) );
		$this->assertTrue( $shuffled->contains( function ( $a ) {
			return $a == 4;
		} ) );
		$this->assertTrue( $shuffled->contains( function ( $a ) {
			return $a == 5;
		} ) );
		$this->assertTrue( $shuffled->contains( function ( $a ) {
			return $a == 6;
		} ) );

		$this->assertNotEquals( $col, $shuffled );
	}
}
