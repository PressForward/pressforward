<?php

require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';

function _manually_load_plugin() {
	require __DIR__ . '/../pressforward.php';
	require __DIR__ . '/includes/install.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
require __DIR__ . '/includes/testcase.php';
require __DIR__ . '/includes/factory.php';


