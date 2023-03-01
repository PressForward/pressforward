<?php
/**
 * Service provider for preferences.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Preferences;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

/**
 * PreferencesServiceProvider class.
 */
class PreferencesServiceProvider extends ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ) {
		$container->share(
			'admin.settings',
			new Preferences( $container->fetch( 'basename' ), $container->fetch( 'admin.templates' ) )
		);
	}
}
