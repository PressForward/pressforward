<?php
/*
Plugin Name: PressForward
Plugin URI: http://pressforward.org/
Description: This plugin is a aggregation parser for CHNM's Press Forward project.
Version: 1.8.7
Author: Aram Zucker-Scharff, Boone B Gorges, Jeremy Boggs
Author URI: http://aramzs.me, http://boone.gorg.es/, http://clioweb.org
License: GPL2
*/

/*  Developed for the Center for History and New Media

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Set up some constants
define( 'PF_SLUG', 'pf' );
define( 'PF_TITLE', 'PressForward' );
define( 'PF_MENU_SLUG', PF_SLUG . '-menu' );
define( 'PF_NOM_EDITOR', 'edit.php?post_type=nomination' );
define( 'PF_NOM_POSTER', 'post-new.php?post_type=nomination' );
define( 'PF_ROOT', dirname(__FILE__) );
define( 'PF_FILE_PATH', PF_ROOT . '/' . basename(__FILE__) );
define( 'PF_URL', plugins_url('/', __FILE__) );

class PressForward {
	var $modules = array();

	var $schema;
	var $admin;
	var $nominations;

	// See http://php.net/manual/en/language.oop5.decon.php to get a better understanding of what's going on here.
	function __construct() {

		$this->includes();

		$this->set_up_schema();
		$this->set_up_nominations();
		$this->set_up_admin();

		add_action( 'plugins_loaded', array( $this, 'pressforward_init' ) );

		add_action( 'pressforward_init', array( $this, 'setup_modules' ), 1000 );

		load_plugin_textdomain( 'pf', false, PF_ROOT );

	}

	/**
	 * Include necessary files
	 *
	 * @since 1.7
	 */
	function includes() {

		// External libraries

		// Pull and parse Open Graph data from a page.
		require( PF_ROOT . "/lib/OpenGraph.php" );

		// Check the HTML of each item for open tags and close them.
		// I've altered it specifically for some odd HTML artifacts that occur when
		// WP sanitizes the content input.
		require( PF_ROOT . "/lib/htmlchecker.php" );

		// A slightly altered version of the Readability library from Five Filters,
		// who based it off readability.com's code.
		require( PF_ROOT . "/lib/fivefilters-readability/Readability.php" );

		// For reading through an HTML page.
		require( PF_ROOT . "/lib/simple_html_dom.php" );
		$dom = new simple_html_dom;

		// Internal tools

		// Load the module base class and our test module
		require( PF_ROOT . "/includes/functions.php" );
		require( PF_ROOT . "/includes/module-base.php" );
		require( PF_ROOT . '/includes/schema.php' );
		require( PF_ROOT . '/includes/readable.php' );
		require( PF_ROOT . '/includes/feed-items.php' );
		require( PF_ROOT . '/includes/relationships.php' );
		require( PF_ROOT . '/includes/nominations.php' );
		require( PF_ROOT . '/includes/admin.php' );
	}

	/**
	 * Sets up the Dashboard admin
	 *
	 * @since 1.7
	 */
	function set_up_schema() {
		if ( empty( $this->schema ) ) {
			$this->schema = new PF_Feed_Item_Schema;
		}
	}

	/**
	 * Sets up the Dashboard admin
	 *
	 * @since 1.7
	 */
	function set_up_nominations() {
		if ( empty( $this->nominations ) ) {
			$this->nominations = new PF_Nominations;
		}
	}

	/**
	 * Sets up the Dashboard admin
	 *
	 * @since 1.7
	 */
	function set_up_admin() {
		if ( empty( $this->admin ) ) {
			$this->admin = new PF_Admin;
		}
	}

	/**
	 * Fire the pressforward_init action, to let plugins know that our
	 * libraries are available
	 */
	function pressforward_init() {
		do_action( 'pressforward_init' );
	}

	/**
	 * Locate and load modules
	 *
	 * This method supports loading our packaged modules, as well as those
	 * provided by plugins
	 */
	function setup_modules() {

		$module_args = array();

		// Scrape the built-in modules
		$module_dirs = scandir( PF_ROOT . '/modules/' );
		foreach ( $module_dirs as $module_dir ) {
			// Skip hidden items
			if ( '.' == substr( $module_dir, 0, 1 ) ) {
				continue;
			}

			if ( file_exists( PF_ROOT . "/modules/{$module_dir}/{$module_dir}.php" ) ) {
				include_once( PF_ROOT . "/modules/{$module_dir}/{$module_dir}.php" );

				// Prepare the class name
				$tmp = explode( '-', $module_dir );
				$tmp = array_map( 'ucwords', $tmp );
				$class_name = 'PF_' . implode( '_', $tmp );

				$module_args[] = array(
					'slug' => $module_dir,
					'class' => $class_name
				);
			}
		}

		// Plugins should not filter this array directly. Use
		// pressforward_register_module() instead
		$plugin_module_args = apply_filters( 'pressforward_register_modules', array() );

		$module_args = array_merge( $module_args, $plugin_module_args );
		foreach ( $module_args as $module ) {
			$this->modules[ $module['slug'] ] = new $module['class'];
		}

		do_action( 'pf_setup_modules', $this );
	}

	/**
	 * Get the feed item post type
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public function get_feed_item_post_type() {
		if ( isset( $this->schema ) ) {
			return $this->schema->feed_item_post_type;
		}

		return '';
	}

	/**
	 * Get the feed item tag taxonomy
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public function get_feed_item_tag_taxonomy() {
		if ( isset( $this->schema ) ) {
			return $this->schema->feed_item_tag_taxonomy;
		}

		return '';
	}
}

/**
 * Bootstrap
 *
 * You can also use this to get a value out of the global, eg
 *
 *    $foo = pressforward()->bar;
 *
 * @since 1.7
 */
function pressforward() {
	global $pf;
	if ( ! is_a( $pf, 'PressForward' ) ) {
		$pf = new PressForward();
	}
	return $pf;
}

// Start me up!
pressforward();
