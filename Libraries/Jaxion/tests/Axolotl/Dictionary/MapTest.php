<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;

class MapTest extends \PHPUnit_Framework_TestCase {
	public function test_map_infers_type_for_dict() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
		) );

		$m = $d->map( function ( $v ) {
			return 42;
		} );

		$this->assertEquals( 'string', $m->get_key_type() );
		$this->assertEquals( 'integer', $m->get_value_type() );
		$this->assertEquals( 42, $m->get( 'a' ) );
	}

	public function test_map_example() {
		$d = new Dictionary( 'int', 'string', array(
			1 => 'a',
			2 => 'b',
		) );

		$m = $d->map( function ( $v ) {
			return ord( $v );
		} );

		$this->assertEquals( 'integer', $m->get_value_type() );
		$this->assertEquals( 'integer', $m->get_key_type() );
		$this->assertEquals( ord( 'a' ), $m->get( 1 ) );
		$this->assertEquals( ord( 'b' ), $m->get( 2 ) );
	}
}
