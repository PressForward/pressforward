<?php
namespace PressForward\Core\Models;

class Nomination extends BasicModel {
	function init( $item = array(), $handlers = array(), $post_type = false ) {
		$this->type     = 'nomination';
		$this->type_key = 'nomination';
	}
}
