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
		add_action( 'admin_menu', array( $this, 'register_pf_custom_menu_pages' ) );
        add_action( 'init', array( $this, 'dead_post_status') );

		// Adding javascript and css to admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'wp_head', array( $this, 'pf_aggregation_forwarder'));
		add_filter( 'admin_body_class',  array( $this, 'add_pf_body_class'));
		add_filter( 'pf_admin_pages', array($this, 'state_pf_admin_pages'), 10,3);
		// Catch form submits
		add_action( 'admin_init', array($this, 'pf_options_admin_page_save') );
		add_action( 'admin_notices', array($this, 'admin_notices_action' ));

		// Launch a batch delete process, if necessary.
		add_action( 'admin_init', array( $this, 'launch_batch_delete' ) );

		//Modify the Singleton Edit page.
		add_action( 'post_submitbox_misc_actions', array( $this, 'posted_submitbox_pf_actions' ) );
		add_action( 'save_post', array( $this, 'save_submitbox_pf_actions' ) );

		// AJAX handlers
		add_action( 'wp_ajax_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_build_a_nom_draft', array( $this, 'build_a_nom_draft') );
		add_action( 'wp_ajax_simple_nom_to_draft', array( $this, 'simple_nom_to_draft') );
		add_action( 'wp_ajax_assemble_feed_for_pull', array( $this, 'trigger_source_data') );
		add_action( 'wp_ajax_disassemble_item', array( $this, 'trigger_item_disassembly' ) );
		add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed') );
		add_action( 'wp_ajax_make_it_readable', array( $this, 'make_it_readable') );
		add_action( 'wp_ajax_archive_a_nom', array( $this, 'archive_a_nom') );
		add_action( 'wp_ajax_pf_ajax_get_comments', array( $this, 'pf_ajax_get_comments') );
		add_action( 'wp_ajax_pf_ajax_thing_deleter', array( $this, 'pf_ajax_thing_deleter') );
		add_action( 'wp_ajax_pf_ajax_retain_display_setting', array( $this, 'pf_ajax_retain_display_setting' ) );
		add_action( 'wp_ajax_pf_ajax_move_to_archive', array( $this, 'pf_ajax_move_to_archive' ) );
		add_action( 'wp_ajax_pf_ajax_move_out_of_archive', array( $this, 'pf_ajax_move_out_of_archive' ) );
		add_action( 'wp_ajax_pf_ajax_user_setting', array( $this, 'pf_ajax_user_setting' ));
		add_action( 'init', array( $this, 'register_feed_item_removed_status') );

		// Modify the Subscribed Feeds panel
		add_filter( 'manage_pf_feed_posts_columns', array( $this, 'add_last_retrieved_date_column' ) );
		add_action( 'manage_pf_feed_posts_custom_column', array( $this, 'last_retrieved_date_column_content' ), 10, 2 );
		add_action( 'manage_edit-pf_feed_sortable_columns', array( $this, 'make_last_retrieved_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_last_retrieved' ) );
		#add_filter( 'parse_query', array( $this, 'include_alerts_in_edit_feeds' ) );
		add_filter( 'ab_bug_status_args', array( $this, 'pf_ab_bug_status_args' ) );

		add_filter( 'manage_pf_feed_posts_columns', array( $this, 'add_last_checked_date_column' ) );
		add_action( 'manage_pf_feed_posts_custom_column', array( $this, 'last_checked_date_column_content' ), 10, 2 );
		add_action( 'manage_edit-pf_feed_sortable_columns', array( $this, 'make_last_checked_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_last_checked' ) );

		add_action( 'before_delete_post', array( $this, 'pf_delete_children_of_feeds' ) );
		add_action( 'wp_trash_post', array( $this, 'pf_trash_children_of_feeds' ) );

		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_field' ), 10, 2 );
		add_action( 'save_post', array( $this, 'quick_edit_save' ), 10, 2 );

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



		if ( $alert_count = The_Alert_Box::alert_count() ) {
			$alert_count_notice = '<span class="feed-alerts count-' . intval( $alert_count ) . '"><span class="alert-count">' . number_format_i18n( $alert_count ) . '</span></span>';
			$subscribed_feeds_menu_text = sprintf( __( 'Subscribed Feeds %s', 'pf' ), $alert_count_notice );
		} else {
			$subscribed_feeds_menu_text = __( 'Subscribed Feeds', 'pf' );
		}

		add_submenu_page(
			PF_MENU_SLUG,
			__('Subscribed Feeds', 'pf'),
			$subscribed_feeds_menu_text,
			get_option('pf_menu_feeder_access', pf_get_defining_capability_by_role('editor')),
			'edit.php?post_type=' . pressforward()->pf_feeds->post_type
		);

		// Options page is accessible to contributors, setting visibility controlled by tab
		add_submenu_page(
			PF_MENU_SLUG,
			__('Preferences', 'pf'), // @todo sprintf
			__('Preferences', 'pf'),
			get_option('pf_menu_all_content_access', pf_get_defining_capability_by_role('contributor')),
			PF_SLUG . '-options',
			array($this, 'display_options_builder')
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('Tools', 'pf'),
			__('Tools', 'pf'),
			get_option('pf_menu_tools_access', pf_get_defining_capability_by_role('contributor')),
			PF_SLUG . '-tools',
			array($this, 'display_tools_builder')
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

	function add_pf_body_class($classes) {

		$classes .= strtolower(PF_TITLE);

		return $classes;
	}

	function posted_submitbox_pf_actions(){
		global $post;
		$check = pressforward('controller.metas')->get_post_pf_meta($post->ID, 'item_link', true);
		if ( empty($check) ){
			return;
		}
	    $value = pressforward('controller.metas')->get_post_pf_meta($post->ID, 'pf_forward_to_origin', true);
	    if ( empty($value) ){

	    	$option_value = get_option('pf_link_to_source');
				if ( empty($option_value) ){
					$value = 'no-forward';
				} else {
					$value = 'forward';
				}
	    }

	    echo '<div class="misc-pub-section misc-pub-section-last">
				<label>
				<select id="pf_forward_to_origin_single" name="pf_forward_to_origin">
				  <option value="forward"'.( 'forward' == $value ? ' selected ' : '') .'>Forward</option>
				  <option value="no-forward"'.( 'no-forward' == $value ? ' selected ' : '') .'>Don\'t Forward</option>
				</select><br />
				to item\'s original URL</label></div>';
	}

	function save_submitbox_pf_actions( $post_id )
	{
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ return $post_id; }
	    if ( !current_user_can( 'edit_page', $post_id ) ){ return $post_id; }
		#var_dump($_POST['pf_forward_to_origin']); die();
		#$current = pressforward('controller.metas')->get_post_pf_meta();
			if ( !array_key_exists('pf_forward_to_origin', $_POST) ) {

 			} else {
				pressforward('controller.metas')->update_pf_meta($post_id, 'pf_forward_to_origin', $_POST['pf_forward_to_origin']);
			}

		return $post_id;
	}

	/**
	 * Display function for the Under Review panel
	 */
	function display_review_builder() {
		include( PF_ROOT . "/includes/under-review/under-review.php" );

	}

	function pf_ajax_get_comments(){
			if (has_action('pf_modal_comments')){
				$id_for_comments = $_POST['id_for_comments'];
				do_action('pf_modal_comments', $id_for_comments);
			}
			die();
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


	public function register_feed_item_removed_status(){

		$args = array(
			'label'						=>	_x('Removed Feed Item', 'pf' ),
			'public'					=>	false,
			'exclude_from_search'		=>	true,
			'show_in_admin_all_list'	=>	false,
			'show_in_admin_status_list'	=>	false,
			'label_count'				=>	_n_noop( 'Removed <span class="count">(%s)</span>', 'Removed <span class="count">(%s)</span>' )
		);

		register_post_status('removed_feed_item', $args);

	}

	public function search_the_posts($s, $post_type){

		$args = array(
			's'			=>  $s,
			'post_type' => $post_type

		);

		$q = WP_Query($args);
		return $q;

	}


	public function pf_ajax_move_to_archive(){
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		update_post_meta($nom_id, 'pf_archive', 1);
		update_post_meta($item_post_id, 'pf_archive', 1);
		$check = wp_update_post( array(
					'ID'			=>	$item_post_id,
					'post_status'	=>	'removed_feed_item'
				)
			);
		pf_log($check);
		die();
	}

	public function pf_ajax_move_out_of_archive(){
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		update_post_meta($nom_id, 'pf_archive', 'false');
		update_post_meta($item_post_id, 'pf_archive', 'false');
		$check = wp_update_post( array(
					'ID'			=>	$item_post_id,
					'post_status'	=>	'publish'
				)
			);
		pf_log($check);
		die();
	}

    public function dead_post_status(){
        register_post_status('removed_feed_item', array(
            'label'                 =>     _x('Removed Feed Item', 'pf'),
            'public'                =>      false,
            'exclude_from_search'   =>      true,
            'show_in_admin_all_list'=>      false
        ) );
    }

    public function dead_feed_status(){
        register_post_status('removed_'.pressforward()->pf_feeds->post_type, array(
            'label'                 =>     _x('Removed Feed', 'pf'),
            'public'                =>      false,
            'exclude_from_search'   =>      true,
            'show_in_admin_all_list'=>      false
        ) );
    }

    public function pf_delete_children_of_feeds( $post_id ){
    	if ( pressforward()->pf_feeds->post_type == get_post_type( $post_id ) ){
    		pf_log('Delete a feed and all its children.');
		pf_delete_item_tree( $post_id );
    	}
    }

    public function pf_trash_children_of_feeds( $post_id ){
    	if ( pressforward()->pf_feeds->post_type == get_post_type( $post_id ) ){
    		pf_log('Trash a feed and all its children.');
    		$this->pf_thing_trasher( $post_id, true, pressforward()->pf_feeds->post_type );
    	}
    }

	function pf_thing_trasher($id = 0, $readability_status = false, $item_type = 'feed_item'){
		if ($id == 0)
			return new WP_Error('noID', __("No ID supplied for deletion", 'pf'));

		pf_log('On trash hook:');
		# Note: this will also remove feed items if a feed is deleted, is that something we want?
		if ($readability_status || $readability_status > 0){
			if ( 'feed_item' == $item_type ){
				$post_type = pf_feed_item_post_type();
			} else {
				$post_type = $item_type;
			}
			$args = array(
				'post_parent' => $id,
				'post_type'   => $post_type
			);
			$attachments = get_children($args);
			pf_log('Get Children of '.$id);
			pf_log($attachments);
			foreach ($attachments as $attachment) {
				wp_trash_post($attachment->ID, true);
			}
		}

		return $id;

	}

	function pf_bad_call($action, $msg = 'You made a bad call and it did not work. Try again.'){
		$response = array(
			'what'=>'pressforward',
			'action'=>$action,
			'id'=>pressforward()->form_of->user_id(),
			'data'=>$msg,
			'supplemental' => array(
					'buffered' => ob_get_contents(),
					'timestamp' => gmdate( 'd-M-Y H:i:s' )
			)
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();
	}

	function pf_ajax_thing_deleter() {
		ob_start();
		if(isset($_POST['post_id'])){
			$id = $_POST['post_id'];
		} else {
			self::pf_bad_call('pf_ajax_thing_deleter','Option not sent');
		}
		if(isset($_POST['made_readable'])){
			$read_status = $_POST['made_readable'];
		} else { $read_status = false; }
		$returned = pf_delete_item_tree( $id, true );
		var_dump($returned);
		$vd = ob_get_clean();
		ob_end_clean();
		$response = array(
		   'what'=>'pressforward',
		   'action'=>'pf_ajax_thing_deleter',
		   'id'=>$id,
		   'data'=>(string)$vd
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		die();

	}

	function pf_ajax_retain_display_setting() {
		ob_start();
		if(isset($_POST['pf_read_state'])){
			$read_state = $_POST['pf_read_state'];
		} else {
			$read_status = false;
		}
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		$returned = $this->pf_switch_display_setting($user_id, $read_state);
		#var_dump($user_id);

		$response = array(
			'what'=>'pressforward',
			'action'=>'pf_ajax_retain_display_setting',
			'id'=>$user_id,
			'data'=>(string) $returned
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();

	}

	function pf_ajax_user_setting() {
		ob_start();
		if(isset($_POST['pf_user_setting'])){
			$setting_name = $_POST['pf_user_setting'];
		} else {
			$setting_name = false;
			self::pf_bad_call('pf_ajax_user_setting', 'No setting name, try again.');
		}
		if(isset($_POST['setting'])){
			$setting = $_POST['setting'];
		} else {
			$setting = false;
		}

		$user_id = pressforward()->form_of->user_id();
		$returned = $this->pf_switch_user_option($user_id, $setting_name, $setting);
		#var_dump($user_id);

		$response = array(
			'what'=>'pressforward',
			'action'=>'pf_ajax_user_setting',
			'id'=>$user_id,
			'data'=>(string) $returned,
			'supplemental' => array(
					'buffered' => ob_get_contents(),
					'setting' => $setting_name,
					'set'		=> $setting
			)
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();

	}


	public function pf_switch_display_setting($user_id, $read_state){
		if ( !current_user_can( 'edit_user', $user_id ) ){
			return false;
		}

		$check = update_user_meta($user_id, 'pf_user_read_state', $read_state);
		return $check;
	}


	function pf_switch_user_option($user_id, $option, $state){
		if ( !current_user_can( 'edit_user', $user_id ) ){
			return false;
		}

		$check = update_user_option($user_id, $option, $state);
		return $check;
	}

	/**
	 * Add a Last Retrieved column to the pf_feed table.
	 *
	 * @since 3.4.0
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function add_last_retrieved_date_column( $posts_columns ) {
		#unset( $posts_columns['date'] );
		$posts_columns['last_retrieved'] = __('Last Time Feed Item Retrieved', 'pf');
		return $posts_columns;
	}

	/**
	 * Add a Last Checked column to the pf_feed table.
	 *
	 * @since 3.5.0
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function add_last_checked_date_column( $posts_columns ) {
		#unset( $posts_columns['date'] );
		$posts_columns['last_checked'] = __('Last Time Feed Checked', 'pf');
		return $posts_columns;
	}

	/**
	 * Content of the Last Retrieved column.
	 *
	 * We also hide the feed URL in this column, so we can reveal it on Quick Edit.
	 *
	 * @since 3.4.0
	 *
	 * @param string $column_name Column ID.
	 * @param int $post_id ID of the post for the current row in the table.
	 */
	public function last_retrieved_date_column_content( $column_name, $post_id ) {
		if ( 'last_retrieved' !== $column_name ) {
			return;
		}

		$last_retrieved = get_post_meta( $post_id, 'pf_feed_last_retrieved', true );

		if ( '' === $last_retrieved ) {
			$lr_text = '-';
		} else {
			// Modified from WP_Posts_List_Table
			$lr_unix = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time = date( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$lr_text = sprintf( __( '%s ago' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . $t_time . '">' . $lr_text . '</abbr>';
		}

		$feed_url = get_post_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

		echo $lr_text;
	}

	/**
	 * Content of the Last Checked column.
	 *
	 * We also hide the feed URL in this column, so we can reveal it on Quick Edit.
	 *
	 * @since 3.5.0
	 *
	 * @param string $column_name Column ID.
	 * @param int $post_id ID of the post for the current row in the table.
	 */
	public function last_checked_date_column_content( $column_name, $post_id ) {
		if ( 'last_checked' !== $column_name ) {
			return;
		}

		$last_retrieved = get_post_meta( $post_id, 'pf_feed_last_checked', true );

		if ( '' === $last_retrieved ) {
			$lr_text = '-';
		} else {
			// Modified from WP_Posts_List_Table
			$lr_unix = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time = date( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$lr_text = sprintf( __( '%s ago' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . $t_time . '">' . $lr_text . '</abbr>';
		}

		$feed_url = get_post_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

		echo $lr_text;
	}

	/**
	 * Add the Last Retrieved column to the list of sortable columns.
	 *
	 * @since 3.4.0
	 *
	 * @param array $sortable Sortable column identifiers.
	 * @return array
	 */
	public function make_last_retrieved_column_sortable( $sortable ) {
		$sortable['last_retrieved'] = array( 'last_retrieved', true );
		return $sortable;
	}

	/**
	 * Add the Last Checked column to the list of sortable columns.
	 *
	 * @since 3.5.0
	 *
	 * @param array $sortable Sortable column identifiers.
	 * @return array
	 */
	public function make_last_checked_column_sortable( $sortable ) {
		$sortable['last_checked'] = array( 'last_checked', true );
		return $sortable;
	}

	/**
	 * Enable 'last_retrieved' sorting.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Query
	 */
	public function sort_by_last_retrieved( $query ) {
		// For now, only enable this sorting when on the edit-pf_feed screen
		// This could be lifted in the future to enable last_retrieved
		// sorting throughout PF
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries
		$feed_post_type = '';
		if ( ! empty( pressforward()->pf_feeds->post_type ) ) {
			$feed_post_type = pressforward()->pf_feeds->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_retrieved' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,
		// bail or we'll mess it up
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_retrieved' );

		// In order to ensure that we get the items without a
		// Last Retrieved key set, force the meta_query to an OR with
		// NOT EXISTS
		$query->set( 'meta_query', array(
			'relation' => 'OR',
			array(
				'key' => 'pf_feed_last_retrieved',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key' => 'pf_feed_last_retrieved',
				'compare' => 'EXISTS',
			)
		) );

		#var_dump($query); die();


	}

	/**
	 * Enable 'last_checked' sorting.
	 *
	 * @since 3.5.0
	 *
	 * @param WP_Query
	 */
	public function sort_by_last_checked( $query ) {
		// For now, only enable this sorting when on the edit-pf_feed screen
		// This could be lifted in the future to enable last_retrieved
		// sorting throughout PF
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries
		$feed_post_type = '';
		if ( ! empty( pressforward()->pf_feeds->post_type ) ) {
			$feed_post_type = pressforward()->pf_feeds->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_checked' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,
		// bail or we'll mess it up
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_checked' );

		// In order to ensure that we get the items without a
		// Last Retrieved key set, force the meta_query to an OR with
		// NOT EXISTS
		$query->set( 'meta_query', array(
			'relation' => 'OR',
			array(
				'key' => 'pf_feed_last_checked',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key' => 'pf_feed_last_checked',
				'compare' => 'EXISTS',
			)
		) );
	}

	/**
	 * Echo the output for the Feed URL field on Quick Edit.
	 *
	 * @since 3.5.0
	 *
	 * @param string $column_name Name of the Quick Edit column being output.
	 * @param string $post_type   Name of the current post type.
	 */
	public function quick_edit_field( $column_name, $post_type ) {
		if ( 'pf_feed' !== $post_type || 'last_retrieved' !== $column_name ) {
			return;
		}

		wp_nonce_field( 'pf-quick-edit', '_pf_quick_edit_nonce', false );

		?>
		<fieldset class="inline-edit-pressforward">
			<div class="inline-edit-col">
				<label for="pf-feed-url">
					<span class="title"><?php _e( 'Feed URL', 'pressforward' ) ?></span>
					<span class="input-text-wrap">
						<input class="inline-edit-pf-feed-input" type="text" value="" name="pf-quick-edit-feed-url" id="pf-quick-edit-feed-url" />
					</span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Process Quick Edit saves.
	 *
	 * Feed URL can be edited via Quick Save.
	 *
	 * @since 3.5.0
	 *
	 * @param int     $post_id ID of the post being edited.
	 * @param WP_Post $post    Post object.
	 */
	public function quick_edit_save( $post_id, $post ) {
		// Only process on the correct post type.
		if ( 'pf_feed' !== $post->post_type ) {
			return;
		}

		// Nonce check.
		if ( ! isset( $_POST['_pf_quick_edit_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_pf_quick_edit_nonce'], 'pf-quick-edit' ) ) {
			return;
		}

		// Don't process if the URL field is not present in the request.
		if ( ! isset( $_POST['pf-quick-edit-feed-url'] ) ) {
			return;
		}

		$feed_url = stripslashes( $_POST['pf-quick-edit-feed-url'] );

		update_post_meta( $post_id, 'feedUrl', $feed_url );
	}

	public function hb_check_feed_retrieve_status( $response, $data, $screen_id = '' ){
		/**
		 * $feed_hb_state = array(
		 * 'feed_id'	=>	$aFeed->ID,
		 * 'feed_title'	=> $aFeed->post_title,
		 * 'last_key'	=> $last_key,
		 * 'feeds_iteration'	=>	$feeds_iteration,
		 * 'total_feeds'	=>	count($feedlist)
		 * );
		**/
		if ( (array_key_exists('pf_heartbeat_request', $data)) && ('feed_state' == $data['pf_heartbeat_request']) ){
			$feed_hb_state = get_option( PF_SLUG.'_feeds_hb_state' );
			foreach ( $feed_hb_state as $key=>$state ){
				$response['pf_'.$key] = $state;
			}
		}

		return $response;

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

	public function build_a_nomination() {
		pressforward()->nominations->build_nomination();
		die();
	}

	public function build_a_nom_draft() {
		pressforward()->nominations->build_nom_draft();
		die();
	}

	public function trigger_source_data() {
		$message = pressforward()->pf_retrieve->trigger_source_data(true);
		wp_send_json($message);
		die();
	}

	public function trigger_item_disassembly() {
		$message = pressforward()->pf_feed_items->ajax_feed_items_disassembler();
		#wp_send_json($message);
		die();
	}

	public function reset_feed() {
		pressforward()->pf_feed_items->reset_feed();
		die();
	}

	public function make_it_readable() {
		pressforward()->readability->make_it_readable();
		die();
	}

	public function archive_a_nom() {
		pressforward()->nominations->archive_a_nom();
		die();
	}

	public function simple_nom_to_draft(){
		pressforward()->nominations->simple_nom_to_draft();
		die();
	}
}
