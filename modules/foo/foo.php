<?php

/**
 * Test of module base class
 */

class RSSPF_Foo extends RSSPF_Module {
	function __construct() {
		parent::start();
		add_filter( 'dash_widget_bar', array($this, 'foo_widget') );
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

	function admin_menu_callback() {
		?>
		<div class="wrap">
			<h2>Foo</h2>
			<p>Foo bar</p>
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
	function foo_widget($widgets_array){
		$foo_widgets_array = array(
									'second_widget' => array(
														'title' => 'Foo Title',
														'slug' => 'foo_widget',
														'callback' => $this->foobody()
													)
								);
		$widgets = array_merge($widgets_array, $foo_widgets_array);						
		return 	$widgets;					
	}
	
	function foobody() {
		return 'foo.';
	}
}
