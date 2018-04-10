<?php
namespace PressForward\Core\Models;

class Feed extends BasicModel {
	protected $data = array();

	function init( $item = array(), $handlers = array(), $post_type = false ) {
		$this->type     = 'feed';
		$this->type_key = 'feed';
	}
}
