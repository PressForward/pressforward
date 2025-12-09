<?php

/**
 * Test feed retrieval cron job health check.
 *
 * @group PF_Feed_Cron_Health_Check
 */
class PF_Tests_Feed_Cron_Health_Check extends PF_UnitTestCase {

	/**
	 * Test that the health check creates retrieval jobs for feeds without them.
	 */
	public function test_check_feed_retrieval_cron_jobs_schedules_missing_jobs() {
		// Create a feed.
		$feed_id = $this->factory->feed->create();
		$feed    = \PressForward\Core\Models\Feed::get_instance_by_id( $feed_id );

		// Clear any existing retrieval schedule.
		$feed->unschedule_retrieval();

		// Verify it's not scheduled.
		$this->assertNull( $feed->get_next_scheduled_retrieval() );

		// Reset the check timestamp to force the check to run.
		update_option( 'pf_feed_cron_check_timestamp', 0 );
		update_option( 'pf_feed_cron_check_offset', 0 );

		// Run the health check.
		pressforward( 'schema.feeds' )->check_feed_retrieval_cron_jobs();

		// Verify the retrieval is now scheduled.
		$this->assertNotNull( $feed->get_next_scheduled_retrieval() );
	}

	/**
	 * Test that the health check respects the one-hour interval.
	 */
	public function test_check_feed_retrieval_cron_jobs_respects_interval() {
		// Set the last check time to 30 minutes ago.
		update_option( 'pf_feed_cron_check_timestamp', time() - ( 30 * MINUTE_IN_SECONDS ) );

		// Create a feed without a scheduled retrieval.
		$feed_id = $this->factory->feed->create();
		$feed    = \PressForward\Core\Models\Feed::get_instance_by_id( $feed_id );
		$feed->unschedule_retrieval();

		// Run the health check - it should not run because less than an hour has passed.
		pressforward( 'schema.feeds' )->check_feed_retrieval_cron_jobs();

		// The retrieval should still not be scheduled.
		$this->assertNull( $feed->get_next_scheduled_retrieval() );
	}

	/**
	 * Test that the health check processes feeds incrementally.
	 */
	public function test_check_feed_retrieval_cron_jobs_incremental_processing() {
		// Create multiple feeds.
		$feed_ids = array();
		for ( $i = 0; $i < 150; $i++ ) {
			$feed_ids[] = $this->factory->feed->create();
		}

		// Clear all retrieval schedules.
		foreach ( $feed_ids as $feed_id ) {
			$feed = \PressForward\Core\Models\Feed::get_instance_by_id( $feed_id );
			$feed->unschedule_retrieval();
		}

		// Reset the check timestamp and offset.
		update_option( 'pf_feed_cron_check_timestamp', 0 );
		update_option( 'pf_feed_cron_check_offset', 0 );

		// First run should process up to 100 feeds (default batch size).
		pressforward( 'schema.feeds' )->check_feed_retrieval_cron_jobs();

		// Check that the offset was updated.
		$offset = get_option( 'pf_feed_cron_check_offset' );
		$this->assertEquals( 100, $offset );

		// Verify that at least some feeds now have scheduled retrievals.
		$scheduled_count = 0;
		foreach ( array_slice( $feed_ids, 0, 100 ) as $feed_id ) {
			$feed = \PressForward\Core\Models\Feed::get_instance_by_id( $feed_id );
			if ( $feed->get_next_scheduled_retrieval() ) {
				$scheduled_count++;
			}
		}
		$this->assertGreaterThan( 0, $scheduled_count );
	}

	/**
	 * Test that the offset resets when all feeds have been checked.
	 */
	public function test_check_feed_retrieval_cron_jobs_offset_reset() {
		// Create a small number of feeds.
		$feed_ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$feed_ids[] = $this->factory->feed->create();
		}

		// Reset the check timestamp and offset.
		update_option( 'pf_feed_cron_check_timestamp', 0 );
		update_option( 'pf_feed_cron_check_offset', 0 );

		// Run the health check.
		pressforward( 'schema.feeds' )->check_feed_retrieval_cron_jobs();

		// Since we have fewer feeds than the batch size, the offset should reset to 0.
		$offset = get_option( 'pf_feed_cron_check_offset' );
		$this->assertEquals( 0, $offset );
	}

	/**
	 * Test that the batch size can be filtered.
	 */
	public function test_check_feed_retrieval_cron_jobs_batch_size_filter() {
		// Create a filter callback function.
		$custom_batch_size    = 50;
		$batch_size_filter_cb = function() use ( $custom_batch_size ) {
			return $custom_batch_size;
		};

		// Add the filter.
		add_filter( 'pf_feed_cron_check_batch_size', $batch_size_filter_cb );

		// Create multiple feeds.
		$feed_ids = array();
		for ( $i = 0; $i < 75; $i++ ) {
			$feed_ids[] = $this->factory->feed->create();
		}

		// Clear all retrieval schedules.
		foreach ( $feed_ids as $feed_id ) {
			$feed = \PressForward\Core\Models\Feed::get_instance_by_id( $feed_id );
			$feed->unschedule_retrieval();
		}

		// Reset the check timestamp and offset.
		update_option( 'pf_feed_cron_check_timestamp', 0 );
		update_option( 'pf_feed_cron_check_offset', 0 );

		// Run the health check.
		pressforward( 'schema.feeds' )->check_feed_retrieval_cron_jobs();

		// Check that the offset matches our custom batch size.
		$offset = get_option( 'pf_feed_cron_check_offset' );
		$this->assertEquals( $custom_batch_size, $offset );

		// Clean up: Remove the filter.
		remove_filter( 'pf_feed_cron_check_batch_size', $batch_size_filter_cb );
	}

	/**
	 * Test that the health check doesn't reschedule already scheduled feeds.
	 */
	public function test_check_feed_retrieval_cron_jobs_preserves_existing_schedules() {
		// Create a feed and schedule its retrieval.
		$feed_id = $this->factory->feed->create();
		$feed    = \PressForward\Core\Models\Feed::get_instance_by_id( $feed_id );
		
		$specific_time = time() + ( 2 * HOUR_IN_SECONDS );
		$feed->schedule_retrieval( array( 'nextrun' => $specific_time ) );
		
		$original_schedule = $feed->get_next_scheduled_retrieval();
		$this->assertNotNull( $original_schedule );

		// Reset the check timestamp to force the check to run.
		update_option( 'pf_feed_cron_check_timestamp', 0 );
		update_option( 'pf_feed_cron_check_offset', 0 );

		// Run the health check.
		pressforward( 'schema.feeds' )->check_feed_retrieval_cron_jobs();

		// Verify the schedule hasn't changed.
		$new_schedule = $feed->get_next_scheduled_retrieval();
		$this->assertEquals( $original_schedule, $new_schedule );
	}
}
