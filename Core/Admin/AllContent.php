<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Interfaces\SystemUsers;

use PressForward\Core\Admin\PFTemplater as PFTemplater;
use PressForward\Core\Utility\Forward_Tools as Forward_Tools;
use PressForward\Core\Schema\Nominations as Nominations;
use PressForward\Controllers\Metas;

class AllContent implements HasActions {

	function __construct( SystemUsers $user_interface ) {
		$this->user_interface = $user_interface;
	}

	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 11,
			),
		);
	}


	public function add_plugin_admin_menu() {

		add_submenu_page(
			PF_MENU_SLUG,
			__( 'All Content', 'pf' ),
			__( 'All Content', 'pf' ),
			get_option( 'pf_menu_all_content_access', $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ) ),
			PF_MENU_SLUG,
			array( $this, 'display_reader_builder' )
		);

	}

	public function display_reader_builder() {
		// Provided in pressforward('admin.menu')->display_reader_builder();
	}

}
