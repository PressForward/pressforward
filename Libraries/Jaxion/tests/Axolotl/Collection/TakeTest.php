<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class FancyTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	private $col;

	public function setup() {
		$this->col = new Collection( 'int', array( 2, 4, 6, ) );
	}

	public function test_take_one_gives_col_with_first() {
		$result = $this->col->take( 1 );

		$this->assertEquals( 1, $result->count() );
		$this->assertEquals( 2, $result->at( 0 ) );
	}

	public function test_take_two_gives_two_from_left() {
		$result = $this->col->take( 2 );

		$this->assertEquals( 2, $result->count() );
		$this->assertEquals( 2, $result->at( 0 ) );
		$this->assertEquals( 4, $result->at( 1 ) );
	}

	public function test_take_all_gives_whole_collection() {
		$result = $this->col->take( 3 );

		$this->assertEquals( $this->col, $result );
	}

	public function test_take_negative_throws_ex() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->col->take( - 1 );
	}

	public function test_take_too_many_throws_ex() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->col->take( 100 );
	}
}
