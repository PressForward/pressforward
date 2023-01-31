<?php
/**
 * Feed model.
 *
 * @package PressForward
 */

namespace PressForward\Core\Models;

/**
 * Feed class.
 */
class Feed extends BasicModel {
	/**
	 * Data array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $data = array();

	/**
	 * Initalizes model.
	 *
	 * @param array $item      Data about item.
	 * @param array $handlers  Controllers.
	 * @param array $post_type Post type name.
	 */
	public function init( $item = array(), $handlers = array(), $post_type = false ) {
		$this->type     = 'feed';
		$this->type_key = 'feed';
	}
}
