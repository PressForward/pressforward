<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;

class KeysTest extends \PHPUnit_Framework_TestCase {
	public function test_keys() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		) );

		$result = $d->keys();

		$this->assertEquals( array( 'a', 'b', 'c', 'd' ), $result );
	}
}
