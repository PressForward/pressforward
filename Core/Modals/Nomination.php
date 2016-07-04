<?php
namespace PressForward\Core\Modals;

class Nomination extends BasicModal {
	function init( $item = array(), $handlers = array(), $post_type = false ){
		$this->type = 'nomination';
		$this->type_key = 'nomination';
	}
}
