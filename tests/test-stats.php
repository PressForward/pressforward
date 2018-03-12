<?php 

class PF_Tests_Stats extends PF_UnitTestCase {
	public function test_archive_feed_to_display_translate_params() {
		$this->assertTrue( is_object(pressforward('controller.stats')->gender_checker) );
	}
}