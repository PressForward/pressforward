<?php
/**
 * Plugin Name: PressForward
 * Plugin URI: http://pressforward.org/
 * Description: The PressForward Plugin is a tool by the Roy Rosenzweig Center for History and New Media for aggregating and curating web-based content within the WordPress dashboard.
 * Version: 5.4.0
 * GitHub Plugin URI: https://github.com/PressForward/pressforward
 * Author: Aram Zucker-Scharff, Boone B Gorges, Jeremy Boggs
 * Author URI: http://pressforward.org/about/team/
 * License: GPL2
 * Text Domain: pressforward
 * Domain Path: /languages/
 *
 * @package PressForward
 */

require 'constants.php';

// Protect File.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WPUpdatePhp' ) ) {
	require 'Libraries/WPUpdatePHP.php';
}

if ( ! class_exists( 'MyCLabs\Enum\Enum' ) ) {
	require 'Libraries/Enum.php';
}

$php_check = new WPUpdatePhp( '5.3.0' );
$php_check->set_plugin_name( 'PressForward' );

if ( ( PHP_VERSION < 5.3 ) || ( ! $php_check->does_it_meet_required_php_version( PHP_VERSION ) ) ) {
	wp_die( 'As of version 3.9.0 PressForward requires at least PHP 5.3.' );
} else {
	require 'init.php';
}

/**
 * Loads plugin textdomain.
 */
function pressforward_load_plugin_textdomain() {
	load_plugin_textdomain( 'pressforward', false, basename( __DIR__ ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pressforward_load_plugin_textdomain' );
