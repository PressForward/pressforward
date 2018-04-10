<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use PHPUnit_Framework_TestCase;

class DropRightTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	protected $collection;
	protected $elements;

	public function setup() {
		$this->collection = new Collection( 'int', $this->elements = array( 2, 4, 6, ) );
	}

	public function test_should_return_same() {
		$col = $this->collection->drop_right( 0 );
		$this->assertEquals( $this->collection, $col );
	}

	public function test_should_drop_last_element() {
		$col = $this->collection->drop_right( 1 );
		$this->assertEquals( 2, $col->count() );
		$this->assertEquals( $this->elements[0], $col->at( 0 ) );
		$this->assertEquals( $this->elements[1], $col->at( 1 ) );
	}

	public function test_should_drop_last_two_elements() {
		$col = $this->collection->drop_right( 2 );
		$this->assertEquals( 1, $col->count() );
		$this->assertEquals( $this->elements[0], $col->at( 0 ) );
	}
}
