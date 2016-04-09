<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Libraries\HTMLChecker as HTMLChecker;

class LibrariesProvider extends ServiceProvider {

	public function register( Container $container ){

		$container->share(
			'library.htmlchecker',
			function( $container ){
				return new HTMLChecker;
			}
		);

		//parent::register( $container );

	}
}
