<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require __DIR__ . '/../pressforward.php';
	require __DIR__ . '/includes/install.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

if ( ! class_exists( '\PHPUnit_Framework_TestCase' ) && class_exists( '\PHPUnit\Framework\TestCase' ) ) {
	class_alias( '\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase' );
}

if ( ! class_exists( '\PHPUnit_Framework_Exception' ) && class_exists( '\PHPUnit\Framework\Exception' ) ) {
	class_alias( '\PHPUnit\Framework\Exception', '\PHPUnit_Framework_Exception' );
}

if ( ! class_exists( '\PHPUnit_Util_Getopt' ) && class_exists( '\PHPUnit\Util\Getopt' ) ) {
	class_alias( '\PHPUnit\Util\Getopt', '\PHPUnit_Util_Getopt' );
}

require __DIR__ . '/includes/testcase.php';
require __DIR__ . '/includes/factory.php';
