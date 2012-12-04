<?php
/*
Plugin Name: RSS to PressForward
Plugin URI: http://aramzs.me
Description: This plugin is a RSS parser for CHNM's Press Forward project.
Version: 1.1.1
Author: Aram Zucker-Scharff
Author URI: http://aramzs.me
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
define( 'RSSPF_SLUG', 'rsspf' );
define( 'RSSPF_TITLE', 'RSS to Press Forward' );
define( 'RSSPF_MENU_SLUG', RSSPF_SLUG . '-menu' );
define( 'RSSPF_NOM_EDITOR', 'edit.php?post_type=nomination' );
define( 'RSSPF_NOM_POSTER', 'post-new.php?post_type=nomination' );
define( 'RSSPF_ROOT', dirname(__FILE__) );
define( 'RSSPF_FILE_PATH', RSSPF_ROOT . '/' . basename(__FILE__) );
define( 'RSSPF_URL', plugins_url('/', __FILE__) );

//This adds the library we're going to use to pull and parse Open Graph data from a page.
require_once(RSSPF_ROOT . "/lib/OpenGraph.php");
/** This is the function to check the HTML of each item for open tags and close them.
 * I've altered it specifically for some odd HTML artifacts that occur when WP sanitizes the content input.
**/
require_once(RSSPF_ROOT . "/lib/htmlchecker.php");
//A slightly altered version of the Readability library from Five Filters, who based it off readability.com's code.
require_once(RSSPF_ROOT . "/lib/fivefilters-readability/Readability.php");

//For reading through an HTML page.
require_once(RSSPF_ROOT . "/lib/simple_html_dom.php");
//require_once(RSSPF_ROOT . "/includes/linkfinder/AB_subscription_builder.php");
$dom = new simple_html_dom;

// Load the module base class and our test module
include( RSSPF_ROOT . "/includes/module-base.php" );

class rsspf {
	var $modules = array();

