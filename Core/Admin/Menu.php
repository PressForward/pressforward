<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;

class Menu implements HasActions {

	protected $basename;

	function __construct( $basename ){
		$this->basename = $basename;

	}


	public function add_plugin_admin_menu() {
		remove_menu_page('edit.php?post_type=pf_feed_item');

		// Top-level menu page
		add_menu_page(
			PF_TITLE, // <title>
			PF_TITLE, // menu title
			'edit_posts', // cap required
			PF_MENU_SLUG, // slug
			array( $this, 'display_reader_builder' ), // callback
			PF_URL . 'pressforward-16.png', // icon URL
			24 // Position (just above comments - 25)
		);
	}

	public function display_reader_builder(){
		echo 'In Progress';
	}

	public function action_hooks() {
		return array(
			array(
				'hook' => 'admin_menu',
				'method' => 'add_plugin_admin_menu',
			),
		);
	}

}

