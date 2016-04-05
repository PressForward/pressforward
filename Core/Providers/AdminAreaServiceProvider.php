<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
//use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\Admin\PFTemplater;
use PressForward\Core\Admin\Tools;
use PressForward\Core\Admin\Nominated;

class AdminAreaServiceProvider extends ServiceProvider {

	protected function add_assets( Assets $assets ){
		$slug = $this->container->fetch( 'slug' );
		$url  = $this->container->fetch( 'url' );


		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> (function($hook){
								$exclusions = array('pf-options');
								//return true;
								return $this->check_hook_for_pressforward_string($hook, $exclusions);
							}),
			'handle'	=> $slug.'-reset-style',
			'src'		=> 'assets/css/reset'
		));

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook){
								$exclusions = array('pf-options');
								return $this->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=> $slug.'-bootstrap-style',
			'src'		=> 'lib/twitter-bootstrap/css/bootstrap'
		));

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook){
								$exclusions = array('pf-options');
								return $this->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=> $slug.'-bootstrap-responsive-style',
			'src'		=> 'lib/twitter-bootstrap/css/bootstrap-responsive'
		));

		$assets->register_style( array(
			'type'	=>	'admin',
			'condition'	=> function($hook){
								$exclusions = array('pf-options');
								return $this->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=>	$slug.'-style',
			'src'		=>	'assets/css/pressforward',
			'deps'		=>	array( $slug . '-bootstrap-style', $slug . '-bootstrap-responsive-style' )
		) );

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook){
								$exclusions = array();
								return $this->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=> $slug.'-settings-style',
			'src'		=> 'assets/css/pf-settings'
		));



		$assets->register_script(
			array(
				'type'	=>	'admin',
				'condition'	=> function(){ return true; },
				'handle'	=>	$slug.'-jq-fullscreen',
				'src'		=>	'lib/jquery-fullscreen/jquery.fullscreen',
				'deps'		=>	array( 'jquery' )
			)
		);

		$assets->register_script(
			array(
				'type'	=>	'admin',
				'condition'	=> function(){ return true; },
				'handle'	=>	$slug.'-twitter-bootstrap',
				'src'		=>	'lib/twitter-bootstrap/js/bootstrap',
				'deps'		=>	array( 'jquery' )
			)
		);

		$assets->register_script(
			array(
				'type'	=>	'admin',
				'condition'	=> function(){ return true; },
				'handle'	=>	$slug.'-tools',
				'src'		=>	'assets/js/tools-imp',
				'deps'		=>	array( 'jquery' )
			)
		);

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
		$container->share(
			'admin.templates',
			function( $container ){
				return new PFTemplater( $container->fetch( 'controller.template_factory' ), $container->fetch( 'controller.users' ) );
			}
		);
		$container->share(
			'admin.menu',
			new Menu(  $container->fetch( 'basename' ), $container->fetch( 'controller.users' )  )
		);
		$container->share(
			'admin.tools',
			function( $container ){
				return new Tools( $container->fetch( 'basename' ), $container->fetch( 'admin.templates' ) );
			}
		);
		$container->share(
			'admin.nominated',
			function( $container ){
				return new Nominated(
									$container->fetch('controller.metas'),
									$container->fetch('admin.templates'),
									$container->fetch('utility.forward_tools'),
									$container->fetch('schema.nominations') // Switch this to a modal
								);
			}
		);
		//parent::register( $container );
		//var_dump(__METHOD__); die();
		//
		//parent::register( $container );
	}

	public function check_hook_for_pressforward_string($hook, $exclusions = array()){
		 $position = strpos($hook, 'pressforward');
		 if (false === $position){ return false; }

		 if (!empty($exclusions)){
		 		 foreach ($exclusions as $exclusion){
		 		 	if (false !== strpos($hook, $exclusion)){
		 		 		return false;
		 		 	}
		 		 }
		 }

		 return true;
	}

}
