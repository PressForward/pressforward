<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\Utility\Forward_Tools;

class UtilityProvider extends ServiceProvider {

	public function register( Container $container ){

		$container->share(
			'utility.forward_tools',
			function( $container ){
				return new Forward_Tools(
									$container->fetch('controller.items'),
									$container->fetch('controller.advancement'),
									$container->fetch('controller.metas')
								);
			}
		);

		//parent::register( $container );

	}
}
