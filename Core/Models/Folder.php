<?php
namespace PressForward\Core\Models;

class Folder extends BasicModel {
	protected $data = array();

	function init( $item = array(), $handlers = array(), $post_type = false ) {
		$this->type     = 'folder';
		$this->type_key = 'folder';
	}
}
