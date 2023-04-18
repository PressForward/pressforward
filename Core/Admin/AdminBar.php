<?php
/**
 * Admin bar setup.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\PFtoWPUsers;

/**
 * Admin bar setup.
 */
class AdminBar implements HasActions, HasFilters {
	/**
	 * PFtoWPUsers object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PFtoWPUsers
	 */
	public $user_interface;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\PFtoWPUsers $user_interface PFtoWPUsers object.
	 */
	public function __construct( PFtoWPUsers $user_interface ) {
		$this->user_interface = $user_interface;
	}

	/**
	 * Sets up action hooks for this class.
	 *
	 * @return array
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_bar_menu',
				'method'   => 'add_plugin_admin_bar_menu',
				'priority' => 85,
			),
		);
	}

	/**
	 * Sets up filter hooks for this class.
	 *
	 * @return array
	 */
	public function filter_hooks() {
		return array();
	}

	/**
	 * Adds admin bar items.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 * @return \WP_Admin_Bar
	 */
	public function add_plugin_admin_bar_menu( $wp_admin_bar ) {
		$valid_capability = get_option( 'pf_menu_preferences_access', $this->user_interface->pf_get_defining_capability_by_role( 'administrator' ) );
		$metrics_config   = get_option( 'pf_metrics_config', array() );
		if ( ( current_user_can( $valid_capability ) ) && ( ! is_array( $metrics_config ) || WP_DEBUG || ! array_key_exists( 'checked', $metrics_config ) || 'yes' !== $metrics_config['checked'] ) ) {
			$alert_count = '!';
			if ( WP_DEBUG && array_key_exists( 'checked', $metrics_config ) && 'yes' === $metrics_config['checked'] ) {
				$alert = '';
			} else {
				$alert = '<span style="display: inline-block; background-color: #d54e21; color: #fff; box-sizing: border-box; font-size: 9px; line-height: 10px; font-weight: 600;  margin: 1px 0 0 6px; vertical-align: top; border: 4px solid #23282D; z-index: 26;"><span style="display: block; padding: 0 11px; margin: -4px;">' . $alert_count . '</span></span>';
			}
			$title = '<div id="pf_metrics_alert" style="display: block; padding-top: 2px; float: left; margin-right:6px;"><img src="' . PF_URL . 'pressforward-16.png" alt="PressForward"></div> <div style="display: block; float: right;"><span>PF</span>' . $alert . '</div>';
			$url   = admin_url( 'admin.php?page=pf-options#top#site' );
			$wp_admin_bar->add_menu(
				array(
					'id'    => 'pf_alerter',
					'title' => $title,
					'href'  => $url,
					'meta'  => array(
						'title' => __( 'PressForward Alert', 'pressforward' ),
					),
				)
			);
		}
		return $wp_admin_bar;
	}
}
