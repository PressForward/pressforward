<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class DropWhileTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	protected $collection;

	public function setUp() {
		parent::setUp();

		$this->collection = new Collection( 'int', array( 2, 4, 6, 7, 8 ) );
	}
	public function test_should_use_callback_to_remove() {
		$col1 = $this->collection->drop_while( function ( $element ) {
			return $element % 2 === 0;
		} );

		$this->assertEquals( 2, $col1->count() );

		$this->assertEquals( 7, $col1->at( 0 ) );
		$this->assertEquals( 8, $col1->at( 1 ) );
	}

	public function test_should_remove_everything() {
		$col3 = $this->collection->drop_while( function ( $item ) {
			return true;
		} );

		$this->assertEquals( 0, $col3->count() );
	}

	public function test_should_return_same_instance() {
		$col2 = $this->collection->drop_while( function ( $item ) {
			return false;
		} );

		$this->assertEquals( $this->collection, $col2 );
	}
}
