<?php
/**
 * Service provider for admin area.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;
use PressForward\Controllers\Modules;

/**
 * ModulesProvider class.
 */
class ModulesProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ) {

		require_once PF_ROOT . '/includes/module-base.php';

		$container->share(
			'modules',
			function ( $container ) {
				return new Modules();
			}
		);
	}
}
