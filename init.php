<?php
// Out-of-namespace dependencies
require 'class-SplClassLoader.php';
require 'Libraries/PFSimpleHtmlDom.php';
require_once 'Libraries/random_compat/lib/random.php';
require_once 'Libraries/php-jwt/src/JWT.php';
require 'Libraries/URLResolver/URLResolver.php';
//require 'Libraries/text-stats/src/DaveChild/TextStatistics/Text.php';
//require 'Libraries/text-stats/src/DaveChild/TextStatistics/TextStatistics.php';
require 'Libraries/FiveFiltersReadability/Readability.php';
require 'Libraries/AlertBox/The_Alert_Box.php';
require 'includes/opml/object.php';
require 'includes/opml/reader.php';
require 'includes/opml/maker.php';
require 'Libraries/PFOpenGraph.php';
// Files included to maintain back-compat
require 'includes/functions.php';
require 'includes/relationships.php';
require 'includes/template-tags.php';


use Intraxia\Jaxion\Core\Config;
use Intraxia\Jaxion\Core\ConfigType;
use SplClassLoader as ClassLoader;
$tsClassLoader = new ClassLoader( 'DaveChild', 'Libraries/text-stats/src', false );
$tsClassLoader->filterFinalPath( 'DaveChild' . 'Libraries/text-stats/src', '' );
$tsClassLoader->register();
$classLoader = new ClassLoader( 'PressForward', dirname( __FILE__ ), false );
// var_dump($classLoader->getIncludePath());
$classLoader->filterFinalPath( 'PressForward' . DIRECTORY_SEPARATOR, '' );
$classLoader->register();

// use PressForward\Loader;
function pressforward( $prop = false ) {
	$instance = new stdClass();
	try {
		$instance = new PressForward\Application( new Config( ConfigType::PLUGIN, __FILE__ ) );
		$instance->boot();
		// var_dump('New Boot');
	} catch (Intraxia\Jaxion\Core\ApplicationAlreadyBootedException $e) {
		// var_dump('Old boot.');
		$instance = PressForward\Application::instance();

	}
	if ( ! $prop ) {
		return $instance;
	} else {
		return $instance[ $prop ];
	}
}
pressforward();
