<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use PressForward\Core\Schema\Nominations;


class SchemaServiceProvider extends ServiceProvider {

	public function register( Container $container ){
		$container->share(
			'schema.nominations',
			function( ){
				return new Nominations;
			}
		);

		parent::register( $container );

	}
}
