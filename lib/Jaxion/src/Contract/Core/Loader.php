<?php
namespace Intraxia\Jaxion\Contract\Core;

interface Loader {
	/**
	 * Register all the actions and filters with WordPress.
	 *
	 * Loops through all the registered actions/filters and fires add_* for each of
	 * them respectively.
	 */
	public function run();

	/**
	 * Registers the service's actions with the loader.
	 *
	 * Actions retrieved from the service are registered with the Loader.
	 * When the loader runs, this actions are registered with WordPress.
	 *
	 * @param HasActions $service
	 */
	public function register_actions( HasActions $service );

	/**
	 * Registers the service's filters with the loader.
	 *
	 * Filters retrieved from the service are registered with the Loader.
	 * When the loader runs, these filters are registered with WordPress.
	 *
	 * @param HasFilters $service
	 */
	public function register_filters( HasFilters $service );

	/**
	 * Registers the service's shortcode with the loader.
	 *
	 * Service's method definitions are passed into the add_shortcode hook
	 * in WordPress to register the shortcode.
	 *
	 * @param HasShortcode $service
	 */
	public function register_shortcode( HasShortcode $service );
}
