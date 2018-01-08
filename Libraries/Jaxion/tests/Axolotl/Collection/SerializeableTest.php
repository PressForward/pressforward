<?php
namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class SerializeableTest extends \PHPUnit_Framework_TestCase {
	public function test_should_not_change_unserializable() {
		$args       = array( 'a', 'b' );
		$collection = new Collection( 'string', $args );

		$this->assertSame( $args, $collection->serialize() );
	}

	public function test_should_serialize_serializables() {
		$collection = new Collection( 'Intraxia\Jaxion\Test\Stub\TableModel', array(
			array(
				'title' => 'Post title',
				'text'  => 'Text value',
				'url'   => 'google.com'
			)
		) );

		$this->assertCount( 1, $collection->serialize() );
	}
}
