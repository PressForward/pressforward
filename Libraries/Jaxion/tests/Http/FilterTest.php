<?php
namespace Intraxia\Jaxion\Test\Http;

use Intraxia\Jaxion\Http\Filter;
use PHPUnit_Framework_TestCase;
use WP_Mock;

class FilterTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
		WP_Mock::setUp();
	}

	public function test_should_ignore_empty_validation() {
		$filter = new Filter( array( 'param' => '' ) );

		$this->assertSame( array(), $filter->rules() );
	}

	public function test_should_set_arg_as_required() {
		$filter = new Filter( array( 'param' => 'required' ) );

		$this->assertSame( array( 'param' => array( 'required' => true ) ), $filter->rules() );
	}

	public function test_should_set_arg_default() {
		$filter = new Filter( array( 'param' => 'default:value' ) );

		$this->assertSame( array( 'param' => array( 'default' => 'value' ) ), $filter->rules() );
	}

	public function test_should_set_empty_string_as_default_if_no_value() {
		$filter = new Filter( array( 'param' => 'default' ) );

		$this->assertSame( array( 'param' => array( 'default' => '' ) ), $filter->rules() );

		$filter = new Filter( array( 'param' => 'default:' ) );

		$this->assertSame( array( 'param' => array( 'default' => '' ) ), $filter->rules() );
	}

	public function test_should_validate_integer() {
		$filter = new Filter( array( 'param' => 'integer' ) );

		$this->assertSame( array(
			'param' => array(
				'validate_callback' => array( $filter, 'validate_integer' ),
				'sanitize_callback' => array( $filter, 'make_integer' ),
			)
		), $filter->rules() );
	}

	public function test_integer_strings_should_validate() {
		$filter = new Filter();

		$this->assertTrue( $filter->validate_integer( '1234567890' ) );
	}

	public function test_integer_ints_should_validate() {
		$filter = new Filter();

		$this->assertTrue( $filter->validate_integer( 1234567890 ) );
	}

	public function test_non_integer_shouldnt_validate() {
		$filter = new Filter;

		foreach ( array( 'text', '' ) as $value ) {
			$this->assertFalse( $filter->validate_integer( $value ), sprintf( '%s is erroneously valid.', $value ) );
		}
	}

	public function test_integer_string_should_be_cast() {
		$filter = new Filter();

		$this->assertSame( 1234567890, $filter->make_integer( '1234567890' ) );
	}

	public function test_integer_should_be_returned() {
		$filter = new Filter();

		$this->assertSame( 1234567890, $filter->make_integer( 1234567890 ) );
	}

	public function test_should_validate_oneof() {
		$filter = new Filter( array(
			'param' => 'oneof:a,b,c',
		) );

		$rules = $filter->rules();

		$this->assertTrue( call_user_func( $rules['param']['validate_callback'], 'a' ) );
		$this->assertTrue( call_user_func( $rules['param']['validate_callback'], 'b' ) );
		$this->assertTrue( call_user_func( $rules['param']['validate_callback'], 'c' ) );
		$this->assertFalse( call_user_func( $rules['param']['validate_callback'], 'f' ) );
	}

	public function test_should_take_multiple_rules() {
		$filter = new Filter( array(
			'param' => 'default:value|integer'
		) );

		$this->assertSame( array(
			'param' => array(
				'default'           => 'value',
				'validate_callback' => array( $filter, 'validate_integer' ),
				'sanitize_callback' => array( $filter, 'make_integer' ),
			)
		), $filter->rules() );
	}
}
