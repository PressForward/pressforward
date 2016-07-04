<?php
namespace PressForward\Core\Modals;

class Feed extends BasicModal {
	protected $data = array();

	function init( $item = array(), $handlers = array(), $post_type = false ){
		$this->type = 'feed';
		$this->type_key = 'feed';
	}
}
