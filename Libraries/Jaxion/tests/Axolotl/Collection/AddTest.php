<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use DateTime;
use DateTimeZone;
use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Test\Stub\TableModel;
use PHPUnit_Framework_TestCase;
use stdClass;

class AddTest extends PHPUnit_Framework_TestCase {
	public function test_add_item_creates_new_col_with_item() {
		$col = new Collection( 'Intraxia\Jaxion\Test\Stub\TableModel' );

		//count should be zero
		$this->assertEquals( 0, $col->count() );
		$this->assertCount( 0, $col );

		//add the item
		$a    = new TableModel;
		$col2 = $col->add( $a );

		//col should be unchanged
		$this->assertEquals( 0, $col->count() );
		$this->assertEquals( 1, $col2->count() );
		$this->assertCount( 1, $col2 );
		$this->assertEquals( $a, $col2->at( 0 ) );
	}

	public function test_should_throw_error_on_wrong_class() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$col = new Collection( 'Intraxia\Jaxion\Test\Stub\TableModel' );
		$col->add( new stdClass() );
	}

	public function test_should_accept_subtypes() {
		$col = new Collection( 'Intraxia\Jaxion\Axolotl\Model' );
		$col = $col->add( new TableModel );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_integer() {
		$col = new Collection( 'integer' );
		$col = $col->add( 1 );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_int() {
		$col = new Collection( 'int' );
		$col = $col->add( 1 );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_float() {
		$col = new Collection( 'float' );
		$col = $col->add( 1.0 );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_double() {
		$col = new Collection( 'double' );
		$col = $col->add( 1.0 );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_string() {
		$col = new Collection( 'string' );
		$col = $col->add( 'mystring' );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_bool() {
		$col = new Collection( 'bool' );
		$col = $col->add( true );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_boolean() {
		$col = new Collection( 'boolean' );
		$col = $col->add( true );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_array() {
		$col = new Collection( 'array' );
		$col = $col->add( array( 1, 2, 3 ) );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_throw_for_non_existent_type() {
		$this->setExpectedException( 'InvalidArgumentException' );

		new Collection( 'this_type_is_invalid' );
	}

	public function test_collection_supports_interfaces() {
		$col = new Collection( 'Intraxia\Jaxion\Contract\Axolotl\Collection' );
		$col = $col->add( new Collection( 'string' ) );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_support_callables() {
		$col = new Collection( 'callable' );
		$col = $col->add( function () {} );
		$col = $col->add( array( $col, 'add' ) );

		$this->assertEquals( 2, $col->count() );
	}

	public function test_should_throw_for_different_types() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$col = new Collection( 'int' );
		$col = $col->add( 'string' );
	}

	public function test_should_support_objects() {
		$col = new Collection( 'object' );
		$col = $col->add( new DateTime( 'now', new DateTimeZone( 'Etc/UTC' )) );

		$this->assertEquals( 1, $col->count() );
	}

	public function test_should_add_model_to_collection_by_array() {
		$collection = new Collection( 'Intraxia\Jaxion\Test\Stub\TableModel', array(
			array( 'title' => 'Post title' )
		));

		$this->assertInstanceOf( 'Intraxia\Jaxion\Test\Stub\TableModel', $collection->at( 0 ) );

		$collection = $collection->add( array( 'title' => 'Post title 2' ) );

		$this->assertInstanceOf( 'Intraxia\Jaxion\Test\Stub\TableModel', $collection->at( 1 ) );
		$this->assertSame( 'Post title 2', $collection->at( 1 )->title );
	}

	public function test_should_add_model_to_collection_by_model() {
		$collection = new Collection(
			$class = 'Intraxia\Jaxion\Test\Stub\TableModel',
			array( array( 'title' => 'Post title' ) )
		);

		$this->assertInstanceOf( $class, $collection->at( 0 ) );

		$collection = $collection->add( new $class( array( 'title' => 'Post title 2' ) ) );

		$this->assertInstanceOf( 'Intraxia\Jaxion\Test\Stub\TableModel', $collection->at( 1 ) );
		$this->assertSame( 'Post title 2', $collection->at( 1 )->title );
	}
}
