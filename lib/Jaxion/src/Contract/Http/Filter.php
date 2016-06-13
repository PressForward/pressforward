<?php
namespace Intraxia\Jaxion\Contract\Http;

/**
 * Interface Filter
 *
 * Defines a standard interface for generating WP-API argument rules.
 *
 * @package Intraxia\Jaxion
 * @subpackage Contract\Http
 */
interface Filter {
	/**
	 * Generates argument rules.
	 *
	 * Returns an array matching the WP-API format for argument rules,
	 * including sanitization, validation, required, or defaults.
	 *
	 * @return array
	 */
	public function rules();
}
