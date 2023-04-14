<?php
/**
 * Plugin bootstrap.
 *
 * @package PressForward
 */

// Out-of-namespace dependencies.
require 'vendor/autoload.php';
require 'class-SplClassLoader.php';
require_once 'Libraries/random_compat/lib/random.php';
require_once 'Libraries/php-jwt/src/JWT.php';
require 'Libraries/URLResolver/URLResolver.php';
require 'Libraries/FiveFiltersReadability/Readability.php';
require 'Libraries/AlertBox/The_Alert_Box.php';
require 'includes/opml/object.php';
require 'includes/opml/reader.php';
require 'includes/opml/maker.php';
require 'Libraries/PFOpenGraph.php';

// Files included to maintain back-compat.
require 'includes/functions.php';
require 'includes/relationships.php';
require 'includes/template-tags.php';

use Intraxia\Jaxion\Core\Config;
use Intraxia\Jaxion\Core\ConfigType;
use SplClassLoader as ClassLoader;

// @todo This call to filterFinalPath is likely a bug.
$ts_class_loader = new ClassLoader( 'DaveChild', 'Libraries/text-stats/src', false );
$ts_class_loader->filterFinalPath( 'DaveChild/Libraries/text-stats/src', '' );
$ts_class_loader->register();

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
