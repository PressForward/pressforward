<?php
/**
 * Google Scholar Rate Limiter utility.
 *
 * @package PressForward
 */

namespace PressForward\Core\Utility;

/**
 * GoogleScholarRateLimiter class.
 *
 * Implements rate limiting for Google Scholar requests to prevent IP blocking.
 */
class GoogleScholarRateLimiter {
	/**
	 * Option name for storing request timestamps.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'pf_google_scholar_requests';

	/**
	 * Maximum requests per hour (default).
	 *
	 * @var int
	 */
	const DEFAULT_MAX_REQUESTS_PER_HOUR = 2;

	/**
	 * Maximum requests per day (default).
	 *
	 * @var int
	 */
	const DEFAULT_MAX_REQUESTS_PER_DAY = 10;

	/**
	 * Get the maximum requests allowed per hour.
	 *
	 * @return int
	 */
	public static function get_max_requests_per_hour() {
		return (int) get_option( 'pf_google_scholar_max_per_hour', self::DEFAULT_MAX_REQUESTS_PER_HOUR );
	}

	/**
	 * Get the maximum requests allowed per day.
	 *
	 * @return int
	 */
	public static function get_max_requests_per_day() {
		return (int) get_option( 'pf_google_scholar_max_per_day', self::DEFAULT_MAX_REQUESTS_PER_DAY );
	}

	/**
	 * Get stored request timestamps.
	 *
	 * @return array Array of Unix timestamps.
	 */
	protected static function get_request_timestamps() {
		$timestamps = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $timestamps ) ) {
			$timestamps = [];
		}
		return $timestamps;
	}

	/**
	 * Save request timestamps.
	 *
	 * @param array $timestamps Array of Unix timestamps.
	 * @return bool
	 */
	protected static function save_request_timestamps( $timestamps ) {
		return update_option( self::OPTION_NAME, $timestamps, false );
	}

	/**
	 * Clean up old request timestamps.
	 *
	 * Removes timestamps older than 24 hours.
	 *
	 * @param array $timestamps Array of Unix timestamps.
	 * @return array Cleaned array of timestamps.
	 */
	protected static function cleanup_old_timestamps( $timestamps ) {
		$cutoff_time = time() - DAY_IN_SECONDS;
		return array_filter(
			$timestamps,
			function ( $timestamp ) use ( $cutoff_time ) {
				return $timestamp > $cutoff_time;
			}
		);
	}

	/**
	 * Check if a request is allowed based on rate limits.
	 *
	 * @return bool|array True if allowed, array with error details if not.
	 */
	public static function is_request_allowed() {
		$timestamps = self::get_request_timestamps();
		$timestamps = self::cleanup_old_timestamps( $timestamps );

		$now               = time();
		$one_hour_ago      = $now - HOUR_IN_SECONDS;
		$one_day_ago       = $now - DAY_IN_SECONDS;
		$max_per_hour      = self::get_max_requests_per_hour();
		$max_per_day       = self::get_max_requests_per_day();

		// Count requests in the last hour.
		$requests_last_hour = count(
			array_filter(
				$timestamps,
				function ( $timestamp ) use ( $one_hour_ago ) {
					return $timestamp > $one_hour_ago;
				}
			)
		);

		// Count requests in the last day.
		$requests_last_day = count(
			array_filter(
				$timestamps,
				function ( $timestamp ) use ( $one_day_ago ) {
					return $timestamp > $one_day_ago;
				}
			)
		);

		// Check hourly limit.
		if ( $requests_last_hour >= $max_per_hour ) {
			return [
				'allowed'         => false,
				'reason'          => 'hourly_limit',
				'message'         => sprintf(
					// translators: 1: number of requests, 2: time period.
					__( 'Google Scholar request limit reached: %1$d requests per %2$s. Please try again later.', 'pressforward' ),
					$max_per_hour,
					__( 'hour', 'pressforward' )
				),
				'requests_count'  => $requests_last_hour,
				'limit'           => $max_per_hour,
				'retry_after'     => self::get_retry_after_seconds( $timestamps, HOUR_IN_SECONDS, $max_per_hour ),
			];
		}

		// Check daily limit.
		if ( $requests_last_day >= $max_per_day ) {
			return [
				'allowed'         => false,
				'reason'          => 'daily_limit',
				'message'         => sprintf(
					// translators: 1: number of requests, 2: time period.
					__( 'Google Scholar request limit reached: %1$d requests per %2$s. Please try again later.', 'pressforward' ),
					$max_per_day,
					__( 'day', 'pressforward' )
				),
				'requests_count'  => $requests_last_day,
				'limit'           => $max_per_day,
				'retry_after'     => self::get_retry_after_seconds( $timestamps, DAY_IN_SECONDS, $max_per_day ),
			];
		}

		return true;
	}

	/**
	 * Get the number of seconds until the next request can be made.
	 *
	 * @param array $timestamps Array of Unix timestamps.
	 * @param int   $period     Time period in seconds.
	 * @param int   $limit      Maximum requests in the period.
	 * @return int Seconds until retry is allowed.
	 */
	protected static function get_retry_after_seconds( $timestamps, $period, $limit ) {
		$now         = time();
		$cutoff_time = $now - $period;

		$recent_timestamps = array_filter(
			$timestamps,
			function ( $timestamp ) use ( $cutoff_time ) {
				return $timestamp > $cutoff_time;
			}
		);

		if ( count( $recent_timestamps ) < $limit ) {
			return 0;
		}

		// Sort timestamps in ascending order.
		sort( $recent_timestamps );

		// The oldest timestamp in the period.
		$oldest_timestamp = reset( $recent_timestamps );

		// Calculate when that timestamp will fall outside the period.
		$retry_after = ( $oldest_timestamp + $period ) - $now;

		return max( 0, $retry_after );
	}

	/**
	 * Record a request timestamp.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function record_request() {
		$timestamps   = self::get_request_timestamps();
		$timestamps   = self::cleanup_old_timestamps( $timestamps );
		$timestamps[] = time();

		return self::save_request_timestamps( $timestamps );
	}

	/**
	 * Get current rate limit status information.
	 *
	 * @return array Status information.
	 */
	public static function get_status() {
		$timestamps = self::get_request_timestamps();
		$timestamps = self::cleanup_old_timestamps( $timestamps );

		$now               = time();
		$one_hour_ago      = $now - HOUR_IN_SECONDS;
		$one_day_ago       = $now - DAY_IN_SECONDS;
		$max_per_hour      = self::get_max_requests_per_hour();
		$max_per_day       = self::get_max_requests_per_day();

		$requests_last_hour = count(
			array_filter(
				$timestamps,
				function ( $timestamp ) use ( $one_hour_ago ) {
					return $timestamp > $one_hour_ago;
				}
			)
		);

		$requests_last_day = count(
			array_filter(
				$timestamps,
				function ( $timestamp ) use ( $one_day_ago ) {
					return $timestamp > $one_day_ago;
				}
			)
		);

		return [
			'requests_last_hour'    => $requests_last_hour,
			'max_requests_per_hour' => $max_per_hour,
			'requests_last_day'     => $requests_last_day,
			'max_requests_per_day'  => $max_per_day,
			'hourly_remaining'      => max( 0, $max_per_hour - $requests_last_hour ),
			'daily_remaining'       => max( 0, $max_per_day - $requests_last_day ),
		];
	}

	/**
	 * Reset all stored request timestamps.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function reset() {
		return delete_option( self::OPTION_NAME );
	}
}
