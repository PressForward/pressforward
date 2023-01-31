<?php
/**
 * Feed item model.
 *
 * @package PressForward
 */

namespace PressForward\Core\Models;

/**
 * Feed_Item class.
 */
class Nomination extends BasicModel {
	/**
	 * Initalizes model.
	 *
	 * @param array $item      Data about item.
	 * @param array $handlers  Controllers.
	 * @param array $post_type Post type name.
	 */
	public function init( $item = array(), $handlers = array(), $post_type = false ) {
		$this->type     = 'nomination';
		$this->type_key = 'nomination';
	}
}
