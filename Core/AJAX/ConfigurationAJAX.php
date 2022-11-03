<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;
use PressForward\Interfaces\SystemUsers as SystemUsers;
use PressForward\Controllers\PF_JWT as PF_JWT;

use WP_Ajax_Response;

class ConfigurationAJAX implements HasActions {

	protected $basename;

	function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items, SystemUsers $user_interface, PF_JWT $pf_jwt ) {
		$this->metas          = $metas;
		$this->posts          = $posts;
		$this->items          = $items;
		$this->user_interface = $user_interface;
		$this->pf_jwt         = $pf_jwt;
				add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed' ) );
	}


	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_ajax_pf_ajax_retain_display_setting',
				'method' => 'pf_ajax_retain_display_setting',
			),
			array(
				'hook'   => 'wp_ajax_pf_ajax_user_setting',
				'method' => 'pf_ajax_user_setting',
			),
			array(
				'hook'     => 'wp_ajax_regenerate_user_keys',
				'method'   => 'regenerate_user_keys',
				'priority' => 10,
			),
		);
	}

	function pf_bad_call( $action, $msg = 'You made a bad call and it did not work. Try again.' ) {
		$response    = array(
			'what'         => 'pressforward',
			'action'       => $action,
			'id'           => pressforward( 'controller.template_factory' )->user_id(),
			'data'         => $msg,
			'supplemental' => array(
				'buffered'  => ob_get_contents(),
				'timestamp' => current_time( 'Y-m-d H:i:s' ),
			),
		);
		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		ob_end_clean();
		die();
	}

	function pf_ajax_retain_display_setting() {
		ob_start();
		if ( isset( $_POST['pf_read_state'] ) ) {
			$read_state = sanitize_text_field( wp_unslash( $_POST['pf_read_state'] ) );
		} else {
			$read_status = false;
		}
		$userObj  = wp_get_current_user();
		$user_id  = $userObj->ID;
		$returned = $this->pf_switch_display_setting( $user_id, $read_state );
		$response    = array(
			'what'   => 'pressforward',
			'action' => 'pf_ajax_retain_display_setting',
			'id'     => $user_id,
			'data'   => (string) $returned,
		);
		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		ob_end_clean();
		die();

	}

	function pf_ajax_user_setting() {
		ob_start();
		if ( isset( $_POST['pf_user_setting'] ) ) {
			$setting_name = sanitize_text_field( wp_unslash( $_POST['pf_user_setting'] ) );
		} else {
			$setting_name = false;
			$this->pf_bad_call( 'pf_ajax_user_setting', 'No setting name, try again.' );
		}
		if ( isset( $_POST['setting'] ) ) {
			$setting = sanitize_text_field( wp_unslash( $_POST['setting'] ) );
		} else {
			$setting = false;
		}

		$user_id  = pressforward( 'controller.template_factory' )->user_id();
		$returned = $this->pf_switch_user_option( $user_id, $setting_name, $setting );
		$response    = array(
			'what'         => 'pressforward',
			'action'       => 'pf_ajax_user_setting',
			'id'           => $user_id,
			'data'         => (string) $returned,
			'supplemental' => array(
				'buffered' => ob_get_contents(),
				'setting'  => $setting_name,
				'set'      => $setting,
			),
		);
		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		ob_end_clean();
		die();

	}


	public function pf_switch_display_setting( $user_id, $read_state ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$check = update_user_meta( $user_id, 'pf_user_read_state', $read_state );
		return $check;
	}


	function pf_switch_user_option( $user_id, $option, $state ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$check = update_user_option( $user_id, $option, $state );
		return $check;
	}

	public function reset_feed() {
		pressforward( 'schema.feed_item' )->reset_feed();
		die();
	}

	public function regenerate_user_keys() {
		ob_start();
		$user_public_key = \bin2hex($this->pf_jwt->get_a_user_public_key( false, true ));
		$user_private_key = $this->pf_jwt->get_a_user_private_key( false, true );
		$the_user = $this->user_interface->get_current_user();
		$response    = array(
			'what'         => 'pressforward',
			'action'       => 'pf_ajax_regenerate_user_keys',
			'id'           => $the_user->ID,
			'data'         => (string) '{ ku: "' . $user_public_key . '", ki: "' . $user_private_key . '" }',
			'supplemental' => array(
				'buffered' => ob_get_contents(),
				'setting'  => array( 'public_key', 'private_key' ),
				'keygen'   => 'jwt',
			),
		);
		wp_send_json( array( 'ku' => $user_public_key, 'ki' => $user_private_key ) );
		// $xmlResponse = new WP_Ajax_Response( $response );
		// $xmlResponse->send();
		ob_end_clean();
		die();
	}

}
