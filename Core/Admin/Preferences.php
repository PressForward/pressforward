<?php
namespace PressForward\Core\Admin;
use PressForward\Core\Admin\PFTemplater;

use Intraxia\Jaxion\Contract\Core\HasActions as HasActions;

class Preferences implements HasActions {

	protected $basename;

	function __construct( $basename, PFTemplater $templates ){
		$this->basename = $basename;
		$this->templates = $templates;
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
		if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'user';
		$vars = array(
				'current'		=> 	$tab,
				'user_ID'		=> 	true,
				'page_title'	=>	__('PressForward Preferences', 'pf'),
				'page_slug'		=>	'settings',
				'settings_tab_group'	=> $this->prefrences_tab($tab),
				'tabs'			=>	$this->tabs()
			);
		echo $this->templates->get_view(array('settings','settings-page'), $vars);

		return;
	}

	public function prefrences_tab($tab){
		return $this->templates->settings_tab_group($tab, 'settings');
	}

	public function tabs(){
		return $this->templates->permitted_tabs('settings');
	}

}

