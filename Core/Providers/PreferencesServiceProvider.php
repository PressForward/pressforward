<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Preferences;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

class PreferencesServiceProvider extends ServiceProvider {

	public function register( Container $container ){
		$container->share(
			'admin.settings',
			new Preferences( $container->fetch( 'basename' ), $container->fetch( 'admin.templates' ) )
		);


	}

}