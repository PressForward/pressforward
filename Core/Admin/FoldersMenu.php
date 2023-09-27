<?php
/**
 * Folders admin panel.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use PressForward\Controllers\PFtoWPUsers;

/**
 * Folders admin panel.
 */
class FoldersMenu implements \Intraxia\Jaxion\Contract\Core\HasActions {
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
				'priority' => 17,
			),
		);
	}

	/**
	 * Adds 'Folders' admin panel.
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Folders', 'pressforward' ),
			__( 'Folders', 'pressforward' ),
			get_option( 'pf_menu_feeder_access', $this->user_interface->pf_get_defining_capability_by_role( 'editor' ) ),
			'edit-tags.php?taxonomy=' . pressforward( 'schema.folders' )->tag_taxonomy
		);
	}
}
