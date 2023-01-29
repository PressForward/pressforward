<?php
/**
 * Folders admin panel.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Interfaces\SystemUsers;

use PressForward\Core\Admin\PFTemplater as PFTemplater;
use PressForward\Core\Utility\Forward_Tools as Forward_Tools;
use PressForward\Core\Schema\Nominations as Nominations;
use PressForward\Controllers\Metas;

/**
 * Folders admin panel.
 */
class FoldersMenu implements HasActions {
	/**
	 * SystemUsers interface.
	 *
	 * @access public
	 * @var PressForward\Interfaces\SystemUsers
	 */
	public $user_interface;

	/**
	 * Constructor.
	 *
	 * @param PressForward\Interfaces\SystemUsers $user_interface SystemUsers object.
	 */
	public function __construct( SystemUsers $user_interface ) {
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
			__( 'Folders', 'pf' ),
			__( 'Folders', 'pf' ),
			get_option( 'pf_menu_feeder_access', $this->user_interface->pf_get_defining_capability_by_role( 'editor' ) ),
			'edit-tags.php?taxonomy=' . pressforward( 'schema.folders' )->tag_taxonomy,
			''
		);
	}
}
