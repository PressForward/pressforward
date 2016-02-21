<?php
namespace PressForward\Core\Admin;

use PressForward\Interfaces\Templates;
use PressForward\Interfaces\SystemUsers;
class PFTemplater {

	public function __construct( Templates $template_factory, SystemUsers $users ) {
		$this->factory = $template_factory;
		$this->parts = $this->factory->build_path(array(PF_ROOT, "parts"), false);
		$this->users = $users;
	}

	/**
	 * Get a given view (if it exists)
	 *
	 * @param string     $view      The slug of the view
	 * @return string
	 */
	public function get_view( $view, $vars = array() ) {
		if (is_array($view)){
			$view = $this->factory->build_path($view, false);
		}
		$view_file = $this->factory->build_path(array($this->parts, $view.'.tpl.php'), false);

		if (isset($vars['user_ID']) && ( true === $vars['user_ID'] ) ){
			$vars['user_ID'] = $this->users->get_current_user_id();
		}
		//if (WP_DEBUG){ var_dump( $view_file ); }
		if ( ! file_exists( $view_file ) ){
			if (PF_DEBUG){ var_dump( $view_file ); }
			return ' ';
		}
		extract( $vars, EXTR_SKIP );
		ob_start();
		include $view_file;
		return ob_get_clean();
	}

	public function the_view_for($view, $vars = array()){
		echo $this->get_view($view, $vars);
	}

	public function nominate_this($context){
		if ( $this->users->current_user_can('edit_posts') ) :

			$have_you_seen = $this->users->get_user_option('have_you_seen_nominate_this');
			if ( ('as_paragraph' == $context) || ('as_feed' == $context) || (empty($have_you_seen)) ){
					$vars = array(
						'context'	=> $context
					);
				echo $this->get_view('nominate-this', $vars);
			} else {
				return;
			}
		endif;

		return;
	}


	public function permitted_tabs($slug = 'settings'){
		if ( 'settings' == $slug ){
			$permitted_tabs = array(
						'user' => array(
											'title' => __('User Options', 'pf'),
											'cap'  => get_option('pf_menu_all_content_access', $this->users->pf_get_defining_capability_by_role('contributor'))
										),
						'site' => array(
											'title' => __('Site Options', 'pf'),
											'cap'  => get_option('pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role('administrator'))
										),
						'user-control' => array(
											'title' => __('User Control', 'pf'),
											'cap'  => get_option('pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role('administrator'))
										),
						'modules' => array(
											'title' =>__('Module Control', 'pf'),
											'cap'  => get_option('pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role('administrator'))
										)
					);
			$permitted_tabs = apply_filters('pf_settings_tabs', $permitted_tabs);
		} else {
			$permitted_tabs = array();
			$permitted_tabs = apply_filters('pf_tabs_'.$slug, $permitted_tabs);
		}
		return $permitted_tabs;
	}

	public function the_settings_page(){
		if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'user';
		$user_ID = get_current_user_id();
		$vars = array(
				'current'		=> 	$tab,
				'user_ID'		=> 	$user_ID,
				'page_title'	=>	__('PressForward Preferences', 'pf'),
				'page_slug'		=>	'settings'
			);
		return $this->get_view($this->build_path(array('settings','settings-page'), false), $vars);
	}

	public function settings_tab_group($current, $page_slug = 'settings'){
		$tabs = $this->permitted_tabs($page_slug);
		#var_dump($tabs); die();
		ob_start();
		foreach ($tabs as $tab=>$tab_meta){
			if (current_user_can($tab_meta['cap'])){
				if ($current == $tab) $class = 'pftab tab active'; else $class = 'pftab tab';
				?>
				<div id="<?php echo $tab; ?>" class="<?php echo $class; ?>">
	            <h2><?php echo $tab_meta['title']; ?></h2>
		            <?php
						if (has_action('pf_do_'.$page_slug.'_tab_'.$tab) || !array_key_exists($tab, $tabs)){
							//var_dump('pf_do_'.$page_slug.'_tab_'.$tab); die();
		            		do_action('pf_do_'.$page_slug.'_tab_'.$tab);
		            	} else {
							echo $this->the_settings_tab($tab, $page_slug);
						}
					?>
				</div>
				<?php
			}
		}

		return ob_get_clean();
	}


	public function the_settings_tab($tab, $page_slug = 'settings'){
		$permitted_tabs = $this->permitted_tabs($page_slug);
		if ( array_key_exists($tab, $permitted_tabs) ) $tab = $tab; else return '';
		$vars = array(
				'current'		=> $tab
			);
		#var_dump($page_slug.' - '.$tab); die();
		return $this->get_view(array($page_slug,'tab-'.$tab), $vars);
	}

	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ){
		if (is_array($capability)){
			$capability = $this->users->user_level( $capability[0], $capability[1] );
		}
		$this->factory->add_submenu_page(
			$parent_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$function
		);
	}

	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ){
		if (is_array($capability)){
			$capability = $this->users->user_level( $capability[0], $capability[1] );
		}
		$this->factory->add_menu_page(
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$function,
			$icon_url,
			$position
		);
	}

	public function the_side_menu(){
		$user_ID = get_current_user_id();
		$pf_user_menu_set = get_user_option('pf_user_menu_set', $user_ID);
		if ('true' == $pf_user_menu_set){
			$screen = $this->the_screen;
			$vars = array(
					'slug'		=> $screen['id'],
					'version'	=> 0,
					'deck'		=> false
				);
			return $this->get_view('side-menu', $vars);
		}

		return;

	}

}