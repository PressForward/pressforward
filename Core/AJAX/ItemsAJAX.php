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
			array(
				'hook' 		=> 'wp_ajax_pf_ajax_thing_deleter',
				'method'	=> 'pf_ajax_thing_deleter',
			),
			array(
				'hook'		=> 'wp_ajax_pf_ajax_get_comments',
				'method'	=> 'pf_ajax_get_comments',
			),
		);
	}

	public function pf_ajax_move_to_archive() {
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		$this->metas->update_pf_meta( $nom_id, 'pf_archive', 1 );
		$this->metas->update_pf_meta( $item_post_id, 'pf_archive', 1 );
		$check = $this->posts->update_post( array(
			'ID'			=> $item_post_id,
			'post_status'	=> 'removed_feed_item',
			)
		);
		pf_log( $check );
		die();
	}

	public function pf_ajax_move_out_of_archive() {
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		$this->metas->update_pf_meta( $nom_id, 'pf_archive', 'false' );
		$this->metas->update_pf_meta( $item_post_id, 'pf_archive', 'false' );
		$check = $this->posts->update_post( array(
			'ID'			=> $item_post_id,
			'post_status'	=> 'publish',
			)
		);
		pf_log( $check );
		die();
	}

	public function trigger_item_disassembly() {
		$this->items->disassemble_feed_items();
		$message = array(
			'action_taken'	=> 'Feed items being removed',
		);
		wp_send_json( $message );
		die();
	}

	function pf_ajax_thing_deleter() {
		ob_start();
		if ( isset( $_POST['post_id'] ) ) {
			$id = $_POST['post_id'];
		} else {
			pressforward( 'ajax.configuration' )->pf_bad_call( 'pf_ajax_thing_deleter','Option not sent' );
		}
		if ( isset( $_POST['made_readable'] ) ) {
			$read_status = $_POST['made_readable'];
		} else { $read_status = false; }
		$returned = pf_delete_item_tree( $id, true, true );
		//var_dump( $returned );
		$vd = ob_get_clean();

		$response = array(
		   'what' => 'pressforward',
		   'action' => 'pf_ajax_thing_deleter',
		   'id' => $id,
		   'data' => (string) $vd,
		);
		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		ob_end_clean();
		die();

	}

	function pf_ajax_get_comments() {
		if ( has_action( 'pf_modal_comments' ) ) {
			$id_for_comments = $_POST['id_for_comments'];
			do_action( 'pf_modal_comments', $id_for_comments );
		}
			die();
	}


}
