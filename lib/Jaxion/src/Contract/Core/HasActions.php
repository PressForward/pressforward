<?php
namespace Intraxia\Jaxion\Contract\Core;

interface HasActions {
	/**
	 * Provides the array of actions the class wants to register with WordPress.
	 *
	 * These actions are retrieved by the Loader class and used to register the
	 * correct service methods with WordPress.
	 *
	 * @return array[]
	 */
	public function action_hooks();
}
