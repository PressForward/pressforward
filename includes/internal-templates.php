<?php

/**
 * Setting up the admin interface pieces, including menus and AJAX
 *
 * @since 3.5
 */

class PF_Form_Of {

	public function __construct() {
		add_action( 'current_screen', array( $this, 'init' ) );
	}

	public function init() {
		$this->parts = $this->build_path(array(PF_ROOT, "parts"), false);
		$this->the_screen = $this->the_screen();
		$this->user_id = $this->user_id();
		$this->is_a_pf_page();
		add_filter('ab_alert_specimens_labels', array($this, 'alter_alert_boxes'));
		if (WP_DEBUG && $this->is_pf){
			@trigger_error($this->pf_current_screen_trace, E_USER_NOTICE);
		}
	}

	/**
	 * Build file paths.
	 *
	 * Build paths with arrays Call out of static function class_name->build_path
	 * or self::build_path. Use like:
	 *
	 * 		build_path(array("home", "alice", "Documents", "example.txt"));
	 *
	 * @see http://php.net/manual/en/dir.constants.php
	 * @global string DIRECTORY_SEPARATOR Called from class definition, system variable
	 *
	 * @param array $segments The pieces of the URL, should be array of strings. Default null Accepts string.
	 * @param bool $leading Optional If the returned path should have a leading slash. Default true.
	 * @param bool $url Optional If the returned path should use web URL style pathing or system style. Default false
	 * @return string The composed path.
	 *
	 */
	public function build_path($segments=array(), $leading = true, $url = false) {
		if ($url){
            $slash = '/';
        } else {
            $slash = DIRECTORY_SEPARATOR;
        }
        $string = join($slash, $segments);
		if ($leading){
			$string = $slash . $string;
		}
		# Let's make sure eh?
		if ('/' != $slash){
			$string = str_replace('/', $slash, $string);
		}
		return $string;
	}

	public function user_id(){
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		return $user_id;
	}

	public function alter_alert_boxes($alert_names){
		if ($this->is_pf){
			$new_alert_names = array(
				'name'                => _x( 'Feed Alerts', 'post type general name', 'pf' ),
				'singular_name'       => _x( 'Alert', 'post type singular name', 'pf' ),
				'menu_name'           => _x( 'Feed Alerts', 'admin menu', 'pf' ),
				'name_admin_bar'      => _x( 'Feed Alert', 'add new on admin bar', 'pf' ),
				'add_new'             => _x( 'Add Feed Alert', 'alert', 'pf' ),
				'add_new_item'        => __( 'Add New Feed Alert', 'pf' ),
				'new_item'            => __( 'New Feed Alert', 'pf' ),
				'edit_item'           => __( 'Edit Feed Alert', 'pf' ),
				'view_item'           => __( 'View Feed Alert', 'pf' ),
				'all_items'           => __( 'All Feed Alerts', 'pf' ),
				'search_items'        => __( 'Search Feed Alerts', 'pf' ),
				'parent_item_colon'   => __( 'Parent Alerts:', 'pf' ),
				'not_found'           => __( 'No feed alerts found.', 'pf' ),
				'not_found_in_trash'  => __( 'No feed alerts found in Trash.', 'pf' ),
        		'dismiss_one_check'   => __( 'This will set the feed to inactive. Are you sure you want to dismiss the alert on', 'pf' ),
        		'dismiss_all_check'   => __( 'Are you sure you want to dismiss all alerts? It will set all alerted feeds to inactive.', 'pf' ),
        		'dismiss_all'         => __( 'Dismiss all alerts', 'pf' ),
        		'delete_all_check'    => __( 'Are you sure you want to delete all feeds with alerts?', 'pf' ),
        		'delete_all'          => __( 'Delete all feeds with alerts', 'pf' ),
        		'dismissed'           => __( 'Inactive', 'pf' ),
        		'all_well'			  => __( 'No problems!', 'pf' ),
        		'turn_on'			  => __( 'Turn alerts on.', 'pf' ),
        		'activate_q'		  => __( 'Active Alert Boxes?', 'pf' ),
        		'turned_off'		  => __( 'Feed alert boxes not active.', 'pf')
			);
			$new_alert_names = array_merge($alert_names, $new_alert_names);
			return $new_alert_names;
		} else {
			return $alert_names;
		}
	}

	/**
	 * Get a given view (if it exists)
	 *
	 * @param string     $view      The slug of the view
	 * @return string
	 */
	public function get_view( $view, $vars = array() ) {
		$view_file = $this->build_path(array($this->parts, $view.'.tpl.php'), false);
		#if (WP_DEBUG){ var_dump( $view_file ); }
		if ( ! file_exists( $view_file ) ){
			if (WP_DEBUG){ var_dump( $view_file ); }
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
			echo $this->get_view('side-menu', $vars);
		}

		return;

	}

	public function nominate_this($context){
		if ( current_user_can('edit_posts') ) :

			$have_you_seen = get_user_option('have_you_seen_nominate_this');
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
											'cap'  => get_option('pf_menu_all_content_access', pf_get_defining_capability_by_role('contributor'))
										),
						'site' => array(
											'title' => __('Site Options', 'pf'),
											'cap'  => get_option('pf_menu_preferences_access', pf_get_defining_capability_by_role('administrator'))
										),
						'user-control' => array(
											'title' => __('User Control', 'pf'),
											'cap'  => get_option('pf_menu_preferences_access', pf_get_defining_capability_by_role('administrator'))
										),
						'modules' => array(
											'title' =>__('Module Control', 'pf'),
											'cap'  => get_option('pf_menu_preferences_access', pf_get_defining_capability_by_role('administrator'))
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
		echo $this->get_view($this->build_path(array('settings','settings-page'), false), $vars);

		return;
	}

