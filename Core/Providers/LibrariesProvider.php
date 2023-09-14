<?php
/**
 * Service provider for third-party libraries.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;

use PressForward\Libraries\HTMLChecker;
use PFOpenGraph;
use AlertBox\The_Alert_Box;
use mattwright\URLResolver;

/**
 * LibrariesProvider class.
 */
class LibrariesProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container.
	 */
	public function register( Container $container ) {
		$container->define(
			'library.url_resolver',
			function ( $container ) {
				return new URLResolver();
			}
		);

		$container->share(
			'library.htmlchecker',
			function ( $container ) {
				return new HTMLChecker();
			}
		);

		$container->share(
			'library.opengraph',
			function ( $container ) {
				return new PFOpenGraph();
			}
		);

		$container->define(
			'library.readability',
			function ( $container ) {
				return ( function ( $html, $url = null, $parser = 'libxml', $logger = 'pf_log' ) {
					if ( ! class_exists( '\Readability' ) ) {
						require_once PF_ROOT . '/Libraries/FiveFiltersReadability/Readability.php';
					}

					return new \Readability( $html, $url, $parser, $logger );
				} );
			}
		);

		$container->share(
			'library.alertbox',
			function ( $container ) {
				return The_Alert_Box::init();
			}
		);
	}
}
