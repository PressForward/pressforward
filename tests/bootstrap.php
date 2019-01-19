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

	class Getopt {
		/**
		 * @throws Exception
		 */
		public static function getopt(array $args, string $short_options, array $long_options = null)
		{
			if (empty($args)) {
				return [[], []];
			}
			$opts	 = [];
			$non_opts = [];
			if ($long_options) {
				\sort($long_options);
			}
			if (isset($args[0][0]) && $args[0][0] !== '-') {
				\array_shift($args);
			}
			\reset($args);
			$args = \array_map('trim', $args);
			/* @noinspection ComparisonOperandsOrderInspection */
			while (false !== $arg = \current($args)) {
				$i = \key($args);
				\next($args);
				if ($arg === '') {
					continue;
				}
				if ($arg === '--') {
					$non_opts = \array_merge($non_opts, \array_slice($args, $i + 1));
					break;
				}
				if ($arg[0] !== '-' || (\strlen($arg) > 1 && $arg[1] === '-' && !$long_options)) {
					$non_opts[] = $args[$i];
					continue;
				}
				if (\strlen($arg) > 1 && $arg[1] === '-') {
					self::parseLongOption(
						\substr($arg, 2),
						$long_options,
						$opts,
						$args
					);
				} else {
					self::parseShortOption(
						\substr($arg, 1),
						$short_options,
						$opts,
						$args
					);
				}
			}
			return [$opts, $non_opts];
		}
		/**
		 * @throws Exception
		 */
		private static function parseShortOption(string $arg, string $short_options, array &$opts, array &$args)
		{
			$argLen = \strlen($arg);
			for ($i = 0; $i < $argLen; $i++) {
				$opt	 = $arg[$i];
				$opt_arg = null;
				if ($arg[$i] === ':' || ($spec = \strstr($short_options, $opt)) === false) {
					throw new Exception(
						"unrecognized option -- $opt"
					);
				}
				if (\strlen($spec) > 1 && $spec[1] === ':') {
					if ($i + 1 < $argLen) {
						$opts[] = [$opt, \substr($arg, $i + 1)];
						break;
					}
					if (!(\strlen($spec) > 2 && $spec[2] === ':')) {
						/* @noinspection ComparisonOperandsOrderInspection */
						if (false === $opt_arg = \current($args)) {
							throw new Exception(
								"option requires an argument -- $opt"
							);
						}
						\next($args);
					}
				}
				$opts[] = [$opt, $opt_arg];
			}
		}
		/**
		 * @throws Exception
		 */
		private static function parseLongOption(string $arg, array $long_options, array &$opts, array &$args)
		{
			$count   = \count($long_options);
			$list	= \explode('=', $arg);
			$opt	 = $list[0];
			$opt_arg = null;
			if (\count($list) > 1) {
				$opt_arg = $list[1];
			}
			$opt_len = \strlen($opt);
			for ($i = 0; $i < $count; $i++) {
				$long_opt  = $long_options[$i];
				$opt_start = \substr($long_opt, 0, $opt_len);
				if ($opt_start !== $opt) {
					continue;
				}
				$opt_rest = \substr($long_opt, $opt_len);
				if ($opt_rest !== '' && $i + 1 < $count && $opt[0] !== '=' &&
					\strpos($long_options[$i + 1], $opt) === 0) {
					throw new Exception(
						"option --$opt is ambiguous"
					);
				}
				if (\substr($long_opt, -1) === '=') {
					/* @noinspection StrlenInEmptyStringCheckContextInspection */
					if (\substr($long_opt, -2) !== '==' && !\strlen((string) $opt_arg)) {
						/* @noinspection ComparisonOperandsOrderInspection */
						if (false === $opt_arg = \current($args)) {
							throw new Exception(
								"option --$opt requires an argument"
							);
						}
						\next($args);
					}
				} elseif ($opt_arg) {
					throw new Exception(
						"option --$opt doesn't allow an argument"
					);
				}
				$full_option = '--' . \preg_replace('/={1,2}$/', '', $long_opt);
				$opts[]	  = [$full_option, $opt_arg];
				return;
			}
			throw new Exception("unrecognized option --$opt");
		}
	}
	class_alias( 'Getopt', 'PHPUnit_Util_Getopt' );

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
