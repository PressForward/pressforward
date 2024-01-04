<?php
/**
 * Service provider for Blocks.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;
use PressForward\Core\Blocks\Blocks;

/**
 * BlocksProvider class.
 */
class BlocksProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {

	/**
	 * {@inheritDoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container.
	 */
	public function register( Container $container ) {
		$container->share(
			'blocks',
			function ( $container ) {
				return new Blocks();
			}
		);

		return $container;
	}
}
