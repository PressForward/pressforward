<?php
/**
 * Service provider for admin area.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\Utility\Forward_Tools;
use PressForward\Core\Utility\Relate;
use PressForward\Core\Utility\Retrieval;

/**
 * AssetsProvider class.
 */
class UtilityProvider extends ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ) {

		$container->share(
			'utility.forward_tools',
			function( $container ) {
				return new Forward_Tools(
					$container->fetch( 'controller.items' ),
					$container->fetch( 'controller.advancement' ),
					$container->fetch( 'controller.metas' )
				);
			}
		);

		$container->share(
			'utility.relate',
			function( $container ) {
				return new Relate(
					$container->fetch( 'controller.items' ),
					$container->fetch( 'controller.advancement' ),
					$container->fetch( 'controller.metas' ),
					$container->fetch( 'schema.relationships' )
				);
			}
		);

		$container->share(
			'utility.retrieval',
			function( $container ) {
				return new Retrieval();
			}
		);
	}
}
