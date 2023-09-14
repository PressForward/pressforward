<?php
/**
 * Service provider for admin area.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;

use PressForward\Core\Utility\Forward_Tools;
use PressForward\Core\Utility\Relate;
use PressForward\Core\Utility\Retrieval;

/**
 * AssetsProvider class.
 */
class UtilityProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container object.
	 */
	public function register( Container $container ) {

		$container->share(
			'utility.forward_tools',
			function ( $container ) {
				return new Forward_Tools(
					$container->fetch( 'controller.items' ),
					$container->fetch( 'controller.advancement' ),
					$container->fetch( 'controller.metas' )
				);
			}
		);

		$container->share(
			'utility.relate',
			function ( $container ) {
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
			function ( $container ) {
				return new Retrieval();
			}
		);
	}
}
