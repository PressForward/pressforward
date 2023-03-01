<?php
/**
 * Folder model.
 *
 * @package PressForward
 */

namespace PressForward\Core\Models;

/**
 * Feed_Item class.
 */
class Folder extends BasicModel {
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
	 * @param array  $item      Data about item.
	 * @param array  $handlers  Controllers.
	 * @param string $post_type Post type name.
	 */
	public function init( $item = array(), $handlers = array(), $post_type = '' ) {
		$this->type     = 'folder';
		$this->type_key = 'folder';
	}
}
