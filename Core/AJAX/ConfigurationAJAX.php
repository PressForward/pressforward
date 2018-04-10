<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;
use PressForward\Interfaces\SystemUsers as SystemUsers;

use WP_Ajax_Response;

class ConfigurationAJAX implements HasActions {

	protected $basename;

	function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items, SystemUsers $user_interface ) {
		$this->metas          = $metas;
		$this->posts          = $posts;
		$this->items          = $items;
		$this->user_interface = $user_interface;
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
				'hook'   => 'wp_ajax_pf_checked_in',
				'method' => 'pf_checked_in',
			),
			array(
				'hook'   => 'wp_ajax_pf_metrics_prompt',
				'method' => 'pf_metrics_prompt',
			),
			array(
				'hook'     => 'wp_ajax_pf_metrics_quick_submit',
				'method'   => 'pf_metrics_quick_submit',
				'priority' => 11,
			),
			array(
				'hook'     => 'admin_head',
				'method'   => 'pf_metrics_settings_box',
				'priority' => 10,
			),
		);
	}

	public function pf_checked_in() {
		$metrics_config                     = get_option( 'pf_metrics_config', array() );
		$metrics_config['checkin_complete'] = true;
		update_option( 'pf_metrics_config', $metrics_config );
	}

	public function pf_metrics_prompt_text() {
		$debug_button = '';
		if ( WP_DEBUG ) {
			$debug_button = '<a class="debug button button-secondary" id="pf_metrics_debug">Debug Reset</a>';
		}
		$pf_metrics_opt_check = get_option( 'pf_metrics_config', array() );
		if ( empty( $pf_metrics_opt_check ) || ( 'no' === $pf_metrics_opt_check['checked'] ) || ( 'yes' === $pf_metrics_opt_check['basic'] && 'yes' === $pf_metrics_opt_check['detailed'] ) ) {
			$active = true;
		} else {
			$active = false;
		}
		foreach ( $pf_metrics_opt_check as $key => $val ) {
			if ( 'yes' === $val ) {
				$pf_metrics_opt_check[ $key ] = 'checked';
			} elseif ( $active ) {
				$pf_metrics_opt_check[ $key ] = 'checked';
			} else {
				$pf_metrics_opt_check[ $key ] = '';
			}
		}
		$basic = ( isset( $pf_metrics_opt_check['basic'] ) ? $pf_metrics_opt_check['basic'] : 'checked' );
		// $detailed = (isset($pf_metrics_opt_check["detailed"]) ? $pf_metrics_opt_check["detailed"] : 'checked' );
		$detailed = '';
		return <<<EOT
		<div id="pf_metrics_mouseover" class="ab-sub-wrapper"><h4 style="font-size:20px;">Please help us improve PressForward</h4><p style="display:none;">If you agree to allow us to collect anonymous data about how you use this plugin we can use that information to improve our next release. <input id="pf_metrics_drawer_detailed" type="checkbox" style="display:none;" {$detailed} /></p><p>Opt-in to allow us to collect non-sensitive diagnostic information for plugin improvement. <input id="pf_metrics_drawer_basic" type="checkbox" style="display:none;" {$basic} /></p><a class="submit button button-primary" id="pf_metrics_opt-in">Opt-In</a><a class="cancel button button-secondary" id="pf_metrics_dismiss">Dismiss Alert</a>{$debug_button}</div>
EOT;
	}

	public function pf_metrics_settings_box() {
		$metrics_config = get_option( 'pf_metrics_config', array() );
		if ( ! isset( $metrics_config['checked'] ) ) {
			$metrics_config['checked']  = false;
			$metrics_config['basic']    = false;
			$metrics_config['detailed'] = false;
		} else {
			if ( ! isset( $metrics_config['basic'] ) ) {
				$metrics_config['basic'] = false;
			}
			if ( ! isset( $metrics_config['detailed'] ) ) {
				$metrics_config['detailed'] = false;
			}
			if ( ! isset( $metrics_config['checked'] ) ) {
				$metrics_config['checked'] = false;
			}
			foreach ( $metrics_config as $key => $value ) {
				if ( 'yes' === $value || true === $value || 1 === $value || '1' === $value ) {
					$metrics_config[ $key ] = true;
				} else {
					$metrics_config[ $key ] = false;
				}
			}
		}
		if ( true !== $metrics_config['checked'] ) {
			$msg    = $this->pf_metrics_prompt_text();
			$script = <<<EOT
				jQuery( window ).load(function() {
					window.pf = window.pf || {};
					window.pf.loadAdminPrompt = true;
					if ( jQuery('.plugins-php').length > 0 ){
						window.pf.loadAdminPrompt = false;
						jQuery('#wp-admin-bar-pf_alerter').hide();
						var prompt = jQuery('<div id="pf_metrics_settings_alerter" class="update-message notice inline notice-warning notice-alt" style="padding-bottom:30px">{$msg}</div>');
						prompt.find('a.button').css({
							"margin":" 14px 20px 0 0"
						});
						jQuery('.subsubsub').first().before(prompt);
						window.hidePFPrompt = function(){
							window.setTimeout(
								function(){ jQuery('#pf_metrics_settings_alerter').hide(); return false; },
								1000
							);
							return false;
						}
					}
					console.log('PF Metrics Request');
					jQuery('#pf_metrics_opt-in').click(function(){
						var detailed = jQuery('#pf_metrics_drawer_detailed').attr('checked');
						var basic = jQuery('#pf_metrics_drawer_basic').attr('checked');
						jQuery.post(ajaxurl, {
							action: 'pf_metrics_quick_submit',
							pf_basic: ( typeof basic === 'undefined' ) ? 'no' : 'yes',
							pf_detailed: ( typeof detailed === 'undefined' ) ? 'no' : 'yes',
							pf_checked: 'yes'
						}, function(response) {
							alert('Thank you for helping the PressForward project.');
							hidePFPrompt();
						});
						return false;
					});
					jQuery('#pf_metrics_dismiss').click(function(){
						jQuery.post(ajaxurl, {
							action: 'pf_metrics_quick_submit',
							pf_basic: 'no',
							pf_detailed: 'no',
							pf_checked: 'yes'
						}, function(response) {
							hidePFPrompt();
						});
						return false;
					});
					jQuery('#pf_metrics_debug').click(function(){
						jQuery.post(ajaxurl, {
							action: 'pf_metrics_quick_submit',
							pf_basic: 'no',
							pf_detailed: 'no',
							pf_checked: 'no'
						}, function(response) {
							//a
						});
						window.setTimeout(
							function(){ location.reload(); return false; },
							3000
						);
						return false;
					});
		});
EOT;
			echo '<script type="text/javascript">' . $script . '</script>';
		}
	}

	public function pf_metrics_prompt() {
		$msg      = $this->pf_metrics_prompt_text();
		$script   = <<<EOT
				window.pf = window.pf || {};
				if ( ( typeof window.pf.loadAdminPrompt == 'undefined' ) || true === window.pf.loadAdminPrompt || jQuery('.plugins-php').length < 1 ){
					var prompt = jQuery('{$msg}');
					prompt.hide();
					prompt.css({
						"width": "350px",
						"height": "300px",
						"background": "rgb(35, 40, 45)",
						"margin-top": "-1px",
						"border-top": "0px",
						"padding": "20px",
						"box-sizing": "border-box",
						"position": "fixed",
						"line-height": "20px",
						"color": "white",
						"font-style": "none"
					});
					jQuery(prompt).find('p').css({
						"line-height": "20px",
						"padding":"12px 0"
					});
					jQuery(prompt).find('input[type=checkbox]').css({
						"height":"15px"
					});
					jQuery(prompt).find('a.button').css({
						"font-size": "13px",
						"line-height": "26px",
						"height": "28px",
						"margin": "29% 12px 0 0",
						"padding": "0 10px 1px",
						"cursor": "pointer",
						"border-width": "1px",
						"border-style": "solid",
						"display": "inline-block",
						"-webkit-appearance": "none",
						"-webkit-border-radius": "3px",
						"border-radius": "3px",
						"white-space": "nowrap",
						"-webkit-box-sizing": "border-box",
						"-moz-box-sizing": "border-box",
						"box-sizing": "border-box",
					});
					jQuery(prompt).find('a.button-primary').css({
						"background": "#0085ba",
						"border-color": "#0073aa #006799 #006799",
						"-webkit-box-shadow": "0 1px 0 #006799",
						"box-shadow": "0 1px 0 #006799",
						"color": "#fff",
						"text-decoration": "none",
						"text-shadow": "0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799",

					});
					jQuery(prompt).find('a.button-secondary').css({
						"color": "#555",
						"border-color": "#ccc",
						"background": "#f7f7f7",
						"-webkit-box-shadow": "0 1px 0 #ccc",
						"box-shadow": "0 1px 0 #ccc",
						"vertical-align": "top"
					});
					jQuery(prompt).find('a.debug').css({
						"background": "rgb(255, 208, 208)"
					});
					jQuery('#pf_metrics_alert').append(prompt);
					jQuery('#wp-admin-bar-pf_alerter').mouseover(function(){ prompt.show(); });
					jQuery('#wp-admin-bar-pf_alerter').mouseout(function(){ prompt.hide(); });
					window.hidePFPrompt = function(){
						window.setTimeout(
							function(){ jQuery('#wp-admin-bar-pf_alerter').hide(); return false; },
							1500
						);
						return false;
					}

					console.log('PF Metrics Request');
					jQuery('#pf_metrics_opt-in').click(function(){
						var detailed = jQuery('#pf_metrics_drawer_detailed').attr('checked');
						var basic = jQuery('#pf_metrics_drawer_basic').attr('checked');
						jQuery.post(ajaxurl, {
							action: 'pf_metrics_quick_submit',
							pf_basic: ( typeof basic === 'undefined' ) ? 'no' : 'yes',
							pf_detailed: ( typeof detailed === 'undefined' ) ? 'no' : 'yes',
							pf_checked: 'yes'
						}, function(response) {
							alert('Thank you for helping the PressForward project.');
							hidePFPrompt();
						});
						return false;
					});
					jQuery('#pf_metrics_dismiss').click(function(){
						jQuery.post(ajaxurl, {
							action: 'pf_metrics_quick_submit',
							pf_basic: 'no',
							pf_detailed: 'no',
							pf_checked: 'yes'
						}, function(response) {
							hidePFPrompt();
						});
						return false;
					});
					jQuery('#pf_metrics_debug').click(function(){
						jQuery.post(ajaxurl, {
							action: 'pf_metrics_quick_submit',
							pf_basic: 'no',
							pf_detailed: 'no',
							pf_checked: 'no'
						}, function(response) {
							//a
						});
						window.setTimeout(
							function(){ location.reload(); return false; },
							3000
						);
						return false;
					});
				}
EOT;
		$response = array(
			'what'         => 'pressforward',
			'action'       => 'pf_metrics_prompt',
			'id'           => pressforward( 'controller.template_factory' )->user_id(),
			'data'         => '<script>' . $script . '</script>',
			'supplemental' => array(
				// 'buffered' => ob_get_contents(),
				'timestamp' => current_time( 'Y-m-d H:i:s' ),
			),
		);
		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		// ob_end_clean();
		die();
	}

	public function pf_metrics_quick_submit() {
		ob_start();
		$metrics_settings = array(
			'basic'    => 'no',
			'detailed' => 'no',
			'checked'  => 'yes',
		);
		foreach ( $_POST as $key => $val ) {
			$key                      = str_replace( 'pf_', '', $key );
			$metrics_settings[ $key ] = $val;
		}
		$valid_capability = get_option( 'pf_menu_preferences_access', $this->user_interface->pf_get_defining_capability_by_role( 'administrator' ) );
		if ( ! current_user_can( $valid_capability ) ) {
			$result = 'user cannot update';
		} else {
			$result = update_option( 'pf_metrics_config', $metrics_settings );
		}
		// var_dump($user_id);
		$response    = array(
			'what'   => 'pressforward',
			'action' => 'pf_metrics_quick_submit',
			'id'     => pressforward( 'controller.template_factory' )->user_id(),
			'data'   => (string) $result,
		);
		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		ob_end_clean();
		die();
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
			$read_state = $_POST['pf_read_state'];
		} else {
			$read_status = false;
		}
		$userObj  = wp_get_current_user();
		$user_id  = $userObj->ID;
		$returned = $this->pf_switch_display_setting( $user_id, $read_state );
		// var_dump($user_id);
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
			$setting_name = $_POST['pf_user_setting'];
		} else {
			$setting_name = false;
			$this->pf_bad_call( 'pf_ajax_user_setting', 'No setting name, try again.' );
		}
		if ( isset( $_POST['setting'] ) ) {
			$setting = $_POST['setting'];
		} else {
			$setting = false;
		}

		$user_id  = pressforward( 'controller.template_factory' )->user_id();
		$returned = $this->pf_switch_user_option( $user_id, $setting_name, $setting );
		// var_dump($user_id);
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

}
