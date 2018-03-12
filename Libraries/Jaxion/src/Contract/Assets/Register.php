<?php
namespace Intraxia\Jaxion\Contract\Assets;

use Intraxia\Jaxion\Contract\Core\HasActions;

interface Register extends HasActions {
	/**
	 * Enable debug mode for the enqueued assets.
	 *
	 * Debug mode will enqueue unminified versions of the registered assets.
	 * Primarily, this is intended to be used along with WordPress's `SCRIPT_DEBUG`
	 * constant, which enables unminified core assets to be enqueued.
	 *
	 * @param bool $debug
	 */
	public function set_debug( $debug );

	/**
	 * Provides a method to register new scripts outside of the constructor.
	 *
	 * @param array $script
	 */
	public function register_script( $script );

	/**
	 * Provides a method to register new styles outside of the constructor.
	 *
	 * @param array $style
	 */
	public function register_style( $style );

	/**
	 * Enqueues the web & shared scripts on the Register.
	 */
	public function enqueue_web_scripts();

	/**
	 * Enqueues the web & shared styles on the Register.
	 */
	public function enqueue_web_styles();

	/**
	 * Enqueues the admin & shared scripts on the Register.
	 *
	 * @param string $hook Passes a string representing the current page.
	 */
	public function enqueue_admin_scripts( $hook );

	/**
	 * Enqueues the admin & shared styles on the Register.
	 *
	 * @param string $hook Passes a string representing the current page.
	 */
	public function enqueue_admin_styles( $hook );
}
