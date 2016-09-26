<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
//use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\API\PostExtension;
use PressForward\Core\API\FeedEndpoint;


class APIProvider extends ServiceProvider {

	public function register( Container $container ){
		$container->share(
			'api.post_extension',
			function( $container ){
				return new PostExtension( $container->fetch('controller.metas') );
			}
		);
		$container->share(
			'api.feed_endpoint',
			function( $container ){
				return new FeedEndpoint( $container->fetch('controller.metas') );
			}
		);
	}

}
