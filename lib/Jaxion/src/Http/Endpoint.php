<?php
namespace Intraxia\Jaxion\Http;

use Intraxia\Jaxion\Contract\Http\Guard as GuardContract;
use Intraxia\Jaxion\Contract\Http\Filter as FilterContract;
use Intraxia\Jaxion\Utility\Str;

/**
 * Class Endpoint
 *
 * Manages the data for a single endpoint.
 *
 * @package Intraxia\Jaxion
 * @subpackage Http
 */
class Endpoint {
	/**
	 * Endpoint's route.
	 *
	 * @var string
	 */
	protected $route;

	/**
	 * Endpoint's HTTP verb(s).
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * Endpoint's callback.
	 *
	 * @var callable
	 */
	protected $callback;

	/**
	 * Endpoint's permission guard.
	 *
	 * @var GuardContract
	 */
	protected $guard;

	/**
	 * Endpoint's arguments filter.
	 *
	 * @var FilterContract
	 */
	protected $filter;

	/**
	 * Endpoint's route prefix.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Instantiate a new endpoint with a provided route, method, and callback.
	 *
	 * @param string   $route
	 * @param string   $method
	 * @param callable $callback
	 *
	 * @throws MalformedRouteException
	 */
	public function __construct( $route, $method, $callback ) {
		if ( ! Str::starts_with( $route, '/' ) || Str::ends_with( $route, '/' ) ) {
			throw new MalformedRouteException;
		}

		$this->route    = $route;
		$this->method   = $method;
		$this->callback = $callback;
	}

	/**
	 * Generates the endpoint's route.
	 *
	 * Combines the prefix with the route to generate the full route string.
	 *
	 * @return string
	 */
	public function get_route() {
		return ( $this->prefix ?: '' ) . $this->route;
	}

	/**
	 * Generates the endpoint's WP-API options array.
	 *
	 * @return array
	 */
	public function get_options() {
		$options = array(
			'methods'  => $this->method,
			'callback' => $this->callback,
		);

		if ( $this->guard ) {
			$options['permission_callback'] = array( $this->guard, 'authorized' );
		}

		if ( $this->filter ) {
			$options['args'] = $this->filter->rules();
		}

		return $options;
	}

	/**
	 * Sets the endpoint's permission guard.
	 *
	 * @param GuardContract $guard
	 *
	 * @return $this
	 */
	public function set_guard( GuardContract $guard ) {
		$this->guard = $guard;

		return $this;
	}

	/**
	 * Sets the endpoint's arguments filter.
	 *
	 * @param FilterContract $filter
	 *
	 * @return $this
	 */
	public function set_filter( FilterContract $filter ) {
		$this->filter = $filter;

		return $this;
	}

	/**
	 * Sets the endpoint's prefix.
	 *
	 * @param string $prefix
	 *
	 * @return $this
	 * @throws MalformedRouteException
	 */
	public function set_prefix( $prefix ) {
		if ( ! Str::starts_with( $prefix, '/' ) || Str::ends_with( $prefix, '/' ) ) {
			throw new MalformedRouteException;
		}

		$this->prefix = $prefix;

		return $this;
	}
}
