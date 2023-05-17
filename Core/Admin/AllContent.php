<?php
/**
 * 'All Content' admin panel.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Controllers\PFtoWPUsers;

use PressForward\Core\Admin\PFTemplater as PFTemplater;
use PressForward\Core\Utility\Forward_Tools as Forward_Tools;
use PressForward\Core\Schema\Nominations as Nominations;
use PressForward\Controllers\Metas;

/**
 * 'All Content' admin panel.
 */
class AllContent implements HasActions {
	/**
	 * SystemUsers interface.
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
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 11,
			),
		);
	}

	/**
	 * Adds 'All Content' admin menu item.
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			PF_MENU_SLUG,
			__( 'All Content', 'pressforward' ),
			__( 'All Content', 'pressforward' ),
			get_option( 'pf_menu_all_content_access', $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ) ),
			PF_MENU_SLUG,
			array( $this, 'display_reader_builder' )
		);
	}

	/**
	 * Displays reader builder.
	 */
	public function display_reader_builder() {
		// phpcs:ignore
		// Provided in pressforward('admin.menu')->display_reader_builder().
	}
}
