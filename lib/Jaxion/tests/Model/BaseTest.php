<?php
namespace Intraxia\Jaxion\Test\Model;

use Intraxia\Jaxion\Test\Stubs\MetaBase;
use Intraxia\Jaxion\Test\Stubs\TableBase;
use Mockery;

/**
 * @group model
 */
class BaseTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
		$mockPost = Mockery::mock( 'overload:WP_Post' );
	}

	public function test_should_construct_to_meta_with_no_table() {
		$base = new MetaBase( array(
			'test' => 'value'
		) );

		$attributes = $base->get_attributes();
		$this->assertSame( 'value', $attributes['test'] );
		$this->assertSame( 'value', $base->test );
	}

	public function test_should_assign_to_meta_with_no_table() {
		$base       = new MetaBase();
		$base->test = 'value';

		$attributes = $base->get_attributes();
		$this->assertSame( 'value', $attributes['test'] );
		$this->assertSame( 'value', $base->test );
	}

	public function test_should_construct_to_table() {
		$base = new TableBase( array(
			'test' => 'value'
		) );

		$attributes = $base->get_attributes();
		$this->assertSame( 'value', $attributes['test'] );
		$this->assertSame( 'value', $base->test );
	}

	public function test_should_assign_to_table() {
		$base       = new TableBase();
		$base->test = 'value';

		$attributes = $base->get_attributes();
		$this->assertSame( 'value', $attributes['test'] );
		$this->assertSame( 'value', $base->test );
	}

	public function test_should_not_assign_post() {
		$base = new TableBase();

		$this->assertFalse( $base->get_underlying_post() );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}
}
