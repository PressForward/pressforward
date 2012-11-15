<?php

/**
 * Test of module base class
 */

class RSSPF_Foo extends RSSPF_Module {
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
			'page_title' => __( 'Foo', 'rsspf' ),
			'menu_title' => __( 'Foo', 'rsspf' ),
			'cap'        => 'edit_posts',
			'slug'       => 'rsspf-foo',
			'callback'   => array( $this, 'admin_menu_callback' ),
		);

		parent::setup_admin_menus( $admin_menus );
	}
	
	function setup_module() {
		$enabled = get_option( 'rsspf_foo_enable' );
		if ( ! in_array( $enabled, array( 'yes', 'no' ) ) ) {
			$enabled = 'yes';
		}
		
		$mod_settings = array(
			'name' => 'Foo Test Module',
			'slug' => 'foo',
			'options' => ''
		);
		
		//update_option( 'rsspf_foo_settings', $mod_settings );

		
	}
	
	function module_setup(){
		$mod_settings = array(
			'name' => 'Foo Test Module',
			'slug' => 'foo',
			'description' => 'This module provides a set of test functions for developers to check.',
			'thumbnail' => '',
			'options' => ''
		);
		
		update_option( RSSPF_SLUG . '_' . $this->id . '_settings', $mod_settings );	

		//return $test;
	}	
	
	function admin_menu_callback() {
		?>
		<div class="wrap">
			<h2>Foo</h2>
			<p>Foo bar</p>
			<p><?php echo $this->id ?></p>
		</div>
		<?php
	}

	/**
	 * If this module has any styles to enqueue, do it in a method
	 * If you have no styles, etc, just ignore this
	 */
	function admin_enqueue_styles() {
		wp_register_style( RSSPF_SLUG . '-foo-style', RSSPF_URL . 'includes/foo/css/style.css' );
	}
	function pf_add_dash_widgets() {
		$foo_widgets_array = array(
									'second_widget' => array(
														'title' => 'Foo Title',
														'slug' => 'foo_widget',
														'callback' => array( $this, 'foobody')
													)
								);
								
		return $foo_widgets_array;
		
	}
	
	function foobody() {
		echo 'foo.';
	}
}
