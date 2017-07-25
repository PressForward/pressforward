<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
// use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

use PressForward\Core\API\PostExtension;
use PressForward\Core\API\FeedEndpoint;
use PressForward\Core\API\ItemEndpoint;
use PressForward\Core\API\PFEndpoint;
use PressForward\Core\API\FolderExtension;
use PressForward\Core\API\ReadabilityEndpoint;
use PressForward\Core\API\NominateThisEndpoint;
use PressForward\Core\API\StatsEndpoint;

class APIProvider extends ServiceProvider {

	public function register( Container $container ) {

		$api_base = array(
			'version'	=>	1,
			'base_namespace'	=>	'pf/v'
		);

		$container->share(
			'api.pf_endpoint',
			function( $container ) {
				return new PFEndpoint( $container->fetch( 'controller.metas' ) );
			}
		);

		$container->share(
			'api.post_extension',
			function( $container ) {
				return new PostExtension( $container->fetch( 'controller.metas' ) );
			}
		);
		$container->share(
			'api.feed_endpoint',
			function( $container ) {
				return new FeedEndpoint( $container->fetch( 'controller.metas' ) );
			}
		);
		$container->share(
			'api.item_endpoint',
			function( $container ) {
				return new ItemEndpoint( $container->fetch( 'controller.metas' ) );
			}
		);
		$container->share(
			'api.folder_extension',
			function( $container ) {
				return new FolderExtension( $container->fetch( 'controller.metas' ) );
			}
		);

		$container->share(
			'api.readability',
			function( $container ) use ($api_base) {
				return new ReadabilityEndpoint( $api_base, $container->fetch( 'controller.readability' ), $container->fetch( 'utility.forward_tools' ), $container->fetch( 'library.htmlchecker' ) );
			}
		);

		$container->share(
			'api.nominatethis',
			function( $container ) use ($api_base) {
				return new NominateThisEndpoint( $api_base,  $container->fetch( 'admin.templates' ) );
			}
		);

		$container->share(
			'api.stats',
			function( $container ) use ($api_base) {
				return new StatsEndpoint( $api_base, $container->fetch( 'controller.metas' ), $container->fetch( 'controller.stats' ) );
			}
		);

		return $container;
	}

}
