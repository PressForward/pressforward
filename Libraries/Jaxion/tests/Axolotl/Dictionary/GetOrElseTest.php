<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class GetOrElseTest extends PHPUnit_Framework_TestCase {
	public function test_get_or_else() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		) );

		$result  = $d->get_or_else( 'a', 'default' );
		$result2 = $d->get_or_else( 'e', 'default' );

		$this->assertEquals( 1, $result );
		$this->assertEquals( 'default', $result2 );
	}
}
