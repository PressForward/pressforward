<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class ClearTest extends PHPUnit_Framework_TestCase {
	public function test_clear_creates_empty_dictionary_of_same_type() {
		$d = new Dictionary( 'string', 'int' );
		$d = $d->add( 'a', 1 );

		$empty = $d->clear();

		//original is unchanged
		$this->assertEquals( 1, $d->count() );
		$this->assertEquals( 0, $empty->count() );

		//key and value types should be the same
		$this->assertEquals( $d->get_key_type(), $empty->get_key_type() );
		$this->assertEquals( $d->get_value_type(), $empty->get_value_type() );
	}
}
