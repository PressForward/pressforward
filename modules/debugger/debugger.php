<?php

/**
 * Test of module base class
 */

class PF_Debugger extends PF_Module {
	function __construct() {
		parent::start();
	}

	/**
	 * Register the admin menu items
	 *
	 * The parent class will take care of registering them
	 */
	function setup_admin_menus() {
		$admin_menus   = array();

		$admin_menus[] = array(
			'page_title' => __( 'View Log', 'pf' ),
			'menu_title' => __( 'View Log', 'pf' ),
			'cap'        => 'edit_posts',
			'slug'       => 'pf-debugger',
			'callback'   => array( $this, 'admin_menu_callback' ),
		);

		parent::setup_admin_menus( $admin_menus );
	}
	
	function setup_module() {
		$enabled = get_option( 'pf_debugger_enable' );
		if ( ! in_array( $enabled, array( 'yes', 'no' ) ) ) {
			$enabled = 'yes';
		}
		
		$mod_settings = array(
			'name' => 'Debugger Log Viewing Module',
			'slug' => 'debugger',
			'options' => ''
		);
		
		//update_option( 'pf_foo_settings', $mod_settings );

		
	}
	
	function module_setup(){
		$mod_settings = array(
			'name' => 'Debugger Log Viewing Module',
			'slug' => 'debugger',
			'description' => 'This module provides a way to view the feet retrieval log within the dashboard.',
			'thumbnail' => '',
			'options' => ''
		);
		
		update_option( PF_SLUG . '_' . $this->id . '_settings', $mod_settings );	

		//return $test;
	}	
	
	function admin_menu_callback() {
		// Default log location is in the uploads directory
		if ( ! defined( 'PF_DEBUG_LOG' ) ) {
			$upload_dir = wp_upload_dir();
			$log_path = $upload_dir['basedir'] . '/pressforward.log';
		} else {
			$log_path = PF_DEBUG_LOG;
		}
		?>
		<div class="wrap">
			<h2>Current Log</h2>
			<p>Does not update in real time.</p>
			<br /><br />
			<?php
				if(file_exists($log_path)){
				  echo '<pre>';
				  echo file_get_contents($log_path);
				  echo '</pre>';
				} else {
					echo "The log does not exist.";
				}
			?>
		</div>
		<?php
	}

	/**
	 * If this module has any styles to enqueue, do it in a method
	 * If you have no styles, etc, just ignore this
	 */
	function admin_enqueue_styles() {
		wp_register_style( PF_SLUG . '-debug-style', PF_URL . 'includes/debugger/css/style.css' );
	}
}
