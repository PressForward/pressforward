<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
//use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\Admin\PFTemplater;

class AdminAreaServiceProvider extends ServiceProvider {

	public function register( Container $container ){
		$container->define(
			'admin.templates',
			function( $container ){
				return new PFTemplater( $container->fetch( 'controller.template_factory' ), $container->fetch( 'controller.users' ) );
			}
		);
		$container->define(
			'admin.menu',
			new Menu( $container )
		);
		//parent::register( $container );
		//var_dump(__METHOD__); die();
	}

}