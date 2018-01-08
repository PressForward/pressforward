<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class ExistsTest extends PHPUnit_Framework_TestCase {
	public function test_key_exists_returns_true() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
		) );

		$this->assertTrue( $d->exists( 'a' ) );
	}

	public function test_key_exists_returns_false() {
		$d = new Dictionary( 'string', 'int', array( 'a' => 1 ) );

		$this->assertFalse( $d->exists( 'b' ) );
	}

	public function test_value_exists_returns_true() {
		$d = new Dictionary( 'string', 'int', array( 'a' => 1 ) );

		$this->assertTrue( $d->value_exists( 1 ) );
	}

	public function test_value_exists_returns_false() {
		$d = new Dictionary( 'string', 'int', array( 'a' => 1 ) );

		$this->assertFalse( $d->value_exists( 2 ) );
	}
}
