<?php
/**
 * Plugin Name: PressForward
 * Plugin URI: http://pressforward.org/
 * Description: The PressForward Plugin is a tool by the Roy Rosenzweig Center for History and New Media for aggregating and curating web-based content within the WordPress dashboard.
 * Version: 5.9.5
 * GitHub Plugin URI: https://github.com/PressForward/pressforward
 * Author: Boone Gorges, Aram Zucker-Scharff, Jeremy Boggs
 * Author URI: http://pressforward.org/about/team/
 * License: GPL2
 * Requires PHP: 7.3
 * Text Domain: pressforward
 * Domain Path: /languages/
 *
 * @package PressForward
 */

require __DIR__ . '/constants.php';

// Protect File.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'MyCLabs\Enum\Enum' ) ) {
	require __DIR__ . '/Libraries/Enum.php';
}

if ( ! version_compare( phpversion(), '7.3', '>=' ) ) {
	wp_die( esc_html__( 'PressForward requires PHP 7.3 or greater.', 'pressforward' ) );
} else {
	require __DIR__ . '/init.php';
}

/**
 * Loads plugin textdomain.
 */
function pressforward_load_plugin_textdomain() {
	load_plugin_textdomain( 'pressforward', false, basename( __DIR__ ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pressforward_load_plugin_textdomain' );
