<?php
/**
 * Service provider for AJAX handlers.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;

use PressForward\Core\AJAX\ConfigurationAJAX;
use PressForward\Core\AJAX\ItemsAJAX;
use PressForward\Core\AJAX\SourceAJAX;
use PressForward\Core\AJAX\NominationsAJAX;
use PressForward\Core\AJAX\MetaAJAX;

/**
 * AJAXServiceProvider class.
 */
class AJAXServiceProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritdoc}
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ) {
		$container->share(
			'ajax.configuration',
			function ( $container ) {
				return new ConfigurationAJAX( $container->fetch( 'controller.metas' ), $container->fetch( 'controller.items' ), $container->fetch( 'schema.feed_item' ), $container->fetch( 'controller.users' ), $container->fetch( 'controller.jwt' ) );
			}
		);

		$container->share(
			'ajax.items',
			function ( $container ) {
				return new ItemsAJAX( $container->fetch( 'controller.metas' ), $container->fetch( 'controller.items' ), $container->fetch( 'schema.feed_item' ) );
			}
		);

		$container->share(
			'ajax.source',
			function ( $container ) {
				return new SourceAJAX( $container->fetch( 'controller.readability' ), $container->fetch( 'utility.retrieval' ), $container->fetch( 'schema.feed_item' ) );
			}
		);

		$container->share(
			'ajax.nominations',
			function ( $container ) {
				return new NominationsAJAX( $container->fetch( 'controller.metas' ), $container->fetch( 'controller.items' ), $container->fetch( 'schema.feed_item' ) );
			}
		);

		$container->share(
			'ajax.meta',
			function ( $container ) {
				return new MetaAJAX( $container->fetch( 'controller.metas' ), $container->fetch( 'controller.items' ), $container->fetch( 'schema.feed_item' ) );
			}
		);
	}
}
