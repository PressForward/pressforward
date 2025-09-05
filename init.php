<?php
/**
 * Plugin bootstrap.
 *
 * @package PressForward
 */

// Out-of-namespace dependencies.
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/class-SplClassLoader.php';
require __DIR__ . '/Libraries/AlertBox/The_Alert_Box.php';
require __DIR__ . '/includes/opml/object.php';
require __DIR__ . '/includes/opml/reader.php';
require __DIR__ . '/includes/opml/maker.php';
require __DIR__ . '/Libraries/PFOpenGraph.php';

// Files included to maintain back-compat.
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/relationships.php';
require __DIR__ . '/includes/template-tags.php';

use Intraxia\Jaxion\Core\Config;
use Intraxia\Jaxion\Core\ConfigType;
use SplClassLoader as ClassLoader;

$class_loader = new ClassLoader( 'PressForward', __DIR__, false );
$class_loader->filterFinalPath( 'PressForward' . DIRECTORY_SEPARATOR, '' );
$class_loader->register();

/**
 * PressForward instance.
 *
 * @param string|null $prop Optional. Use to fetch a property from the global instance.
 * @return mixed
 */
function pressforward( $prop = null ) {
	$instance = new stdClass();
	try {
		$instance = new PressForward\Application( new Config( ConfigType::PLUGIN, __FILE__ ) );
		$instance->boot();
	} catch ( Intraxia\Jaxion\Core\ApplicationAlreadyBootedException $e ) {
		$instance = PressForward\Application::instance();
	}

	if ( ! $prop ) {
		return $instance;
	} else {
		return $instance[ $prop ];
	}
}
pressforward();
