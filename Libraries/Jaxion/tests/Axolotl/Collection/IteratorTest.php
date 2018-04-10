<?php
namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class IteratorTest extends \PHPUnit_Framework_TestCase {
	public function test_should_iterate_correctly() {
		foreach ( new Collection( 'string', array( 'a', 'b' ) ) as $key => $item ) {
			$this->assertTrue( in_array( $key, array( 0, 1 ) ) );

			switch ( $key ) {
				case 0:
					$this->assertSame( 'a', $item );
					break;
				case 1:
					$this->assertSame( 'b', $item );
					break;
			}
		}
	}
}
