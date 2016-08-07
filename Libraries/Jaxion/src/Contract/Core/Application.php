<?php
namespace Intraxia\Jaxion\Contract\Core;

use Intraxia\Jaxion\Core\ApplicationNotBootedException;

interface Application {
	/**
	 * Starts up the Application.
	 *
	 * Retrieves the Application's loader instance, and registers all of the Application's
	 * services with the loader. It loops through its own services, and any services that
	 * implement the required interface are passed into the loader. It then attaches the
	 * loader to the required WordPress hook.
	 */
	public function boot();

	/**
	 * Fired on plugin activation.
	 *
	 * This function is attached to `register_activation_hook` and is fired when the plugin is
	 * activated by WordPress. This gives the developer a place to set up any options,
	 * add any custom tables, or flush rewrite rules, as required.
	 */
	public function activate();

	/**
	 * Fired on plugin deactivation.
	 *
	 * This function is attached to `register_deactivation_hook` and is fired when the plugin
	 * is deactivated by WordPress. This gives the developer a place to clean up anything left
	 * behind by the plugin.
	 */
	public function deactivate();

	/**
	 * Retrieves the booted Application instance.
	 *
	 * If the Application has not yet been booted, an Exception will be thrown.
	 *
	 * @return Application
	 * @throws ApplicationNotBootedException
	 */
	public static function instance();

	/**
	 * Shuts down the booted Application.
	 *
	 * If the Application has already been booted, the Application instance
	 * will be destroyed by assigning it a null value, freeing it from memory.
	 * However, the service objects will likely remain in memory if they've been
	 * attached to hooks when this method is called. This function is primarily
	 * for uniting testing to make sure you can boot a new instance for each test.
	 */
	public static function shutdown();
}
