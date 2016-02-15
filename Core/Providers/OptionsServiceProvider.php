<?php

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

class OptionsServiceProvider extends ServiceProvider {

	protected function add_assets( Assets $assets ){
		$slug = $this->container->fetch( 'slug' );
		$url  = $this->container->fetch( 'url' );
		var_dump($url);
		var_dump($assets);
	}

}