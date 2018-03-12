<?php
namespace Intraxia\Jaxion\Contract\Axolotl;

use Countable;
use Iterator;

interface Dictionary extends Iterator, Countable, Serializes {
	/**
	 * Returns the type of the dictionary's keys.
	 *
	 * @return string
	 */
	public function get_key_type();

	/**
	 * Returns the type of the dictionary's values.
	 *
	 * @return string
	 */
	public function get_value_type();

	/**
	 * Returns true if $key is in the dictionary, returns false if it is not.
	 *
	 * @param mixed $key Key to check for.
	 *
	 * @return bool
	 */
	public function exists( $key );

	/**
	 * Returns true if the callable returns true. The callable should accept a
	 * key and value as arguments and return a boolean.
	 *
	 * @param  callable $callable
	 * @return bool
	 */
	public function contains( $callable );

	/**
	 * Returns the value associated with $key.
	 *
	 * @param mixed $key Key to get.
	 *
	 * @return mixed
	 */
	public function get( $key );

	/**
	 * Removes the key-value pair represented by $key from the dictionary.
	 *
	 * @param mixed $key Key to remove.
	 *
	 * @return static
	 */
	public function delete( $key );

	/**
	 * Returns true if $value is in the dictionary, returns false if not.
	 *
	 * @param mixed $value Value to check for existence.
	 *
	 * @return bool
	 */
	public function value_exists( $value );

	/**
	 * Returns the number of key-value pairs in the dictionary.
	 *
	 * @return int
	 */
	public function count();

	/**
	 * Removes every key-value pair from the dictionary.
	 *
	 * @return static
	 */
	public function clear();

	/**
	 * Returns the key-value pairs in the dictionary as an associative array.
	 *
	 * @return array
	 */
	public function to_array();

	/**
	 * Returns a dictionary that only contains the key-value pairs which satisfy
	 * $condition.
	 *
	 * @param callable $condition
	 *
	 * @return static
	 */
	public function filter( $condition );

	/**
	 * Removes all key-value pairs from the Dictionary that do not satisfy
	 * $condition.
	 *
	 * @param callable $condition
	 *
	 * @return Dictionary
	 */
	public function reject( $condition );

	/**
	 * Adds the key-value pair containing $key and $value to the dictionary.
	 *
	 * @param mixed $key   Key to add.
	 * @param mixed $value Value to add.
	 *
	 * @return Dictionary
	 * @throws \InvalidArgumentException
	 */
	public function add( $key, $value );

	/**
	 * Applies the callback function $callable to each key-value pair in the
	 * dictionary.
	 *
	 * @param callable $callable
	 */
	public function each( $callable );

	/**
	 * Returns the value associated with $key in the dictionary, returns
	 * $default if it does not.
	 *
	 * @param mixed $key     Key to fetch.
	 * @param mixed $default Default value if key is not found.
	 *
	 * @return mixed
	 */
	public function get_or_else( $key, $default );

	/**
	 * Returns an array of all keys in the dictionary.
	 *
	 * @return array
	 */
	public function keys();

	/**
	 * Returns an array of all values in the dictionary.
	 *
	 * @return array
	 */
	public function values();

	/**
	 * Returns a new dictionary with the callback function $callable applied to
	 * every key-value pair in the dictionary.
	 *
	 * @param callable $callable
	 *
	 * @return static
	 */
	public function map( $callable );

	/**
	 * Adds every key-value pair in $storage to the dictionary.
	 *
	 * @param array|Dictionary $source Storage object to merge.
	 *
	 * @return static
	 */
	public function merge( $source );


	/**
	 * Reduce the dictionary to a single value. The callable should contain
	 * args: carry, key, value
	 *
	 * @param callable $callable
	 * @param mixed    $initial
	 *
	 * @return mixed
	 */
	public function reduce( $callable, $initial );
}
