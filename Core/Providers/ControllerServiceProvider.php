<?php
namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
//use Intraxia\Jaxion\Contract\Core\ServiceProvider as ServiceProvider;
use PressForward\Controllers\PFtoWPTemplates as Template_Factory;
use PressForward\Controllers\PFtoWPUsers as Users;
use PressForward\Controllers\PF_to_WP_Meta as PF_to_WP_Meta;
use PressForward\Controllers\PF_to_WP_Posts as PF_to_WP_Posts;
use PressForward\Controllers\PF_Advancement as PF_Advancement;
use PressForward\Controllers\Metas;

class ControllerServiceProvider extends ServiceProvider {

	public function register( Container $container ){

		$container->share(
			'controller.meta_interface',
			function( ){
				return new PF_to_WP_Meta;
			}
		);

		$container->share(
			'controller.metas',
			function( $container ){
				return new Metas(
									$container->fetch('controller.meta_interface')
								);
			}
		);

		$container->share(
			'controller.users',
			function( $container ){
				return new Users( $container->fetch('controller.metas') );
			}
		);

		$container->share(
			'controller.template_factory',
			function( ){
				return new Template_Factory;
			}
		);

		$container->share(
			'controller.items',
			function( ){
				return new PF_to_WP_Posts;
			}
		);

		$container->share(
			'controller.advancement',
			function( $container ){
				return new PF_Advancement( $container->fetch('controller.metas') );
			}
		);

		//parent::register( $container );

	}
}
