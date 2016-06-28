<?php
namespace Intraxia\Jaxion\Contract\Http;

/**
 * Interface Guard
 *
 * Defines a standard interface for permission rules for a route.
 *
 * @package Intraxia\Jaxion
 * @subpackage Contract\Http
 */
interface Guard {
	/**
	 * Validates when the user is authorized for the route.
	 *
	 * Returns a boolean based on whether the current user is authorized
	 * to interact with the given route.
	 *
	 * @return bool
	 */
	public function authorized();
}
