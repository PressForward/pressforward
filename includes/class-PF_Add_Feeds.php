<?php
class PF_Add_Feeds {

	public static function init() {
		static $instance;

		if ( ! is_a( $instance, 'PF_Add_Feeds' ) ) {
			$instance = new self();
		}

		return $instance;
	}

	private function __construct() {
		add_filter( 'pf_tabs_pf-add-feeds', array($this, 'set_permitted_tools_tabs') );
	}

	public function the_settings_page(){
		if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'primary_feed_type';
		$user_ID = get_current_user_id();
		$form_head = '<form method="post" action="options.php" enctype="multipart/form-data">';
		$vars = array(
				'current'		=> $tab,
				'user_ID'		=> $user_ID,
				'page_title'	=>	__('PressForward: Add Feeds', 'pf'),
				'page_slug'		=>	'pf-add-feeds',
				'no_save_button' =>	true,
				'form_head'		 => $form_head,
				'settings_field' => PF_SLUG . '_feedlist_group'
			);
		echo pressforward()->form_of->get_view(pressforward()->form_of->build_path(array('settings','settings-page'), false), $vars);

		return;
	}

	public function set_permitted_tools_tabs( $permitted_tabs ){
		$permitted_tabs['primary_feed_type'] = array(
										'title' => __('Subscribe to Feeds', 'pf'),
										'cap'  => pf_get_defining_capability_by_role('contributor')
									);
		$permitted_tabs['alerts'] = array(
										'title' => __('Alerts', 'pf'),
										'cap'  => pf_get_defining_capability_by_role('administrator')
									);
		return $permitted_tabs;
	}

}