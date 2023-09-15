<?php
/**
 * Add Feeds admin panel.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\PFtoWPUsers;

/**
 * Add Feeds admin panel.
 */
class AddFeeds implements HasActions, HasFilters {
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
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 13,
			),
		);
	}

	/**
	 * Sets up filter hooks for this class.
	 *
	 * @return array
	 */
	public function filter_hooks() {
		return array(
			array(
				'hook'     => 'pf_tabs_pf-add-feeds',
				'method'   => 'set_permitted_tools_tabs',
				'priority' => 1,
				'args'     => 1,
			),
			array(
				'hook'     => 'pf_tabs_pf-add-feeds',
				'method'   => 'set_permitted_tools_tabs_alerts',
				'priority' => 200,
				'args'     => 1,
			),
		);
	}

	/**
	 * Adds 'Add Feeds' admin panel.
	 *
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		// Feed-listing page is accessible only to Editors and above.
		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Add Feeds', 'pressforward' ),
			__( 'Add Feeds', 'pressforward' ),
			get_option( 'pf_menu_feeder_access', $this->user_interface->pf_get_defining_capability_by_role( 'editor' ) ),
			PF_SLUG . '-feeder',
			array( $this, 'display_feeder_builder' )
		);
	}

	/**
	 * Callback for the display of the 'Add Feeds' panel.
	 *
	 * @return string
	 */
	public function display_feeder_builder() {
		wp_enqueue_style( 'pf-style' );
		wp_enqueue_style( 'pf-settings-style' );

		wp_enqueue_script( 'pf-settings-tools' );

		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		} else {
			$tab = 'primary_feed_type';
		}

		$user_ID   = get_current_user_id();
		$form_head = '<form method="post" action="options.php" enctype="multipart/form-data">';
		$vars      = array(
			'current'            => $tab,
			'user_ID'            => $user_ID,
			'page_title'         => __( 'PressForward: Add Feeds', 'pressforward' ),
			'page_slug'          => 'pf-add-feeds',
			'no_save_button'     => true,
			'form_head'          => $form_head,
			'settings_tab_group' => pressforward( 'admin.tools' )->tab_group( $tab ),
			'settings_field'     => PF_SLUG . '_feedlist_group',
		);
		pressforward( 'admin.templates' )->the_view_for( pressforward( 'controller.template_factory' )->build_path( array( 'settings', 'settings-page' ), false ), $vars );

		return '';
	}

	/**
	 * Adds tabs for 'Subscribe to Feeds' section.
	 *
	 * @param array $permitted_tabs Tab definitions.
	 * @return array()
	 */
	public function set_permitted_tools_tabs( $permitted_tabs ) {
		$permitted_tabs['primary_feed_type'] = array(
			'title' => __( 'Subscribe to Feeds', 'pressforward' ),
			'cap'   => $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ),
		);
		return $permitted_tabs;
	}

	/**
	 * Adds tabs for 'Alerts' section.
	 *
	 * @param array $permitted_tabs Tab definitions.
	 * @return array()
	 */
	public function set_permitted_tools_tabs_alerts( $permitted_tabs ) {
		$permitted_tabs['alerts'] = array(
			'title' => __( 'Alerts', 'pressforward' ),
			'cap'   => $this->user_interface->pf_get_defining_capability_by_role( 'administrator' ),
		);
		return $permitted_tabs;
	}
}
