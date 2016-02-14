<?php
/*
Plugin Name: PressForward
Plugin URI: http://pressforward.org/
Description: The PressForward Plugin is a tool by the Roy Rosenzweig Center for History and New Media for aggregating and curating web-based content within the WordPress dashboard.
Version: 3.8.0
GitHub Plugin URI: https://github.com/PressForward/pressforward
Author: Aram Zucker-Scharff, Boone B Gorges, Jeremy Boggs
Author URI: http://pressforward.org/about/team/
License: GPL2
*/


namespace PressForward;
//var_dump('2<pre>');

require 'class-SplClassLoader.php';

use SplClassLoader as ClassLoader;
$classLoader = new ClassLoader('PressForward', dirname(__FILE__));
//var_dump($classLoader->getIncludePath());
$classLoader->filterFinalPath("PressForward\\", '');
$classLoader->register();

/**
// we've writen this code where we need
spl_autoload_register(function ($classname) {
    $classname = str_replace('PressForward', '', $classname);
    $filename = "./". $classname .".php";
    var_dump('fn: '.$filename);
    include_once($filename);
});
**/

//use PressForward\Loader;

// Protect File.
if ( ! defined( 'WPINC' ) ) {
	//die;
}

//var_dump(__NAMESPACE__); //die();

use PressForward\lib\WPUpdatePHP as PHPChecker;

$PHPCheck = new PHPChecker( '5.3.0', 'PressForward' );


if ( ! $PHPCheck->does_it_meet_required_php_version( PHP_VERSION ) ) {
	return;
}

$obj = new Loader;
//$obj->test();

use Intraxia\Jaxion\Core\Application as JaxionCore;
//use Intraxia\Jaxion\Contract\Core\Application as ApplicationContract;

class Application extends JaxionCore {
	const VERSION = '3.9.0';
}
$pf = new Application( __FILE__ );
var_dump($pf->VERSION);