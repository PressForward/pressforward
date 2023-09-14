<?php
/**
 * Service provider for bookmarklet.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;
use PressForward\Core\Bookmarklet\NominateThisCore;

/**
 * BookmarkletProvider class.
 */
class BookmarkletProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {

	/**
	 * {@inheritDoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container.
	 */
	public function register( Container $container ) {
		$api_base = array(
			'version'        => 1,
			'base_namespace' => 'pf/v',
		);

		$container->share(
			'bookmarklet.core',
			function ( $container ) {
				return new NominateThisCore();
			}
		);

		return $container;
	}
}
