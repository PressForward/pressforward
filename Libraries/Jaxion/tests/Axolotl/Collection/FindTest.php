<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class FindTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	private $collection;

	public function setUp() {
		parent::setUp();
		$this->collection = new Collection( 'int', array( 1, 2, 3, 4, 5 ) );
	}

	public function test_should_return_first_element_match() {
		$this->assertEquals( 2, $this->collection->find( function ( $element ) {
			return $element % 2 === 0;
		} ) );
	}

	public function test_should_return_element_element_match() {
		$this->assertEquals( 4, $this->collection->find_last( function ( $element ) {
			return $element % 2 === 0;
		} ) );
	}

	public function test_should_return_null_if_no_match() {
		$this->assertNull( $this->collection->find( function ( $element ) {
			return $element > 10;
		} ) );

		$this->assertNull( $this->collection->find_last( function ( $element ) {
			return $element > 10;
		} ) );
	}
}
