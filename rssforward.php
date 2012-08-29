<?php
/*
Plugin Name: RSS to PressForward
Plugin URI: http://aramzs.me
Description: This plugin is am RSS parser for CHNM's Press Forward project. 
Version: 0.001
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
require_once("OpenGraph.php");

class rsspf {

	
	// See http://php.net/manual/en/language.oop5.decon.php to get a better understanding of what's going on here. 
	function __construct() {
	
		//Activate the admin menu creation function.
		add_action('admin_menu', array($this, 'register_rsspf_custom_menu_pages') );
		//Activate the nominations post-type
		add_action('init', array($this, 'create_rsspf_nomination_post_type') );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		
		
  // creating Ajax call for WordPress
   //add_action( 'wp_ajax_nopriv_MyAjaxFunction', array( $this, 'MyAjaxFunction') );
   //add_action( 'wp_ajax_MyAjaxFunction', array( $this, 'MyAjaxFunction') );	
		if( is_admin() )
		{
		add_action( 'wp_ajax_nopriv_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_build_a_nomination', array( $this, 'build_a_nomination') );	
		
		}
		add_action('edit_post', array( $this, 'send_nomination_for_publishing'));
		add_filter( 'manage_edit-nomination_columns', array ($this, 'edit_nominations_columns') );
		add_action( 'manage_nomination_posts_custom_column',  array ($this, 'nomination_custom_columns') );
		
		add_filter('the_author', array($this, 'replace_author_presentation'));
		add_filter( 'author_link', array($this, 'replace_author_uri_presentation') );		
		add_filter( "manage_edit-nomination_sortable_columns", array ($this, "nomination_sortable_columns") );	
		
	}

	//Create the menus
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

		add_submenu_page(RSSPF_MENU_SLUG, 'Nominations', 'Nominations', 'edit_posts', RSSPF_NOM_EDITOR);
		
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
					'public' => false,
					//'show_ui' => false,
					'show_ui' => true,
					//'show_in_menu' => $rsspf_menu_slug,
					'show_in_menu' => false,
					'register_meta_box_cb' => array($this, 'nominations_meta_boxes'),
					'capability_type' => 'post',
					'supports' => array('title', 'editor', 'thumbnail', 'revisions'),
					'has_archive' => false
				);
		
		register_post_type('nomination', $args);

	}
	
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
	
	public function nominations_meta_boxes() {
		global $post;
		
		add_meta_box('rsspf-nominations', 'Nomination Data', array($this, 'nominations_box_builder'), 'nomination', 'side', 'high' );

	}
	
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
		echo '<strong>Item ID</strong>: ' . $origin_item_ID . '<br />'; 
		echo '<strong>Nomination Count</strong>: ' . $nomination_count . '<br />'; 
		echo '<strong>Submitted By</strong>: ' . $user->display_name . '<br />'; 
		echo '<strong>Feed Title</strong>: ' . $source_title . '<br />'; 
		echo '<strong>Source Posted</strong>: ' . $posted_date . '<br />'; 
		echo '<strong>Source Authors</strong>: ' . $nom_authors . '<br />'; 
		echo '<strong>Source Link</strong>: <a href="' . $nomination_permalink . '" target="_blank">Original Post</a><br />'; 
		echo '<strong>Date Nominated</strong>: ' . $date_nominated . '<br />'; 
		
	}	

	public function rsspf_feedlist() {
	
		$feedlist = 'http://feeds.feedburner.com/DHNowEditorsChoiceAndNews';
		return $feedlist;
	
	}
	
	private function feed_object( $itemTitle='', $sourceTitle='', $itemDate='', $itemAuthor='', $itemContent='', $itemLink='', $itemFeatImg='', $itemUID='', $itemWPDate='' ) {
		
		if($itemFeatImg == ''){
		
			if ( false === ( $itemFeatImg = get_transient( 'feed_img_' . $itemUID ) ) ) {
			
				//If there is no featured image passed, let's try and grab the opengraph image. 
				$node = OpenGraph::fetch($itemLink);
				$itemFeatImg = $node->image;
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
					
					);
		
		return $itemArray;
	
	}
	
	function get_rss_authors($item) {
	
		$authorArray = ($item->get_authors());
		foreach ($authorArray as $author) {
			
			$nameArray[] = $author->get_name();
				
		}
		$authors = implode(', ', $nameArray);

		return $authors;
	
	}
	
	public function rss_object() {
	
		$feedlist = call_user_func(array($this, 'rsspf_feedlist'));
		$theFeed = fetch_feed($feedlist);
		$rssObject = array();
		$c = 0;
		
		foreach($theFeed->get_items() as $item) {
			
			$id = md5($item->get_id()); //die();
			//print_r($id);
			if ( false === ( $rssObject['rss_' . $c] = get_transient( 'rsspf_' . $id ) ) ) {
					
				$iFeed = $item->get_feed();
				$authors = $this->get_rss_authors($item);
					
				$rssObject['rss_' . $c] = $this->feed_object(
											$item->get_title(),
											$iFeed->get_title(),
											$item->get_date('r'),
											$authors,
											$item->get_content(),
											$item->get_link(),
											'',
											$id,
											$item->get_date('Y-m-d')
											);
												
				set_transient( 'rsspf_' . $id, $rssObject['rss_' . $c], 60*10 );
				
			}
			$c++;
		
		}
		
		return $rssObject;
	
	}

	public function assemble_public_stream() {
	
		//build a function with hooks to assemble a bunch of function that gather content into one coherent stream.
	
	}
	
	private function prep_item_for_submit($item) {
		$item['item_content'] = htmlspecialchars($item['item_content']);
		$itemid = $item['item_id'];
	
		foreach ($item as $itemKey => $itemPart) {
		
			echo '<input type="hidden" name="' . $itemKey . '" id="' . $itemKey . '_' . $itemid . '" id="' . $itemKey . '" value="' . $itemPart . '" />';
		
		}
	
	}
	
	public function rsspf_reader_builder() {
		//Calling the feedlist within the rsspf class. 
		
		echo '<h1>' . RSSPF_TITLE . '</h1>';
		
		//A testing method, to insure the feed is being received and processed. 
		//print_r($theFeed);
		
		//Use this foreach loop to go through the overall feedlist, select each individual feed item (post) and do stuff with it.
		//Based off SimplePie's tutorial at http://simplepie.org/wiki/tutorial/how_to_display_previous_feed_items_like_google_reader.
		$c = 1;
		foreach($this->rss_object() as $item) {
			
			if ($item['item_feat_img'] != '')
				echo '<div style="float:left; margin-right: 10px; margin-bottom: 10px;"><img src="' . $item['item_feat_img'] . '"></div>';
			echo $c++ . '. ';
			//The following is a fix as described in http://simplepie.org/wiki/faq/typical_multifeed_gotchas
			//$iFeed = $item->get_feed();
			echo '<strong>' . $item['source_title'] . '</strong>';
			echo ' : ';
			echo $item['item_title'];
			echo '<br />';
			echo '<small>Published on ' . $item['item_date'] . ' by ' . $item['item_author'] . '</small>';
			echo '<br />';
			echo '<div>' . $item['item_content'] . '</div>';
			echo '<br />';
			echo '<a target="_blank" href="' . $item['item_link'] . '">Read More</a>';
			echo '<br />';
			//print_r($item);
			//print_r($ent = htmlentities($item['item_content']));
			//print_r(html_entity_decode($ent));
			//This needs a nonce for security.
			echo '<form name="form-' . $item['item_id'] . '"><p>';
			$this->prep_item_for_submit($item);
			wp_nonce_field('nomination', RSSPF_SLUG . '_nomination_nonce', false);
			//print_r($this->get_posts_after_for_check( 2011-01-03, 'nomination' ));
			//if(!($this->get_post_nomination_status('2012-08-10', $item['item_id'], 'post'))){
				//print_r( 'false < test.'); } else { print_r('true'); die();}
			echo '<input type="hidden" name="GreetingAll" class="GreetingAll" value="Hello Everyone!" />'
					. '<input type="submit" class="PleasePushMe" id="' . $item['item_id'] . '" />'
					. '<div class="nominate-result-' . $item['item_id'] . '">'
					. '<img class="loading-' . $item['item_id'] . '" src="' . RSSPF_URL . 'includes/images/ajax-loader.gif" alt="Loading..." style="display: none" />'
					. '</div></p>'
				  . '</form>';
			echo '<hr />';
			echo '<br />';
			
			//check out the built comment form from EditFlow at https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/editorial-comments.php
			
			// So, we're going to need some AJAXery method of sending RSS data to a nominations post. 
			// Best example I can think of? The editorial comments from EditFlow, see edit-flow/modules/editorial-comments/editorial-comments.php, esp ln 284
			// But lets start simple and get the hang of AJAX in WP first. http://wp.tutsplus.com/articles/getting-started-with-ajax-wordpress-pagination/
			// Eventually should use http://wpseek.com/wp_insert_post/ I think....
			// So what to submit? I could store all the post data in hidden fields and submit it within seperate form docs, but that's a lot of data.
			// Perhaps just an md5 hash of the ID of the post? Then use the retrieval function to find the matching post and submit it properly? 
			// Something to experement with...
		}
		
		
	}
	
	function rsspf_options_builder() {
	
		echo 'Options';
	
	}
	
	function rsspf_feeder_builder() {
	
		echo 'Feeder';
	
	}
	
	//This function can add js and css that we need to specific admin pages. 
	function add_admin_scripts() {
	
		//This gets the current page the user is on.
		global $pagenow;
		//This if loop will check to make sure we are on the right page for the js we are going to use.
		if ((!in_array($pagenow, array('admin.php?page=rsspf-menu')))) {
			//And now lets enqueue the script, ensuring that jQuery is already active. 
			
			wp_enqueue_script('nomination-imp', RSSPF_URL . 'includes/js/nomination-imp.js', array( 'jquery' ));
			wp_register_style( RSSPF_SLUG . '-style', RSSPF_URL . 'includes/css/style.css');
			wp_enqueue_style( RSSPF_SLUG . '-style' );
		
		}
	
	
	}
		
	function MyAjaxFunction(){
		  //get the data from ajax() call
		   $GreetingAll = $_POST['GreetingAll'];
		   $results = "<h2>".$GreetingAll."</h2>";
		  // Return the String
		   die($results);
	 
		//Ref for eventual building of nomination ajax:
			//https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/editorial-comments.php ln284	//https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/lib/editorial-comments.js
			//https://github.com/danielbachhuber/Edit-Flow/blob/master/edit_flow.php
			
	 
	}
	
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

	function get_post_nomination_status($date, $item_id, $post_type){
	
		$postsAfter = $this->get_posts_after_for_check( $date, $post_type );
		$check = false;
		if ($postsAfter):
			global $post;
			foreach ($postsAfter as $post):
				setup_postdata($post);
				$id = get_the_ID();
				$origin_item_id = get_post_meta($id, 'origin_item_ID', true);
				if ($origin_item_id == $item_id) {
					$check = true;
					$nomCount = get_post_meta($id, 'nomination_count', true);
					$nomCount++;
					update_post_meta($id, 'nomination_count', $nomCount);
					return $check;
					break;
				}
			endforeach;
		endif;
		return $check;
	
	}
	
	function set_ext_as_featured($postID,$ogImage){
		
			if ( (strlen($ogImage)) > 0 ){
			
				$imgParts = pathinfo($ogImage);
				$imgExt = $imgParts['extension'];
				$imgTitle = $imgParts['filename'];

				
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
			if ($current_user->has_prop( 'nom_count' )){
			
				$nom_counter = $current_user->__get( 'nom_count' );
				$nom_counter++;
				$current_user->__set('nom_count', $nom_counter);
			
			} else {
			
				$current_user->__set('nom_count', 1);
			
			}
		}
		$userString = $userID;		
		
		//Filter not going to work? Guess the answer is http://codex.wordpress.org/Displaying_Posts_Using_a_Custom_Select_Query.
		
		//Going to check posts first on the assumption that there will be more nominations than posts. 
		$post_check = $this->get_post_nomination_status($item_wp_date, $item_id, 'post');
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
		
		$this->set_ext_as_featured($newNomID, $_POST['item_feat_img']);
		//die($_POST['item_feat_img']);

		add_post_meta($newNomID, 'origin_item_ID', $item_id, true);
		add_post_meta($newNomID, 'nomination_count', 1, true);
		add_post_meta($newNomID, 'submitted_by', $userString, true);
		add_post_meta($newNomID, 'source_title', $_POST['source_title'], true);
		add_post_meta($newNomID, 'posted_date', $_POST['item_date'], true);
		add_post_meta($newNomID, 'authors', $_POST['item_author'], true);
		add_post_meta($newNomID, 'nomination_permalink', $_POST['item_link'], true);
		add_post_meta($newNomID, 'date_nominated', date('c'), true);
		
		
		$result  = $item_title . ' nominated.';
		die($result);
		
	
	}
	
	function send_nomination_for_publishing() {
		global $post;
		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		//if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		if (($_POST['post_status'] == 'publish') && ($_POST['post_type'] == 'nomination')){
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
			
			//A quick note: This will increment the nomination count every time a post is updated,
			//even ahead of the nomination count on the nomination itself. 
			//is this a behaviour we want? 
			$post_check = $this->get_post_nomination_status($nom_date, $item_id, 'post');
			
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

?>