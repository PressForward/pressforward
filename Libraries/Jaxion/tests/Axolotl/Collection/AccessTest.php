<?php
namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Test\Stub\TableModel;
use PHPUnit_Framework_TestCase;

class AccessTest extends PHPUnit_Framework_TestCase {

	public function test_should_throw_access_invalid_index() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$collection = new Collection('Intraxia\Jaxion\Test\Stub\TableModel', array( new TableModel ) );
		$collection->at('one');
	}

	public function test_should_throw_missing_index() {
		$this->setExpectedException( 'OutOfRangeException' );

		$collection = new Collection('Intraxia\Jaxion\Test\Stub\TableModel', array( new TableModel ) );
		$collection->at(1);
	}

	public function test_should_throw_negative_index() {
		$this->setExpectedException ( 'InvalidArgumentException' );

		$collection = new Collection('Intraxia\Jaxion\Test\Stub\TableModel', array( new TableModel ) );
		$collection->at(-1);
	}

	public function test_should_return_value() {
		$collection = new Collection('Intraxia\Jaxion\Test\Stub\TableModel', array( new TableModel ) );
		$res = $collection->at(0);

		$this->assertEquals(new TableModel, $res);
	}
}
