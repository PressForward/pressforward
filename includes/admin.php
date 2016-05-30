<?php

/**
 * Setting up the admin interface, including menus and AJAX
 *
 * @since 1.7
 */

class PF_Admin {
	/**
	 * Constructor
	 *
	 * @since 1.7
	 */
	function __construct() {
		//add_action( 'admin_menu', array( $this, 'register_pf_custom_menu_pages' ) );


		// Adding javascript and css to admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'wp_head', array( $this, 'pf_aggregation_forwarder'));

		add_filter( 'pf_admin_pages', array($this, 'state_pf_admin_pages'), 10,3);
		// Catch form submits
		add_action( 'admin_init', array($this, 'pf_options_admin_page_save') );
		add_action( 'admin_notices', array($this, 'admin_notices_action' ));

		// Launch a batch delete process, if necessary.
		add_action( 'admin_init', array( $this, 'launch_batch_delete' ) );


		// Modify the Subscribed Feeds panel
		#add_filter( 'parse_query', array( $this, 'include_alerts_in_edit_feeds' ) );
		add_filter( 'ab_bug_status_args', array( $this, 'pf_ab_bug_status_args' ) );

		add_filter( 'heartbeat_received', array( $this, 'hb_check_feed_retrieve_status' ), 10, 2 );
	}

	/**
	 * Register menu pages
	 */
	function register_pf_custom_menu_pages() {

		add_submenu_page(
			PF_MENU_SLUG,
			__('All Content', 'pf'),
			__('All Content', 'pf'),
			get_option('pf_menu_all_content_access', pf_get_defining_capability_by_role('contributor')),
			PF_MENU_SLUG,
			array($this, 'display_reader_builder')
		);
/**
		add_submenu_page(
			PF_MENU_SLUG,
			__('Add Nomination', 'pf'),
			__('Add Nomination', 'pf'),
			get_option('pf_menu_add_nomination_access', pf_get_defining_capability_by_role('contributor')),
			PF_NOM_POSTER
		);
**/

#		$verifyPages = array();

#		$pf_admin_pages = apply_filters('pf_admin_pages',$verifyPages);

	}

	function state_pf_admin_pages($thepages){

		$basePages = array(PF_SLUG . '-feeder',PF_SLUG . '-options',PF_SLUG . '-review',PF_MENU_SLUG);
		$thepages = array_merge($basePages, (array)$thepages);
		return $thepages;

	}

	/**
	 * Display function for the Under Review panel
	 */
	function display_review_builder() {
		include( PF_ROOT . "/includes/under-review/under-review.php" );

	}

	/**
	 * Display function for the Options panel
	 */
	function display_options_builder() {
		pressforward()->form_of->the_settings_page();
	}

	/**
	* Display function for Feeder panel
	*/
	function display_tools_builder() {
		pressforward()->tools->the_settings_page();
	}

	/**
	 * Display function for Feeder panel
	 */
	function display_feeder_builder() {

		pressforward()->add_feeds->the_settings_page();


	}

    public function pf_alert_displayer(){
        add_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
        add_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
        add_filter('ab_alert_specimens_check_message', array($this, 'alert_check_message'));
        add_filter('ab_alert_specimens_delete_all_text', array($this, 'alert_delete_all_message'));
            the_alert_box()->alert_box_outsides();
        remove_filter('ab_alert_specimens_delete_all_text', array($this, 'alert_delete_all_message'));
        remove_filter('ab_alert_specimens_check_message', array($this, 'alert_check_message'));
        remove_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
        remove_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
    }

    public function alert_filterer($post_types){
        return array(pressforward()->pf_feeds->post_type);
    }

    public function alert_check_message($msg){
        return __('Are you sure you want to delete all feeds with alerts?', 'pf');
    }

    public function alert_delete_all_message($msg){
        return __('Delete all feeds with alerts', 'pf');
    }

    public function alert_safe_filterer($safe_msg){
        return __('All feeds are ok!', 'pf');
    }

	function admin_notices_action() {
		settings_errors( 'add_pf_feeds' );
	}

	function include_alerts_in_edit_feeds( $query ){
		global $pagenow;
		if ( is_admin() && 'edit.php' === $pagenow && 'pf_feed' === $_GET['post_type'] ) {
			#$statuses = $query->query['post_status'];
			#var_dump('<pre>'); var_dump( $query ); die();
			#$query->query['post_status'] = '';
			#$query->query_vars['post_status'] = '';
		}
		#return $query;
	}

