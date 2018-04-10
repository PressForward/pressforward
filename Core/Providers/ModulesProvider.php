<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Controllers\Modules;

class ModulesProvider extends ServiceProvider {

	public function register( Container $container ) {

		require_once PF_ROOT . '/includes/module-base.php';

		$container->share(
			'modules',
			function( $container ) {
				return new Modules();
			}
		);

		// parent::register( $container );
	}
}
