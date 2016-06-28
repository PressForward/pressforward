<?php
namespace Intraxia\Jaxion\Core;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use Intraxia\Jaxion\Contract\Core\HasShortcode;
use Intraxia\Jaxion\Contract\Core\Loader as LoaderContract;

/**
 * Class Loader
 *
 * Iterates over a service container to register its services with their respective WordPres hooks.
 *
 * @package Intraxia\Jaxion
 * @subpackage Core
 */
class Loader implements LoaderContract {
	/**
	 * Array of action hooks to attach.
	 *
	 * @var array[]
	 */
	protected $actions = array();

	/**
	 * Array of filter hooks to attach.
	 *
	 * @var array[]
	 */
	protected $filters = array();

	/**
	 * {@inheritDoc}
	 */
	public function run() {
		foreach ( $this->actions as $action ) {
			add_action(
				$action['hook'],
				array( $action['service'], $action['method'] ),
				$action['priority'],
				$action['args']
			);
		}

		foreach ( $this->filters as $filter ) {
			add_filter(
				$filter['hook'],
				array( $filter['service'], $filter['method'] ),
				$filter['priority'],
				$filter['args']
			);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param HasActions $service
	 */
	public function register_actions( HasActions $service ) {
		foreach ( $service->action_hooks() as $action ) {
			$this->actions = $this->add(
				$this->actions,
				$action['hook'],
				$service,
				$action['method'],
				isset( $action['priority'] ) ? $action['priority'] : 10,
				isset( $action['args'] ) ? $action['args'] : 1
			);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param HasFilters $service
	 */
	public function register_filters( HasFilters $service ) {
		foreach ( $service->filter_hooks() as $filter ) {
			$this->filters = $this->add(
				$this->filters,
				$filter['hook'],
				$service,
				$filter['method'],
				isset( $filter['priority'] ) ? $filter['priority'] : 10,
				isset( $filter['args'] ) ? $filter['args'] : 1
			);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param HasShortcode $service
	 */
	public function register_shortcode( HasShortcode $service ) {
		add_shortcode( $service->shortcode_name(), array( $service, 'do_shortcode' ) );
	}

	/**
	 * Utility to register the actions and hooks into a single collection.
	 *
	 * @param array  $hooks
	 * @param string $hook
	 * @param object $service
	 * @param string $method
	 * @param int    $priority
	 * @param int    $accepted_args
	 *
	 * @return array
	 */
	protected function add( $hooks, $hook, $service, $method, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'     => $hook,
			'service'  => $service,
			'method'   => $method,
			'priority' => $priority,
			'args'     => $accepted_args,
		);

		return $hooks;
	}
}
