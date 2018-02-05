<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class SliceTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Collection
	 */
	private $c;

	/**
	 * @var array
	 */
	private $elements;

	public function setUp() {
		parent::setUp();
		$this->elements = array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, );

		$this->c = new Collection( 'int', $this->elements );
	}

	public function test_slice_negative_start_throws_ex() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->c->slice( -1, 3 );
	}

	public function test_slice_negative_end_throws_ex() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->c->slice( 1, -4 );
	}

	public function test_start_gt_end_is_invalid() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->c->slice( 3, 2 );
	}

	public function test_start_out_of_range_is_invalid() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->c->slice( 20, 22 );
	}

	public function test_end_out_of_range_is_invalid() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->c->slice( 2, 22 );
	}

	public function test_subset_within_middle_of_collection_gives_correct_indices() {
		$subset = $this->c->slice( 2, 4 );
		$this->assertEquals( 3, $subset->count() );

		$this->assertEquals( $this->elements[2], $subset->at( 0 ) );
		$this->assertEquals( $this->elements[3], $subset->at( 1 ) );
		$this->assertEquals( $this->elements[4], $subset->at( 2 ) );
	}

	public function test_subset_from_start_gives_correct_indices() {
		$subset = $this->c->slice( 0, 1 );
		$this->assertEquals( 2, $subset->count() );

		$this->assertEquals( $this->elements[0], $subset->at( 0 ) );
		$this->assertEquals( $this->elements[1], $subset->at( 1 ) );
	}

	public function test_subset_to_end_gives_correct_indices() {
		$subset = $this->c->slice( 8, 9 );
		$this->assertEquals( 2, $subset->count() );

		$this->assertEquals( $this->elements[8], $subset->at( 0 ) );
		$this->assertEquals( $this->elements[9], $subset->at( 1 ) );
	}

	public function test_subset_from_start_with_one_item() {
		$subset = $this->c->slice( 0, 0 );
		$this->assertEquals( 1, $subset->count() );
		$this->assertEquals( $this->elements[0], $subset->at( 0 ) );
	}

	public function test_subset_with_only_end() {
		$subset = $this->c->slice( 9, 9 );
		$this->assertEquals( 1, $subset->count() );
		$this->assertEquals( $this->elements[9], $subset->at( 0 ) );
	}
}
