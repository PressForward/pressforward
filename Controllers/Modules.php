<?php
namespace PressForward\Controllers;

/**
 * Classes and functions for dealing with feed items
 */

/**
 * Database class for manipulating feed items
 */
class Modules {
	var $modules = array();

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'pressforward_init' ), 20 );
		add_action( 'pressforward_init', array( $this, 'setup_modules' ), 1000 );

	}

	/**
	 * Fire the pressforward_init action, to let plugins know that our
	 * libraries are available
	 */
	function pressforward_init() {
		// var_dump('PFI');
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
				include_once PF_ROOT . "/modules/{$module_dir}/{$module_dir}.php";

				// Prepare the class name
				$tmp        = explode( '-', $module_dir );
				$tmp        = array_map( 'ucwords', $tmp );
				$class_name = 'PF_' . implode( '_', $tmp );

				$module_args[] = array(
					'slug'  => $module_dir,
					'class' => $class_name,
				);
			}
		}

		// Plugins should not filter this array directly. Use
		// pressforward_register_module() instead
		$plugin_module_args = apply_filters( 'pressforward_register_modules', array() );

		$module_args = array_merge( $module_args, $plugin_module_args );
		foreach ( $module_args as $module ) {
			$this->modules[ $module['slug'] ] = new $module['class']();
		}

		do_action( 'pf_setup_modules', $this );
	}

}
