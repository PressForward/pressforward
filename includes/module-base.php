<?php

/**
 * Base class for PressForward RSS modules
 */
class RSSPF_Module {

	function start() {
		$this->setup_hooks();
	}

	function setup_hooks() {
		// Run at 15 to make sure the core menu is loaded first
		add_action( 'admin_menu', array( $this, 'setup_admin_menus' ), 15 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );	// There's no admin_enqueue_styles action

		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_enqueue_styles',  array( $this, 'wp_enqueue_styles' ) );
		add_action( RSSPF_SLUG . '_feeder_menu', array( $this, 'add_to_feeder' ) );
	}

	function setup_admin_menus( $admin_menus ) {
		foreach ( (array) $admin_menus as $admin_menu ) {
			$defaults = array(
				'page_title' => '',
				'menu_title' => '',
				'cap'        => 'edit_posts',
				'slug'       => '',
				'callback'   => '',
			);
			$r = wp_parse_args( $admin_menu, $defaults );

			// add_submenu_page() will fail if any arguments aren't passed
			if ( empty( $r['page_title'] ) || empty( $r['menu_title'] ) || empty( $r['cap'] ) || empty( $r['slug'] ) || empty( $r['callback'] ) ) {
				continue;
			}

			add_submenu_page( RSSPF_MENU_SLUG, $r['page_title'], $r['menu_title'], $r['cap'], $r['slug'], $r['callback'] );
		}
	}
/**
	function setup_dash_widgets( $dash_widgets ) {
		foreach ( (array) $dash_widgets as $dash_widget ) {
			$defaults = array(
				'widget_title' => '',
				'slug'       => '',
				'callback'   => '',
			);
			$r = wp_parse_args( $dash_widget, $defaults );

			// add_submenu_page() will fail if any arguments aren't passed
			if ( empty( $r['widget_title'] ) || empty( $r['slug'] ) || empty( $r['callback'] ) ) {
				continue;
			}

			//add_action( RSSPF_MENU_SLUG, $r['page_title'], $r['menu_title'], $r['cap'], $r['slug'], $r['callback'] );
		}
	}	
**/	
	// Fetch and return a formatted data object - optional
	function get_data_object() { return array(); }

	// Scripts and styles - optional
	function admin_enqueue_scripts() {}
	function admin_enqueue_styles() {}
	function wp_enqueue_scripts() {}
	function wp_enqueue_styles() {}
	function add_to_feeder() {}

}
