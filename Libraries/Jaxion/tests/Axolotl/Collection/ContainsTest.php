<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Test\Stub\TableModel;
use PHPUnit_Framework_TestCase;

class ContainsTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	protected $collection;

	public function setup() {
		$this->collection = new Collection( 'Intraxia\Jaxion\Test\Stub\TableModel', array(
			new TableModel( array(
				'title' => 'Post title 1',
				'text'  => 'Text value 1',
				'url'   => 'google.com/1',
			) ),
			new TableModel( array(
				'title' => 'Post title2',
				'text'  => 'Text value 2',
				'url'   => 'google.com/2',
			) ),
		) );
	}

	public function test_should_find_target_and_return_true() {
		$result = $this->collection->contains( function ( TableModel $item ) {
			return $item->title == 'Post title 1';
		} );

		$this->assertTrue( $result );
	}


	public function test_should_return_false_when_no_match() {
		$result = $this->collection->contains( function ( TableModel $item ) {
			return $item->title == 'Post title 3';
		} );

		$this->assertFalse( $result );
	}
}
