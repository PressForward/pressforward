<?php
namespace Intraxia\Jaxion\Utility;

/**
 * Class Str
 *
 * String utility class. Much of this has been borrowed from Illuminate\Support
 * and dumbed down for PHP 5.3 compatibility.
 *
 * @package Intraxia\Jaxion
 * @subpackage Utility
 */
class Str {
	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param  string       $haystack
	 * @param  string|array $needles
	 *
	 * @return bool
	 */
	public static function starts_with( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( '' !== $needle && 0 === strpos( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param  string       $haystack
	 * @param  string|array $needles
	 *
	 * @return bool
	 */
	public static function ends_with( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( substr( $haystack, - strlen( $needle ) ) === (string) $needle ) {
				return true;
			}
		}

		return false;
	}
}
