<?php

namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use InvalidArgumentException;

class MergeTest extends \PHPUnit_Framework_TestCase {
	public function test_can_merge_dict() {
		$l = new Dictionary( 'string', 'int', array( 'a' => 1 ) );
		$r = new Dictionary( 'string', 'int', array( 'b' => 2 ) );

		$m = $l->merge( $r );

		$this->assertEquals( 2, $m->count() );
		$this->assertEquals( 1, $m->get( 'a' ) );
		$this->assertEquals( 2, $m->get( 'b' ) );
	}

	public function test_can_merge_array() {
		$l = new Dictionary( 'string', 'int', array( 'a' => 1 ) );
		$r = array( 'b' => 2 );

		$m = $l->merge( $r );

		$this->assertEquals( 2, $m->count() );
		$this->assertEquals( 1, $m->get( 'a' ) );
		$this->assertEquals( 2, $m->get( 'b' ) );
	}

	public function test_new_keys_overwrite_old_ones() {
		$l = new Dictionary( 'string', 'int', array( 'a' => 1 ) );
		$r = new Dictionary( 'string', 'int', array( 'a' => 2 ) );

		$m = $l->merge( $r );

		$this->assertEquals( 1, $m->count() );
		$this->assertEquals( 2, $m->get( 'a' ) );
	}

	public function test_merge_of_bad_types_fails() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$l = new Dictionary( 'string', 'int', array( 'a' => 1 ) );
		$r = new Dictionary( 'string', 'int', array( 'b' => '2' ) );

		$m = $l->merge( $r );
	}

	public function test_merge_of_non_array() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$d = new Dictionary( 'string', 'int', array( 'a' => 1 ) );

		$d->merge( 3 );
	}
}