	public function settings_tab_group($current, $page_slug = 'settings'){
		$tabs = $this->permitted_tabs($page_slug);
		#var_dump($tabs); die();
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
							$this->the_settings_tab($tab, $page_slug);
						}
					?>
				</div>
				<?php
			}
		}

		return;
	}


	public function the_settings_tab($tab, $page_slug = 'settings'){
		$permitted_tabs = $this->permitted_tabs($page_slug);
		if ( array_key_exists($tab, $permitted_tabs) ) $tab = $tab; else return '';
		$vars = array(
				'current'		=> $tab
			);
		#var_dump($page_slug.' - '.$tab); die();
		echo $this->get_view($this->build_path(array($page_slug,'tab-'.$tab), false), $vars);

		return;
	}

	public function valid_pf_page_ids($page_id = false){
		$valid = array(
				'toplevel_page_pf-menu',
				'pressforward_page_pf-review',
				'pressforward_page_pf-feeder',
				'edit-pf_feed',
				'pressforward_page_pf-options',
				'pressforward_page_pf-tools',
				'edit-pf_feed_category',
				'pressforward_page_pf-debugger'
			);
		$valid = apply_filters('pf_page_ids', $valid);
		if (false != $page_id){
			return in_array($page_id, $valid);
		} else {
			return $valid;
		}
	}

	public function the_screen(){
		#global $current_screen;
		$screen = get_current_screen();
		$id = $screen->id;
		$action = $screen->action;
		$base = $screen->base;
		$parent_base = $screen->parent_base;
		$parent_file = $screen->parent_file;
		$post_type = $screen->post_type;
		$taxonomy = $screen->taxonomy;
		$is_pf = self::valid_pf_page_ids($id);
		$this->pf_current_screen_trace = "PF screen trace: ID: $id; action: $action; base: $base; parent_base: $parent_base; parent_file: $parent_file; post_type: $post_type; taxonomy: $taxonomy;";
		#echo $base;
		$screen_array = array(

				'screen' 		=> $screen,
				'id'			=> $id,
				'action'		=> $action,
				'base'			=> $base,
				'parent_base'	=> $parent_base,
				'parent_file'	=> $parent_file,
				'post_type'		=> $post_type,
				'taxonomy'		=> $taxonomy

			);
		$screen_array = apply_filters('pf_screen', $screen_array);
		return $screen_array;
	}

	public function is_a_pf_page(){
		$screen = $this->the_screen;
		$is_pf = self::valid_pf_page_ids($screen['id']);
		$this->is_pf = $is_pf;
		return $is_pf;
	}

	public function get_the_folder_view_title(){
		if (isset($_GET['feed'])){
			$title = get_the_title($_GET['feed']);
		} else if (isset($_GET['folder'])){

			$term = get_term($_GET['folder'], pressforward()->pf_feeds->tag_taxonomy);
			$title = $term->name;

		} else {
			$title = '';
		}
		return $title;
	}

	public function title_variant(){
		$is_variant = false;
		$variant = '';
		$showing = __('Showing', 'pf');

		if (isset($_GET['feed']) || isset($_GET['folder'])){
			$variant .= ' ' . $this->get_the_folder_view_title();
			$is_variant = true;
		}

		if (isset($_POST['search-terms'])){
			$variant .= ' <span class="search-term-title">' . __('Search for:', 'pf') . ' ' . $_POST['search-terms'] . '</span>';
			$is_variant = true;
		}

		if (isset($_GET['by'])){
			$variant .= ' <span>'.$showing.' - ' . ucfirst($_GET['by']) . '</span>';
			$is_variant = true;
		}

		if (isset($_GET["pc"])){
			$page = $_GET["pc"];
			$page = $page;
			if ($page > 0) {
				$pageNumForPrint = sprintf( __('Page %1$d', 'pf'), $page);
				$variant .= ' <span> ' . $pageNumForPrint . '</span>';
				$is_variant = true;
			}
		}

		if (isset($_GET['reveal'])){

			$revealing = '';
			if ('no_hidden' == $_GET['reveal']){
				$revealing = 'hidden';
			} else {
				$revealing = $_GET['reveal'];
			}

			$variant .= ' <span>'. $showing . ' ' . $revealing . '</span>';
			$is_variant = true;
		}

		if (isset($_GET['pf-see'])){
			$only = ' ';
			$and = 'only ';
			if ('archive-only' == $_GET['pf-see']) {
				$only .= $and . __('archived', 'pf');
				$and = ' ';
			}
			if ('starred-only' == $_GET['pf-see']) {
				$only .= $and . __('starred', 'pf');
			}
			if ('unread-only' == $_GET['pf-see']) {
				$only .= $and . __('unread', 'pf');
			}
			if ('drafted-only' == $_GET['pf-see']) {
				$only .= $and . __( 'drafted', 'pf' );
			}
			$variant .= ' <span>'. $showing . $only . '</span>';
			$is_variant = true;
		}

		$variant = apply_filters('pf_title_variation', $variant, $is_variant);

		if (!empty($variant)){
			$variant = ' |' . $variant;
		}

		return $variant;

	}

	public function get_page_headline($page_title = ''){
		if ($this->is_a_pf_page()){
			$title = '<h1>'.PF_TITLE;

			if (!empty($page_title)){
				$page_title = ' ' . $page_title;
			}

			$middle = $page_title;

			$middle = $middle . $this->title_variant();

			$end_title = '</h1> <span id="h-after"> &#8226; </span>';

			$title = $title.$middle.$end_title;

			return $title;
		} else {
			return NULL;
		}
	}

	public function the_page_headline($title = ''){
		echo $this->get_page_headline($title);
		return;
	}

}
