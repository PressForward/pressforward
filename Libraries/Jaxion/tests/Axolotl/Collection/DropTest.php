<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use PHPUnit_Framework_TestCase;

class DropTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	private $collection;
	private $elements;

	public function setup() {
		$this->collection = new Collection( 'int', $this->elements = array( 2, 4, 6, ) );
	}

	public function test_should_return_same() {
		$col = $this->collection->drop( 0 );

		$this->assertEquals( $this->collection, $col );
	}

	public function test_should_drop_first_element() {
		$col = $this->collection->drop( 1 );
		$this->assertEquals( 2, $col->count() );
		$this->assertEquals( $this->elements[1], $col->at( 0 ) );
		$this->assertEquals( $this->elements[2], $col->at( 1 ) );
	}

	public function test_should_drop_first_two_elements() {
		$col = $this->collection->drop( 2 );
		$this->assertEquals( 1, $col->count() );
		$this->assertEquals( $this->elements[2], $col->at( 0 ) );
	}

	public function test_should_drop_all_3_elements() {
		$col = $this->collection->drop( 3 );
		$this->assertEquals( 0, $col->count() );

		$col = $this->collection->drop( 4 );
		$this->assertEquals( 0, $col->count() );
	}
}
