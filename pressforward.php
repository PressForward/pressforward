<?php
/*
Plugin Name: PressForward
Plugin URI: http://pressforward.org/
Description: The PressForward Plugin is a tool by the Roy Rosenzweig Center for History and New Media for aggregating and curating web-based content within the WordPress dashboard.
Version: 3.9.0
GitHub Plugin URI: https://github.com/PressForward/pressforward
Author: Aram Zucker-Scharff, Boone B Gorges, Jeremy Boggs
Author URI: http://pressforward.org/about/team/
License: GPL2
*/


namespace PressForward;
//var_dump('2<pre>');

//Set up some constants
define( 'PF_SLUG', 'pf' );
define( 'PF_TITLE', 'PressForward' );
define( 'PF_MENU_SLUG', PF_SLUG . '-menu' );
define( 'PF_NOM_EDITOR', 'edit.php?post_type=nomination' );
define( 'PF_NOM_POSTER', 'post-new.php?post_type=nomination' );
define( 'PF_ROOT', dirname(__FILE__) );
define( 'PF_FILE_PATH', PF_ROOT . '/' . basename(__FILE__) );
define( 'PF_URL', plugins_url('/', __FILE__) );
define( 'PF_VERSION', '3.8' );

require 'class-SplClassLoader.php';

use SplClassLoader as ClassLoader;
$classLoader = new ClassLoader('PressForward', dirname(__FILE__));
//var_dump($classLoader->getIncludePath());
$classLoader->filterFinalPath("PressForward".DIRECTORY_SEPARATOR, '');
$classLoader->register();

//use PressForward\Loader;

// Protect File.
if ( ! defined( 'WPINC' ) ) {
	//die;
}

//var_dump(__NAMESPACE__); //die();

use PressForward\lib\WPUpdatePHP as PHPChecker;

$PHPCheck = new PHPChecker( '5.3.0', 'PressForward' );


if ( ! $PHPCheck->does_it_meet_required_php_version( PHP_VERSION ) ) {
	return;
}

$obj = new Loader;
//$obj->test();

use Intraxia\Jaxion\Core\Application as JaxionCore;
//use Intraxia\Jaxion\Contract\Core\Application as ApplicationContract;

class Application extends JaxionCore {
	const VERSION = '3.9.0';
	var $ver = 3.9;
		/**
	 * ServiceProviders to register with the Application
	 *
	 * @var string[]
	 */
	protected $providers = array(
		'PressForward\Core\Providers\ControllerServiceProvider',
		'PressForward\Core\Providers\AdminAreaServiceProvider',
		'PressForward\Core\Providers\PreferencesServiceProvider',
//		'PressForward\Core\Providers\CoreServiceProvider',
//		'PressForward\Core\Providers\EmbedServiceProvider',
//		'PressForward\Core\Providers\RouterServiceProvider',
	);
	/**
	 * {@inheritdoc}
	 */
	public function activate() {
		global $wp_rewrite;
		$current_version = VERSION; // define this constant in the loader file
		$saved_version = get_option( 'pf_version' );

		// This is a new installation
		if ( ! $saved_version ) {
			// Do whatever you need to do during first installation
			$check = pressforward()->pf_feeds->create(
				'http://pressforward.org/feed/',
				array(
					'title'         => 'PressForward',
					'htmlUrl'       => 'http://pressforward.org/',
					'description'   => 'The news feed for the PressForward project.',
					'type'          => 'rss',
					'module_added'  => 'rss-import'
				)
			);

			$wp_rewrite->flush_rules(false);

		// This is an upgrade
		} else if ( version_compare( $saved_version, $current_version, '<' ) ) {
			// Do whatever you need to do on an upgrade

		// Version is up to date - do nothing
		} else {
			return;
		}

		// Update the version number stored in the db (so this does not run again)
		update_option( 'pf_version', PF_VERSION );
	}

}

call_user_func(array(new Application(__FILE__), 'boot'));
