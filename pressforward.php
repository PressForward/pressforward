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

//Out-of-namespace dependencies
require 'class-SplClassLoader.php';
require 'Libraries/URLResolver/URLResolver.php';
require 'Libraries/FiveFiltersReadability/Readability.php';
require 'Libraries/AlertBox/The_Alert_Box.php';
require 'Libraries/PFOpenGraph.php';
//Files included to maintain back-compat
require 'includes/functions.php';
require 'includes/relationships.php';
require 'includes/template-tags.php';

use SplClassLoader as ClassLoader;
$classLoader = new ClassLoader('PressForward', dirname(__FILE__), false);
//var_dump($classLoader->getIncludePath());
$classLoader->filterFinalPath("PressForward".DIRECTORY_SEPARATOR, '');
$classLoader->register();

//use PressForward\Loader;

// Protect File.
if ( ! defined( 'WPINC' ) ) {
	//die;
}

function pressforward($prop = false) {
	$instance = new stdClass();
	try {
		$instance = new PressForward\Application(__FILE__);
		$instance->boot();
		//var_dump('New Boot');
	} catch (Intraxia\Jaxion\Core\ApplicationAlreadyBootedException $e) {
		//var_dump('Old boot.');
		$instance = PressForward\Application::instance();

	}
	if (!$prop){
		return $instance;
	} else {
		return $instance[$prop];
	}
}
pressforward();
//call_user_func(array(new Application(__FILE__), 'boot'));
