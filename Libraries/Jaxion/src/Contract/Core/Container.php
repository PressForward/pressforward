<?php
namespace Intraxia\Jaxion\Contract\Core;

interface Container extends \ArrayAccess, \Iterator {
	/**
	 * Define a new service or value on the Container.
	 *
	 * The alias is the name that the value will be referenced by. This can be used by both
	 * the `get` method to retrieve the value or through ArrayAccess (`$container['alias']`).
	 * It should be a short name used to reference the defined value. The definition can be
	 * any scalar value to assign to the alias, or it can define a service object to return.
	 * This can be accomplished by passing in a closure, which takes the container and returns
	 * a fully constructed object. This closure will be executed every time the class is fetched.
	 * If an already-instantiated object is passed in, it will be returned when fetched. A
	 * Definition object will be returned for additional manipulation. Scalar values will be
	 * locked automatically and can't be overridden.
	 *
	 * @param string|array $alias
	 * @param mixed        $definition
	 *
	 * @return $this
	 */
	public function define( $alias, $definition );

	/**
	 * Defines a new singleton on the Container.
	 *
	 * Functions identically to Container::define, except closures passed in are only executed
	 * once, and the return value is reused across multiple fetches.
	 *
	 * @param string|array $alias
	 * @param mixed        $definition
	 *
	 * @return $this
	 */
	public function share( $alias, $definition );

	/**
	 * Fetches the value for the provided alias.
	 *
	 * @param string $alias
	 *
	 * @return mixed
	 */
	public function fetch( $alias );

	/**
	 * Checks whether the provided alias exists on the container.
	 *
	 * @param string $alias
	 *
	 * @return bool
	 */
	public function has( $alias );

	/**
	 * Removes the provided alias from the container.
	 *
	 * @param string $alias
	 *
	 * @return bool
	 */
	public function remove( $alias );

	/**
	 * Registers a service provider with the container.
	 *
	 * A service provider is responsible for defining and generating services that will be bound
	 * into the container. This keeps the container and Application responsible solely for maintaining
	 * the generated services and the API for registering them and allows for a clean interface for
	 * adding new services to the container.
	 *
	 * @param ServiceProvider $provider
	 */
	public function register( ServiceProvider $provider );
}
