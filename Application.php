<?php
namespace PressForward;

// var_dump('2<pre>');
// var_dump(__NAMESPACE__); //die();
$obj = new Loader();
// $obj->test();
use Intraxia\Jaxion\Core\Application as JaxionCore;
// use Intraxia\Jaxion\Contract\Core\Application as ApplicationContract;
class Application extends JaxionCore {
	var $ver = PF_VERSION;
		/**
		 * ServiceProviders to register with the Application
		 *
		 * @var string[]
		 */
	protected $providers = array(
		'PressForward\Core\Providers\LibrariesProvider',
		'PressForward\Core\Providers\SchemaProvider',
		'PressForward\Core\Providers\ControllerServiceProvider',
		'PressForward\Core\Providers\ModelsProvider',
		'PressForward\Core\Providers\AssetsProvider',
		'PressForward\Core\Providers\UtilityProvider',
		'PressForward\Core\Providers\AdminAreaServiceProvider',
		'PressForward\Core\Providers\PreferencesServiceProvider',
		'PressForward\Core\Providers\ModulesProvider',
		'PressForward\Core\Providers\AJAXServiceProvider',
		'PressForward\Core\Providers\APIProvider',
		'PressForward\Core\Providers\BookmarkletProvider',
	// 'PressForward\Core\Providers\CoreServiceProvider',
	// 'PressForward\Core\Providers\EmbedServiceProvider',
	// 'PressForward\Core\Providers\RouterServiceProvider',
	);
	/**
	 * {@inheritdoc}
	 */
	public function activate() {
		global $wp_rewrite;
		define( 'VERSION', '5.1.0' );
		$current_version = VERSION; // define this constant in the loader file
		$saved_version   = get_option( 'pf_version' );

		// This is a new installation
		if ( ! $saved_version ) {
			// Do whatever you need to do during first installation
			$check = pressforward( 'schema.feeds' )->create(
				'http://pressforward.org/feed/',
				array(
					'title'        => 'PressForward',
					'htmlUrl'      => 'http://pressforward.org/',
					'description'  => 'The news feed for the PressForward project.',
					'type'         => 'rss',
					'module_added' => 'rss-import',
				)
			);

			$wp_rewrite->flush_rules( false );

			// This is an upgrade
		} elseif ( version_compare( $saved_version, $current_version, '<' ) ) {
			// Do whatever you need to do on an upgrade
			delete_option( 'pf_delete_queue_nonce' );
			$wp_rewrite->flush_rules( false );
			// Version is up to date - do nothing
		} else {
			return;
		}

		// Update the version number stored in the db (so this does not run again)
		update_option( 'pf_version', PF_VERSION );
	}

}
// call_user_func(array(new Application(__FILE__), 'boot'));
