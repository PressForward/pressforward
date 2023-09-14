<?php
/**
 * Service provider for PF controllers.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;
use PressForward\Controllers\PFtoWPTemplates as Template_Factory;
use PressForward\Controllers\PFtoWPUsers as Users;
use PressForward\Controllers\PF_to_WP_Meta;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Controllers\PF_to_WP_System;
use PressForward\Controllers\PF_Advancement;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_Loops;
use PressForward\Controllers\PF_JWT;
use PressForward\Controllers\PF_Readability;
use PressForward\Controllers\HTTPTools;
use PressForward\Controllers\Stats;

/**
 * ControllerServiceProvider class.
 */
class ControllerServiceProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container.
	 */
	public function register( Container $container ) {
		$container->share(
			'controller.meta_interface',
			function () {
				return new PF_to_WP_Meta();
			}
		);

		$container->share(
			'controller.system',
			function () {
				return new PF_to_WP_System();
			}
		);

		$container->share(
			'controller.metas',
			function ( $container ) {
				return new Metas(
					$container->fetch( 'controller.meta_interface' ),
					$container->fetch( 'controller.system' )
				);
			}
		);

		$container->share(
			'controller.http_tools',
			function ( $container ) {
				return new HTTPTools(
					$container->fetch( 'library.url_resolver' ),
					$container->fetch( 'controller.system' ),
					$container->fetch( 'controller.metas' )
				);
			}
		);

		$container->share(
			'controller.users',
			function ( $container ) {
				return new Users( $container->fetch( 'controller.metas' ) );
			}
		);

		$container->share(
			'controller.template_factory',
			function () {
				return new Template_Factory();
			}
		);

		$container->share(
			'controller.readability',
			function () {
				return new PF_Readability();
			}
		);

		$container->share(
			'controller.items',
			function () {
				return new PF_to_WP_Posts();
			}
		);

		$container->share(
			'controller.jwt',
			function ( $container ) {
				return new PF_JWT( $container->fetch( 'controller.users' ), $container->fetch( 'controller.system' ) );
			}
		);

		$container->share(
			'controller.advancement',
			function ( $container ) {
				return new PF_Advancement( $container->fetch( 'controller.metas' ) );
			}
		);

		$container->share(
			'controller.loops',
			function ( $container ) {
				return new PF_Loops();
			}
		);

		$container->share(
			'controller.stats',
			function ( $container ) {
				return new Stats( $container->fetch( 'controller.metas' ) );
			}
		);
	}
}
