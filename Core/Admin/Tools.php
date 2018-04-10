<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use PressForward\Core\Admin\PFTemplater;

class Tools implements HasActions, HasFilters {

	protected $basename;

	function __construct( $basename, PFTemplater $templates ) {
		$this->basename  = $basename;
		$this->templates = $templates;
		add_filter( 'pf_tabs_pf-tools', array( $this, 'set_permitted_tools_tabs' ) );
		// return true;
	}

	public function add_plugin_admin_menu() {
		$this->templates->add_submenu_page(
			PF_MENU_SLUG,
			__( 'Tools', 'pf' ),
			__( 'Tools', 'pf' ),
			array( 'pf_menu_tools_access', 'contributor' ),
			PF_SLUG . '-tools',
			array( $this, 'display_tools_builder' )
		);
	}

	public function filter_hooks() {
		return array(
			array(
				'hook'     => 'pf_tabs_pf-tools',
				'method'   => 'set_permitted_tools_tabs',
				'priority' => 10,
				'args'     => 1,
			),
		);
	}

	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 16,
			),
			array(
				'hook'     => 'admin_init',
				'method'   => 'nom_this_endpoint',
				'priority' => 1,
			),
		);
	}

	public function display_tools_builder() {
		if ( isset( $_GET['tab'] ) ) {
			$tab = $_GET['tab'];
		} else {
			$tab = 'nominate-this'; }
		$vars = array(
			'current'            => $tab,
			'user_ID'            => true,
			'page_title'         => __( 'PressForward Tools', 'pf' ),
			'page_slug'          => 'pf-tools',
			'no_save_button'     => true,
			'settings_tab_group' => '',
			'tabs'               => $this->tabs(),
		);
		echo $this->templates->get_view( array( 'settings', 'settings-page' ), $vars );

		return;
	}

	public function set_permitted_tools_tabs( $permitted_tabs ) {
		$permitted_tabs['nominate-this']    = array(
			'title' => __( 'Bookmarklet', 'pf' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'contributor' ),
		);
		$permitted_tabs['reset-refresh']    = array(
			'title' => __( 'Debug and Refresh', 'pf' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'administrator' ),
		);
		$permitted_tabs['retrieval-status'] = array(
			'title' => __( 'Retrieval Status', 'pf' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'contributor' ),
		);
		$permitted_tabs['stats']            = array(
			'title' => __( 'Statistics', 'pf' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'contributor' ),
		);
		return $permitted_tabs;
	}

	public function tab_group( $tab ) {
		return $this->templates->settings_tab_group( $tab, 'pf-tools' );
	}

	public function tabs() {
		return $this->templates->permitted_tabs( 'pf-tools' );
	}

	public function nom_this_endpoint() {
		\start_pf_nom_this();
	}

}
