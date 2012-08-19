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
					'capability_type' => 'post',
					'supports' => array('title', 'editor', 'thumbnail', 'revisions'),
					'has_archive' => false
				);
		
		register_post_type('nomination', $args);

	}
	//A quick note for later, nomination count - perhaps stored as post_meta for each nomination? 

	public function rsspf_feedlist() {
	
		$feedlist = 'http://feeds.feedburner.com/DHNowEditorsChoiceAndNews';
		return $feedlist;
	
	}
	
	private function feed_object( $itemTitle='', $sourceTitle='', $itemDate='', $itemAuthor='', $itemContent='', $itemLink='', $itemFeatImg='', $itemUID='' ) {
		
		$itemArray = array(
					
						'item_title' 	=> 	$itemTitle,
						'source_title' 	=>	$sourceTitle,
						'item_date'		=>	$itemDate,
						'item_author'	=>	$itemAuthor,
						'item_content'	=>	$itemContent,
						'item_link'		=>	$itemLink,
						'item_feat_img'	=>	$itemFeatImg,
						'item_id'		=>	$itemUID
					
					);
		
		return $itemArray;
	
	}
	
	public function rss_object() {
	
		$feedlist = call_user_func(array($this, 'rsspf_feedlist'));
		$theFeed = fetch_feed($feedlist);
		$rssObject = array();
		$c = 0;
		
		foreach($theFeed->get_items() as $item) {
			
			$id = md5($item->get_id()); //die();
			//print_r($id);
			//if ( false === ( $rssObject['rss_' . $c] = get_transient( 'rsspf_' . $id ) ) ) {
					
				$iFeed = $item->get_feed();
					
				$rssObject['rss_' . $c] = $this->feed_object(
											$item->get_title(),
											$iFeed->get_title(),
											$item->get_date('r'),
											$item->get_author(),
											$item->get_content(),
											$item->get_permalink(),
											'',
											$id
											);
												
			//	set_transient( 'rsspf_' . $id, $rssObject['rss_' . $c], 60*10 );
				
			//}
			$c++;
		
		}
		
		return $rssObject;
	
	}

	public function assemble_public_stream() {
	
		//build a function with hooks to assemble a bunch of function that gather content into one coherent stream.
	
	}
	
	private function prep_item_for_submit($item) {
		$item['item_content'] = htmlentities($item['item_content']);
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
			echo '<input type="hidden" name="GreetingAll" class="GreetingAll" value="Hello Everyone!" />'
					. '<input type="submit" class="PleasePushMe" id="' . $item['item_id'] . '" />'
					. '<div class="test-div1">'
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
	
	function build_a_nomination() {
		
		// Verify nonce
		//if ( !wp_verify_nonce( $_POST['_nonce'], 'nomination') )
		//	die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'rsspf' ) );

		//ref http://wordpress.stackexchange.com/questions/8569/wp-insert-post-php-function-and-custom-fields, http://wpseek.com/wp_insert_post/
		$time = current_time('mysql', $gmt = 0); 
		//@todo Play with post_exists (wp-admin/includes/post.php ln 493) to make sure that submissions have not already been submitted in some other method.
			//Perhaps with some sort of "Are you sure you don't mean this... reddit style thing?
			//Should also figure out if I can create a version that triggers on nomination publishing to send to main posts. 

		//set up nomination data
		$item_title = $_POST['item_title'];
		$item_content = $_POST['item_content'];
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
			'post_content' => 'test'//$item_content,
			
		);
		
		wp_insert_post( $data );

	
	}



}

global $rsspf;
$rsspf = new rsspf();

?>