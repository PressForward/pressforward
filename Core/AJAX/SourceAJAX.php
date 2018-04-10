<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\PF_Readability;
use PressForward\Core\Utility\Retrieval;
use PressForward\Core\Schema\Feed_Items;

class SourceAJAX implements HasActions {

	protected $basename;

	function __construct( PF_Readability $readability, Retrieval $retrieval, Feed_Items $items ) {
		$this->readability = $readability;
		$this->retrieval   = $retrieval;
		$this->items       = $items;

	}


	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_ajax_make_it_readable',
				'method' => 'make_it_readable',
			),
			array(
				'hook'   => 'wp_ajax_assemble_feed_for_pull',
				'method' => 'trigger_source_data',
			),
		);
	}


	public function make_it_readable() {
		$this->readability->make_it_readable();
		die();
	}

	public function trigger_source_data() {
		ob_start();
		$message           = $this->retrieval->trigger_source_data( true );
		$buffer            = ob_get_contents();
		$message['buffer'] = (string) $buffer;
		wp_send_json( $message );
		ob_end_clean();
		die();
	}


}
