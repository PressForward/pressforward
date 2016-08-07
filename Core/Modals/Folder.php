<?php
namespace PressForward\Core\Modals;

class Folder extends BasicModal {
	protected $data = array();

	function init( $item = array(), $handlers = array(), $post_type = false ){
		$this->type = 'folder';
		$this->type_key = 'folder';
	}
}
