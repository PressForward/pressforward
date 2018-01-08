<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use DateTime;
use DateTimeZone;
use Intraxia\Jaxion\Axolotl\Collection;

class MapTest extends \PHPUnit_Framework_TestCase {
	public function test_should_map_ints() {
		$collection = new Collection( 'int', array( 1, 2, 3, 4 ) );

		$result = $collection->map( function ( $a ) {
			return $a * 3;
		} );

		$this->assertEquals( new Collection( 'int', array( 3, 6, 9, 12 ) ), $result );
	}

	public function test_should_map_strings() {
		$collection = new Collection( 'string', array( 'a', 'b', 'c', 'd ') );

		$result = $collection->map( function ( $a ) {
			return strtoupper( $a );
		} );

		$this->assertEquals( new Collection( 'string', array( 'A', 'B', 'C', 'D ') ), $result );
	}

	public function test_should_map_to_new_type() {
		$collection = new Collection(
			'string',
			array ('05/01/2016', '05/02/2016', '05/03/2016', '05/04/2016' )
		);

		$result = $collection->map( function ( $a ) {
			return new DateTime( $a, new DateTimeZone( 'Etc/UTC' ) );
		} );

		$this->assertEquals( array(
			new DateTime( '05/01/2016', new DateTimeZone( 'Etc/UTC' ) ),
			new DateTime( '05/02/2016', new DateTimeZone( 'Etc/UTC' ) ),
			new DateTime( '05/03/2016', new DateTimeZone( 'Etc/UTC' ) ),
			new DateTime( '05/04/2016', new DateTimeZone( 'Etc/UTC' ) ),
		), $result->to_array() );

		$count  = 0;
		$result = $collection->map( function () use ( &$count ) {
			return $count ++;
		} );

		$this->assertEquals( array( 0, 1, 2, 3 ), $result->to_array() );
	}
}
