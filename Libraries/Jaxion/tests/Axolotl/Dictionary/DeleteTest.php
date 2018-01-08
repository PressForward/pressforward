<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class DeleteTest extends PHPUnit_Framework_TestCase {
	public function test_delete_key_creates_second_dic_without_key() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
		) );
		$this->assertEquals( 2, $d->count() );

		$d2 = $d->delete( 'a' );
		$this->assertEquals( 2, $d->count() );
		$this->assertEquals( 1, $d2->count() );
		$this->assertFalse( $d2->value_exists( 1 ) );
	}
}
