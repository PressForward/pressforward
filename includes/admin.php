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
		//add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );


		// Modify the Subscribed Feeds panel
		#add_filter( 'parse_query', array( $this, 'include_alerts_in_edit_feeds' ) );

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

	/////////////////////////
	//    AJAX HANDLERS    //
	/////////////////////////

}
