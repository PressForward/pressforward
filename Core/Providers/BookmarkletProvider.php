<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
// use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\Bookmarklet\NominateThisCore;

class BookmarkletProvider extends ServiceProvider {

	public function register( Container $container ) {

		$api_base = array(
			'version'        => 1,
			'base_namespace' => 'pf/v',
		);

		$container->share(
			'bookmarklet.core',
			function( $container ) {
				return new NominateThisCore();
			}
		);

		return $container;
	}

}
