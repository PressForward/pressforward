<?php
/**
 * Service provider for preferences.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Preferences;
use Intraxia\Jaxion\Contract\Core\Container;

/**
 * PreferencesServiceProvider class.
 */
class PreferencesServiceProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
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
