<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use PressForward\Core\Schema\Nominations;
use PressForward\Core\Schema\Folders as Folders;
use PressForward\Core\Schema\Feed_Items;
use PressForward\Core\Schema\Feeds;
use PressForward\Core\Schema\Relationships;

class SchemaProvider extends ServiceProvider {

	public function register( Container $container ) {

		$container->share(
			'schema.relationships',
			function() {
				return new Relationships();
			}
		);

		$container->share(
			'schema.feeds',
			function( $container ) {
				return new Feeds( $container->fetch( 'controller.metas' ) );
			}
		);

		$container->share(
			'schema.feed_item',
			function() {
				return new Feed_Items( pressforward( 'controller.items' ), pressforward( 'controller.metas' ) );
			}
		);

		$container->share(
			'schema.folders',
			function() {
				return new Folders();
			}
		);

		$container->share(
			'schema.nominations',
			function() {
				return new Nominations();
			}
		);

		parent::register( $container );

	}
}
