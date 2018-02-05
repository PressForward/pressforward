<?php
namespace Intraxia\Jaxion\Test\Axolotl\Dictionary;

use Intraxia\Jaxion\Axolotl\Dictionary;
use Intraxia\Jaxion\Test\Stub\PostAndMetaModel;
use Mockery;
use PHPUnit_Framework_TestCase;

class AddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		parent::setUp();

		Mockery::mock('overload:WP_Post');
	}

	protected function tearDown() {
		parent::tearDown();

		Mockery::close();
	}


	public function test_adding_with_okay_types_adds_to_dictionary() {
		//string key and val
		$d = new Dictionary( 'string', 'string' );
		$d = $d->add( 'testkey', 'testval' );
		$this->assertCount( 1, $d );

		//int key and val
		$d = new Dictionary( 'int', 'int' );
		$d = $d->add( 1, 42 );
		$this->assertCount( 1, $d );

		//integer synonymous with int
		$d = new Dictionary( 'integer', 'integer' );
		$d = $d->add( 1, 42 );
		$this->assertCount( 1, $d );

		//float works as val
		$d = new Dictionary( 'string', 'float' );
		$d = $d->add( "a", 2.0 );
		$this->assertCount( 1, $d );

		//double synonymous with float
		$d = new Dictionary( 'string', 'double' );
		$d = $d->add( 'b', 2.0 );
		$this->assertCount( 1, $d );

		//bool valid value
		$d = new Dictionary( 'int', 'bool' );
		$d = $d->add( 1, false );
		$this->assertCount( 1, $d );

		//boolean synonymous with bool
		$d = new Dictionary( 'int', 'boolean' );
		$d = $d->add( 1, false );
		$this->assertCount( 1, $d );

		//callable works as value type
		$d = new Dictionary( 'string', 'callable' );
		$d = $d->add( 'test', function () {
		} );
		$this->assertCount( 1, $d );

		//class works as value type
		$d = new Dictionary( 'string', 'Intraxia\Jaxion\Test\Stub\PostAndMetaModel' );
		$d = $d->add( 'test', new PostAndMetaModel );
		$this->assertCount( 1, $d );

		//dervied classes, too work.
		$d = new Dictionary( 'string', 'Intraxia\Jaxion\Axolotl\Model' );
		$d = $d->add( 'test', new PostAndMetaModel );
		$this->assertEquals( 1, $d->count() );

		//interface works as value type
		$d = new Dictionary( 'string', 'Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost' );
		$d = $d->add( 'test', new PostAndMetaModel );
		$this->assertCount( 1, $d );
	}

	public function test_adding_with_invalid_key_type_throws_ex() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'string', 'int' );
		$d = $d->add( 4, 1977 );
	}

	public function test_adding_with_invalid_value_type_throws_ex() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'string', 'string' );
		$d = $d->add( "Episode IV", 1977 );
	}

	public function test_array_is_invalid_key_type() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'array', 'int' );
	}

	public function test_object_is_invalid_key_type() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'object', 'int' );
	}

	public function test_callable_is_invalid_key_type() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'callable', 'int' );
	}

	public function test_class_is_invalid_key_type() {
		$this->setExpectedException( 'InvalidArgumentException' );

		new Dictionary( 'Intraxia\Jaxion\Axolotl\Model', 'int' );
	}

	public function test_interface_is_invalid_key_type() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost', 'int' );
	}

	public function test_madeup_string_is_invalid_key_type() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'test2', 'int' );
	}

	public function test_float_is_invalid_key_type() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'float', 'int' );
	}

	public function test_setting_key_will_overwrite_if_exists() {
		$d = new Dictionary( 'string', 'int' );
		$d = $d->add('key', 1) ->add('key', 2);

		$this->assertEquals( 2, $d->get( 'key' ) );
	}

	public function test_ex_thrown_if_callable_is_not_passed() {
		$this->setExpectedException( 'InvalidArgumentException' );
		$d = new Dictionary( 'string', 'callable' );
		$d = $d->add( 'test', 123 );
	}

}
