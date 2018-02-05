<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use PHPUnit_Framework_TestCase;

class FindIndexTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	protected $collection;

	protected function setUp() {
		parent::setUp();

		$this->collection = new Collection( 'int', array( 2, 4, 6, 8 ) );
	}

	public function test_should_find_correct_index() {
		$this->assertEquals( 0, $this->collection->find_index( function ( $item ) {
			return $item % 2 === 0;
		} ) );

	}

	public function test_should_return_neg_one_if_not_found() {
		$this->assertEquals( - 1, $this->collection->find_index( function ( $item ) {
			return $item % 2 !== 0;
		} ) );
	}

	public function test_should_find_correct_last_index() {
		$this->assertEquals( 3, $this->collection->find_last_index( function ( $item ) {
			return $item % 2 === 0;
		} ) );
	}

	public function test_should_return_neg_one_if_last_not_found() {
		$this->assertEquals( - 1, $this->collection->find_last_index( function ( $item ) {
			return $item % 2 !== 0;
		} ) );
	}
}
