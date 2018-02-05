<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class ValuesTest extends PHPUnit_Framework_TestCase {
	public function test_values() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		) );

		$result = $d->values();

		$this->assertEquals( array( 1, 2, 3, 4 ), $result );
	}
}
