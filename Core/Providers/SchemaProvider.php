<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use PressForward\Core\Schema\Nominations;
use PressForward\Core\Schema\Folders as Folders;
use PressForward\Core\Schema\Feed_Item;
use PressForward\Core\Schema\Feeds;

class SchemaProvider extends ServiceProvider {

	public function register( Container $container ){

		$container->share(
			'schema.feeds',
			function( ){
				return new Feeds;
			}
		);

		$container->share(
			'schema.feed_item',
			function( ){
				return new Feed_Item;
			}
		);

		$container->share(
			'schema.folders',
			function( ){
				return new Folders;
			}
		);


		$container->share(
			'schema.nominations',
			function( ){
				return new Nominations;
			}
		);

		parent::register( $container );

	}
}
