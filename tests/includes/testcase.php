<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class PF_UnitTestCase extends TestCase {
	public $factory;

	public function set_up() {
		$this->factory = new PF_UnitTest_Factory;
	}
}
