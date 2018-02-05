<?php

namespace Intraxia\Jaxion\Axolotl;

use Intraxia\Jaxion\Contract\Axolotl\Dictionary as DictionaryContract;
use Intraxia\Jaxion\Contract\Axolotl\Serializes;
use InvalidArgumentException;

/**
 * Class Dictionary
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl
 */
class Dictionary implements DictionaryContract {

	/**
	 * Dictionary storage array.
	 *
	 * @var array
	 */
	protected $storage = array();

	/**
	 * Key Type service.
	 *
	 * @var Type
	 */
	protected $key_type;

	/**
	 * Value Type service.
	 *
	 * @var Type
	 */
	protected $val_type;

	/**
	 * Where Dictionary is in loop.
	 *
	 * @var int
	 */
	protected $position = 0;

	/**
	 * Dictionary keys.
	 *
	 * @var array
	 */
	protected $keys = array();

	/**
	 * Dictionary constructor.
	 *
	 * @param string $key_type
	 * @param string $val_type
	 * @param array  $storage
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $key_type, $val_type, array $storage = array() ) {
		$this->key_type = new Type( $key_type, true );
		$this->val_type = new Type( $val_type );

		foreach ( $storage as $key => $val ) {
			$this->key_type->validate_element( $key );
			$this->val_type->validate_element( $val );

			$this->storage[ $key ] = $val;
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function get_key_type() {
		return $this->key_type->get_type();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function get_value_type() {
		return $this->val_type->get_type();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $key Key to check.
	 *
	 * @return bool
	 */
	public function exists( $key ) {
		return array_key_exists( $key, $this->storage );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $key Key to get.
	 *
	 * @return mixed|null
	 */
	public function get( $key ) {
		return $this->exists( $key ) ? $this->storage[ $key ] : null;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $key Key to remove.
	 *
	 * @return DictionaryContract
	 */
	public function delete( $key ) {
		$storage = $this->storage;
		if ( $this->exists( $key ) ) {
			unset( $storage[ $key ] );
		}

		return new static( $this->get_key_type(), $this->get_value_type(), $storage );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $value Value to validate.
	 *
	 * @return bool
	 */
	public function value_exists( $value ) {
		return in_array( $value, $this->storage );
	}

	/**
	 * {@inheritdoc}
	 */
	public function count() {
		return count( $this->storage );
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear() {
		return new static( $this->get_key_type(), $this->get_value_type() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->storage;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $condition Conditional callback.
	 *
	 * @return DictionaryContract
	 */
	public function filter( $condition ) {
		$storage = array();

		foreach ( $this->storage as $key => $value ) {
			if ( call_user_func( $condition, $value, $key ) ) {
				$storage[ $key ] = $value;
			}
		}

		return new static( $this->get_key_type(), $this->get_value_type(), $storage );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $condition Callback condition.
	 *
	 * @return DictionaryContract
	 */
	public function reject( $condition ) {
		return $this->filter( function ( $v, $k ) use ( $condition ) {
			return ! call_user_func( $condition, $v, $k );
		} );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $key   Key to add.
	 * @param mixed $value Value to add.
	 *
	 * @return DictionaryContract
	 */
	public function add( $key, $value ) {
		$storage         = $this->storage;
		$storage[ $key ] = $value;

		return new static( $this->get_key_type(), $this->get_value_type(), $storage );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable Function to call.
	 */
	public function each( $callable ) {
		foreach ( $this->storage as $key => $value ) {
			call_user_func( $callable, $value, $key );
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $key     Key to fetch.
	 * @param mixed $default Default to return if key is missing.
	 *
	 * @return mixed
	 */
	public function get_or_else( $key, $default ) {
		return ( $this->exists( $key ) ) ? $this->get( $key ) : $default;
	}

	/**
	 * {@inheritdoc}
	 */
	public function keys() {
		return array_keys( $this->storage );
	}

	/**
	 * {@inheritdoc}
	 */
	public function values() {
		return array_values( $this->storage );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable Function to call.
	 *
	 * @return DictionaryContract
	 */
	public function map( $callable ) {
		$items = array();
		$val_type = null;

		foreach ( $this->storage as $key => $val ) {
			$v = call_user_func( $callable, $val, $key );

			if ( ! isset( $val_type ) ) {
				$val_type = gettype( $v );
			}

			$items[ $key ] = $v;
		}

		return new static( $this->get_key_type(), $val_type ? : $this->get_value_type(), $items );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array|DictionaryContract $source Source to merge.
	 *
	 * @return DictionaryContract
	 *
	 * @throws InvalidArgumentException
	 */
	public function merge( $source ) {
		if ( $source instanceof self ) {
			$source = $source->to_array();
		}

		if ( ! is_array( $source ) ) {
			throw new InvalidArgumentException( 'Combine must be a Dictionary or an array' );
		}

		return new static( $this->get_key_type(), $this->get_value_type(), array_merge( $this->storage, $source ) );
	}


	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable
	 *
	 * @return bool
	 */
	public function contains( $callable ) {
		foreach ( $this->storage as $key => $value ) {
			if ( call_user_func( $callable, $value, $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable
	 * @param mixed    $initial
	 *
	 * @return mixed
	 */
	public function reduce( $callable, $initial ) {
		$carry = $initial;

		foreach ( $this->storage as $key => $value ) {
			$carry = $callable( $carry, $value, $key );
		}

		return $carry;
	}

	/**
	 * Return the current element.
	 *
	 * @return mixed
	 */
	public function current() {
		$key = $this->keys[ $this->position ];
		return $this->storage[ $key ];
	}

	/**
	 * Move forward to next element.
	 */
	public function next() {
		$this->position ++;
	}

	/**
	 * Return the key of the current element.
	 *
	 * @return mixed
	 */
	public function key() {
		return $this->keys[ $this->position ];
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return bool
	 */
	public function valid() {
		return isset( $this->keys[ $this->position ] );
	}

	/**
	 * Rewind the Iterator to the first element.
	 */
	public function rewind() {
		$this->position = 0;
		$this->keys = array_keys( $this->storage );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function serialize() {
		return $this->map(function( $val ) {
			if ( $val instanceof Serializes ) {
				$val = $val->serialize();
			}

			return $val;
		})->to_array();
	}
}
