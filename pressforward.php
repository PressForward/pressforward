<?php
/*
Plugin Name: PressForward
Plugin URI: http://pressforward.org/
Description: This plugin is a aggregation parser for CHNM's Press Forward project.
Version: 1.6.1
Author: Aram Zucker-Scharff, Boone B Gorges
Author URI: http://aramzs.me, http://boone.gorg.es/
License: GPL2
*/

/*  Developed for the Center for History and New Media

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Set up some constants
define( 'PF_SLUG', 'pf' );
define( 'PF_TITLE', 'PressForward' );
define( 'PF_MENU_SLUG', PF_SLUG . '-menu' );
define( 'PF_NOM_EDITOR', 'edit.php?post_type=nomination' );
define( 'PF_NOM_POSTER', 'post-new.php?post_type=nomination' );
define( 'PF_ROOT', dirname(__FILE__) );
define( 'PF_FILE_PATH', PF_ROOT . '/' . basename(__FILE__) );
define( 'PF_URL', plugins_url('/', __FILE__) );

class PressForward {
	var $modules = array();
	var $admin;

	// See http://php.net/manual/en/language.oop5.decon.php to get a better understanding of what's going on here.
	function __construct() {

		$this->includes();

		$this->set_up_schema();
		$this->set_up_nominations();
		$this->set_up_admin();

		add_action( 'admin_init', array($this, 'pf_options_admin_page_save') );

		add_filter('the_author', array($this, 'replace_author_presentation'));
		add_filter( 'author_link', array($this, 'replace_author_uri_presentation') );

		// Set up modules
		add_action( 'pressforward_init', array( $this, 'setup_modules' ), 1000 );

		add_action( 'plugins_loaded', array( $this, 'pressforward_init' ) );

		load_plugin_textdomain( 'pf', false, PF_ROOT );

	}

	/**
	 * Include necessary files
	 *
	 * @since 1.7
	 */
	function includes() {

		// External libraries

		// Pull and parse Open Graph data from a page.
		require( PF_ROOT . "/lib/OpenGraph.php" );

		// Check the HTML of each item for open tags and close them.
		// I've altered it specifically for some odd HTML artifacts that occur when
		// WP sanitizes the content input.
		require( PF_ROOT . "/lib/htmlchecker.php" );

		// A slightly altered version of the Readability library from Five Filters,
		// who based it off readability.com's code.
		require( PF_ROOT . "/lib/fivefilters-readability/Readability.php" );

		// For reading through an HTML page.
		require( PF_ROOT . "/lib/simple_html_dom.php" );
		$dom = new simple_html_dom;

		// Internal tools

		// Load the module base class and our test module
		require( PF_ROOT . "/includes/functions.php" );
		require( PF_ROOT . "/includes/module-base.php" );
		require( PF_ROOT . '/includes/schema.php' );
		require( PF_ROOT . '/includes/readable.php' );
		require( PF_ROOT . '/includes/feed-items.php' );
		require( PF_ROOT . '/includes/relationships.php' );
		require( PF_ROOT . '/includes/nominations.php' );
		require( PF_ROOT . '/includes/admin.php' );
	}

	/**
	 * Sets up the Dashboard admin
	 *
	 * @since 1.7
	 */
	function set_up_schema() {
		if ( empty( $this->schema ) ) {
			$this->schema = new PF_Feed_Item_Schema;
		}
	}

	/**
	 * Sets up the Dashboard admin
	 *
	 * @since 1.7
	 */
	function set_up_nominations() {
		if ( empty( $this->nominations ) ) {
			$this->nominations = new PF_Nominations;
		}
	}

	/**
	 * Sets up the Dashboard admin
	 *
	 * @since 1.7
	 */
	function set_up_admin() {
		if ( empty( $this->admin ) ) {
			$this->admin = new PF_Admin;
		}
	}

	/**
	 * Fire the pressforward_init action, to let plugins know that our
	 * libraries are available
	 */
	function pressforward_init() {
		do_action( 'pressforward_init' );
	}

	/**
	 * Locate and load modules
	 *
	 * This method supports loading our packaged modules, as well as those
	 * provided by plugins
	 */
	function setup_modules() {

		$module_args = array();

		// Scrape the built-in modules
		$module_dirs = scandir( PF_ROOT . '/modules/' );
		foreach ( $module_dirs as $module_dir ) {
			// Skip hidden items
			if ( '.' == substr( $module_dir, 0, 1 ) ) {
				continue;
			}

			if ( file_exists( PF_ROOT . "/modules/{$module_dir}/{$module_dir}.php" ) ) {
				include_once( PF_ROOT . "/modules/{$module_dir}/{$module_dir}.php" );

				// Prepare the class name
				$tmp = explode( '-', $module_dir );
				$tmp = array_map( 'ucwords', $tmp );
				$class_name = 'PF_' . implode( '_', $tmp );

				$module_args[] = array(
					'slug' => $module_dir,
					'class' => $class_name
				);
			}
		}

		// Plugins should not filter this array directly. Use
		// pressforward_register_module() instead
		$plugin_module_args = apply_filters( 'pressforward_register_modules', array() );

		$module_args = array_merge( $module_args, $plugin_module_args );
		foreach ( $module_args as $module ) {
			$this->modules[ $module['slug'] ] = new $module['class'];
		}

		do_action( 'pf_setup_modules', $this );
	}

	/**
	 * Get the feed item post type
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public function get_feed_item_post_type() {
		if ( isset( $this->schema ) ) {
			return $this->schema->feed_item_post_type;
		}

		return '';
	}

	/**
	 * Get the feed item tag taxonomy
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public function get_feed_item_tag_taxonomy() {
		if ( isset( $this->schema ) ) {
			return $this->schema->feed_item_tag_taxonomy;
		}

		return '';
	}

	# This function takes measures to try and get item content throguh methods of increasing reliability, but decreasing relevance.
	public function get_content_through_aggregator($url){

		set_time_limit(0);
		//$this->set_error_handler("customError");
		$url = pf_de_https($url);
		$descrip = '';
		//$url = http_build_url($urlParts, HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT);
		//print_r($url);
		# First run it through Readability.
		$descrip = PF_Readability::readability_object($url);
		//print_r($url);
		# If that doesn't work...
		if (!$descrip) {
			$url = str_replace('&amp;','&', $url);
			#Try and get the OpenGraph description.
			if (OpenGraph::fetch($url)){
				$node = OpenGraph::fetch($url);
				$descrip = $node->description;
			} //Note the @ below. This is because get_meta_tags doesn't have a failure state to check, it just throws errors. Thanks PHP...
			elseif ('' != ($contentHtml = @get_meta_tags($url))) {
				# Try and get the HEAD > META DESCRIPTION tag.
				$descrip = $contentHtml['description'];
				print_r($url . ' has no meta OpenGraph description we can find.');

			}
			else
			{
				# Ugh... we can't get anything huh?
				print_r($url . ' has no description we can find.');
				# We'll want to return a false to loop with.
				$descrip = false;

				break;
			}
		}
		return $descrip;

	}

	function pf_options_admin_page_save() {
		global $pagenow;

		if ( 'admin.php' != $pagenow ) {
			return;
		}

		if ( empty( $_POST['submit'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'pf_settings' );

		do_action( 'pf_admin_op_page_save' );
	}

	//Ref for eventual building of nomination ajax:
	//https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/editorial-comments.php ln284	//https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/lib/editorial-comments.js
	//https://github.com/danielbachhuber/Edit-Flow/blob/master/edit_flow.php

	//http://codex.wordpress.org/Class_Reference/WP_Query#Time_Parameters
	function get_posts_after_for_check( $theDate, $post_type ) {
		global $wpdb;
		$querystr = "
			SELECT $wpdb->posts.*
			FROM $wpdb->posts, $wpdb->postmeta
			WHERE $wpdb->posts.post_type = '" . $post_type . "'
			AND $wpdb->posts.post_date >= '". $theDate . "'
			ORDER BY $wpdb->posts.post_date DESC
			";

		$postsAfter = $wpdb->get_results($querystr, OBJECT);

		return $postsAfter;
	}


	function set_ext_as_featured($postID,$ogImage){

			if ( (strlen($ogImage)) > 0 ){

				//Remove Queries from the URL
				$ogImage = preg_replace('/\?.*/', '', $ogImage);

				$imgParts = pathinfo($ogImage);
				$imgExt = $imgParts['extension'];
				$imgTitle = $imgParts['filename'];

				if ($imgExt != ('jpg'||'png'||'jrpg'||'bmp'||'gif')){
					//print_r('bad og img');
					return;
				}


				//'/' . get_option(upload_path, 'wp-content/uploads') . '/' . date("o")
				$uploadDir = wp_upload_dir();
				$ogCacheImg = $uploadDir['path'] . $postID . "-" . $imgTitle . "." . $imgExt;

				if ( !file_exists($ogCacheImg) ) {


					$result  = copy($ogImage, $ogCacheImg);


				}
		}

		//Methods within sourced from http://codex.wordpress.org/Function_Reference/wp_insert_attachment
		//and http://wordpress.stackexchange.com/questions/26138/set-post-thumbnail-with-php

		//Get the type of the image file. .jpg, .gif, or whatever
		$filetype = wp_check_filetype( $ogCacheImg );

		//Set the identifying variables for the about to be featured image.
		$imgData = array(
						//tell WordPress what the filetype is.
						'post_mime_type' => $filetype['type'],
						//set the image title to the title of the site you are pulling from
						'post_title' => '',
						//WordPress tells us we must set this and set it to empty. Why? Dunno.
						'post_content' => '',
						//Now we set the status of the image. It will inheret that of the post.
						//If the post is published, then the image will be to.
						'post_status' => 'inherit'
					);
		//WordPress needs an absolute path to the image, as opposed to the relative path we used before.
		//I'm hoping that by using the upload_dir function (above) I can make this function work with multisite.
		//$pathedImg = $uploadDir['url'] . $img;
		//Now we insert the image as a WordPress attachement, and associate it with the current post.
		$thumbid = wp_insert_attachment($imgData, $ogCacheImg, $postID);

		//To set a thumbnail, you need metadata associated with an image.
		//To get that we need to call the image.php file
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$metadata = wp_generate_attachment_metadata( $thumbid, $ogCacheImg );
		//Now we attach the meta data to the image.
		wp_update_attachment_metadata( $thumbid, $metadata );

		//Now that we have a correctly meta-ed and attached image we can finally turn it into a post thumbnail.
		update_post_meta($postID, '_thumbnail_id', $thumbid);


	}


	function ajax_user_option_set(){
		//Function to set user options via AJAX.
		/** Requires AJAX to send a name, slug and value in the forms of:
			user_op_slug
			user_op_value
			user_op_name
		**/
		$pf_user_nonce = $_POST['pf_user_nonce'];
		if (! wp_verify_nonce($pf_user_nonce, 'user')){
			die($this->__('Nonce not recieved. Are you sure you should be setting user options?', 'pf'));
		} else {
			$current_user = wp_get_current_user();
			$user_op_bool = update_user_option($current_user->ID, $_POST['user_op_slug'], $_POST['user_op_value'], true);
			if ($user_op_bool){
				print_r('User option set:' . $_POST['user_op_name']);
			} else {
				print_r('User option not set:' . $_POST['user_op_name']);
			}
			die();
		}
	}

	//Based on http://seoserpent.com/wordpress/custom-author-byline

	function replace_author_presentation( $author ) {

		global $post;
		$custom_author = get_post_meta($post->ID, 'authors', TRUE);
		if($custom_author)
			return $custom_author;
		return $author;

	}

	function replace_author_uri_presentation( $author_uri ) {

		//global $authordata;
		global $post, $authordata;
		$custom_author_uri = get_post_meta($post->ID, 'nomination_permalink', TRUE);
		if($custom_author_uri)
			return $custom_author_uri;
		return $author_uri;

	}



}

/**
 * Bootstrap
 *
 * You can also use this to get a value out of the global, eg
 *
 *    $foo = pressforward()->bar;
 *
 * @since 1.7
 */
function pressforward() {
	global $pf;
	if ( ! is_a( $pf, 'PressForward' ) ) {
		$pf = new PressForward();
	}
	return $pf;
}

// Start er up!
pressforward();

function pressforward_register_module( $args ) {
	$defaults = array(
		'slug' => '',
		'class' => '',
	);
	$r = wp_parse_args( $args, $defaults );

	// We need the 'class' and 'slug' terms
	if ( empty( $r['class'] ) || empty( $r['slug'] ) ) {
		continue;
	}

	// Ensure the class exists before attempting to initialize it
	// @todo Should probably have better error reporting
	if ( ! class_exists( $r['class'] ) ) {
		continue;
	}

	add_filter( 'pressforward_register_modules', create_function( '$modules', '
		return array_merge( $modules, array( array(
			"slug"  => "' . $r['slug']  . '",
			"class" => "' . $r['class'] . '",
		) ) );
	' ) );
}
