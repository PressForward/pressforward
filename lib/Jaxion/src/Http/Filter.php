<?php
namespace Intraxia\Jaxion\Http;

use Intraxia\Jaxion\Contract\Http\Filter as FilterContract;

/**
 * Class Filter
 *
 * Generates the rules used by the WP-API to validate and sanitize and
 *
 * @package Intraxia\Jaxion
 * @subpackage Http
 */
class Filter implements FilterContract {
	/**
	 * Filter rules.
	 *
	 * @var array
	 */
	protected $rules;

	/**
	 * Instantiates a new filter with the provided rules array.
	 *
	 * @param array $rules
	 */
	public function __construct( $rules = array() ) {
		$this->rules = $rules;
	}

	/**
	 * Generates argument rules.
	 *
	 * Returns an array matching the WP-API format for argument rules,
	 * including sanitization, validation, required, or defaults.
	 *
	 * @return array
	 */
	public function rules() {
		$args = array();

		foreach ( $this->rules as $arg => $validation ) {
			if ( ! $validation || ! is_string( $validation ) ) {
				continue;
			}

			$args[ $arg ] = $this->parse_validation( $validation );
		}

		return $args;
	}

	/**
	 * Parses a validation string into a WP-API compatible rule.
	 *
	 * @param string $validation
	 *
	 * @return array
	 *
	 * @todo The next rule added needs to refactor this process.
	 */
	protected function parse_validation( $validation ) {
		$validation = explode( '|', $validation );

		$rules = array();

		foreach ( $validation as $rule ) {
			if ( 0 === strpos( $rule, 'default' ) ) {
				$rule_arr = explode( ':', $rule );

				$rules['default'] = count( $rule_arr ) === 2 ? array_pop( $rule_arr ) : '';
			}

			if ( 0 === strpos( $rule, 'oneof' ) ) {
				list( $rule, $values ) = explode( ':', $rule );

				$values   = explode( ',', $values );
				$callback = function ( $value ) use ( $values ) {
					if ( in_array( $value, $values, true ) ) {
						return true;
					}

					return false;
				};

				$rules['validate_callback'] = isset( $rules['validate_callback'] ) ? $this->add_callback( $rules['validate_callback'], $callback ) : $callback;
			}

			switch ( $rule ) {
				case 'required':
					$rules['required'] = true;
					break;
				case 'integer':
					$callback                   = array( $this, 'validate_integer' );
					$rules['validate_callback'] = isset( $rules['validate_callback'] ) ? $this->add_callback( $rules['validate_callback'], $callback ) : $callback;

					$callback                   = array( $this, 'make_integer' );
					$rules['sanitize_callback'] = isset( $rules['sanitize_callback'] ) ? $this->add_callback( $rules['sanitize_callback'], $callback ) : $callback;
					break;
			}
		}

		return $rules;
	}

	/**
	 * Validate that provided value is an integer.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function validate_integer( $value ) {
		return filter_var( $value, FILTER_VALIDATE_INT ) !== false;
	}

	/**
	 * Casts a provided value to an integer.
	 *
	 * @param mixed $value
	 *
	 * @return int
	 */
	public function make_integer( $value ) {
		return (int) $value;
	}

	/**
	 * Creates a new callback that connects the previous and next callback.
	 *
	 * @param callable $previous
	 * @param callable $next
	 *
	 * @return \Closure;
	 */
	private function add_callback( $previous, $next ) {
		return function ( $value ) use ( $previous, $next ) {
			if ( call_user_func( $previous, $value ) ) {
				return call_user_func( $next, $value );
			}

			return false;
		};
	}
}
