<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;

class ItemsAJAX implements HasActions {

	protected $basename;

	function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items ){
		$this->metas = $metas;
		$this->posts = $posts;
		$this->items = $items;

	}


	public function action_hooks() {
		return array(
			array(
				'hook' => 'wp_ajax_pf_ajax_move_to_archive',
				'method' => 'pf_ajax_move_to_archive',
			),
			array(
				'hook' => 'wp_ajax_pf_ajax_move_out_of_archive',
				'method' => 'pf_ajax_move_out_of_archive',
			),
			array(
				'hook' => 'wp_ajax_disassemble_item',
				'method' => 'trigger_item_disassembly',
			),
		);
	}

	public function pf_ajax_move_to_archive(){
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		$this->metas->update_pf_meta($nom_id, 'pf_archive', 1);
		$this->metas->update_pf_meta($item_post_id, 'pf_archive', 1);
		$check = $this->posts->update_post( array(
					'ID'			=>	$item_post_id,
					'post_status'	=>	'removed_feed_item'
				)
			);
		pf_log($check);
		die();
	}

	public function pf_ajax_move_out_of_archive(){
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		$this->metas->update_pf_meta($nom_id, 'pf_archive', 'false');
		$this->metas->update_pf_meta($item_post_id, 'pf_archive', 'false');
		$check = $this->posts->update_post( array(
					'ID'			=>	$item_post_id,
					'post_status'	=>	'publish'
				)
			);
		pf_log($check);
		die();
	}

	public function trigger_item_disassembly() {
		$this->items->disassemble_feed_items();
		$message = array(
			'action_taken'	=>	'Feed items being removed'
		);
		wp_send_json($message);
		die();
	}


}