	// See http://php.net/manual/en/language.oop5.decon.php to get a better understanding of what's going on here.
	function __construct() {

		//Activate the admin menu creation function.
		add_action('admin_menu', array($this, 'register_rsspf_custom_menu_pages') );
		//Activate the nominations post-type
		add_action('init', array($this, 'create_rsspf_nomination_post_type') );
		//Activate the post-type that will archive the incoming RSS feed.
		add_action('init', array($this, 'create_rsspf_archive_post_type') );
		//Adding javascript and css to admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		
		add_action( 'admin_init', array($this, 'rsspf_options_admin_page_save') );	


		/** Some actions are only needed inside of the admin area.
		* These are they.
		*/
		if( is_admin() )
		{
		add_action( 'wp_ajax_nopriv_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_nopriv_assemble_feed_for_pull', array($this, 'assemble_feed_for_pull') );
		add_action( 'wp_ajax_assemble_feed_for_pull', array( $this, 'assemble_feed_for_pull') );
		add_action( 'wp_ajax_nopriv_reset_feed', array($this, 'reset_feed') );
		add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed') );
		}
		add_action('edit_post', array( $this, 'send_nomination_for_publishing'));
		add_filter( 'manage_edit-nomination_columns', array ($this, 'edit_nominations_columns') );
		add_action( 'manage_nomination_posts_custom_column',  array ($this, 'nomination_custom_columns') );

		add_filter('the_author', array($this, 'replace_author_presentation'));
		add_filter( 'author_link', array($this, 'replace_author_uri_presentation') );
		add_filter( "manage_edit-nomination_sortable_columns", array ($this, "nomination_sortable_columns") );

		//Activate our cron actions
		add_action('init', array($this, 'scheduale_feed_in') );
		add_action('init', array($this, 'scheduale_feed_out') );
		
		//Register options
		add_action( 'init', array( $this, 'feeder_options_init' ) );

		//The take_feed_out action is now initiated, we should be able to attach our feed disassembly function.
		add_action( 'take_feed_out', array($this, 'disassemble_feed_items') );
		//The pull_feed_in action is now initiated, we should be able to attach our feed assembly function.
		add_action( 'pull_feed_in', array($this, 'assemble_feed_for_pull') );

		// Set up modules
		add_action( 'pressforward_init', array( $this, 'setup_modules' ), 1000 );

		add_action( 'init', array( $this, 'pressforward_init' ), 20 );
		
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
		$module_dirs = scandir( RSSPF_ROOT . '/modules/' );
		foreach ( $module_dirs as $module_dir ) {
			// Skip hidden items
			if ( '.' == substr( $module_dir, 0, 1 ) ) {
				continue;
			}

			if ( file_exists( RSSPF_ROOT . "/modules/{$module_dir}/{$module_dir}.php" ) ) {
				include_once( RSSPF_ROOT . "/modules/{$module_dir}/{$module_dir}.php" );

				// Prepare the class name
				$tmp = explode( '-', $module_dir );
				$tmp = array_map( 'ucwords', $tmp );
				$class_name = 'RSSPF_' . implode( '_', $tmp );

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

		do_action( 'rsspf_setup_modules', $this );
	}

	//Create the menus for this plugin
	function register_rsspf_custom_menu_pages() {

		/*
			First create the plugin menu, with the following variables
			The page title (in title tags)
			The menu title (on screen title)
			The permissions level required for access (edit_posts makes it accessable to Contributor level users and above).
			The menu's slug.
			The function to build the menu.
			The icon URL.
			The menu position (25 is Comments' menu position, so 24 should put it right above the Comments menu entry).
		*/
		add_menu_page (RSSPF_TITLE, RSSPF_TITLE, 'edit_posts', RSSPF_MENU_SLUG, array($this, 'rsspf_reader_builder'), RSSPF_URL . '/rss-forward-16.png', 24);

		add_submenu_page(RSSPF_MENU_SLUG, 'All Content', 'All Content', 'edit_posts', RSSPF_MENU_SLUG, array($this, 'rsspf_reader_builder'));

		add_submenu_page(RSSPF_MENU_SLUG, 'Under Review', 'Under Review', 'edit_posts', RSSPF_NOM_EDITOR);

		//Now create an options page for the plugin. This page is only accessable to Administrative level users.
		add_submenu_page(RSSPF_MENU_SLUG, RSSPF_TITLE . ' Options', RSSPF_TITLE . ' Options', 'manage_options', RSSPF_SLUG . '-options', array($this, 'rsspf_options_builder'));

		//Now create an feed-listing page for the plugin, where the user can add feeds. This page is Editor level users and above.
		add_submenu_page(RSSPF_MENU_SLUG, RSSPF_TITLE . ' Feeder', RSSPF_TITLE . ' Feeder', 'edit_others_posts', RSSPF_SLUG . '-feeder', array($this, 'rsspf_feeder_builder'));

		add_submenu_page(RSSPF_MENU_SLUG, 'Add Nomination', 'Add Nomination', 'edit_posts', RSSPF_NOM_POSTER);

	}

	//Create the post type
	function create_rsspf_nomination_post_type() {
		$args = array(
					'labels' => array(
										'name' => __( 'Nominations' ),
										'singular_name' => __( 'Nomination' ),
										'add_new' => __('Nominate'),
										'add_new_item' => __('Add New Nomination'),
										'edit_item' => __('Edit Nomination'),
										'new_item' => __('New Nomination'),
										'view_item' => __('View Nomination'),
										'search_items' => __('Search Nominations'),
										'not_found' => __('No nominations found'),
										'not_found_in_trash' => __('No nominations found in Trash')
									),
					'description' => 'Posts from around the internet nominated for consideration to public posting',
					//Not available to non-users.
					'public' => false,
					//I want a UI for users to use, so true.
					'show_ui' => true,
					//But not the default UI, we want to attach it to the plugin menu.
					'show_in_menu' => false,
					//Linking in the metabox building function.
					'register_meta_box_cb' => array($this, 'nominations_meta_boxes'),
					'capability_type' => 'post',
					//The type of input (besides the metaboxes) that it supports.
					'supports' => array('title', 'editor', 'thumbnail', 'revisions'),
					//I think this is set to false by the public argument, but better safe.
					'has_archive' => false
				);

		register_post_type('nomination', $args);

	}

	/**Create the archive post type.
	 * Until the CS folks build the 'pressbox' this will pull in RSS items.
	 * Elsewhere it is set up to store them for up to 2 months in the database.
	 * We'll want to clean out anything older, because otherwise the database is likely to get heavy with items.
	**/
	function create_rsspf_archive_post_type() {
		$args = array(
					'labels' => array(
										'name' => __( 'Archival' ),
										'singular_name' => __( 'Archival' )
									),
					'description' => 'Archival posts for saving RSS',
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

	// Our first cron job. This scheduales hourly pulls of the rss feed(s).
	function scheduale_feed_in() {
		//Check to make sure it isn't already schedualed.
		if ( ! wp_next_scheduled( 'pull_feed_in' ) ) {
		 //Scheduale the pull_feed_in action to go off every hour.
		  wp_schedule_event( time(), 'hourly', 'pull_feed_in' );
		}
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

	public function assemble_feed_for_pull() {
		# This pulls the RSS feed into a set of predetermined objects.
		# The rss_object function takes care of all the feed pulling and item arraying so we can just do stuff with the feed output.
		$feedObj = $this->source_data_object();

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
				AND $wpdb->posts.post_type = 'rssarchival'
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
						AND $wpdb->posts.post_type = 'rssarchival'
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
					'post_type' => 'rssarchival',
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
						$itemLink = $this->de_https($itemLink);
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

		//die('Refreshing...');

	}

// Create a new filtering function that will add our where clause to the query
	function filter_where_older_sixty_days( $where = '' ) {
		// posts before the last 60 days
		$where .= " AND post_date < '" . date('Y-m-d', strtotime('-60 days')) . "'";
		return $where;
	}

	# Creating the action to, once a month, check for items older than two months and remove them from the database.
	function scheduale_feed_out() {
		if ( ! wp_next_scheduled( 'take_feed_out' ) ) {
		  wp_schedule_event( time(), 'monthly', 'take_feed_out' );
		}
	}

	# The function we add to the action to clean our database.
	function disassemble_feed_items() {
		//delete rss feed items with a date past a certian point.
		add_filter( 'posts_where', array($this, 'filter_where_older_sixty_days') );
		$queryForDel = new WP_Query( array( 'post_type' => 'rssarchival' ) );
		remove_filter( 'posts_where', array($this, 'filter_where_older_sixty_days') );

		// The Loop
		while ( $queryForDel->have_posts() ) : $queryForDel->the_post();
			# All the posts in this loop are older than 60 days from 'now'.
			# Delete them all.
			$postid = get_the_ID();
			wp_delete_post( $postid, true );

		endwhile;

		// Reset Post Data
		wp_reset_postdata();

	}

	# Method to manually delete rssarchival entries on user action.
	function reset_feed() {
		global $wpdb, $post;
		//$args = array(
		//				'post_type' => array('any')
		//			);
		$args = 'post_type=rssarchival';
		//$archiveQuery = new WP_Query( $args );
		$dquerystr = "
			SELECT $wpdb->posts.*, $wpdb->postmeta.*
			FROM $wpdb->posts, $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND $wpdb->posts.post_type = 'rssarchival'
		 ";
		# This is how we do a custom query, when WP_Query doesn't do what we want it to.
		$rssarchivalposts = $wpdb->get_results($dquerystr, OBJECT);
		//print_r(count($rssarchivalposts)); die();
		$rssObject = array();
		$c = 0;

		if ($rssarchivalposts):

			foreach ($rssarchivalposts as $post) :
			# This takes the $post objects and translates them into something I can do the standard WP functions on.
			setup_postdata($post);
			$post_id = get_the_ID();
			//Switch the delete on to wipe rss archive posts from the database for testing.
			wp_delete_post( $post_id, true );
			endforeach;


		endif;
		wp_reset_postdata();
		print_r('All archives deleted.');

	}

	# This function feeds items to our display feed function rsspf_reader_builder.
	# It is just taking our database of rssarchival items and putting them into a
	# format that the builder understands.
	public function archive_feed_to_display($pageTop = 0) {
		global $wpdb, $post;
		//$args = array(
		//				'post_type' => array('any')
		//			);
		//$pageBottom = $pageTop + 20;
		$args = 'post_type=rssarchival';
		//$archiveQuery = new WP_Query( $args );
		 $dquerystr = "
			SELECT $wpdb->posts.*, $wpdb->postmeta.*
			FROM $wpdb->posts, $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND $wpdb->posts.post_type = 'rssarchival'
			AND $wpdb->postmeta.meta_key = 'sortable_item_date'
			ORDER BY $wpdb->postmeta.meta_value DESC
			LIMIT $pageTop, 20
		 ";
		 # DESC here because we are sorting by UNIX datestamp, where larger is later.
		 //Provide an alternative to load by feed date order.
		# This is how we do a custom query, when WP_Query doesn't do what we want it to.
		$rssarchivalposts = $wpdb->get_results($dquerystr, OBJECT);
		//print_r(count($rssarchivalposts)); die();
		$rssObject = array();
		$c = 0;

		if ($rssarchivalposts):

			foreach ($rssarchivalposts as $post) :
			# This takes the $post objects and translates them into something I can do the standard WP functions on.
			setup_postdata($post);
			# I need this data to check against existing transients.
			$post_id = get_the_ID();
			$id = get_post_meta($post_id, 'item_id', true); //die();
			//Switch the delete on to wipe rss archive posts from the database for testing.
			//wp_delete_post( $post_id, true );
			//print_r($id);
			# If the transient exists than there is no reason to do any extra work.
			if ( false === ( $rssObject['rss_archive_' . $c] = get_transient( 'rsspf_archive_' . $id ) ) ) {

				$item_id = get_post_meta($post_id, 'item_id', true);
				$source_title = get_post_meta($post_id, 'source_title', true);
				$item_date = get_post_meta($post_id, 'item_date', true);
				$item_author = get_post_meta($post_id, 'item_author', true);
				$item_link = get_post_meta($post_id, 'item_link', true);
				$item_feat_img = get_post_meta($post_id, 'item_feat_img', true);
				$item_wp_date = get_post_meta($post_id, 'item_wp_date', true);
				$item_tags = get_post_meta($post_id, 'item_tags', true);
				$source_repeat = get_post_meta($post_id, 'source_repeat', true);

				$contentObj = new htmlchecker(get_the_content());
				$item_content = $contentObj->closetags(get_the_content());

				$rssObject['rss_archive_' . $c] = $this->feed_object(
											get_the_title(),
											$source_title,
											$item_date,
											$item_author,
											$item_content,
											$item_link,
											$item_feat_img,
											$item_id,
											$item_wp_date,
											$item_tags,
											//Manual ISO 8601 date for pre-PHP5 systems.
											get_the_date('o-m-d\TH:i:sO'),
											$source_repeat
											);
				set_transient( 'rsspf_archive_' . $id, $rssObject['rss_archive_' . $c], 60*10 );

			}
			$c++;
			endforeach;


		endif;
		wp_reset_postdata();
		return $rssObject;
	}

	# This and the next few functions are to modify the table that shows up when you click "Nominations".
	function edit_nominations_columns ( $columns ){

		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'date' => 'Last Modified',
			'nomcount' => 'Nominations',
			'nominatedby' => 'Nominated By',
			'original_author' => 'Original Author',
			'date_nominated' => 'Date Nominated'
		);

		return $columns;

	}

	//Via http://slides.helenhousandi.com/wcnyc2012.html#15 and http://svn.automattic.com/wordpress/tags/3.4/wp-admin/includes/class-wp-posts-list-table.php
	function nomination_custom_columns ( $column ) {

		global $post;
		switch ($column) {
			case 'nomcount':
				echo get_post_meta($post->ID, 'nomination_count', true);
				break;
			case 'nominatedby':
				$nominatorID = get_post_meta($post->ID, 'submitted_by', true);
				$user = get_user_by('id', $nominatorID);
				echo $user->display_name;
				break;
			case 'original_author':
				$orig_auth = get_post_meta($post->ID, 'authors', true);
				echo $orig_auth;
				break;
			case 'date_nominated':
				$dateNomed = get_post_meta($post->ID, 'date_nominated', true);
				echo $dateNomed;
				break;


		}
	}

	// Make these columns sortable
	function nomination_sortable_columns() {
	  return array(
		'title' => 'title',
		'date' => 'date',
		'nomcount' => 'nomcount',
		'nominatedby' => 'nominatedby',
		'original_author' => 'original_author',
		'date_nominated' => 'date_nominated'
	  );
	}

	# Meta boxes to show up in nomination editing pages.
	public function nominations_meta_boxes() {
		global $post;

		add_meta_box('rsspf-nominations', 'Nomination Data', array($this, 'nominations_box_builder'), 'nomination', 'side', 'high' );

	}

	# The builder for the box that shows us the nomination metadata.
	public function nominations_box_builder() {
		global $post;
		//wp_nonce_field( 'nominate_meta', 'nominate_meta_nonce' );
		$origin_item_ID = get_post_meta($post->ID, 'origin_item_ID', true);
		$nomination_count = get_post_meta($post->ID, 'nomination_count', true);
		$submitted_by = get_post_meta($post->ID, 'submitted_by', true);
		$source_title = get_post_meta($post->ID, 'source_title', true);
		$posted_date = get_post_meta($post->ID, 'posted_date', true);
		$nom_authors = get_post_meta($post->ID, 'authors', true);
		$nomination_permalink = get_post_meta($post->ID, 'nomination_permalink', true);
		$date_nominated = get_post_meta($post->ID, 'date_nominated', true);
		$user = get_user_by('id', $submitted_by);
		$item_tags = get_post_meta($post->ID, 'item_tags', true);
		$source_repeat = get_post_meta($post->ID, 'source_repeat', true);
		echo '<strong>Item ID</strong>: ' . $origin_item_ID . '<br />';
		echo '<strong>Nomination Count</strong>: ' . $nomination_count . '<br />';
		echo '<strong>Submitted By</strong>: ' . $user->display_name . '<br />';
		echo '<strong>Feed Title</strong>: ' . $source_title . '<br />';
		echo '<strong>Source Posted</strong>: ' . $posted_date . '<br />';
		echo '<strong>Source Authors</strong>: ' . $nom_authors . '<br />';
		echo '<strong>Source Link</strong>: <a href="' . $nomination_permalink . '" target="_blank">Original Post</a><br />';
		echo '<strong>Item Tags</strong>: ' . $item_tags . '<br />';
		echo '<strong>Date Nominated</strong>: ' . $date_nominated . '<br />';
		echo '<strong>Repeated in Feed</strong>: ' . $source_repeat . '<br />';

	}

	# Here's where we build the core object that we use to pass everything around in a standardized way.
	# Perhaps it should take this as an array?
	public function feed_object( $itemTitle='', $sourceTitle='', $itemDate='', $itemAuthor='', $itemContent='', $itemLink='', $itemFeatImg='', $itemUID='', $itemWPDate='', $itemTags='', $addedDate='', $sourceRepeat='' ) {

		# Assemble all the needed variables into our fancy object!
		$itemArray = array(

						'item_title' 	=> 	$itemTitle,
						'source_title' 	=>	$sourceTitle,
						'item_date'		=>	$itemDate,
						'item_author'	=>	$itemAuthor,
						'item_content'	=>	$itemContent,
						'item_link'		=>	$itemLink,
						'item_feat_img'	=>	$itemFeatImg,
						'item_id'		=>	$itemUID,
						'item_wp_date'	=>  $itemWPDate,
						'item_tags'		=>	$itemTags,
						'item_added_date' => $addedDate,
						'source_repeat'	=>	$sourceRepeat

					);

		return $itemArray;

	}

	public function customError($errno, $errstr)
	{
	  return false;

	}

	
	# via http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
	public function sanitize($string, $force_lowercase = true, $anal = false) {
		$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
					   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
					   "—", "–", ",", "<", ".", ">", "/", "?");
		$clean = trim(str_replace($strip, "", strip_tags($string)));
		$clean = preg_replace('/\s+/', "-", $clean);
		$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
		return ($force_lowercase) ?
			(function_exists('mb_strtolower')) ?
				mb_strtolower($clean, 'UTF-8') :
				strtolower($clean) :
			$clean;
	}

	public function slugger($string, $case = false, $strict = true, $spaces = false){

		if ($spaces == false){
			$string = strip_tags($string);
			$stringArray = explode(' ', $string);
			$stringSlug = '';
			foreach ($stringArray as $stringPart){
				$stringSlug .= ucfirst($stringPart);
			}
			$stringSlug = str_replace('&amp;','&', $stringSlug);
			//$charsToElim = array('?','/','\\');
			$stringSlug = $this->sanitize($stringSlug, $case, $strict);
		} else {
			//$string = strip_tags($string);
			//$stringArray = explode(' ', $string);
			//$stringSlug = '';
			//foreach ($stringArray as $stringPart){
			//	$stringSlug .= ucfirst($stringPart);
			//}
			$stringSlug = str_replace('&amp;','&', $string);
			//$charsToElim = array('?','/','\\');
			$stringSlug = $this->sanitize($stringSlug, $case, $strict);
		}
		
		
		return $stringSlug;
		
	}	
	
	# Tries to turn any HTTPS URL into an HTTP URL for servers without ssl configured.
	public function de_https($url) {
		$urlParts = parse_url($url);
		if (in_array('https', $urlParts)){
			$urlParts['scheme'] = 'http';
			$url = $urlParts['scheme'] . '://'. $urlParts['host'] . $urlParts['path'] . $urlParts['query'];
		}
		return $url;
	}

	# The function that runs a URL through Readability and attempts to give back the plain content.
	public function readability_object($url) {
	//ref: http://www.keyvan.net/2010/08/php-readability/
		set_time_limit(0);
		
		$url = $this->de_https($url);
		$url = str_replace('&amp;','&', $url);
		print_r($url); print_r(' - Readability<br />');
		// change from Boone - use wp_remote_get() instead of file_get_contents()
		$request = wp_remote_get( $url, array('timeout' => '30') );
		if (is_wp_error($request)) {
			$content = false;
			print_r($request); die();
			return $content;
		}
		if ( ! empty( $request['body'] ) ){
			$html = $request['body'];
		} else {
			$content = false;
			return $content;
		}
		
		//check if tidy exists to clean up the input.
		if (function_exists('tidy_parse_string')) {
			$tidy = tidy_parse_string($html, array(), 'UTF8');
			$tidy->cleanRepair();
			$html = $tidy->value;
		}
		// give it to Readability
		$readability = new Readability($html, $url);

		// print debug output?
		// useful to compare against Arc90's original JS version -
		// simply click the bookmarklet with FireBug's
		// console window open
		$readability->debug = false;

		// convert links to footnotes?
		$readability->convertLinksToFootnotes = false;

		// process it
		$result = $readability->init();

		if ($result){
			$content = $readability->getContent()->innerHTML;
			//$content = $contentOut->innerHTML;
				//if we've got tidy, let's use it.
				if (function_exists('tidy_parse_string')) {
					$tidy = tidy_parse_string($content,
						array('indent'=>true, 'show-body-only'=>true),
						'UTF8');
					$tidy->cleanRepair();
					$content = $tidy->value;
				}

		} else {
			# If Readability can't get the content, send back a FALSE to loop with.
			$content = false;
			# and let's throw up an error via AJAX as well, so we know what's going on.
			print_r($url . ' fails Readability.<br />');
		}

		return $content;

	}
	
	public function make_it_readable(){
		
		// Verify nonce
		if ( !wp_verify_nonce($_POST[RSSPF_SLUG . '_nomination_nonce'], 'nomination') )
			die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'rsspf' ) );
			
		$item_id = $_POST['read_item_id'];

		if ( false === ( $itemReadReady = get_transient( 'item_readable_content_' . $item_id ) ) ) {

			set_time_limit(0);
			$url = $this->de_https($_POST['url']);
			$descrip = $_POST['content'];
			$aggregated = $this->is_from_aggregator($url);
			
			
			if ((strlen($descrip) <= 160) || $aggregated) {
				$itemReadReady = $this->readability_object($url);
			}
			if (!$itemReadReady) {
				$itemReadReady = __( "This content failed Readability.", 'rsspf' );
				$itemReadReady .= '<br />';
				$url = str_replace('&amp;','&', $url);
				#Try and get the OpenGraph description.
				if (OpenGraph::fetch($url)){
					$node = OpenGraph::fetch($url);
					$itemReadReady .= $node->description;
				} //Note the @ below. This is because get_meta_tags doesn't have a failure state to check, it just throws errors. Thanks PHP...
				elseif ('' != ($contentHtml = @get_meta_tags($url))) {
					# Try and get the HEAD > META DESCRIPTION tag.
					$itemReadReady .= __( "This content failed an OpenGraph check.", 'rsspf' );
					$itemReadReady .= '<br />';
					$descrip = $contentHtml['description'];

				}
				else
				{
					# Ugh... we can't get anything huh?
					$itemReadReady .= __( "This content has no description we can find.", 'rsspf' );
					$itemReadReady .= '<br />';					
					print_r($url . ' has no description we can find.');
					# We'll want to return a false to loop with.
					$itemReadReady = $descrip;
					
				}
			}	

			set_transient( 'item_readable_content_' . $item_id, $itemReadReady, 60*60*24 );
		}
		
		return $itemReadReady;
		die(); // < to keep from returning 0s with everything.
	}
	
	# Checks the URL against a list of aggregators.
	public function is_from_aggregator($xmlbase){
		$c = 0;
		$urlParts = parse_url($xmlbase);

		$aggregators = array (
								'tweetedtimes',
								'tweetedtimes.com',
								'www.tweetedtimes.com',
								'pipes.yahoo.com'
							);
		foreach ($aggregators as $aggregator) {
			if (in_array($aggregator, $urlParts)){
				$c++;
			}
		}
		if ($c > 0){

			return true;


		} else {
			return false;
		}

	}	
	
	# This function takes measures to try and get item content throguh methods of increasing reliability, but decreasing relevance.
	public function get_content_through_aggregator($url){

		set_time_limit(0);
		//$this->set_error_handler("customError");
		$url = $this->de_https($url);
		$descrip = '';
		//$url = http_build_url($urlParts, HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT);
		//print_r($url);
		# First run it through Readability.
		$descrip = $this->readability_object($url);
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

	public function assemble_public_stream() {

		//build a function with hooks to assemble a bunch of function that gather content into one coherent stream.

	}

	private function prep_item_for_submit($item) {
		$item['item_content'] = htmlspecialchars($item['item_content']);
		$itemid = $item['item_id'];

		foreach ($item as $itemKey => $itemPart) {

			if ($itemKey == 'item_content'){
				$itemPart = htmlspecialchars($itemPart);
			}

			echo '<input type="hidden" name="' . $itemKey . '" id="' . $itemKey . '_' . $itemid . '" id="' . $itemKey . '" value="' . $itemPart . '" />';

		}

	}
	
//Let's build a better excerpt! 
	public function feed_excerpt( $text ) {

			$text = apply_filters('the_content', $text);
			$text = str_replace('\]\]\>', ']]&gt;', $text);
			$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
			$text = strip_tags($text);
			$text = substr($text, 0, 260);
			$excerpt_length = 28; 
			$words = explode(' ', $text, $excerpt_length + 1);
			  array_pop($words);
			  array_push($words, '...');
			  $text = implode(' ', $words);
		
		return $text;
	}	

	public function rsspf_reader_builder() {
		//Calling the feedlist within the rsspf class.
	echo '<div class="container-fluid">';
		echo '<div class="row-fluid">';
			echo '<div class="span9 title-span">';
				echo '<h1>' . RSSPF_TITLE . '</h1>';
				echo '<img class="loading-top" src="' . RSSPF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />';
				echo '<div id="errors"></div>';
			echo '</div><!-- End title 9 span -->';
		echo '</div><!-- End Row -->';
		echo '<div class="row-fluid">';

			echo 	'<div class="span6">
						<div class="btn-group">
							<button type="submit" class="refreshfeed btn btn-warning" id="refreshfeed" value="Refresh">Refresh</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbyitemdate" value="Sort by item date" >Sort by item date</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbyfeedindate" value="Sort by date entered RSS">Sort by date entered RSS</button>
							<button class="btn btn-inverse" id="fullscreenfeed">Full Screen</button>
						</div><!-- End btn-group -->
					</div><!-- End span6 -->';
			echo 	'<div class="span3 offset3">
						<button type="submit" class="delete btn btn-danger pull-right" id="deletefeedarchive" value="Delete entire feed archive" >Delete entire feed archive</button>
					</div><!-- End span3 -->';

		echo '</div><!-- End Row -->';
		//A testing method, to insure the feed is being received and processed.
		//print_r($theFeed);
		echo '<div class="row-fluid main-container">';

			# Some buttons to the left
			echo '<div class="span1 deck">';
					echo '<div class="row-fluid">
							<div class="span12 main-card card well">
								<div class="tapped">
									Main Feed
								</div>
							</div>
						</div>
					';
					
					# Auto add these actions depending on if the module presents a stream?
					//do_action( 'module_stream' );
					
					echo '<div class="row-fluid">
							<div class="span12 sub-card card well">
								<div class="tapped">
									Module Feed
								</div>
							</div>
						</div>
					';
			echo '</div><!-- End span1 -->';

		//Use this foreach loop to go through the overall feedlist, select each individual feed item (post) and do stuff with it.
		//Based off SimplePie's tutorial at http://simplepie.org/wiki/tutorial/how_to_display_previous_feed_items_like_google_reader.
		$c = 1;

			echo '<div class="span7 feed-container accordion" id="feed-accordion">';
		$ic = 0;	
		# http://twitter.github.com/bootstrap/javascript.html#collapse
			if (isset($_GET["pc"])){
				$page = $_GET["pc"];
				$page = $page-1;
			} else {
				$page = 0;
			}
			$count = $page * 20;
			$c = $c+$count;
			//print_r($count);
		foreach($this->archive_feed_to_display($count+1) as $item) {

			$itemTagsArray = explode(",", $item['item_tags']);
			$itemTagClassesString = '';
			foreach ($itemTagsArray as $itemTag) { $itemTagClassesString .= $this->slugger($itemTag, true, false, true); $itemTagClassesString .= ' '; }
			echo '<div class="well accordion-group feed-item row-fluid ' . $this->slugger(($item['source_title']), true, false, true) . ' ' . $itemTagClassesString . '" id="' . $item['item_id'] . '">';

				echo '<div class="span12" id="' . $c . '">';
							# Let's build an info box!
							//http://nicolasgallagher.com/pure-css-speech-bubbles/

							$urlArray = parse_url($item['item_link']);
							$sourceLink = 'http://' . $urlArray['host'];
							//http://nicolasgallagher.com/pure-css-speech-bubbles/demo/
							echo '<div class="feed-item-info-box well leftarrow" id="info-box-' . $item['item_id'] . '" style="display:none;">';
								echo '
								Feed: <span class="feed_title">' . $item['source_title'] . '</span><br />
								Posted on: <span class="feed_posted">' . $item['item_date'] . '</span><br />
								Added to feed on <span class="item_meta item_meta_added_date">' . $item['item_added_date'] . '.</span>
								Authors: <span class="item_authors">' . $item['item_author'] . '</span><br />
								Origin: <span class="source_name"><a target ="_blank" href="' . $sourceLink . '">' . $sourceLink . '</a></span><br />
								Original Item: <span class="source_link"><a href="' . $item['item_link'] . '" class="item_url" target ="_blank">' . $item['item_title'] . '</a></span><br />
								Tags: <span class="item_tags">' . $item['item_tags'] . '</span><br />
								Times repeated in source: <span class="feed_repeat">' . $item['source_repeat'] . '</span><br />
								';
							echo '</div>';
					echo '<div class="row-fluid accordion-heading">';
					//echo '<a name="' . $c . '" style="display:none;"></a>';
					
		echo '<script type="text/javascript">	
				jQuery(document).ready(function() {
					jQuery("#' . $item['item_id'] . '").on("show", function () {
						jQuery("#excerpt' . $c . '").hide("slow");
					});
					
					jQuery("#' . $item['item_id'] . '").on("hide", function () {
						jQuery("#excerpt' . $c . '").show("slow");
					});
				});				
			</script>';
							
					
					echo '<a class="accordion-toggle" data-toggle="collapse" data-parent="#feed-accordion" href="#collapse' . $c . '">';
						if ($item['item_feat_img'] != ''){
						echo '<div class="span3">';
							echo '<div class="thumbnail">';
							echo '<div style="float:left; margin-right: 10px; margin-bottom: 10px;"><img src="' . $item['item_feat_img'] . '"></div>';
							echo '</div>';
						echo '</div><!-- End span3 -->';
						echo '<div class="span8">';
						} else {
						echo '<div class="span1">';
								echo '<div style="float:left; margin: 10px auto;">
										<div class="thumbnail" >
										<img src="' . RSSPF_URL . 'assets/images/books.png">
										</div>
									</div>';
						echo '</div><!-- End span1 -->';
						echo '<div class="span10">';
						}

							echo $c . '. ';
							//The following is a fix as described in http://simplepie.org/wiki/faq/typical_multifeed_gotchas
							//$iFeed = $item->get_feed();
							echo '<span class="source_title">' . $item['source_title'] . '</span>';
							echo ' : ';
							echo '<h3>' . $item['item_title'] . '</h3>';
							//echo '<br />';
							echo '<div class="item_meta item_meta_date">Published on ' . $item['item_date'] . ' by ' . $item['item_author'] . '.</div>';
							echo '<div style="display:none;">Unix timestamp for item date:<span class="sortableitemdate">' . strtotime($item['item_date']) . '</span> and for added to RSS date <span class="sortablerssdate">' . strtotime($item['item_added_date']) . '</span>.</div>';
							echo '<div class="item_excerpt" id="excerpt' . $c . '">' . $this->feed_excerpt($item['item_content']) . '</div>';
						echo '</div><!-- End span8 or 10 -->';
					echo '</a>';
						echo '<div class="span1">';
							# Perhaps use http://twitter.github.com/bootstrap/javascript.html#popovers instead?
							echo '<button class="btn btn-small itemInfobutton" id="' . $item['item_id'] . '"><i class="icon-info-sign"></i></button>';
						echo '</div>';
					echo '</div><!-- End row-fluid -->';

					echo '<div id="collapse' . $c . '" class="accordion-body collapse">';
					echo '<div class="accordion-inner">';
					echo '<div class="row-fluid">';
						echo '<div class="span12 item_content">';
							echo '<div>' . $item['item_content'] . '</div>';
							echo '<br />';
							echo '<a target="_blank" href="' . $item['item_link'] . '">Read More</a>';
							echo '<br />';
							echo '<strong class="item-tags">Item Tags</strong>: ' . $item['item_tags'] . '.';
							echo '<br />';
						echo '</div><!-- end item_content span12 -->';
					echo '</div><!-- End row-fluid -->';
					//print_r($item);
					//print_r($ent = htmlentities($item['item_content']));
					//print_r(html_entity_decode($ent));

					echo '<div class="item_actions row-fluid">';
						echo '<div class="span12">';
							//This needs a nonce for security.
							echo '<form name="form-' . $item['item_id'] . '"><p>';
							$this->prep_item_for_submit($item);
							wp_nonce_field('nomination', RSSPF_SLUG . '_nomination_nonce', false);
							//print_r($this->get_posts_after_for_check( 2011-01-03, 'nomination' ));
							//if(!($this->get_post_nomination_status('2012-08-10', $item['item_id'], 'post'))){
								//print_r( 'false < test.'); } else { print_r('true'); die();}
							echo '<input type="hidden" name="GreetingAll" class="GreetingAll" value="Hello Everyone!" />'
									. '<input type="submit" class="PleasePushMe" id="' . $item['item_id'] . '" value="Nominate" />'
									. '<div class="nominate-result-' . $item['item_id'] . '">'
									. '<img class="loading-' . $item['item_id'] . '" src="' . RSSPF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />'
									. '</div></p>'
								  . '</form>';


					echo '</div><!-- End accordion Inner -->';
					echo '</div><!-- End accordion body -->';

						echo '</div>';
					echo '</div>';
				echo '</div><!-- End span12 -->';

			echo '</div><!-- End row-fluid -->';

			$c++;

			//check out the built comment form from EditFlow at https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/editorial-comments.php

			// So, we're going to need some AJAXery method of sending RSS data to a nominations post.
			// Best example I can think of? The editorial comments from EditFlow, see edit-flow/modules/editorial-comments/editorial-comments.php, esp ln 284
			// But lets start simple and get the hang of AJAX in WP first. http://wp.tutsplus.com/articles/getting-started-with-ajax-wordpress-pagination/
			// Eventually should use http://wpseek.com/wp_insert_post/ I think....
			// So what to submit? I could store all the post data in hidden fields and submit it within seperate form docs, but that's a lot of data.
			// Perhaps just an md5 hash of the ID of the post? Then use the retrieval function to find the matching post and submit it properly?
			// Something to experement with...
		} // End foreach

		echo '</div><!-- End feed-container span7 -->';		

		echo '<div class="span4 feed-widget-container">';
			# Some widgets go here.
				# Does this work? [nope...]
				$blogusers = get_users('orderby=nom_count');
				$uc = 1;
				echo '<div class="row-fluid">
				<div class="rsspf-right-widget well span12">
						<div class="widget-title">
							Nominator Leaderboard
						</div>
						<div class="widget-body">
							<div class="navwidget">
								<ol>';
								foreach ($blogusers as $user){
									if ($uc <= 5){
										if (get_user_meta( $user->ID, 'nom_count', true )){
										$userNomCount = get_user_meta( $user->ID, 'nom_count', true );

										} else {
											$userNomCount = 0;
										}
										$uc++;
										echo '<li>' . $user->display_name . ' - ' . $userNomCount . '</li>';
									}

								}
				echo			'</ol>
							</div>
						</div>
				</div>
				</div>
				';
				
				$widgets_array = $this->widget_array();
				$all_widgets_array = apply_filters( 'dash_widget_bar', $widgets_array );
				
				//$all_widgets_array = array_merge($widgets_array, $mod_widgets);
				foreach ($all_widgets_array as $dash_widget) {

					$defaults = array(
						'title' => '',
						'slug'       => '',
						'callback'   => '',
					);
					$r = wp_parse_args( $dash_widget, $defaults );

					// add_submenu_page() will fail if any arguments aren't passed
					if ( empty( $r['title'] ) || empty( $r['slug'] ) || empty( $r['callback'] ) ) {
						continue;
					} else {

						echo '<div class="row-fluid">
						<div class="rsspf-right-widget well span12 ' . $r['slug'] . '">';
							echo '<div class="widget-title">' . 
								$r['title']
							. '</div>';		
							echo '<div class="widget-body">';
								call_user_func($r['callback']);
							echo '</div>';
						echo '</div>
						</div>';
					
					}
					
				}
				
				/**
				// Loop through each module to get its source data
				foreach ( $this->modules as $module ) {
					//$source_data_object = array_merge( $source_data_object, $module->get_widget_object() );
					
					echo '<div class="row-fluid">
					<div class="rsspf-right-widget well span12">';
					
					echo '</div>
					</div>';
				}
				**/

		echo '</div><!-- End feed-widget-container span4 -->';

	echo '</div><!-- End row -->';
	
		//Nasty hack because infinite scroll only works starting with page 2 for some reason. 
		if ($page == 0){ $page = 1; }
		$pagePrev = $page-1;
		$pageNext = $page+1;
		echo '<div class="rsspf-navigation">';
		if ($pagePrev > -1){
			echo '<span class="feedprev"><a class="prevnav" href="admin.php?page=rsspf-menu&pc=' . $pagePrev . '">Previous Page</a></span> | ';
		}
		echo '<span class="feednext"><a class="nextnav" href="admin.php?page=rsspf-menu&pc=' . $pageNext . '">Next Page</a></span>';
		echo '</div>';

	echo '</div><!-- End container-fluid -->';
	}
	
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
	
	function widget_one_call(){
		echo '<div class="navwidget">	Widget Body <br />	<a href="#20">Test link to item 20.</a>	</div>'	;
	}

	function rsspf_options_builder() {
		?>
		<form action="<?php echo $this->rsspf_admin_url(); ?>" method="post">
			<div class="wrap">
				<?php
				echo 'Options';

				?>
					<h3><?php _e( 'Modules', RSSPF_SLUG ) ?></h3>

					<p class="description"><?php _e( '<strong>PressForward Modules</strong> are addons to alter or improve the functionality of the ' . RSSPF_TITLE . ' plugin.', RSSPF_SLUG ) ?></p>
				<?php
				do_action( 'rsspf_admin_op_page' );
				wp_nonce_field( 'rsspf_settings' );
				?>
					<br />
					<input type="submit" name="submit" class="button-primary" value="<?php _e( "Save Changes", RSSPF_SLUG ) ?>" />
			</div>
		</form>
		<?php

	}
	
	function rsspf_options_admin_page_save() {
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

		check_admin_referer( 'rsspf_settings' );

		do_action( 'rsspf_admin_op_page_save' );
	}
	
	/**
	 * Returns the URL of the admin page
	 *
	 * We need this all over the place, so I've thrown it in a function
	 *
	 * @return string
	 */
	function rsspf_admin_url() {
		return add_query_arg( 'page', RSSPF_SLUG . '-options', admin_url( 'admin.php' ) );
	}	
	
	function feeder_options_init() {
		# Activate when settings are ready to go.
		//register_setting(RSSPF_SLUG . '_feeder_options', RSSPF_SLUG . '_plugin_feeder_options', RSSPF_SLUG . '_plugin_feeder_options_validate');
	}	

	function rsspf_feeder_builder() {
		
		echo 'Feeder. <br />';

			if ( current_user_can('edit_posts') ) : ?>
			<div class="tool-box">
				<h3 class="title"><?php _e('Nominate This'); ?></h3>
				<p><?php _e('Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.');?></p>

				<p><?php _e('Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', RSSPF_SLUG); ?></p>
				<p class="description"><?php _e('Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', RSSPF_SLUG); ?></p>
				<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo htmlspecialchars( $this->rsspf_get_shortcut_link() ); ?>"><span><?php _e('Nominate This', RSSPF_SLUG); ?></span></a></p>
				<div class="pressthis-code" style="display:none;">
				<p class="description"><?php _e('If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', RSSPF_SLUG); ?></p>
				<p><textarea rows="5" cols="120" readonly="readonly"><?php echo htmlspecialchars( $this->rsspf_get_shortcut_link() ); ?></textarea></p>
				</div>
			</div>
			<?php
			endif;
			?><form method="post" action="options.php"><?php
            //settings_fields(RSSPF_SLUG . '_feeder_options');
            //$options = get_option(RSSPF_SLUG . '_plugin_feeder_options');			
			
			do_action( 'feeder_menu' );
			
			?><input type="submit" class="button-primary" value="<?php _e('Save Options', RSSPF_SLUG); ?>" />
			</form><?php


	}

	function rsspf_get_shortcut_link() {

		// In case of breaking changes, version this. #WP20071
		$link = "javascript:
				var d=document,
				w=window,
				e=w.getSelection,
				k=d.getSelection,
				x=d.selection,
				s=(e?e():(k)?k():(x?x.createRange().text:0)),
				f='" . RSSPF_URL . "includes/nomthis/nominate-this.php" . "',
				l=d.location,
				e=encodeURIComponent,
				u=f+'?u='+e(l.href)+'&t='+e(d.title)+'&s='+e(s)+'&v=4';
				a=function(){if(!w.open(u,'t','toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=570'))l.href=u;};
				if (/Firefox/.test(navigator.userAgent)) setTimeout(a, 0); else a();
				void(0)";

		$link = str_replace(array("\r", "\n", "\t"),  '', $link);

		return apply_filters('shortcut_link', $link);

	}

	//This function can add js and css that we need to specific admin pages.
	function add_admin_scripts($hook) {

		//This gets the current page the user is on.
		global $pagenow;
		//print_r($hook);
		//This if loop will check to make sure we are on the right page for the js we are going to use.
		if (('toplevel_page_rsspf-menu') == $hook) { 
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script('tinysort', RSSPF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script('sort-imp', RSSPF_URL . 'assets/js/sort-imp.js', array( 'tinysort', 'twitter-bootstrap', 'jq-fullscreen' ));
			wp_enqueue_script('nomination-imp', RSSPF_URL . 'assets/js/nomination-imp.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', RSSPF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));
			wp_enqueue_script('jq-fullscreen', RSSPF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script('infiniscroll', RSSPF_URL . 'lib/jquery.infinitescroll.js', array( 'jquery' ));
			wp_enqueue_script('scrollimp', RSSPF_URL . 'assets/js/scroll-imp.js', array( 'infiniscroll' ));

			wp_register_style( RSSPF_SLUG . '-style', RSSPF_URL . 'assets/css/style.css');
			wp_register_style( 'bootstrap-style', RSSPF_URL . 'lib/twitter-bootstrap/css/bootstrap.css');
			wp_register_style( 'bootstrap-responsive-style', RSSPF_URL . 'lib/twitter-bootstrap/css/bootstrap-responsive.css');

			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( RSSPF_SLUG . '-style' );

		}
		if (('rss-to-press-forward_page_rsspf-feeder') != $hook) { return; }
		else {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script('tinysort', RSSPF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', RSSPF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));
			
			wp_register_style( RSSPF_SLUG . '-style', RSSPF_URL . 'assets/css/style.css');
			wp_register_style( 'bootstrap-style', RSSPF_URL . 'lib/twitter-bootstrap/css/bootstrap.css');
			wp_register_style( 'bootstrap-responsive-style', RSSPF_URL . 'lib/twitter-bootstrap/css/bootstrap-responsive.css');

			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( RSSPF_SLUG . '-style' );

		}		
		


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

	function get_posts_by_id_for_check( $theDate, $post_type, $item_id ) {
		global $wpdb;

		 $querystr = "
				SELECT $wpdb->posts.*
				FROM $wpdb->posts, $wpdb->postmeta
				WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
				AND $wpdb->postmeta.meta_key = 'origin_item_ID'
				AND $wpdb->postmeta.meta_value = '" . $item_id . "'
				AND $wpdb->posts.post_type = '" . $post_type . "'
				AND $wpdb->posts.post_date >= '". $theDate . "'
				ORDER BY $wpdb->posts.post_date DESC
			 ";

		$postsAfter = $wpdb->get_results($querystr, OBJECT);

		return $postsAfter;
	}

	function get_post_nomination_status($date, $item_id, $post_type, $updateCount = true){
		//Get the query object, limiting by date, type and metavalue ID.
		$postsAfter = $this->get_posts_by_id_for_check( $date, $post_type, $item_id );
		//Assume that it will not find anything.
		$check = false;
		if ($postsAfter):
			global $post;
			foreach ($postsAfter as $post):
				setup_postdata($post);
				$id = get_the_ID();
				$origin_item_id = get_post_meta($id, 'origin_item_ID', true);
				if ($origin_item_id == $item_id) {
					$check = true;
					//Only update the nomination count on request.
					if ($updateCount){
						$nomCount = get_post_meta($id, 'nomination_count', true);
						$nomCount++;
						update_post_meta($id, 'nomination_count', $nomCount);
						$current_user = wp_get_current_user();
						if ( 0 == $current_user->ID ) {
							//Not logged in.
							//If we ever reveal this to non users and want to count nominations by all, here is where it will go.
						} else {
							$nominators = get_post_meta($id, 'nominator_array', true);
							$nominators .= ',' . $current_user->ID;
							update_post_meta($id, 'nominator_array', $nominators);
						}

					return $check;
					break;
					}
				}
			endforeach;
		endif;
		return $check;

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

	function build_a_nomination() {

		// Verify nonce
		if ( !wp_verify_nonce($_POST[RSSPF_SLUG . '_nomination_nonce'], 'nomination') )
			die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'rsspf' ) );

		//ref http://wordpress.stackexchange.com/questions/8569/wp-insert-post-php-function-and-custom-fields, http://wpseek.com/wp_insert_post/
		$time = current_time('mysql', $gmt = 0);
		//@todo Play with post_exists (wp-admin/includes/post.php ln 493) to make sure that submissions have not already been submitted in some other method.
			//Perhaps with some sort of "Are you sure you don't mean this... reddit style thing?
			//Should also figure out if I can create a version that triggers on nomination publishing to send to main posts.


		//There is some serious delay here while it goes through the database. We need some sort of loading bar.

		//set up nomination check
		$item_wp_date = $_POST['item_wp_date'];
		$item_id = $_POST['item_id'];
		//die($item_wp_date);

		//Record first nominator and/or add a nomination to the user's count.
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			//Not logged in.
			$userSlug = "external";
			$userName = "External User";
			$userID = 0;
		} else {
			// Logged in.
			$userID = $current_user->ID;
			if (get_user_meta( $userID, 'nom_count', true )){

				$nom_counter = get_user_meta( $userID, 'nom_count', true );
				$nom_counter++;
				update_user_meta( $userID, 'nom_count', $nom_counter, true );

			} else {
				add_user_meta( $userID, 'nom_count', 1, true );

			}
		}
		$userString = $userID;

		//Going to check posts first on the assumption that there will be more nominations than posts.
		$post_check = $this->get_post_nomination_status($item_wp_date, $item_id, 'post');
		/** The system will only check for nominations of the item does not exist in posts. This will stop increasing the user and nomination count in nominations once they are sent to draft.
		**/
		if ($post_check == true) {
			//Run this function to increase the nomination count in the nomination, even if it is already a post.
			$this->get_post_nomination_status($item_wp_date, $item_id, 'nomination');
			$result = 'This item has already been nominated';
			die($result);
		}
		else {
			$nom_check = $this->get_post_nomination_status($item_wp_date, $item_id, 'nomination');
				if ($nom_check == true) { $result = 'This item has already been nominated'; die($result); }
		}


		//set up rest of nomination data
		$item_title = $_POST['item_title'];
		$item_content = htmlspecialchars_decode($_POST['item_content']);

		//No need to define every post arg right? I should only need the ones I'm pushing through. Well, I guess we will find out.
		$data = array(
			'post_status' => 'draft',
			'post_type' => 'nomination',
			//'post_author' => $user_ID,
				//Hurm... what we really need is a way to pass the nominator's userID to this function to credit them as the author of the nomination.
				//Then we could create a leaderboard.
			'post_date' => $_SESSION['cal_startdate'],
				//Do we want this to be nomination date or origonal posted date? Prob. nomination date? Optimally we can store and later sort by both.
			'post_title' => $item_title,//$item_title,
			'post_content' => $item_content,

		);

		$newNomID = wp_insert_post( $data );

		if ($_POST['item_feat_img'] != '')
			$this->set_ext_as_featured($newNomID, $_POST['item_feat_img']);
		//die($_POST['item_feat_img']);

		add_post_meta($newNomID, 'origin_item_ID', $item_id, true);
		add_post_meta($newNomID, 'nomination_count', 1, true);
		add_post_meta($newNomID, 'submitted_by', $userString, true);
		add_post_meta($newNomID, 'nominator_array', $userID, true);
		add_post_meta($newNomID, 'source_title', $_POST['source_title'], true);
		add_post_meta($newNomID, 'posted_date', $_POST['item_date'], true);
		add_post_meta($newNomID, 'authors', $_POST['item_author'], true);
		add_post_meta($newNomID, 'nomination_permalink', $_POST['item_link'], true);
		add_post_meta($newNomID, 'date_nominated', date('c'), true);
		add_post_meta($newNomID, 'item_tags', $_POST['item_tags'], true);
		add_post_meta($newNomID, 'source_repeat', $_POST['source_repeat'], true);

		$result  = $item_title . ' nominated.';
		die($result);


	}

	function send_nomination_for_publishing() {
		global $post;
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		//if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		if ( isset( $_POST['post_status'] ) && isset( $_POST['post_type'] ) && ($_POST['post_status'] == 'publish') && ($_POST['post_type'] == 'nomination')){
		//print_r($_POST); die();
			$item_title = $_POST['post_title'];
			$item_content = $_POST['post_content'];
			$data = array(
				'post_status' => 'draft',
				'post_type' => 'post',
				'post_title' => $item_title,
				'post_content' => $item_content,
			);
			//Will need to use a meta field to pass the content's md5 id around to check if it has already been posted.

			//We assume that it is already in nominations, so no need to check there. This might be why we can't use post_exists here.
			//No need to origonate the check at the time of the feed item either. It can't become a post with the proper meta if it wasn't a nomination first.
			$item_id = get_post_meta($_POST['ID'], 'origin_item_ID', true);
			$nom_date = $_POST['aa'] . '-' . $_POST['mm'] . '-' . $_POST['jj'];

			//Now function will not update nomination count when it pushes nomination to publication.
			$post_check = $this->get_post_nomination_status($nom_date, $item_id, 'post', false);

			//Alternative check with post_exists? or use same as above?
			if ($post_check != true) {
				$newPostID = wp_insert_post( $data );
				add_post_meta($newPostID, 'origin_item_ID', $item_id, true);
				$nomCount = get_post_meta($_POST['ID'], 'nomination_count', true);
				add_post_meta($newPostID, 'nomination_count', $nomCount, true);
				$userID = get_post_meta($_POST['ID'], 'submitted_by', true);
				add_post_meta($newPostID, 'submitted_by', $userID, true);
				$item_permalink = get_post_meta($_POST['ID'], 'nomination_permalink', true);
				add_post_meta($newPostID, 'nomination_permalink', $item_permalink, true);
				$item_permalink = get_post_meta($_POST['ID'], 'authors', true);
				add_post_meta($newPostID, 'authors', $item_permalink, true);
				$date_nom = get_post_meta($_POST['ID'], 'date_nominated', true);
				add_post_meta($newPostID, 'date_nominated', $date_nom, true);
				$item_tags = get_post_meta($_POST['ID'], 'item_tags', true);
				add_post_meta($newPostID, 'item_tags', $item_tags, true);
				//If user wants to use tags, we'll create an option to use it.
				$nominators = get_post_meta($_POST['ID'], 'nominator_array', true);
				add_post_meta($newPostID, 'nominator_array', $nominators, true);
				$source_repeat = get_post_meta($_POST['ID'], 'source_repeat', true);
				add_post_meta($newPostID, 'source_repeat', $source_repeat, true);

				$already_has_thumb = has_post_thumbnail($_POST['ID']);
				if ($already_has_thumb)  {
					$post_thumbnail_id = get_post_thumbnail_id( $_POST['ID'] );
					set_post_thumbnail($newPostID, $post_thumbnail_id);
				}

			}
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

global $rsspf;
$rsspf = new rsspf();

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
