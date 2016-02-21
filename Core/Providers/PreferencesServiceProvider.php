<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Preferences;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

class PreferencesServiceProvider extends ServiceProvider {

	protected function add_assets( Assets $assets ){
		$slug = $this->container->fetch( 'slug' );
		$url  = $this->container->fetch( 'url' );

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=> $slug.'-settings-style',
			'src'		=> 'assets/css/pf-settings'
		));

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-settings-tools',
			'src'		=>	'assets/js/settings-tools',
			'deps'		=>	array( 'jquery' )
		) );

		//var_dump($assets); die();
	}

	public function register( Container $container ){
		$container->define(
			'admin.settings',
			new Preferences( $container->fetch( 'basename' ), $container->fetch( 'admin.templates' ) )
		);

		parent::register( $container );
	}

}