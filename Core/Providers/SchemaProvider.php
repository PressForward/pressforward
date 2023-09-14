<?php
/**
 * Service provider for PF schema.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;
use PressForward\Core\Schema\Nominations;
use PressForward\Core\Schema\Folders;
use PressForward\Core\Schema\Feed_Items;
use PressForward\Core\Schema\Feeds;
use PressForward\Core\Schema\Relationships;

/**
 * AssetsProvider class.
 */
class SchemaProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container object.
	 */
	public function register( Container $container ) {
		$container->share(
			'schema.relationships',
			function () {
				return new Relationships();
			}
		);

		$container->share(
			'schema.feeds',
			function ( $container ) {
				return new Feeds( $container->fetch( 'controller.metas' ) );
			}
		);

		$container->share(
			'schema.feed_item',
			function () {
				return new Feed_Items( pressforward( 'controller.items' ), pressforward( 'controller.metas' ) );
			}
		);

		$container->share(
			'schema.folders',
			function () {
				return new Folders();
			}
		);

		$container->share(
			'schema.nominations',
			function () {
				return new Nominations();
			}
		);

		parent::register( $container );
	}
}
