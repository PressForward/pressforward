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

		//Activate the post-type that will archive the incoming RSS feed.
		add_action('init', array($this, 'create_pf_archive_post_type') );

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

	/**Create the archive post type.
	 * Until the CS folks build the 'pressbox' this will pull in RSS items.
	 * Elsewhere it is set up to store them for up to 2 months in the database.
	 * We'll want to clean out anything older, because otherwise the database is likely to get heavy with items.
	**/
	function create_pf_archive_post_type() {
		$args = array(
					'labels' => array(
										'name' => __( 'Archival', 'pf'),
										'singular_name' => __( 'Archival', 'pf')
									),
					'description' => __('Archival posts for saving RSS', 'pf'),
					'public' => false,
					'show_ui' => false,
					'show_in_menu' => false,
					'capability_type' => 'post',
					'supports' => array('title', 'editor', 'thumbnail', 'revisions'),
					//This might need to be switched to false? The documentation isn't really clear on what this does.
					'has_archive' => true
				);

		register_post_type('rssarchival', $args);

	}

	/**
	 * Get the source data object, in a standardized format
	 *
	 * For the moment, all this data comes from the RSS_Import module. In
	 * the future, other modules can hook in to provide their own data
	 * sources.
	 */
	public function source_data_object() {
		// Loop through each module to get its source data
		$source_data_object = array();
		foreach ( $this->modules as $module ) {
			$source_data_object = array_merge( $source_data_object, $module->get_data_object() );
		}
		return $source_data_object;
	}


	public function assemble_feed_for_pull($feedObj = 0) {
		ignore_user_abort(true);
		set_time_limit(0);
		# Chunking control, the goal here is to ensure that no feed assembly occurs while the feed assembly is already occuring.
		$is_chunk_going = get_option( PF_SLUG . '_chunk_assembly_status', 0);
		if ($is_chunk_going === 1){ exit; }
		else { update_option( PF_SLUG . '_chunk_assembly_status', 1 ); }

		# This pulls the RSS feed into a set of predetermined objects.
		# The rss_object function takes care of all the feed pulling and item arraying so we can just do stuff with the feed output.
		if ($feedObj == 0){
			$feedObj = $this->source_data_object();
		}

		# We need to init $sourceRepeat so it can be if 0 if nothing is happening.
		$sourceRepeat = 0;
		# We'll need this for our fancy query.
		global $wpdb;
		# Since rss_object places all the feed items into an array of arrays whose structure is standardized throughout,
		# We can do stuff with it, using the same structure of items as we do everywhere else.
		foreach($feedObj as $item) {
			$thepostscheck = 0;
			$thePostsDoubleCheck = 0;
			$item_id 		= $item['item_id'];
			$sourceRepeat = 0;
			//$queryForCheck = new WP_Query( array( 'post_type' => 'rssarchival', 'meta_key' => 'item_id', 'meta_value' => $item_id ) );
			 # Originally this query tried to get every archive post earlier than 'now' to check.
			 # But it occured to me that, since I'm doing a custom query anyway, I could just query for items with the ID I want.
			 # Less query results, less time.

			 //Perhaps I should do this outside of the foreach? One query and search it for each item_id and then return those not in?
			 $querystr = "
				SELECT $wpdb->posts.*, $wpdb->postmeta.*
				FROM $wpdb->posts, $wpdb->postmeta
				WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
				AND $wpdb->postmeta.meta_key = 'item_id'
				AND $wpdb->postmeta.meta_value = '" . $item_id . "'
				AND $wpdb->posts.post_type = '" . pf_feed_item_post_type() . "'
				ORDER BY $wpdb->posts.post_date DESC
			 ";
			 // AND $wpdb->posts.post_date < NOW() <- perhaps by removing we can better prevent simultaneous duplications?
			 # Since I've altered the query, I could change this to just see if there are any items in the query results
			 # and check based on that. But I haven't yet.
			$checkposts = $wpdb->get_results($querystr, OBJECT);
			//print_r($checkposts);
				if ($checkposts):
					global $post;
					foreach ($checkposts as $post):
						setup_postdata($post);
						//print_r(get_the_ID());
						//print_r('< the ID');
						if ((get_post_meta($post->ID, 'item_id', $item_id, true)) == $item_id){ $thepostscheck++; }
					endforeach;
				endif;
				wp_reset_query();
				if ($thepostscheck == 0){
					$queryMoreStr = "
						SELECT $wpdb->posts.*, $wpdb->postmeta.*
						FROM $wpdb->posts, $wpdb->postmeta
						WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
						AND $wpdb->postmeta.meta_key = 'item_link'
						AND $wpdb->posts.post_type = " . pf_feed_item_post_type() . "
						ORDER BY $wpdb->posts.post_date DESC
					 ";
					$checkpoststwo = $wpdb->get_results($queryMoreStr, OBJECT);
					if ($checkpoststwo):
						foreach ($checkpoststwo as $post):
							setup_postdata($post);

								# Post comparative values.
								$theTitle = $post->post_title;
								$postID = $post->ID;
								$postDate = strtotime($post->post_date);
								$postItemLink = get_post_meta($post->ID, 'item_link', true);
								# Item comparative values.
								$itemDate = strtotime($item['item_date']);
								$itemTitle = $item['item_title'];
								$itemLink = $item['item_link'];

								# First check if it more recent than the currently stored item.
								if((($theTitle == $itemTitle) || ($postItemLink == $itemLink))){
									$thePostsDoubleCheck++;
									$sourceRepeat = get_post_meta($postID, 'source_repeat', true);
									if (($itemDate > $postDate)) {
										# If it is more recent, than this is the new dominant post.
										$sourceRepeat++;
									} elseif (($itemData <= $postDate)) {
										# if it is less recent, then we need to increment the source count.
										$sourceRepeat++;
										if ($thePostsDoubleCheck > $sourceRepeat) {
											update_post_meta($postID, 'source_repeat', $sourceRepeat);
										}
										$thepostscheck++;
									} else {
										$thepostscheck = 0;
									}
								} else {
									# If it isn't duplicated at all, then we need to give it a source repeat count of 0
									$sourceRepeat = 0;
								}


						endforeach;
					endif;
				}
				wp_reset_query();
			# Why an increment here instead of a bool?
			# If I start getting errors, I can use this to check how many times an item is in the database.
			# Potentially I could even use this to clean the database from duplicates that might occur if
			# someone were to hit the refresh button at the same time as another person.


#			$fo = fopen(PF_ROOT . "/modules/rss-import/rss-import.txt", 'a') or print_r('Can\'t open log file.');
#			if ($fo != false){
#				fwrite($fo, "\nSending " . $item['item_title'] . " to post table.");
#				fclose($fo);
#			}
			if ( $thepostscheck == 0) {
				$item_title 	= $item['item_title'];
				$item_content 	= $item['item_content'];
				$item_feat_img 	= $item['item_feat_img'];
				$source_title 	= $item['source_title'];
				$item_date 		= $item['item_date'];
				$item_author 	= $item['item_author'];
				$item_link 		= $item['item_link'];
				$item_wp_date	= $item['item_wp_date'];
				$item_tags		= $item['item_tags'];
				$source_repeat  = $sourceRepeat;

			# Trying to prevent bad or malformed HTML from entering the database.
			$item_content = strip_tags($item_content, '<p> <strong> <bold> <i> <em> <emphasis> <del> <h1> <h2> <h3> <h4> <h5> <a> <img>');
			//$item_content = wpautop($item_content);
			//$postcontent = sanitize_post($item_content);
			//If we use the @ to prevent showing errors, everything seems to work. But it is still dedicating crap to the database...
			//Perhaps sanitize_post isn't the cause? What is then?

			# Do we want or need the post_status to be published?
				$data = array(
					'post_status' => 'published',
					'post_type' => pf_feed_item_post_type(),
					'post_date' => $_SESSION['cal_startdate'],
					'post_title' => $item_title,
					'post_content' => $item_content,

				);

				//RIGHT HERE is where the content is getting assigned a bunch of screwed up tags.
				//The content is coming in from the rss_object assembler a-ok. But something here saves them to the database screwy.
				//It looks like sanitize post is screwing them up terribly. But what to do about it without removing the security measures which we need to apply?

				# The post gets created here, the $newNomID variable contains the new post's ID.
				$newNomID = wp_insert_post( $data );
				//$posttest = get_post($newNomID);
				//print_r($posttest->post_content);

				# Somewhere in the process links with complex queries at the end (joined by ampersands) are getting encoded.
				# I don't want that, so I turn it back here.
				# For some reason this is only happening to the ampersands, so that's the only thing I'm changing.
				$item_link = str_replace('&amp;','&', $item_link);

				# If it doesn't have a featured image assigned already, I use the set_ext_as_featured function to try and find one.
				# It also, if it finds one, sets it as the featured image for that post.

				if ($_POST['item_feat_img'] != ''){
					# Turned off set_ext_as_featured here, as that should only occur when items are nominated.
					# Before nominations, the featured image should remain a meta field with an external link.
					if ( false === ( $itemFeatImg = get_transient( 'feed_img_' . $itemUID ) ) ) {
						set_time_limit(0);
						# Because many systems can't process https through php, we try and remove it.
						$itemLink = pf_de_https($itemLink);
						# if it forces the issue when we try and get the image, there's nothing we can do.
						$itemLink = str_replace('&amp;','&', $itemLink);
						if (OpenGraph::fetch($itemLink)){
							//If there is no featured image passed, let's try and grab the opengraph image.
							$node = OpenGraph::fetch($itemLink);
							$itemFeatImg = $node->image;

						}

						if ($itemFeatImg == ''){
							//Thinking of starting a method here to pull the first image from the body of a post.
							//http://stackoverflow.com/questions/138313/how-to-extract-img-src-title-and-alt-from-html-using-php
							//http://stackoverflow.com/questions/1513418/get-all-images-url-from-string
							//http://stackoverflow.com/questions/7479835/getting-the-first-image-in-string-with-php
							//preg_match_all('/<img[^>]+>/i',$itemContent, $imgResult);
							//$imgScript = $imgResult[0][0];
						}
						//Most RSS feed readers don't store the image locally. Should we?
						set_transient( 'feed_img_' . $itemUID, $itemFeatImg, 60*60*24 );
					}
				}

				# adding the meta info about the feed item to the post's meta.
				add_post_meta($newNomID, 'item_id', $item_id, true);
				add_post_meta($newNomID, 'source_title', $source_title, true);
				add_post_meta($newNomID, 'item_date', $item_date, true);
				add_post_meta($newNomID, 'item_author', $item_author, true);
				add_post_meta($newNomID, 'item_link', $item_link, true);
				add_post_meta($newNomID, 'item_feat_img', $item_feat_img, true);
				// The item_wp_date allows us to sort the items with a query.
				add_post_meta($newNomID, 'item_wp_date', $item_wp_date, true);
				//We can't just sort by the time the item came into the system (for when mult items come into the system at once)
				//So we need to create a machine sortable date for use in the later query.
				add_post_meta($newNomID, 'sortable_item_date', strtotime($item_date), true);
				add_post_meta($newNomID, 'item_tags', $item_tags, true);
				add_post_meta($newNomID, 'source_repeat', $source_repeat, true);
			}

		}
		update_option( PF_SLUG . '_chunk_assembly_status', 0 );
		pf_rss_import::advance_feeds();
		//die('Refreshing...');

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
