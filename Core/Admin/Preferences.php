<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions as HasActions;

class Preferences implements HasActions {

	protected $basename;

	function __construct( $basename ){
		$this->basename = $basename;
		//return true;
	}


	public function add_plugin_admin_menu() {
		add_submenu_page(
			PF_MENU_SLUG,
			__('Preferences', 'pf'), // @todo sprintf
			__('Preferences', 'pf'),
			'edit_posts',
			PF_SLUG . '-options',
			array($this, 'display_options_builder')
		);
	}

	public function action_hooks() {
		return array(
			array(
				'hook' => 'admin_menu',
				'method' => 'add_plugin_admin_menu',
			),
		);
	}

	public function display_options_builder(){

	}

}

