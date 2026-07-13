<?php
/**
 * Loader: registers actions and filters with WordPress and runs them.
 *
 * @package Artemis_Convert\Inc\Core
 */

namespace Artemis_Convert\Inc\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Loader
 */
class Loader {

	/**
	 * Actions to register.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Filters to register.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Add an action.
	 *
	 * @param string   $hook          WordPress hook name.
	 * @param object   $component     Object with callback method.
	 * @param string   $callback      Method name.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Number of arguments.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a filter.
	 *
	 * @param string   $hook          WordPress hook name.
	 * @param object   $component     Object with callback method.
	 * @param string   $callback      Method name.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Number of arguments.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add hook to collection.
	 *
	 * @param array  $hooks         Collection.
	 * @param string $hook          Hook name.
	 * @param object $component     Component.
	 * @param string $callback      Callback.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Accepted args.
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return $hooks;
	}

	/**
	 * Register all hooks with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
