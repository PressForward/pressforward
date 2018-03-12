<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Libraries\HTMLChecker as HTMLChecker;
use PFOpenGraph;
use AlertBox\The_Alert_Box as The_Alert_Box;
use Readability;
use URLResolver;

class LibrariesProvider extends ServiceProvider {

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

		/**
		 *
		 * <pre><code><?php
		 *  $url = 'http://hacktext.com/2015/03/the-myopic-web-shrinking-the-filter-bubble-with-dropcat-2212/';
		 *  $bob = pressforward('library.readability');
		 *  var_dump($bob(wp_remote_get( $url )['body'], $url));
		 *
		 * @var [type]
		 */
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

		// parent::register( $container );
	}
}
