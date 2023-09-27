<?php
/**
 * AJAX callbacks for configuration panels.
 *
 * @package PressForward
 */

namespace PressForward\Core\AJAX;

use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;
use PressForward\Interfaces\SystemUsers;
use PressForward\Controllers\PF_JWT;

use WP_Ajax_Response;

/**
 * AJAX callbacks for configuration panels.
 */
class ConfigurationAJAX implements \Intraxia\Jaxion\Contract\Core\HasActions {

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
	 * SystemUsers object.
	 *
	 * @access public
	 * @var \PressForward\Interfaces\SystemUsers
	 */
	public $user_interface;

	/**
	 * PF_JWT object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PF_JWT
	 */
	public $pf_jwt;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas          $metas          Metas object.
	 * @param \PressForward\Controllers\PF_to_WP_Posts $posts          PF_to_WP_Posts object.
	 * @param \PressForward\Core\Schema\Feed_Items     $items          Feed_Items object.
	 * @param \PressForward\Interfaces\SystemUsers     $user_interface SystemUsers object.
	 * @param \PressForward\Controllers\PF_JWT         $pf_jwt         PF_JWT object.
	 */
	public function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items, SystemUsers $user_interface, PF_JWT $pf_jwt ) {
		$this->metas          = $metas;
		$this->posts          = $posts;
		$this->items          = $items;
		$this->user_interface = $user_interface;
		$this->pf_jwt         = $pf_jwt;

		add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed' ) );
	}

	/**
	 * {@inheritdoc}
	 */
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

	/**
	 * Error-generating callback for AJAX calls.
	 *
	 * @param string $action Action string.
	 * @param string $msg    Message.
	 */
	public function pf_bad_call( $action, $msg = 'You made a bad call and it did not work. Try again.' ) {
		$response = array(
			'what'         => 'pressforward',
			'action'       => $action,
			'id'           => pressforward( 'controller.template_factory' )->user_id(),
			'data'         => $msg,
			'supplemental' => array(
				'buffered'  => ob_get_contents(),
				'timestamp' => current_time( 'Y-m-d H:i:s' ),
			),
		);

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * AJAX callback for 'wp_ajax_pf_ajax_retain_display_setting'.
	 */
	public function pf_ajax_retain_display_setting() {
		ob_start();
		if ( isset( $_POST['pf_read_state'] ) ) {
			$read_state = sanitize_text_field( wp_unslash( $_POST['pf_read_state'] ) );
		} else {
			$read_state = false;
		}

		$user_obj = wp_get_current_user();
		$user_id  = $user_obj->ID;
		$returned = $this->pf_switch_display_setting( $user_id, $read_state );
		$response = array(
			'what'   => 'pressforward',
			'action' => 'pf_ajax_retain_display_setting',
			'id'     => $user_id,
			'data'   => (string) $returned,
		);

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * AJAX callback for 'wp_ajax_pf_ajax_user_setting'.
	 */
	public function pf_ajax_user_setting() {
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

		$user_id      = pressforward( 'controller.template_factory' )->user_id();
		$returned     = $this->pf_switch_user_option( $user_id, $setting_name, $setting );
		$response     = array(
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
		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * Changes a user's display setting.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $read_state Read state.
	 */
	public function pf_switch_display_setting( $user_id, $read_state ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$check = update_user_meta( $user_id, 'pf_user_read_state', $read_state );
		return $check;
	}

	/**
	 * Changes a user option.
	 *
	 * @param int    $user_id User ID.
	 * @param string $option  Option name.
	 * @param mixed  $state   New value.
	 */
	public function pf_switch_user_option( $user_id, $option, $state ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$check = update_user_option( $user_id, $option, $state );
		return $check;
	}

	/**
	 * AJAX callback for 'wp_ajax_reset_feed'.
	 */
	public function reset_feed() {
		pressforward( 'schema.feed_item' )->reset_feed();
		die();
	}

	/**
	 * AJAX callback for 'wp_ajax_regenerate_user_keys'.
	 */
	public function regenerate_user_keys() {
		ob_start();
		$the_user         = $this->user_interface->get_current_user();
		$user_public_key  = \bin2hex( $this->pf_jwt->get_a_user_public_key( $the_user->ID, true ) );
		$user_private_key = $this->pf_jwt->get_a_user_private_key( $the_user->ID, true );
		$response         = array(
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

		ob_end_clean();

		wp_send_json(
			array(
				'ku' => $user_public_key,
				'ki' => $user_private_key,
			)
		);
	}
}
