<?php
/**
 * Service provider for third-party libraries.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Libraries\HTMLChecker as HTMLChecker;
use PFOpenGraph;
use AlertBox\The_Alert_Box as The_Alert_Box;
use Readability;
use URLResolver;

/**
 * LibrariesProvider class.
 */
class LibrariesProvider extends ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ) {

		$container->define(
			'library.url_resolver',
			function( $container ) {
				return new URLResolver();
			}
		);

		$container->share(
			'library.htmlchecker',
			function( $container ) {
				return new HTMLChecker();
			}
		);

		$container->share(
			'library.opengraph',
			function( $container ) {
				return new PFOpenGraph();
			}
		);

		$container->define(
			'library.readability',
			function( $container ) {
				return ( function( $html, $url = null, $parser = 'libxml', $logger = 'pf_log' ) {
					return new Readability( $html, $url, $parser, $logger );
				} );
			}
		);

		$container->share(
			'library.alertbox',
			function( $container ) {
				return The_Alert_Box::init();
			}
		);
	}
}