	function pf_ab_bug_status_args( $args ){
		$args['public'] = true;

		return $args;
	}

	//This function can add js and css that we need to specific admin pages.
	function add_admin_scripts($hook) {

		//This gets the current page the user is on.
		global $pagenow;

		$user_ID = get_current_user_id();
		$pf_user_scroll_switch = get_user_option('pf_user_scroll_switch', $user_ID);

			//wp_register_style( PF_SLUG . '-style', PF_URL . 'assets/css/style.css');

			//wp_register_style( PF_SLUG . '-susy-style', PF_URL . 'assets/css/susy.css');
			#wp_register_style( PF_SLUG . '-reset-style', PF_URL . 'assets/css/reset.css');
			//wp_register_style( PF_SLUG . '-settings-style', PF_URL . 'assets/css/pf-settings.css');

			// Dependencies
			#wp_register_style( PF_SLUG . '-bootstrap-responsive-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap-responsive.css');
			#wp_register_style( PF_SLUG . '-bootstrap-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap.css');

			#wp_register_script(PF_SLUG . '-twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));


			//SASS-generated styles
			//wp_register_style( PF_SLUG . 'sass-settings-style', PF_URL . 'assets/css/pf-settings.css');
			# wp_register_style( PF_SLUG . '-style', PF_URL . 'assets/css/pressforward.css', array( PF_SLUG . '-bootstrap-style', PF_SLUG . '-bootstrap-responsive-style' ) );
			# wp_register_style( PF_SLUG . '-settings-style', PF_URL . 'assets/css/pf-settings.css' );
			# wp_register_style( PF_SLUG . '-subscribed-styles', PF_URL . 'assets/css/pf-subscribed.css' );

			//wp_register_style( PF_SLUG . '-responsive-style', PF_URL . 'assets/css/pf-responsive.css', array(PF_SLUG . '-reset-style', PF_SLUG . '-style', PF_SLUG . '-bootstrap-style', PF_SLUG . '-susy-style'));
			# wp_register_style('pf-alert-styles', PF_URL . 'assets/css/alert-styles.css');
			# wp_enqueue_style( PF_SLUG . '-alert-styles' );

			# wp_register_script(PF_SLUG . '-views', PF_URL . 'assets/js/views.js', array( PF_SLUG . '-twitter-bootstrap', 'jquery-ui-core', 'jquery-effects-slide'  ));
			# wp_register_script(PF_SLUG . '-readability-imp', PF_URL . 'assets/js/readability-imp.js', array( PF_SLUG . '-twitter-bootstrap', 'jquery', PF_SLUG . '-views' ));
			# wp_register_script(PF_SLUG . '-infiniscroll', PF_URL . 'lib/jquery.infinitescroll.js', array( 'jquery', PF_SLUG . '-views', PF_SLUG . '-readability-imp', 'jquery' ));
			# wp_register_script(PF_SLUG . '-scrollimp', PF_URL . 'assets/js/scroll-imp.js', array( PF_SLUG . '-infiniscroll', 'pf-relationships', PF_SLUG . '-views'));
			# wp_register_script('pf-relationships', PF_URL . 'assets/js/relationships.js', array( 'jquery' ));
			# wp_register_script(PF_SLUG . '-tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			# wp_register_script(PF_SLUG . '-media-query-imp', PF_URL . 'assets/js/media-query-imp.js', array( 'jquery', 'thickbox', 'media-upload' ));
			# wp_register_script(PF_SLUG . '-sort-imp', PF_URL . 'assets/js/sort-imp.js', array( PF_SLUG . '-tinysort', PF_SLUG . '-twitter-bootstrap', PF_SLUG . '-jq-fullscreen' ));
			# wp_register_script( PF_SLUG . '-quick-edit', PF_URL . 'assets/js/quick-edit.js', array( 'jquery' ) );
			# wp_register_script( PF_SLUG . '-settings-tools', PF_URL . 'assets/js/settings-tools.js', array( 'jquery' ) );
			# wp_register_script( PF_SLUG . '-tools', PF_URL . 'assets/js/tools-imp.js', array( 'jquery' ) );


		if ( false != pressforward()->form_of->is_a_pf_page() ){
			//var_dump('heartbeat'); die();
			wp_enqueue_script( 'heartbeat' );
			wp_enqueue_script( 'jquery-ui-progressbar' );
			# wp_enqueue_script( PF_SLUG . '-heartbeat', PF_URL . 'assets/js/pf-heartbeat.js', array( 'heartbeat', 'jquery-ui-progressbar', 'jquery' ) );

		}
		//print_r($hook);
		//This if loop will check to make sure we are on the right page for the js we are going to use.
		if (('toplevel_page_pf-menu') == $hook) {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script(PF_SLUG . '-tinysort');
			wp_enqueue_script(PF_SLUG . '-sort-imp');
			wp_enqueue_script(PF_SLUG . '-views');
			wp_enqueue_script(PF_SLUG . '-readability-imp');
			wp_enqueue_script(PF_SLUG . '-nomination-imp', PF_URL . 'assets/js/nomination-imp.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');
			wp_enqueue_script(PF_SLUG . '-jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			if (empty($pf_user_scroll_switch) || 'true' == $pf_user_scroll_switch){
				wp_enqueue_script(PF_SLUG . '-infiniscroll');
				wp_enqueue_script(PF_SLUG . '-scrollimp');
			}
			wp_enqueue_script('pf-relationships');

			//Styles
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			//wp_enqueue_style( PF_SLUG . '-susy-style' );
			//wp_enqueue_style( PF_SLUG . '-responsive-style' );

		}
		if (('pressforward_page_pf-review') == $hook) {
			wp_enqueue_script(PF_SLUG . '-tinysort');
			wp_enqueue_script(PF_SLUG . '-sort-imp');
			wp_enqueue_script(PF_SLUG . '-jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');
			wp_enqueue_script(PF_SLUG . '-send-to-draft-imp', PF_URL . 'assets/js/send-to-draft-imp.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-archive-nom-imp', PF_URL . 'assets/js/nom-archive-imp.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-views');
			wp_enqueue_script(PF_SLUG . '-readability-imp');

			if (empty($pf_user_scroll_switch) || 'true' == $pf_user_scroll_switch){
				wp_enqueue_script(PF_SLUG . '-infiniscroll');
				wp_enqueue_script(PF_SLUG . '-scrollimp');
			}

			wp_enqueue_script('pf-relationships');

			//Styles
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			//wp_enqueue_style( PF_SLUG . '-susy-style' );
			//wp_enqueue_style( PF_SLUG . '-responsive-style' );

			//Core
			wp_enqueue_script( 'post' );
		}

		if (('pressforward_page_pf-tools') == $hook) {
			wp_enqueue_script(PF_SLUG . '-jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');
			wp_enqueue_script(PF_SLUG . '-tools');
			wp_enqueue_script(PF_SLUG . '-settings-tools' );

			//Styles
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			//wp_enqueue_style( PF_SLUG . '-susy-style' );
			//wp_enqueue_style( PF_SLUG . '-responsive-style' );
			wp_enqueue_style( PF_SLUG . '-settings-style' );
		}
		if (('pressforward_page_pf-options') == $hook) {

			//Styles
			wp_enqueue_style( PF_SLUG . '-settings-style' );

			//Scripts
			wp_enqueue_script(PF_SLUG . '-settings-tools' );
		}

		if (('nomination') == get_post_type()) {
			# wp_enqueue_script(PF_SLUG . '-add-nom-imp', PF_URL . 'assets/js/add-nom-imp.js', array( 'jquery' ));
		}

		if ( 'edit.php' === $hook && 'pf_feed' === get_post_type() ) {

			//Scripts
			wp_enqueue_script( PF_SLUG . '-quick-edit' );

			//Styles
			wp_enqueue_style( PF_SLUG . '-subscribed-styles' );
		}

		if (('pressforward_page_pf-feeder') != $hook) { return; }
		else {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_media();

			//Scripts
			wp_enqueue_script(PF_SLUG . '-tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');
			wp_enqueue_script( PF_SLUG . '-media-query-imp' );
			wp_enqueue_script(PF_SLUG . '-settings-tools' );


			//Styles
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-settings-style' );
			//wp_enqueue_style( PF_SLUG . '-susy-style' );
			//wp_enqueue_style( PF_SLUG . '-responsive-style' );

			//Core
			wp_enqueue_style('thickbox');
		}

	}

