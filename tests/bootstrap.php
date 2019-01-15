<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

if ( ! class_exists( '\PHPUnit_Framework_TestCase' ) && class_exists( '\PHPUnit\Framework\TestCase' ) ) {
	class_alias( '\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase' );
}

if ( ! class_exists( '\PHPUnit_Framework_Exception' ) && class_exists( '\PHPUnit\Framework\Exception' ) ) {
	class_alias( '\PHPUnit\Framework\Exception', '\PHPUnit_Framework_Exception' );
}

if ( class_exists( 'PHPUnit\Runner\Version' ) && version_compare( PHPUnit\Runner\Version::id(), '6.0', '>=' ) ) {
	// class_alias( 'PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase' );
	// class_alias( 'PHPUnit\Framework\Exception', 'PHPUnit_Framework_Exception' );
	class_alias( 'PHPUnit\Framework\ExpectationFailedException', 'PHPUnit_Framework_ExpectationFailedException' );
	class_alias( 'PHPUnit\Framework\Error\Notice', 'PHPUnit_Framework_Error_Notice' );
	class_alias( 'PHPUnit\Framework\Error\Warning', 'PHPUnit_Framework_Error_Warning' );
	class_alias( 'PHPUnit\Framework\Test', 'PHPUnit_Framework_Test' );
	class_alias( 'PHPUnit\Framework\Warning', 'PHPUnit_Framework_Warning' );
	class_alias( 'PHPUnit\Framework\AssertionFailedError', 'PHPUnit_Framework_AssertionFailedError' );
	class_alias( 'PHPUnit\Framework\TestSuite', 'PHPUnit_Framework_TestSuite' );
	class_alias( 'PHPUnit\Framework\TestListener', 'PHPUnit_Framework_TestListener' );
	class_alias( 'PHPUnit\Util\GlobalState', 'PHPUnit_Util_GlobalState' );
	class_alias( 'PHPUnit\Util\Getopt', 'PHPUnit_Util_Getopt' );
	class PHPUnit_Util_Test {
		public static function getTickets( $className, $methodName ) {
			$annotations = PHPUnit\Util\Test::parseTestMethodAnnotations( $className, $methodName );
			$tickets = array();
			if ( isset( $annotations['class']['ticket'] ) ) {
				$tickets = $annotations['class']['ticket'];
			}
			if ( isset( $annotations['method']['ticket'] ) ) {
				$tickets = array_merge( $tickets, $annotations['method']['ticket'] );
			}
			return array_unique( $tickets );
		}
	}
}

function _manually_load_plugin() {
	require __DIR__ . '/../pressforward.php';
	require __DIR__ . '/includes/install.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require __DIR__ . '/includes/testcase.php';
require __DIR__ . '/includes/factory.php';
