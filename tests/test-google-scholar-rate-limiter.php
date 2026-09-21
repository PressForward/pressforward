<?php

/**
 * Test Google Scholar rate limiting.
 *
 * @group PF_Google_Scholar_Rate_Limiter
 */
class PF_Tests_Google_Scholar_Rate_Limiter extends PF_UnitTestCase {

	/**
	 * Set up for each test.
	 */
	public function set_up() {
		parent::set_up();
		// Reset rate limiter before each test.
		\PressForward\Core\Utility\GoogleScholarRateLimiter::reset();
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		\PressForward\Core\Utility\GoogleScholarRateLimiter::reset();
		delete_option( 'pf_google_scholar_max_per_hour' );
		delete_option( 'pf_google_scholar_max_per_day' );
		parent::tear_down();
	}

	/**
	 * Test that requests are allowed initially.
	 */
	public function test_initial_request_is_allowed() {
		$result = \PressForward\Core\Utility\GoogleScholarRateLimiter::is_request_allowed();
		$this->assertTrue( $result );
	}

	/**
	 * Test that requests are blocked after hourly limit is reached.
	 */
	public function test_hourly_limit_is_enforced() {
		$max_per_hour = \PressForward\Core\Utility\GoogleScholarRateLimiter::get_max_requests_per_hour();

		// Make max requests.
		for ( $i = 0; $i < $max_per_hour; $i++ ) {
			$this->assertTrue( \PressForward\Core\Utility\GoogleScholarRateLimiter::is_request_allowed() );
			\PressForward\Core\Utility\GoogleScholarRateLimiter::record_request();
		}

		// Next request should be blocked.
		$result = \PressForward\Core\Utility\GoogleScholarRateLimiter::is_request_allowed();
		$this->assertIsArray( $result );
		$this->assertFalse( $result['allowed'] );
		$this->assertEquals( 'hourly_limit', $result['reason'] );
	}

	/**
	 * Test that requests are blocked after daily limit is reached.
	 */
	public function test_daily_limit_is_enforced() {
		// Set a low hourly limit to test daily limit.
		update_option( 'pf_google_scholar_max_per_hour', 100 );
		$max_per_day = \PressForward\Core\Utility\GoogleScholarRateLimiter::get_max_requests_per_day();

		// Make max requests.
		for ( $i = 0; $i < $max_per_day; $i++ ) {
			\PressForward\Core\Utility\GoogleScholarRateLimiter::record_request();
		}

		// Next request should be blocked by daily limit.
		$result = \PressForward\Core\Utility\GoogleScholarRateLimiter::is_request_allowed();
		$this->assertIsArray( $result );
		$this->assertFalse( $result['allowed'] );
		$this->assertEquals( 'daily_limit', $result['reason'] );
	}

	/**
	 * Test that get_status returns correct information.
	 */
	public function test_get_status_returns_correct_info() {
		// Make some requests.
		\PressForward\Core\Utility\GoogleScholarRateLimiter::record_request();
		\PressForward\Core\Utility\GoogleScholarRateLimiter::record_request();

		$status = \PressForward\Core\Utility\GoogleScholarRateLimiter::get_status();

		$this->assertIsArray( $status );
		$this->assertEquals( 2, $status['requests_last_hour'] );
		$this->assertEquals( 2, $status['requests_last_day'] );
		$this->assertGreaterThan( 0, $status['max_requests_per_hour'] );
		$this->assertGreaterThan( 0, $status['max_requests_per_day'] );
	}

	/**
	 * Test that reset clears all timestamps.
	 */
	public function test_reset_clears_timestamps() {
		// Make some requests.
		\PressForward\Core\Utility\GoogleScholarRateLimiter::record_request();
		\PressForward\Core\Utility\GoogleScholarRateLimiter::record_request();

		$status = \PressForward\Core\Utility\GoogleScholarRateLimiter::get_status();
		$this->assertEquals( 2, $status['requests_last_hour'] );

		// Reset.
		\PressForward\Core\Utility\GoogleScholarRateLimiter::reset();

		$status = \PressForward\Core\Utility\GoogleScholarRateLimiter::get_status();
		$this->assertEquals( 0, $status['requests_last_hour'] );
		$this->assertEquals( 0, $status['requests_last_day'] );
	}

	/**
	 * Test that custom limits can be configured.
	 */
	public function test_custom_limits_can_be_configured() {
		update_option( 'pf_google_scholar_max_per_hour', 5 );
		update_option( 'pf_google_scholar_max_per_day', 20 );

		$this->assertEquals( 5, \PressForward\Core\Utility\GoogleScholarRateLimiter::get_max_requests_per_hour() );
		$this->assertEquals( 20, \PressForward\Core\Utility\GoogleScholarRateLimiter::get_max_requests_per_day() );
	}

	/**
	 * Test that retry_after is calculated correctly.
	 */
	public function test_retry_after_is_calculated() {
		$max_per_hour = \PressForward\Core\Utility\GoogleScholarRateLimiter::get_max_requests_per_hour();

		// Make max requests.
		for ( $i = 0; $i < $max_per_hour; $i++ ) {
			\PressForward\Core\Utility\GoogleScholarRateLimiter::record_request();
		}

		// Next request should be blocked with retry_after value.
		$result = \PressForward\Core\Utility\GoogleScholarRateLimiter::is_request_allowed();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'retry_after', $result );
		$this->assertGreaterThan( 0, $result['retry_after'] );
		$this->assertLessThanOrEqual( HOUR_IN_SECONDS, $result['retry_after'] );
	}
}
