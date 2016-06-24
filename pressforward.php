<?php
/*
Plugin Name: PressForward
Plugin URI: http://pressforward.org/
Description: The PressForward Plugin is a tool by the Roy Rosenzweig Center for History and New Media for aggregating and curating web-based content within the WordPress dashboard.
Version: 3.9.1
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
define( 'PF_VERSION', '3.9' );

// Protect File.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!class_exists('WPUpdatePHP')){
	require 'lib/WPUpdatePHP.php';
}

$PHPCheck = new WPUpdatePHP( '5.3.0', 'PressForward' );


if ( (PHP_VERSION < 5.3) || (! $PHPCheck->does_it_meet_required_php_version( PHP_VERSION ) ) ) {
	wp_die('As of version 3.9.0 PressForward requires at least PHP 5.3.');
	return;
} else {
	require( 'init.php' );
}



//call_user_func(array(new Application(__FILE__), 'boot'));
