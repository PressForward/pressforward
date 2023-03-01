<?php
/**
 * Plugin loader.
 *
 * @package PressForward
 */

namespace PressForward;

use SplClassLoader as JaxionClassLoader;

/**
 * Loader class.
 */
class Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->namespace_to();
	}

	/**
	 * Not used.
	 */
	public function test() {
		echo 'This thing';
	}

	/**
	 * Sets up class loader for Jaxion.
	 */
	public function includes() {
		$class_loader = new JaxionClassLoader( 'Intraxia\Jaxion', __DIR__ . '/Libraries/Jaxion/src' );
		$class_loader->filterFinalPath( 'Intraxia' . DIRECTORY_SEPARATOR . 'Jaxion' . DIRECTORY_SEPARATOR, '' );
		$class_loader->register();
	}

	/**
	 * Not used.
	 */
	public function namespace_to() {}
}
