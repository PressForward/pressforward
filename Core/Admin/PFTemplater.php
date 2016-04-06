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
		return $this->get_view($this->factory->build_path(array('settings','settings-page'), false), $vars);
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

	public function search_template(){
		?>
			<form id="feeds-search" method="post" action="<?php echo basename($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '&action=post'; ?>">
					<label for="search-terms">Search</label>
				<input type="text" name="search-terms" id="search-terms" placeholder="Enter search terms">
				<input type="submit" class="btn btn-small" value="Search">
			</form>
		<?php
	}

	public function nav_bar($page = 'pf-menu'){
		?>
		<div class="display">
			<div class="pf-btns pull-left btn-toolbar">
				<?php if ( 'pf-review' != $page ) { ?>
					<div class="dropdown pf-view-dropdown btn-group" role="group">
					  <button class="btn btn-default btn-small dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
						<?php _e('View', 'pf'); ?>
						<span class="caret"></span>
					  </button>
						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
						<?php
							$view_check = get_user_meta(pressforward('controller.template_factory')->user_id(), 'pf_user_read_state', true);
							if ('golist' == $view_check){
								$this->dropdown_option(__('Grid', 'pf'), "gogrid", 'pf-top-menu-selection display-state');
								$this->dropdown_option(__('List', 'pf'), "golist", 'pf-top-menu-selection unset display-state');
							} else {
								$this->dropdown_option(__('Grid', 'pf'), "gogrid", 'pf-top-menu-selection unset display-state');
								$this->dropdown_option(__('List', 'pf'), "golist", 'pf-top-menu-selection display-state');
							}
							$pf_user_scroll_switch = get_user_option('pf_user_scroll_switch', pressforward('controller.template_factory')->user_id());
							#empty or true
							if ('false' == $pf_user_scroll_switch){
								$this->dropdown_option(__('Infinite Scroll (Reloads Page)', 'pf'), "goinfinite", 'pf-top-menu-selection scroll-toggler');
							} else {
								$this->dropdown_option(__('Paginate (Reloads Page)', 'pf'), "gopaged", 'pf-top-menu-selection scroll-toggler');
							}

						?>
						 </ul>
					</div>
				<?php } ?>
				<div class="dropdown pf-filter-dropdown btn-group" role="group">
				  <button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-expanded="true">
					<?php _e('Filter', 'pf'); ?>
					<span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu2">
					<?php
						if ( 'pf-review' != $page ){
							$this->dropdown_option(__('Reset filter', 'pf'), "showNormal");
							$this->dropdown_option(__('My starred', 'pf'), "showMyStarred");
							$this->dropdown_option(__('Show hidden', 'pf'), "showMyHidden");
							$this->dropdown_option(__('My nominations', 'pf'), "showMyNominations");
							$this->dropdown_option(__('Unread', 'pf'), "showUnread");
							$this->dropdown_option( __( 'Drafted', 'pf' ), "showDrafted" );
						} else {
							if ( isset($_POST['search-terms']) || isset($_GET['by']) || isset($_GET['pf-see']) || isset($_GET['reveal']) ) {
								$this->dropdown_option(__('Reset filter', 'pf'), "showNormalNominations");
							}
							$this->dropdown_option(__('My starred', 'pf'), "sortstarredonly", 'starredonly', null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=starred-only'));
							$this->dropdown_option(__('Toggle visibility of archived', 'pf'), "showarchived");
							$this->dropdown_option(__('Only archived', 'pf'), "showarchiveonly", null, null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=archive-only'));
							$this->dropdown_option(__('Unread', 'pf'), "showUnreadOnly", null, null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=unread-only'));
							$this->dropdown_option( __( 'Drafted', 'pf' ), "showDrafted", null, null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=drafted-only') );

						}
					?>
				  </ul>
				</div>
				<div class="dropdown pf-sort-dropdown btn-group" role="group">
				  <button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu3" data-toggle="dropdown" aria-expanded="true">
					<?php _e('Sort', 'pf'); ?>
					<span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu3">
					<?php
						$this->dropdown_option(__('Reset', 'pf'), "sort-reset");
						$this->dropdown_option(__('Date of item', 'pf'), "sortbyitemdate");
						$this->dropdown_option(__('Date retrieved', 'pf'), "sortbyfeedindate");
						if ( 'pf-review' == $page ){
							$this->dropdown_option(__('Date nominated', 'pf'), "sortbynomdate");
							$this->dropdown_option(__('Nominations received', 'pf'), "sortbynomcount");
						}
					?>
					<?php #<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Feed name</a></li> ?>
				  </ul>
				</div>
				<div class="btn-group" role="group">
					<a href="https://github.com/PressForward/pressforward/wiki" target="_blank" id="pf-help" class="btn btn-small"><?php _e('Need help?', 'pf'); ?></a>
				</div>
			</div>

			<div class="pull-right text-right">
			<!-- or http://thenounproject.com/noun/list/#icon-No9479? -->
				<?php
				if ( function_exists('the_alert_box') ){
										add_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
										add_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
										$alerts = the_alert_box()->get_specimens();
										remove_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
										remove_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
				}

					if ( 'pf-review' == $page ){
						echo '<button type="submit" class="delete btn btn-danger btn-small pull-left" id="archivenoms" value="' . __('Archive all', 'pf') . '" >' . __('Archive all', 'pf') . '</button>';
					}

					$user_ID = get_current_user_id();
					$pf_user_menu_set = get_user_option('pf_user_menu_set', $user_ID);
					if ('true' == $pf_user_menu_set){
						if (!empty($alerts) && (0 != $alerts->post_count)){
							echo '<a class="btn btn-small btn-warning" id="gomenu" href="#">' . __('Menu', 'pf') . ' <i class="icon-tasks"></i> (!)</a>';
						} else {
							echo '<a class="btn btn-small" id="gomenu" href="#">' . __('Menu', 'pf') . ' <i class="icon-tasks"></i></a>';
						}
					}
					echo '<a class="btn btn-small" id="gofolders" href="#">' . __('Folders', 'pf') . '</a>';
				?>

			</div>
		</div><!-- End btn-group -->
		<?php
	}

	public function dropdown_option($string, $id, $class = 'pf-top-menu-selection', $form_id = '', $schema_action = '', $schema_class = '', $href = '', $target = ''){

		$option = '<li role="presentation"><a role="menuitem" id="';
		$option .= $id;
		$option .= '" tabindex="-1" class="';
		$option .= $class;
		$option .= '"';

		$option .= ' href="';
		if (!empty($href)){
			$option .= $href;
		} else {
			$option .= '#';
		}
		$option .= '"';

		if (!empty($target)){
			$option .= ' target="'.$target.'"';
		}


		if (!empty($form_id)){
			$option .= ' data-form="' . $form_id . '" ';
		}

		if (!empty($schema_action)){
			$option .= ' pf-schema="' . $schema_action . '" ';
		}

		if (!empty($schema_class)){
			$option .= ' pf-schema-class="' . $schema_class . '" ';
		}

		$option .= '>';
		$option .= $string;
		$option .= '</a></li>';

		echo $option;

	}

}