	/**
	 * @todo Looks like this was tester code that doesn't do anything important
	 */
	function widget_array(){
		$widgets = array(
				'first_widget' => array(
						'title' => 'Widget Title',
						'slug' => 'first_widget',
						'callback' => array($this, 'widget_one_call')
									)
							);

		return $widgets;
	}

	/**
	 * @todo Looks like this was tester code that doesn't do anything important
	 */
	function widget_one_call(){
		echo '<div class="navwidget">	Widget Body <br />	<a href="#20">Test link to item 20.</a>	</div>'	;
	}

	function pf_options_admin_page_save() {
		global $pagenow;

		if ( 'admin.php' != $pagenow ) {
			return;
		}

		if ( empty( $_POST['submit'] ) ) {
			return;
		}

		check_admin_referer( 'pf_settings' );

		if (current_user_can( get_option('pf_menu_all_content_access', pf_get_defining_capability_by_role('contributor')) ) ){
			$user_ID = get_current_user_id();
			if (isset( $_POST['pf_user_scroll_switch'] )){
				$pf_user_scroll_switch = $_POST['pf_user_scroll_switch'];
				//var_dump($pf_user_scroll_switch); die();
				update_user_option($user_ID, 'pf_user_scroll_switch', $pf_user_scroll_switch);
			} else {
				update_user_option($user_ID, 'pf_user_scroll_switch', 'false');
			}

			if (isset( $_POST['pf_user_menu_set'] )){
				$pf_user_menu_set = $_POST['pf_user_menu_set'];
				//var_dump($pf_user_scroll_switch); die();
				update_user_option($user_ID, 'pf_user_menu_set', $pf_user_menu_set);
			} else {
				update_user_option($user_ID, 'pf_user_menu_set', 'false');
			}

			if (isset( $_POST['pf_pagefull'] )){
				$pf_pagefull = $_POST['pf_pagefull'];
				//var_dump($pf_user_scroll_switch); die();
				update_user_option($user_ID, 'pf_pagefull', $pf_pagefull);
			} else {
				update_user_option($user_ID, 'pf_pagefull', 'false');
			}

		}

		$verifyPages = array();

		$pf_admin_pages = apply_filters('pf_admin_pages',$verifyPages);

		if (! in_array($_GET['page'], $pf_admin_pages)){
			return;
		}

		if ( current_user_can( get_option('pf_menu_preferences_access', pf_get_defining_capability_by_role('administrator')) ) ){


			$arrayedAdminRights = array(
				'pf_menu_group_access'	=>	array(
												'default'=>'contributor',
												'title'=>__( 'PressForward Menu Group', 'pf' )
											),
				'pf_menu_all_content_access'=>array(
												'default'=>'contributor',
												'title'=>__( 'All Content Menu', 'pf' )
											),
				'pf_menu_under_review_access'=>array(
												'default'=>'contributor',
												'title'=>__( 'Nominated Menu', 'pf' )
											),
				'pf_menu_preferences_access'=>array(
												'default'=>'administrator',
												'title'=>__( 'Preferences Menu', 'pf' )
											),
				'pf_menu_feeder_access'=>array(
												'default'=>'editor',
												'title'=>__( 'Add Feeds', 'pf' )
											),
				'pf_menu_add_nomination_access'=>array(
												'default'=>'contributor',
												'title'=> __( 'Add Nomination Menu', 'pf' )
											)
			);

			$arrayedAdminRights = apply_filters('pf_setup_admin_rights',$arrayedAdminRights);

			foreach($arrayedAdminRights as $right=>$parts){
				if (isset( $_POST[$right] )){
					$enabled = $_POST[$right];
					update_option( $right, $enabled );
					$feed_caps = pressforward()->pf_feeds->map_feed_caps();
					$feed_item_caps = pressforward()->schema->map_feed_item_caps();
					if ( 'pf_menu_feeder_access' == $right){
						$all_roles = get_editable_roles();
						foreach ($all_roles as $a_role=>$permissions ){
							$a_role = get_role($a_role);
							foreach ($feed_caps as $feed_cap){
								$a_role->remove_cap($feed_cap);
							}
							foreach ($feed_item_caps as $feed_item_cap){
								$a_role->remove_cap($feed_item_cap);
							}
						}
//						assign_pf_to_standard_roles();
						$role = get_role(pf_get_role_by_capability($enabled));
						//var_dump($role); die();
						$role->add_cap($feed_caps['edit_posts']);
						$role->add_cap($feed_item_caps['edit_posts']);
						//var_dump($role); die();
					}


				}
			}

			if (isset( $_POST['pf_link_to_source'] )){
				$pf_links_opt_check = $_POST['pf_link_to_source'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_link_to_source', $pf_links_opt_check);
			} else {
				update_option('pf_link_to_source', 0);
			}


			if (isset( $_POST['pf_retain_time'] )){
				$pf_links_opt_check = $_POST['pf_retain_time'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_retain_time', $pf_links_opt_check);
			} else {
				update_option('pf_retain_time', 2);
			}

			if (isset( $_POST['pf_errors_until_alert'] )){
				$pf_errors_until_alert = $_POST['pf_errors_until_alert'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_errors_until_alert', $pf_errors_until_alert);
			} else {
				update_option('pf_errors_until_alert', 3);
			}
			if (isset( $_POST[PF_SLUG.'_retrieval_frequency'] )){
				$pf_retrieval_frequency = $_POST[PF_SLUG.'_retrieval_frequency'];
				//print_r($pf_links_opt_check); die();
				update_option(PF_SLUG.'_retrieval_frequency', $pf_retrieval_frequency);
			} else {
				update_option(PF_SLUG.'_retrieval_frequency', 30);
			}
			if (isset( $_POST['pf_present_author_as_primary'] )){
				$pf_author_opt_check = $_POST['pf_present_author_as_primary'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_present_author_as_primary', $pf_author_opt_check);
			} else {
				update_option('pf_present_author_as_primary', 'no');
			}

			if (isset( $_POST['pf_source_statement_position'] )){
				$pf_author_opt_check = $_POST['pf_source_statement_position'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_source_statement_position', $pf_author_opt_check);
			} else {
				update_option('pf_source_statement_position', 'no');
			}

			$pf_draft_post_type = (!empty( $_POST[PF_SLUG . '_draft_post_type'] ) )
				? $_POST[PF_SLUG . '_draft_post_type']
				: 'post';
			update_option(PF_SLUG . '_draft_post_type', $pf_draft_post_type);

			$pf_draft_post_status = (!empty( $_POST[PF_SLUG . '_draft_post_status'] ) )
				? $_POST[PF_SLUG . '_draft_post_status']
				: 'draft';
			update_option(PF_SLUG . '_draft_post_status', $pf_draft_post_status);

			if (class_exists('The_Alert_Box')){
				#var_dump($_POST);
				if(empty($_POST[the_alert_box()->option_name()])){
					#var_dump('<pre>'); var_dump($_POST); var_dump('</pre>');
					update_option(the_alert_box()->option_name(), 'false');
				} else {
					update_option(the_alert_box()->option_name(), $_POST[the_alert_box()->option_name()]);
				}
			}

			if (isset( $_POST['pf_use_advanced_user_roles'] )){
				$pf_author_opt_check = $_POST['pf_use_advanced_user_roles'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_use_advanced_user_roles', $pf_author_opt_check);
			} else {
				update_option('pf_use_advanced_user_roles', 'no');
			}
		}

		do_action( 'pf_admin_op_page_save' );
	}

	function pf_aggregation_forwarder(){
		if(1 == get_option('pf_link_to_source',0)){
			//http://webmaster.iu.edu/tools-and-guides/maintenance/redirect-meta-refresh.phtml ?
			$linked = get_post_meta('item_link', true);
			//Need syndicate tag here.
			if (is_single() && ('' != $linked)){
				?>
				 <script type="text/javascript">alert('You are being redirected to the source item.');</script>
				<META HTTP-EQUIV="refresh" CONTENT="10;URL=<?php echo get_post_meta('item_link', true); ?>">
				<?php

			}
		}
	}


    public function dead_feed_status(){
        register_post_status('removed_'.pressforward()->pf_feeds->post_type, array(
            'label'                 =>     _x('Removed Feed', 'pf'),
            'public'                =>      false,
            'exclude_from_search'   =>      true,
            'show_in_admin_all_list'=>      false
        ) );
    }

	/**
	 * Launch a batch delete, if one is queued.
	 *
	 * @since 3.6
	 */
	public function launch_batch_delete() {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		pf_launch_batch_delete();
	}

	/////////////////////////
	//    AJAX HANDLERS    //
	/////////////////////////

}
