<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use PHPUnit_Framework_TestCase;

class IndexExistsTest extends PHPUnit_Framework_TestCase {
	public function test_should_correctly_check_index() {
		$col = new Collection( 'int', array( 1, 2 ) );
		$this->assertTrue( $col->index_exists( 0 ) );
		$this->assertTrue( $col->index_exists( 1 ) );
		$this->assertFalse( $col->index_exists( 2 ) );
	}

	public function test_should_throw_on_negative_index() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$col = new Collection( 'int' );
		$col->index_exists( -1 );
	}

	public function test_should_throw_on_invalid_index() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$col = new Collection( 'int' );
		$col->index_exists( 'wat' );
	}
}
