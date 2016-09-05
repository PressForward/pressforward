<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;

use WP_Ajax_Response;

class ConfigurationAJAX implements HasActions {

	protected $basename;

	function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items ){
		$this->metas = $metas;
		$this->posts = $posts;
		$this->items = $items;
				add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed') );
	}


	public function action_hooks() {
		return array(
			array(
				'hook' => 'wp_ajax_pf_ajax_retain_display_setting',
				'method' => 'pf_ajax_retain_display_setting',
			),
			array(
				'hook' => 'wp_ajax_pf_ajax_user_setting',
				'method' => 'pf_ajax_user_setting',
			),
		);
	}

	function pf_bad_call($action, $msg = 'You made a bad call and it did not work. Try again.'){
		$response = array(
			'what'=>'pressforward',
			'action'=>$action,
			'id'=>pressforward('controller.template_factory')->user_id(),
			'data'=>$msg,
			'supplemental' => array(
					'buffered' => ob_get_contents(),
					'timestamp' => current_time('Y-m-d H:i:s')
			)
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();
	}

	function pf_ajax_retain_display_setting() {
		ob_start();
		if(isset($_POST['pf_read_state'])){
			$read_state = $_POST['pf_read_state'];
		} else {
			$read_status = false;
		}
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		$returned = $this->pf_switch_display_setting($user_id, $read_state);
		#var_dump($user_id);

		$response = array(
			'what'=>'pressforward',
			'action'=>'pf_ajax_retain_display_setting',
			'id'=>$user_id,
			'data'=>(string) $returned
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();

	}

	function pf_ajax_user_setting() {
		ob_start();
		if(isset($_POST['pf_user_setting'])){
			$setting_name = $_POST['pf_user_setting'];
		} else {
			$setting_name = false;
			$this->pf_bad_call('pf_ajax_user_setting', 'No setting name, try again.');
		}
		if(isset($_POST['setting'])){
			$setting = $_POST['setting'];
		} else {
			$setting = false;
		}

		$user_id = pressforward('controller.template_factory')->user_id();
		$returned = $this->pf_switch_user_option($user_id, $setting_name, $setting);
		#var_dump($user_id);

		$response = array(
			'what'=>'pressforward',
			'action'=>'pf_ajax_user_setting',
			'id'=>$user_id,
			'data'=>(string) $returned,
			'supplemental' => array(
					'buffered' => ob_get_contents(),
					'setting' => $setting_name,
					'set'		=> $setting
			)
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();

	}


	public function pf_switch_display_setting($user_id, $read_state){
		if ( !current_user_can( 'edit_user', $user_id ) ){
			return false;
		}

		$check = update_user_meta($user_id, 'pf_user_read_state', $read_state);
		return $check;
	}


	function pf_switch_user_option($user_id, $option, $state){
		if ( !current_user_can( 'edit_user', $user_id ) ){
			return false;
		}

		$check = update_user_option($user_id, $option, $state);
		return $check;
	}

	public function reset_feed() {
		pressforward('schema.feed_item')->reset_feed();
		die();
	}

}
