<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Libraries\HTMLChecker as HTMLChecker;
use AlertBox\The_Alert_Box as The_Alert_Box;

class LibrariesProvider extends ServiceProvider {

	public function register( Container $container ){

		$container->share(
			'library.htmlchecker',
			function( $container ){
				return new HTMLChecker;
			}
		);

		$container->share(
			'library.alertbox',
			function( $container ){
				return The_Alert_Box::init();
			}
		);

		//parent::register( $container );

	}
}
