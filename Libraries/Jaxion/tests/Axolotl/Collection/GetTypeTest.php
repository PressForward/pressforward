<?php

namespace Intraxia\Jaxion\Test\Axolotl\Collection;

use Intraxia\Jaxion\Axolotl\Collection;

class GetTypeTest extends \PHPUnit_Framework_TestCase {
	public function test_should_return_collection_type() {
		$collection = new Collection( 'int' );
		$this->assertEquals( 'integer', $collection->get_type() );
	}
}
