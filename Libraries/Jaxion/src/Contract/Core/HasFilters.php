<?php
namespace Intraxia\Jaxion\Contract\Core;

interface HasFilters {
	/**
	 * Provides the array of filters the class wants to register with WordPress.
	 *
	 * These filters are retrieved by the Loader class and used to register the
	 * correct service methods with WordPress.
	 *
	 * @return array[]
	 */
	public function filter_hooks();
}
