<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use PHPUnit_Framework_TestCase;

class EachTest extends PHPUnit_Framework_TestCase {
	public function test_fn_applied_to_every_item() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
		) );

		$results = array();

		$d->each( function ( $v, $k ) use ( &$results ) {
			$results[ $k ] = $v;
		} );

		$this->assertEquals( array( 'a' => 1, 'b' => 2 ), $results );
	}

	public function test_for_each_works_too() {
		$d = new Dictionary( 'string', 'int', array(
			'a' => 1,
			'b' => 2,
		) );

		$results = array();

		foreach ( $d as $k => $v ) {
			$results[ $k ] = $v;
		}

		$this->assertEquals( array( "a" => 1, "b" => 2 ), $results );
	}
}
