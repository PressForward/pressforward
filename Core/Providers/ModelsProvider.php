<?php
/**
 * Service provider for PF data models.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\Models\Feed_Item as Feed_Item;

/**
 * AssetsProvider class.
 */
class ModelsProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ) {
		$container->define(
			'model.feed_item',
			function( $container ) {
				return ( function( $item_url, $item_title, $post_type = false ) {
					return new Feed_Item( $item_url, $item_title, $post_type );
				} );
			}
		);
	}
}
