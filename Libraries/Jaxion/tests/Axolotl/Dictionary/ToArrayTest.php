<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;

class ToArrayTest extends \PHPUnit_Framework_TestCase {
	public function test_to_array_returns_assoc_array() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
		) );

		$this->assertEquals( array( 'a' => 1, 'b' => 2 ), $d->to_array() );
	}
}
