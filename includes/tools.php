<?php
class PF_Tools {

	public static function init() {
		static $instance;

		if ( ! is_a( $instance, 'PF_Tools' ) ) {
			$instance = new self();
		}

		return $instance;
	}

	private function __construct() {
		add_filter( 'pf_tabs_pf-tools', array($this, 'set_permitted_tools_tabs') );
	}

	public function the_settings_page(){
		if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'nominate-this';
		$user_ID = get_current_user_id();
		$vars = array(
				'current'		=> $tab,
				'user_ID'		=> $user_ID,
				'page_title'	=>	__('PressForward Tools', 'pf'),
				'page_slug'		=>	'pf-tools',
				'no_save_button'	=>	true,
			);
		echo pressforward()->form_of->get_view(pressforward()->form_of->build_path(array('settings','settings-page'), false), $vars);

		return;
	}

	public function set_permitted_tools_tabs( $permitted_tabs ){
		$permitted_tabs['nominate-this'] = array(
										'title' => __('Bookmarklet', 'pf'),
										'cap'  => pf_get_defining_capability_by_role('contributor')
									);
		$permitted_tabs['reset-refresh'] = array(
										'title' => __('Debug and Refresh', 'pf'),
										'cap'  => pf_get_defining_capability_by_role('administrator')
									);
		return $permitted_tabs;
	}

}