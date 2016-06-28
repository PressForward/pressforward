<?php
namespace Intraxia\Jaxion\Test\Http;

use Intraxia\Jaxion\Http\ServiceProvider;
use Mockery;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase {
	public function test_should_define_router_on_container() {
		$provider  = new ServiceProvider;
		$container = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\Container' );
		$container->shouldReceive( 'define' )
			->once()
			->with( array( 'router' => 'Intraxia\Jaxion\Http\Router' ), Mockery::type( 'Intraxia\Jaxion\Http\Router' ) );

		$provider->register( $container );
	}

	protected function tearDown() {
		parent::tearDown();
		Mockery::close();
	}
}
