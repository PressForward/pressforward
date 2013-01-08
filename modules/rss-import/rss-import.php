<?php

/**
 * Test of module base class
 */

 require_once(RSSPF_ROOT . "/includes/opml-reader/opml-reader.php");
 
class RSSPF_RSS_Import extends RSSPF_Module {

	/////////////////////////////
	// PARENT OVERRIDE METHODS //
	/////////////////////////////

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::start();
		add_action( 'admin_init', array($this, 'register_settings') );
		if( is_admin() )
		{
			add_action( 'wp_ajax_nopriv_remove_a_feed', array( $this, 'remove_a_feed') );
			add_action( 'wp_ajax_remove_a_feed', array( $this, 'remove_a_feed') );		
		}
	}
	
	public function step_through_feedlist() {
		
		$feedlist = call_user_func(array($this, 'rsspf_feedlist'));	
		//The array keys start with zero, as does the iteration number. This will account for that. 
		$feedcount = count($feedlist) - 1;
		//Get the iteration state. If option does not exist, set the iteration variable to 0
		$feeds_iteration = get_option( RSSPF_SLUG . '_feeds_iteration', 0 );	
		if ($feedcount >= $feeds_iteration) {
			//If the feed item is empty, can I loop back through this function for max efficiency? I think so.
			$aFeed = $feedlist[$feeds_iteration];
			if ($feedcount == $feeds_iteration){
				$feeds_iteration = 0;
			} else {
				$feeds_iteration = $feeds_iteration++;
			}
			update_option( RSSPF_SLUG . '_feeds_iteration', $feeds_iteration);
			//If the array entry is empty and this isn't the end of the feedlist, then get the next item from the feedlist while iterating the count. 
			if ((empty($aFeed) || $aFeed == '') && ($feeds_iteration != 0)){
				$aFeed = call_user_func(array($this, 'step_through_feedlist'));	
			}
			return $aFeed;
		} else {
			//An error state that should never, ever, ever, ever, ever happen. 
			return false;
		}
	
	}

	/**
	 * Gets the data from an RSS feed and turns it into a data object
	 * as expected by RSSPF
	 *
	 * @global $rsspf Used to access the feed_object() method
	 */
	public function get_data_object() {
		global $rsspf;

		$aFeed = call_user_func(array($this, 'step_through_feedlist'));		
		$theFeed = fetch_feed($aFeed);
		$rssObject = array();
		$c = 0;

		foreach($theFeed->get_items() as $item) {
			$id = md5($item->get_id()); //die();
			//print_r($item_categories_string); die();

			if ( false === ( $rssObject['rss_' . $c] = get_transient( 'rsspf_' . $id ) ) ) {
				if ($item->get_source()){
					$sourceObj = $item->get_source();
					# Get the link of what created the RSS entry.
					$source = $sourceObj->get_link(0,'alternate');
					# Check if the feed item creator is an aggregator.
					$agStatus = $rsspf->is_from_aggregator($source);
				} else {
					# If we can't get source information then don't do anything.
					$agStatus = false;
				}
				# If there is less than 160 characters of content, than it isn't really giving us meaningful information.
				# So we'll want to get the good stuff from the source.
				if ((strlen($item->get_content())) < 160){
					$agStatus = true;
				}
				//override switch while rest is not working.
				//$agStatus = false;
				//This is where we switch off auto readability 
				//And leave it to an AJAX function instead. 
//				if ($agStatus){
//					# Get the origin post link.
//					$realLink = $item->get_link();
//					# Try and get the actual content of the post.
//					$realContent = $rsspf->get_content_through_aggregator($realLink);
//					# If we can't get the actual content, then just use what we've got from the RSS feed.
//					if (!$realContent){
						$item_content = $item->get_content();
//					} else {
//						$item_content = $realContent;
						//print_r($realContent);
//					}
//				} else {
//						$item_content = $item->get_content();
//				}
				$iFeed = $item->get_feed();
				if (!$agStatus){
					$authors = $this->get_rss_authors($item);
				}
				else {
					$authors = 'aggregation';
				}
				$item_categories = array();
				$item_categories = $item->get_categories();
				$itemTerms = array();
				if (!empty($item_categories)){
					foreach ($item_categories as $item_category){
						$itemTerms[] = $item_category->get_term();
					}
					$item_categories_string = implode(',',$itemTerms);
				} else { $item_categories_string = ''; }
				//one final cleanup of the content.
				$contentObj = new htmlchecker($item_content);
				$item_content = $contentObj->closetags($item_content);
				print_r($c);
				$rssObject['rss_' . $c] = $rsspf->feed_object(
											$item->get_title(),
											$iFeed->get_title(),
											$item->get_date('r'),
											$authors,
											$item_content,
											$item->get_link(),
											'',
											$id,
											$item->get_date('Y-m-d'),
											$item_categories_string
											);

				set_transient( 'rsspf_' . $id, $rssObject['rss_' . $c], 60*10 );

			}
			$c++;

		}
		
		//Here: If feedlist_iteration is not == to feedlist_count, scheduale a cron and trigger it before returning. 
		
		return $rssObject;

	}

	/////////////////////////////
	// UTILITY METHODS         //
	/////////////////////////////

	# Where we store a list of feeds to check.
	public function rsspf_feedlist() {

		$feedlist = array('http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Flabel%2FEditors-at-Large');
		//http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FEditors-at-Large%20Stream
		//'http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FNominations';
		//http://feeds.feedburner.com/DHNowEditorsChoiceAndNews
		//http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FNominations
		if ( false == (get_option( RSSPF_SLUG . '_feedlist' )) ){
			add_option( RSSPF_SLUG . '_feedlist', $feedlist);
		} else {
			$feedlist = get_option( RSSPF_SLUG . '_feedlist' );
		}
		$all_feeds_array = apply_filters( 'imported_rss_feeds', $feedlist );
		return $all_feeds_array;

	}

	# Tries to get the RSS item author for the meta.
	function get_rss_authors($item) {
		// This needs error checking. 
		$authorArray = ($item->get_authors());
		foreach ($authorArray as $author) {

			$nameArray[] = $author->get_name();

		}
		$authors = implode(', ', $nameArray);
		$authors = $authors;
		return $authors;

	}
	
	function add_to_feeder() {
		
		?><form method="post" action="options.php"><?php
        settings_fields( RSSPF_SLUG . '_feedlist_group' );
		$feedlist = get_option( RSSPF_SLUG . '_feedlist' );

        ?>                      
			<br />
			<br />
			<div><?php _e('Add Single Feed', RSSPF_SLUG); ?></div>
				<div>
					<input id="<?php echo RSSPF_SLUG . '_feedlist[single]'; ?>" class="regular-text" type="text" name="<?php echo RSSPF_SLUG . '_feedlist[single]'; ?>" value="" />
                    <label class="description" for="<?php echo RSSPF_SLUG . '_feedlist[single]'; ?>"><?php _e('*Complete URL or RSS path', RSSPF_SLUG); ?></label>

                     			
                </div>	

			<div><?php _e('Add OPML', RSSPF_SLUG); ?></div>
				<div>
					<input id="<?php echo RSSPF_SLUG . '_feedlist[opml]'; ?>" class="regular-text" type="text" name="<?php echo RSSPF_SLUG . '_feedlist[opml]'; ?>" value="" />
                    <label class="description" for="<?php echo RSSPF_SLUG . '_feedlist[opml]'; ?>"><?php _e('*Drop link to OPML here.', RSSPF_SLUG); ?></label>

                     			
                </div>	
				
			<p class="submit">
				<?php submit_button(); ?>
			</p>					
		</form>		
			<div class="show-feeds">
			<form>
				<p>Current items feeding on: </p>
				<?php
					echo '<code><pre>';
					print_r($feedlist);
					echo '</pre></code>';		
					wp_nonce_field('feedremove', RSSPF_SLUG . '_o_feed_nonce', false);					
				?>
				<ul>
				<?php
					$this->feedlist_builder($feedlist);
				?>
				</ul>
			</div>
			</form>
		<?php
    

		
	}
	
	public function feedlist_builder($feedlist){
		foreach ($feedlist as $feed){
			if ((!is_array($feed)) && $feed != ''){
				$feedID = md5($feed);
				echo '<li id="feed-' . $feedID . '" class="feed-list-item">' . $feed . ' <input id="' . $feedID . '" type="submit" class="removeMyFeed icon-remove-sign" value="   Remove"></input>';
				echo '<input type="hidden" name="feed_url" id="o_feed_url_' . $feedID . '" value="' . $feed . '"></li>';
			} elseif (is_array($feed)){
				$this->feedlist_builder($feed);
			}
						
		}
		
		return;
	}
	
	function rsspf_feedlist_validate($input){
		if (!empty($input['single'])){
			if (!(is_array($input['single']))){
				//$simp = new SimplePie();
				$simp = fetch_feed($input['single']);
				//Needs some sort of error returned on no-feed
				$inputSingleSub = $simp->subscribe_url();
				$inputSingle = array($inputSingleSub);
			} else {
				$inputSingle = $input['single'];
			}
		}
		
		//print_r($inputSingle);
		
		if (!empty($input['opml'])){
			$OPML_reader = new OPML_reader;
			$opml_array = $OPML_reader->get_OPML_data($input['opml']);
		}
		//$feedlist = $this->rsspf_feedlist();
		// Needs something to do here if option is empty. 
		$feedlist = get_option( RSSPF_SLUG . '_feedlist' );		
		if (false == $feedlist){
			if (!empty($input['single'])){
				$feedlist = $inputSingle;
			}
			if (!empty($input['opml'])){
				$feedlist = array_merge($feedlist, $opml_array);
			}
			if (!empty($_POST['o_feed_url'])){
			
			}
		} else {
	//		$feedlist = array('http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Flabel%2FEditors-at-Large');
			if (!empty($input['single'])){
				$feedlist = array_merge($feedlist, $inputSingle);
			}
			if (!empty($input['opml'])){
				$feedlist = array_merge($feedlist, $opml_array);
			}
			if (!empty($_POST['o_feed_url'])){
				$offender = array_search($_POST['o_feed_url'], $feedlist);
				if ($offender !== false){
					unset($feedlist[$offender]);
				}
				
			}
			
		}
		
		//Let's ensure no duplicates.
		$feedlist = array_unique($feedlist);
		
		//print_r($feedlist); die();
		return $feedlist;
	}
	
	public function remove_a_feed() {
		
		if (!empty($_POST['o_feed_url'])){
			$feedURL = $_POST['o_feed_url'];
			if ( !wp_verify_nonce($_POST[RSSPF_SLUG . '_o_feed_nonce'], 'feedremove') )
				die( __( "Nonce check failed. Please ensure you're supposed to be removing feeds.", 'rsspf' ) );		
		/**		
			$feedlist = get_option( RSSPF_SLUG . '_feedlist' );
			
			$offender = array_search($feedURL, $feedlist);
			if ($offender !== false){
				unset($feedlist[$offender]);
			}
			//$modfeedlist = array_diff($feedlist, array($feedURL));
			//update_option( RSSPF_SLUG . '_feedlist', '');
			//delete_option( RSSPF_SLUG . '_feedlist' );
**/
			// The rsspf_feedlist setting is being filtered through the rsspf_feedlist_validate
			// method, as a result of being registered with register_setting(). We'll work
			// around this by unhooking the validation method during this update
			//remove_action( 'sanitize_option_rsspf_feedlist', array( 'RSSPF_RSS_Import', 'rsspf_feedlist_validate' ) );
			$check = update_option( RSSPF_SLUG . '_feedlist', $_POST);
			
			if (!$check){
				$result = 'The feedlist failed to update.'; 
			} else {
				$result = $feedURL . ' has been removed from your feedlist.';
			}
			
			die($result);
		} else {
			die("Error");
		}
	
	}	
	
	function register_settings(){
		register_setting(RSSPF_SLUG . '_feedlist_group', RSSPF_SLUG . '_feedlist', array('RSSPF_RSS_Import', 'rsspf_feedlist_validate'));
	}
	
	public function admin_enqueue_scripts() {
		global $rsspf;
		
		wp_enqueue_script( 'feed-manip-ajax', $rsspf->modules['rss-import']->module_url . 'assets/js/feed-manip-imp.js', array( 'jquery', 'twitter-bootstrap') );
		wp_enqueue_style( 'feeder-style', $rsspf->modules['rss-import']->module_url . 'assets/css/feeder-styles.css' );
	}	

}
