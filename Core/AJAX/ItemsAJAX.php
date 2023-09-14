<?php
/**
 * AJAX handlers for Items actions.
 *
 * @package PressForward
 */

namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;

use WP_Ajax_Response;

/**
 * AJAX handlers for Items actions.
 */
class ItemsAJAX implements HasActions {
	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * PF_to_WP_Posts object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PF_to_WP_Posts
	 */
	public $posts;

	/**
	 * Feed_Items object.
	 *
	 * @access public
	 * @var \PressForward\Core\Schema\Feed_Items
	 */
	public $items;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas          $metas Metas object.
	 * @param \PressForward\Controllers\PF_to_WP_Posts $posts PF_to_WP_Posts object.
	 * @param \PressForward\Core\Schema\Feed_Items     $items Feed_Items object.
	 */
	public function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items ) {
		$this->metas = $metas;
		$this->posts = $posts;
		$this->items = $items;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_ajax_pf_ajax_move_to_archive',
				'method' => 'pf_ajax_move_to_archive',
			),
			array(
				'hook'   => 'wp_ajax_pf_ajax_move_out_of_archive',
				'method' => 'pf_ajax_move_out_of_archive',
			),
			array(
				'hook'   => 'wp_ajax_disassemble_item',
				'method' => 'trigger_item_disassembly',
			),
			array(
				'hook'   => 'wp_ajax_pf_ajax_thing_deleter',
				'method' => 'pf_ajax_thing_deleter',
			),
			array(
				'hook'   => 'wp_ajax_pf_ajax_get_comments',
				'method' => 'pf_ajax_get_comments',
			),
		);
	}

	/**
	 * AJAX handler for 'wp_ajax_pf_ajax_move_to_archive'.
	 */
	public function pf_ajax_move_to_archive() {
		$item_post_id = isset( $_POST['item_post_id'] ) ? intval( $_POST['item_post_id'] ) : 0;
		$nom_id       = isset( $_POST['nom_id'] ) ? intval( $_POST['nom_id'] ) : 0;
		$this->metas->update_pf_meta( $nom_id, 'pf_archive', 1 );
		$this->metas->update_pf_meta( $item_post_id, 'pf_archive', 1 );
		$check = $this->posts->update_post(
			array(
				'ID'          => $item_post_id,
				'post_status' => 'removed_feed_item',
			)
		);
		pf_log( $check );
		die();
	}

	/**
	 * AJAX handler for 'wp_ajax_pf_ajax_move_out_of_archive'.
	 */
	public function pf_ajax_move_out_of_archive() {
		$item_post_id = isset( $_POST['item_post_id'] ) ? intval( $_POST['item_post_id'] ) : 0;
		$nom_id       = isset( $_POST['nom_id'] ) ? intval( $_POST['nom_id'] ) : 0;
		$this->metas->update_pf_meta( $nom_id, 'pf_archive', 'false' );
		$this->metas->update_pf_meta( $item_post_id, 'pf_archive', 'false' );
		$check = $this->posts->update_post(
			array(
				'ID'          => $item_post_id,
				'post_status' => 'publish',
			)
		);
		pf_log( $check );
		die();
	}

	/**
	 * AJAX handler for 'wp_ajax_disassemble_item'.
	 */
	public function trigger_item_disassembly() {
		$this->items->disassemble_feed_items();
		$message = array(
			'action_taken' => 'Feed items being removed',
		);
		wp_send_json( $message );
	}

	/**
	 * AJAX handler for 'wp_ajax_pf_ajax_thing_deleter'.
	 */
	public function pf_ajax_thing_deleter() {
		ob_start();
		if ( isset( $_POST['post_id'] ) ) {
			$id = intval( $_POST['post_id'] );
		} else {
			pressforward( 'ajax.configuration' )->pf_bad_call( 'pf_ajax_thing_deleter', __( 'Option not sent', 'pressforward' ) );
			return;
		}

		if ( isset( $_POST['made_readable'] ) ) {
			$read_status = sanitize_text_field( wp_unslash( $_POST['made_readable'] ) );
		} else {
			$read_status = false;
		}

		$returned = pf_delete_item_tree( $id, true, true );
		$vd       = ob_get_clean();

		$response = array(
			'what'   => 'pressforward',
			'action' => 'pf_ajax_thing_deleter',
			'id'     => $id,
			'data'   => (string) $vd,
		);

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * AJAX handler for 'wp_ajax_pf_ajax_get_comments'.
	 */
	public function pf_ajax_get_comments() {
		if ( has_action( 'pf_modal_comments' ) ) {
			$id_for_comments = isset( $_POST['id_for_comments'] ) ? intval( $_POST['id_for_comments'] ) : 0;
			do_action( 'pf_modal_comments', $id_for_comments );
		}

		die();
	}
}
