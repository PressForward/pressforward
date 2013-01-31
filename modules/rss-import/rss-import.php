<?php

/**
 * Test of module base class
 */

 require_once(RSSPF_ROOT . "/includes/opml-reader/opml-reader.php");
 define( 'FEED_LOG', RSSPF_ROOT . "/modules/rss-import/rss-import.txt" );
 
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
		add_action( 'wp_head', array($this, 'alter_for_retrieval'));
		if( is_admin() )
		{
			add_action( 'wp_ajax_nopriv_remove_a_feed', array( $this, 'remove_a_feed') );
			add_action( 'wp_ajax_remove_a_feed', array( $this, 'remove_a_feed') );	
			add_action('get_more_feeds', array($rsspf, 'assemble_feed_for_pull'));		
	
			add_action( 'wp_ajax_nopriv_feed_retrieval_reset', array( $this, 'feed_retrieval_reset') );
			add_action( 'wp_ajax_feed_retrieval_reset', array( $this, 'feed_retrieval_reset') );     
			
		}
	}
	
	public function log_feed_input($log_string){
		$fo = fopen(FEED_LOG, 'a') or print_r('Can\'t open log file.');
		if ($fo != false){
			if($log_string === true){$log_string = 'true';}
			if($log_string === false){$log_string = 'false';}
			if(is_wp_error($log_string)){$log_string = $log_string->get_error_message();}
			if(is_array($log_string)){
					$log_string = print_r($log_string, TRUE);
				}
			$string_to_log = "\n" . $log_string;
			fwrite($fo, $string_to_log);
			fclose($fo);
		}
	}
	
	public function step_through_feedlist() {
		$this->log_feed_input('step_through_feedlist begins.');
		//$feed_go = update_option( RSSPF_SLUG . '_feeds_go_switch', 1);
		//$this->log_feed_input('The Feeds go switch has been updated?');
		//$this->log_feed_input($feed_go);
		$feedlist = call_user_func(array($this, 'rsspf_feedlist'));	
		//The array keys start with zero, as does the iteration number. This will account for that. 
		//$feedcount = count($feedlist) - 1;
		end($feedlist);
		$last_key = key($feedlist);
		$this->log_feed_input('The last key is: ' . $last_key);
		//Get the iteration state. If option does not exist, set the iteration variable to 0
		$feeds_iteration = get_option( RSSPF_SLUG . '_feeds_iteration', 1 );
				$this->log_feed_input('feeds_go_switch updated? (first check).');
				$go_switch_bool = update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
				$this->log_feed_input($go_switch_bool);		
		$prev_iteration = get_option( RSSPF_SLUG . '_prev_iteration', 0);
		$this->log_feed_input('Did the option properly iterate so that the previous iteration count of ' . $prev_iteration . ' is equal to the current of ' . $feeds_iteration . '?');
		// This is the fix for the insanity caused by the planet money feed - http://www.npr.org/rss/podcast.php?id=510289
		if ($prev_iteration === $feeds_iteration){
			$this->log_feed_input('Nope. Did the step_though_feedlist iteration option emergency update work here?');
			$check_iteration = update_option( RSSPF_SLUG . '_feeds_iteration', $feeds_iteration+1);
			$this->log_feed_input($check_iteration);
			$feeds_iteration = $feeds_iteration+1;
		} else {
			$this->log_feed_input('Yes');
		}		
		
		$this->log_feed_input('The current iterate state is: ' . $feeds_iteration);
		if ($feeds_iteration <= $last_key) {
			$this->log_feed_input('The iteration is less than the last key.');
//		print_r($feeds_iteration . ' iterate state'); die();
			//If the feed item is empty, can I loop back through this function for max efficiency? I think so.
			$aFeed = $feedlist[$feeds_iteration];
			$this->log_feed_input('Retrieved feed ' . $aFeed);
			$did_we_start_over = get_option(RSSPF_SLUG . '_iterate_going_switch', 1);
			$this->log_feed_input('Iterate going switch is set to: ' . $did_we_start_over);
			if (($last_key === $feeds_iteration)){
				$this->log_feed_input('The last key is equal to the feeds_iteration.');
				$feeds_iteration = 0;
				$this->log_feed_input('feeds_go_switch updated?.');
				$go_switch_bool = update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
				$this->log_feed_input($go_switch_bool);
				$this->log_feed_input('iterate_going_switch updated?.');
				$going_switch_bool = update_option( RSSPF_SLUG . '_iterate_going_switch', 0);
				$this->log_feed_input($going_switch_bool);
				//print_r('TURN IT OFF');
				
			} elseif ($did_we_start_over == 1) {
				$this->log_feed_input('No, we didn\'t start over.');
				$this->log_feed_input('Did we set the previous iteration option to ' . $feeds_iteration . '?');
				$prev_iteration = update_option( RSSPF_SLUG . '_prev_iteration', $feeds_iteration);
				$this->log_feed_input($prev_iteration);
				$feeds_iteration = $feeds_iteration+1;
				$this->log_feed_input('Did the iterate_going_switch update?');
				$iterate_going_bool = update_option( RSSPF_SLUG . '_iterate_going_switch', 1);
				$this->log_feed_input($iterate_going_bool);
				$this->log_feed_input('We are set to a reiterate state.');
			}
			
			$this->log_feed_input('Did the feeds_iteration option update to ' . $feeds_iteration . '?');
			$iterate_op_check = update_option( RSSPF_SLUG . '_feeds_iteration', $feeds_iteration);
			$this->log_feed_input($iterate_op_check);
			if ($iterate_op_check === false) {
				$this->log_feed_input('For no apparent reason, the option did not update. Delete and try again.');
				$this->log_feed_input('Did the option delete?');
				$deleteCheck = delete_option( RSSPF_SLUG . '_feeds_iteration' );
				$this->log_feed_input($deleteCheck);
				$iterate_op_check = update_option( RSSPF_SLUG . '_feeds_iteration', $feeds_iteration);
				$this->log_feed_input('Did the new option setup work?');
				$this->log_feed_input($iterate_op_check);
			}			
			$this->log_feed_input('The feed iteration option is now set to ' . $feeds_iteration);

			if (((empty($aFeed)) || ($aFeed == '')) && ($feeds_iteration < $last_key)){
				$this->log_feed_input('The feed is either an empty entry or un-retrievable AND the iteration is less than the last key.');
				$theFeed = call_user_func(array($this, 'step_through_feedlist'));	
			} elseif (((empty($aFeed)) || ($aFeed == '')) && ($feeds_iteration >= $last_key)){
				$this->log_feed_input('The feed is either an empty entry or un-retrievable AND the iteration is greater or equal to the last key.');
				$this->log_feed_input('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( RSSPF_SLUG . '_feeds_iteration', 0);
				$this->log_feed_input($feed_it_bool);
				
				$this->log_feed_input('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
				$this->log_feed_input($feed_go_bool);
				
				$this->log_feed_input('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( RSSPF_SLUG . '_iterate_going_switch', 0);
				$this->log_feed_input($feed_going_bool);
				
				$this->log_feed_input('End of the update process. Return false.');
				return false;
			}

			if (is_wp_error($theFeed = fetch_feed($aFeed))){
				$aFeed = '';
			}
			//If the array entry is empty and this isn't the end of the feedlist, then get the next item from the feedlist while iterating the count. 
			if (((empty($aFeed)) || ($aFeed == '') || (is_wp_error($theFeed))) && ($feeds_iteration < $last_key)){
				$this->log_feed_input('The feed is either an empty entry or un-retrievable AND the iteration is less than the last key.');
				$theFeed = call_user_func(array($this, 'step_through_feedlist'));	
			} elseif (((empty($aFeed)) || ($aFeed == '') || (is_wp_error($theFeed))) && ($feeds_iteration >= $last_key)){
				$this->log_feed_input('The feed is either an empty entry or un-retrievable AND the iteration is greater or equal to the last key.');
				$this->log_feed_input('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( RSSPF_SLUG . '_feeds_iteration', 0);
				$this->log_feed_input($feed_it_bool);
				
				$this->log_feed_input('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
				$this->log_feed_input($feed_go_bool);
				
				$this->log_feed_input('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( RSSPF_SLUG . '_iterate_going_switch', 0);
				$this->log_feed_input($feed_going_bool);
				
				$this->log_feed_input('End of the update process. Return false.');
				return false;
			}
			return $theFeed;
		} else {
			//An error state that should never, ever, ever, ever, ever happen. 
			$this->log_feed_input('The iteration is now greater than the last key.');
				$this->log_feed_input('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( RSSPF_SLUG . '_feeds_iteration', 0);
				$this->log_feed_input($feed_it_bool);
				
				$this->log_feed_input('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
				$this->log_feed_input($feed_go_bool);
				
				$this->log_feed_input('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( RSSPF_SLUG . '_iterate_going_switch', 0);
				$this->log_feed_input($feed_going_bool);
				$this->log_feed_input('End of the update process. Return false.');
				return false;			
			//return false;
		}
	
	}
	
	public function pf_feed_fetcher($aFeed){
		$theFeed = fetch_feed($aFeed);

		if ((is_wp_error($theFeed))){
			print_r('<br />The Feed ' . $aFeed . ' could not be retrieved.');
				//$aFeed = call_user_func(array($this, 'step_through_feedlist'));		
				//$theFeed = $this->pf_feed_fetcher($aFeed);
				return false;
		}
		
		return $theFeed;
	}
	
	public function advance_feeds(){
		self::log_feed_input('Begin advance_feeds.');
		//Here: If feedlist_iteration is not == to feedlist_count, scheduale a cron and trigger it before returning. 
				$feedlist = call_user_func(array(self, 'rsspf_feedlist'));	
		//The array keys start with zero, as does the iteration number. This will account for that. 
		$feedcount = count($feedlist) - 1;
		//Get the iteration state. If this variable doesn't exist the planet will break in half. 
		$feeds_iteration = get_option( RSSPF_SLUG . '_feeds_iteration');	

		$feed_get_switch = get_option( RSSPF_SLUG . '_feeds_go_switch');	
		if ($feed_get_switch != 0) {
			self::log_feed_input('Feeds go switch is NOT set to 0.');
			self::log_feed_input('Getting import-cron.');
			//http://codex.wordpress.org/Function_Reference/wp_schedule_single_event
			//add_action( 'pull_feed_in', array($this, 'assemble_feed_for_pull') );
			//wp_schedule_single_event(time()-3600, 'get_more_feeds');
			//print_r('<br /> <br />' . RSSPF_URL . 'modules/rss-import/import-cron.php <br /> <br />');
			$theRetrievalLoop = add_query_arg( 'press', 'forward',  site_url() );
			$pfnonce = wp_create_nonce  ('retrieve-pressforward'); 
			$theRetrievalLoopNounced = add_query_arg( 'nonce', $pfnonce,  $theRetrievalLoop );
			$wprgCheck = wp_remote_get($theRetrievalLoopNounced);
			
			self::log_feed_input('Checking remote get: ');
			return;
			//$this->log_feed_input($wprgCheck);
			//Looks like it is schedualed properly. But should I be using wp_cron() or spawn_cron to trigger it instead? 
			//wp_cron();
			//If I use spawn_cron here, it can only occur every 60 secs. That's no good!
			//print_r('<br />Cron: ' . wp_next_scheduled('get_more_feeds') . ' The next event.');
			//print_r(get_site_url() . '/wp-cron.php');
			//print_r($wprgCheck);
		} else {
			self::log_feed_input('Feeds go switch is set to 0.');
		}	
	}
	
	public function alter_for_retrieval() {
			if ($_GET['press'] == 'forward'){
				//if ( wp_verify_nonce($_GET['nounce'], 'retrieve-pressforward') ){
					include(RSSPF_ROOT . '/modules/rss-import/import-cron.php');
					exit;
				//}
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
		$this->log_feed_input('Begin get_data_object.');
		//Is this process already occuring?
		$feed_go = update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
		$this->log_feed_input('The Feeds go switch has been updated?');
		$this->log_feed_input($feed_go);		
		$is_it_going = get_option(RSSPF_SLUG . '_iterate_going_switch', 1);
		if ($is_it_going == 0){
			//WE ARE? SHUT IT DOWN!!!
			update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
			update_option( RSSPF_SLUG . '_feeds_iteration', 0);
			update_option( RSSPF_SLUG . '_iterate_going_switch', 0);
			print_r('<br /> We\'re doing this thing already in the data object. <br />');
			$this->log_feed_input('We\'re doing this thing already in the data object.');
			//return false;
			exit;
		}
		
		$theFeed = call_user_func(array($this, 'step_through_feedlist'));		
		if (!$theFeed){
			$this->log_feed_input('The feed is false, exit process. [THIS SHOULD NOT OCCUR except at the conclusion of feeds retrieval.]');
			exit;
		}
		$theFeed->set_timeout(60);
		$rssObject = array();
		$c = 0;
		$this->log_feed_input('Begin processing the feed.');
		foreach($theFeed->get_items() as $item) {
			$this->log_feed_input('Feed looping through for the ' . $c . ' time.');
			$id = md5($item->get_link() . $item->get_title()); //die();
			$this->log_feed_input('Now on feed ID ' . $id . '.');
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
				$this->log_feed_input('Setting new transient for ' . $item->get_title() . ' of ' . $iFeed->get_title() . '.');
				set_transient( 'rsspf_' . $id, $rssObject['rss_' . $c], 60*10 );

			}
			$c++;

		}

		$feed_go = update_option( RSSPF_SLUG . '_feeds_go_switch', 1);
		$this->log_feed_input('The Feeds go switch has been updated to on?');
		$this->log_feed_input($feed_go);	
		$prev_iteration = get_option( RSSPF_SLUG . '_prev_iteration', 0);
		$iterate_op_check = get_option( RSSPF_SLUG . '_feeds_iteration', 1);
		$this->log_feed_input('Did the option properly iterate so that the previous iteration count of ' . $prev_iteration . ' is not equal to the current of ' . $iterate_op_check . '?');
		if ($prev_iteration === $iterate_op_check){
			$this->log_feed_input('Nope. Did the iteration option emergency update function here?');
			$check_iteration = update_option( RSSPF_SLUG . '_feeds_iteration', $iterate_op_check+1);
			$this->log_feed_input($check_iteration);
			
		} else {
			$this->log_feed_input('Yes');
		}

		//$this->advance_feeds();	
		
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
		self::log_feed_input('Sending feedlist to function.');
		$ordered_all_feeds_array = array_values($all_feeds_array);
		$tidy_all_feeds_array = array_filter( $ordered_all_feeds_array, 'strlen' );
		return $tidy_all_feeds_array;

	}

	# Tries to get the RSS item author for the meta.
	function get_rss_authors($item) {
		// This needs error checking. 
		$authorArray = ($item->get_authors());
		if (!empty($authorArray)){
			foreach ($authorArray as $author) {

				$nameArray[] = $author->get_name();

			}
			$authors = implode(', ', $nameArray);
			$authors = $authors;
			
		} else {
			$authors = 'No author.';
		}
		
		return $authors;
	}
	
	function add_to_feeder() {
		
		?><form method="post" action="options.php"><?php
        settings_fields( RSSPF_SLUG . '_feedlist_group' );
		$feedlist = get_option( RSSPF_SLUG . '_feedlist' );

        ?>
			<br />
			<br />
		<button type="submit" class="resetFeedOps btn btn-warning" id="resetFeedOps" value="Reset all Feed Retrieval Options">Reset all Feed Retrieval Options</button>    <br />  
			 <?php
			$feed_go = get_option( RSSPF_SLUG . '_feeds_go_switch', 0);
			$feed_iteration = get_option( RSSPF_SLUG . '_feeds_iteration', 0);
			$retrieval_state = get_option( RSSPF_SLUG . '_iterate_going_switch', 0);
			echo 'Feeds Go? ' . $feed_go . ' Feeds iteration? ' . $feed_iteration . ' Going switch? ' . $retrieval_state;
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
                    <label class="description" for="<?php echo RSSPF_SLUG . '_feedlist[opml]'; ?>"><?php _e('*Drop link to OPML here. No HTTPS allowed.', RSSPF_SLUG); ?></label>

                     			
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
			//print_r($opml_array); die();
		}
		//$feedlist = $this->rsspf_feedlist();
		// Needs something to do here if option is empty. 
		$feedlist = get_option( RSSPF_SLUG . '_feedlist' );		
		if (false == $feedlist){
			if (!empty($input['single'])){
				$feedlist = $inputSingle;
			}
			if (!empty($input['opml'])){
				$feedlist = $opml_array;
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
//		print_r($feedlist); die();
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
	
  function feed_retrieval_reset(){
		$feed_go = update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
		$feed_iteration = update_option( RSSPF_SLUG . '_feeds_iteration', 0);
		$retrieval_state = update_option( RSSPF_SLUG . '_iterate_going_switch', 0);
		update_option( RSSPF_SLUG . '_chunk_assembly_status', 1 );
 	}  	
	

}
