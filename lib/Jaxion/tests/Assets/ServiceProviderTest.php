<?php
namespace Intraxia\Jaxion\Test\Assets;

use Intraxia\Jaxion\Assets\ServiceProvider;
use Mockery;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase {
	public function test_should_define_register_on_container() {
		$provider  = new ServiceProvider;
		$container = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\Container' );
		$container->shouldReceive( 'fetch' )
			->twice()
			->andReturn( 'test.com/', '1.0.0' );
		$container->shouldReceive( 'define' )
			->once()
			->with(
				array( 'assets' => 'Intraxia\Jaxion\Contract\Assets\Register' ),
				Mockery::type( 'Intraxia\Jaxion\Contract\Assets\Register' )
			);

		$provider->register( $container );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}
}
