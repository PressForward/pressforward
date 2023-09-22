<?php
/**
 * AJAX handlers for "source" actions.
 *
 * @package PressForward
 */

namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\PF_Readability;
use PressForward\Core\Utility\Retrieval;
use PressForward\Core\Schema\Feed_Items;

/**
 * AJAX handlers for "source" actions.
 */
class SourceAJAX implements HasActions {
	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * Readability object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PF_Readability
	 */
	public $readability;

	/**
	 * Retrieval object.
	 *
	 * @access public
	 * @var \PressForward\Core\Utility\Retrieval
	 */
	public $retrieval;

	/**
	 * Feed_Items object.
	 *
	 * @access public
	 * @var \PressForward\Core\Schema\Feed_Items
	 */
	public $items;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\PF_Readability $readability Readability object.
	 * @param \PressForward\Core\Utility\Retrieval     $retrieval   Retrieval object.
	 * @param \PressForward\Core\Schema\Feed_Items     $items       Feed_Items object.
	 */
	public function __construct( PF_Readability $readability, Retrieval $retrieval, Feed_Items $items ) {
		$this->readability = $readability;
		$this->retrieval   = $retrieval;
		$this->items       = $items;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_ajax_make_it_readable',
				'method' => 'make_it_readable',
			),
		);
	}

	/**
	 * AJAX handler for 'wp_ajax_make_it_readable'.
	 */
	public function make_it_readable() {
		$this->readability->make_it_readable();
		die();
	}
}
