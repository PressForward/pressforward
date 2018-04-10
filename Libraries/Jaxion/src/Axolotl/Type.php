<?php

namespace Intraxia\Jaxion\Axolotl;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Class Type
 *
 * Responsible for validating new items against a type.
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl
 */
class Type {

	/**
	 * Type to validate against.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Type constructor.
	 *
	 * @param string $type
	 * @param bool   $key_type
	 */
	public function __construct( $type, $key_type = false ) {
		$this->type = $this->determine( $type, $key_type );
	}

	/**
	 * Get validation type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Returns whether the type is an Axolotl model.
	 *
	 * @return bool
	 */
	public function is_model() {
		if ( ! class_exists( $this->type ) ) {
			return false;
		}

		$reflection = new ReflectionClass( $this->type );
		return $reflection->isSubclassOf( 'Intraxia\Jaxion\Axolotl\Model' );
	}

	/**
	 * Create a new model from the given data.
	 *
	 * @param array $data Data for the model.
	 *
	 * @return Model
	 */
	public function create_model( array $data ) {
		return new $this->type( $data );
	}

	/**
	 * Validates an array of element.
	 *
	 * @param array $elements Elements to be validated.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate_elements( array $elements ) {
		foreach ( $elements as $element ) {
			$this->validate_element( $element );
		}
	}

	/**
	 * Validate whether the
	 *
	 * @param mixed $element Element to validate.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate_element( $element ) {
		$type = gettype( $element );
		$callable = $this->type === 'callable';
		$is_object = 'object' === $type;
		$loose_check = $this->type === 'object';

		// callable must be callable
		if ( $callable && ! is_callable( $element ) ) {
			throw new InvalidArgumentException( 'Item must be callable' );
		}

		// target isn't callable, object must be an instance of target
		if ( ! $loose_check && ! $callable && $is_object && ! is_a( $element, $this->type ) ) {
			throw new InvalidArgumentException( "Item is not type or subtype of $this->type" );
		}

		// a non callable, non object type should match the target string
		if ( ! $callable && ! $is_object && $type !== $this->type ) {
			throw new InvalidArgumentException( "Item is not of type: $this->type" );
		}
	}

	/**
	 * Determine the type to validate against.
	 *
	 * @param string $type     Type to determine.
	 * @param bool   $key_type Whether the type is for keys.
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	private function determine( $type, $key_type = false ) {
		if ( ! $key_type && $this->non_scalar_type_exists( $type ) ) {
			return $type;
		}

		if ( $scalar_type = $this->determine_scalar( $type ) ) {
			if ( $key_type && (in_array( $scalar_type, array( 'double', 'boolean' ) )) ) {
				throw new InvalidArgumentException( 'This type is not supported as a key.' );
			}

			return $scalar_type;
		}

		throw new InvalidArgumentException( 'This type does not exist.' );
	}

	/**
	 * Determines whether the given type exists.
	 *
	 * @param string $type Type to check.
	 *
	 * @return bool
	 */
	private function non_scalar_type_exists( $type ) {
		return class_exists( $type )
				|| interface_exists( $type )
				|| in_array( $type, array( 'array', 'object', 'callable' ) );
	}

	/**
	 * Returns the type if it's scalar, otherwise, returns null.
	 *
	 * @param string $type Type to check.
	 *
	 * @return string|null
	 */
	private function determine_scalar( $type ) {
		$synonyms = array(
			'int' => 'integer',
			'float' => 'double',
			'bool' => 'boolean',
		);

		if ( array_key_exists( $type, $synonyms ) ) {
			$type = $synonyms[ $type ];
		}

		return in_array( $type, array( 'string', 'integer', 'double', 'boolean' ) ) ?
			$type :
			null;
	}
}
