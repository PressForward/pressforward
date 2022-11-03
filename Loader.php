<?php

namespace PressForward;

use SplClassLoader as JaxionClassLoader;

class Loader {

	function __construct() {

		$this->includes();
		$this->namespace_to();

	}

	function test() {
		echo 'This thing';
	}

	function includes() {
		$classLoader = new JaxionClassLoader( 'Intraxia\Jaxion', dirname( __FILE__ ) . '/Libraries/Jaxion/src' );
		$classLoader->filterFinalPath( 'Intraxia' . DIRECTORY_SEPARATOR . 'Jaxion' . DIRECTORY_SEPARATOR, '' );
		$classLoader->register();

	}

	function namespace_to() {

	}

}
