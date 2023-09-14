<?php
/**
 * Service provider for admin area.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use PressForward\Core\Admin\Menu;
use Intraxia\Jaxion\Contract\Core\Container;

use PressForward\Core\Admin\PFTemplater;
use PressForward\Core\Admin\Tools;
use PressForward\Core\Admin\Nominated;
use PressForward\Core\Admin\AddFeeds;
use PressForward\Core\Admin\EditPost;
use PressForward\Core\Admin\FoldersMenu;
use PressForward\Core\Admin\AllContent;
use PressForward\Core\Admin\SubscribedFeeds;
use PressForward\Core\Admin\AdminBar;

/**
 * AdminAreaServiceProvider class.
 */
class AdminAreaServiceProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritdoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container.
	 */
	public function register( Container $container ) {
		$container->share(
			'admin.templates',
			function ( $container ) {
				return new PFTemplater( $container->fetch( 'controller.template_factory' ), $container->fetch( 'controller.users' ) );
			}
		);
		$container->share(
			'admin.menu',
			function ( $container ) {
				return new Menu( $container->fetch( 'basename' ), $container->fetch( 'controller.users' ) );
			}
		);
		$container->share(
			'admin.edit_post',
			function ( $container ) {
				return new EditPost();
			}
		);
		$container->share(
			'admin.all_content',
			function ( $container ) {
				return new AllContent( $container->fetch( 'controller.users' ) );
			}
		);
		$container->share(
			'admin.subscribed_feeds',
			function ( $container ) {
				return new SubscribedFeeds( $container->fetch( 'controller.users' ), $container->fetch( 'library.alertbox' ), $container->fetch( 'controller.metas' ) );
			}
		);
		$container->share(
			'admin.tools',
			function ( $container ) {
				return new Tools( $container->fetch( 'basename' ), $container->fetch( 'admin.templates' ) );
			}
		);
		$container->share(
			'admin.add_feeds',
			function ( $container ) {
				return new AddFeeds( $container->fetch( 'controller.users' ) );
			}
		);
		$container->share(
			'admin.admin_bar',
			function ( $container ) {
				return new AdminBar( $container->fetch( 'controller.users' ) );
			}
		);
		$container->share(
			'admin.folders',
			function ( $container ) {
				return new FoldersMenu( $container->fetch( 'controller.users' ) );
			}
		);
		$container->share(
			'admin.nominated',
			function ( $container ) {
				return new Nominated(
					$container->fetch( 'controller.metas' ),
					$container->fetch( 'admin.templates' ),
					$container->fetch( 'utility.forward_tools' ),
					$container->fetch( 'schema.nominations' ),
					$container->fetch( 'controller.users' )
				);
			}
		);
	}
}
