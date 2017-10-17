<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;

use WP_Ajax_Response;

class ItemsAJAX implements HasActions {

	protected $basename;

	function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items ) {
		$this->metas = $metas;
		$this->posts = $posts;
		$this->items = $items;

	}

	public function action_hooks() {
		return array(
			array(
				'hook' => 'wp_ajax_pf_meta_edit',
				'method' => 'meta_edit',
			),
		);
	}

	public function meta_edit(){
		check_ajax_referer('pf_metas_nonce', 'nonce');
		$post_id = $_POST['item_post_id'];
		$metas = $_POST['pf_metas'];
	}

	public function meta_editor(){
		$nonce = wp_create_nonce('pf_metas_nonce');
		//Form JS argument;
		$data = 'pf.meta_edit = {';
		$data .= '"nonce": "'.$nonce.'"';
	}

}
