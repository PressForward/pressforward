<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
//use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use Intraxia\Jaxion\Contract\Core\ServiceProvider as ServiceProvider;

class AdminAreaServiceProvider implements ServiceProvider {

	public function register( Container $container ){
		$container->define(
			'admin.menu',
			new Menu( $container->fetch( 'basename' ) )
		);

		//var_dump(__METHOD__); die();
	}

}