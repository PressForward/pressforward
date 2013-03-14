<?php

/**
 * Test of module base class
 */

define( 'FEED_LOG', PF_ROOT . "/modules/rss-import/rss-import.txt" );
class PF_RSS_Import extends PF_Module {

	/////////////////////////////
	// PARENT OVERRIDE METHODS //
	/////////////////////////////

	/**
	 * Constructor
	 */
	public function __construct() {
		global $pf;

		parent::start();

		//self::check_nonce = wp_create_nonce('retrieve-pressforward');
		add_action( 'admin_init', array($this, 'register_settings') );
		add_action( 'wp_head', array($this, 'get_chunk_nonce'));
		add_action( 'init', array($this, 'alter_for_retrieval'));

		// Schedule our cron actions for fetching feeds
		add_action( 'init', array($this, 'schedule_feed_in' ) );
		add_action( 'init', array($this, 'schedule_feed_out' ) );

		add_action( 'take_feed_out', array( 'PF_Feed_Item', 'disassemble_feed_items' ) );
		add_action( 'pull_feed_in', array( pressforward()->admin, 'trigger_source_data') );

		if( is_admin() )
		{
			add_action( 'wp_ajax_nopriv_remove_a_feed', array( $this, 'remove_a_feed') );
			add_action( 'wp_ajax_remove_a_feed', array( $this, 'remove_a_feed') );
			add_action( 'get_more_feeds', array( 'PF_Feed_Item', 'assemble_feed_for_pull' ) );

			add_action( 'wp_ajax_nopriv_feed_retrieval_reset', array( $this, 'feed_retrieval_reset') );
			add_action( 'wp_ajax_feed_retrieval_reset', array( $this, 'feed_retrieval_reset') );

		}
	}

	/**
	 * Schedules the hourly wp-cron job
	 */
	public function schedule_feed_in() {
		if ( ! wp_next_scheduled( 'pull_feed_in' ) ) {
			wp_schedule_event( time(), 'hourly', 'pull_feed_in' );
		}
	}

	/**
	 * Schedules the monthly feed item cleanup
	 */
	function schedule_feed_out() {
		if ( ! wp_next_scheduled( 'take_feed_out' ) ) {
			wp_schedule_event( time(), 'monthly', 'take_feed_out' );
		}
	}

	public function get_chunk_nonce(){
		$create_nonce = wp_create_nonce('chunkpressforward');
		update_option('chunk_nonce', $create_nonce);
	}

	public function step_through_feedlist() {
		pf_log('step_through_feedlist begins.');
		//$feed_go = update_option( PF_SLUG . '_feeds_go_switch', 1);
		//pf_log('The Feeds go switch has been updated?');
		//pf_log($feed_go);
		$feedlist = $this->pf_feedlist();
		//The array keys start with zero, as does the iteration number. This will account for that.
		//$feedcount = count($feedlist) - 1;
		end($feedlist);
		$last_key = key($feedlist);
		pf_log('The last key is: ' . $last_key);

		//Get the iteration state. If option does not exist, set the iteration variable to 0
		$feeds_iteration = get_option( PF_SLUG . '_feeds_iteration');

		pf_log('feeds_go_switch updated? (first check).');
		# We begin the process of getting the next feed. If anything asks the system, from here until the end of the feed retrieval process, you DO NOT attempt to retrieve another feed. 
		pf_log('feeds_go_switch updated?.');
		$go_switch_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
		pf_log($go_switch_bool);

		$prev_iteration = get_option( PF_SLUG . '_prev_iteration', 0);
		pf_log('Did the option properly iterate so that the previous iteration count of ' . $prev_iteration . ' is equal to the current of ' . $feeds_iteration . '?');
		// This is the fix for the insanity caused by the planet money feed - http://www.npr.org/rss/podcast.php?id=510289
		if ( (int) $prev_iteration == (int) $feeds_iteration){
			pf_log('Nope. Did the step_though_feedlist iteration option emergency update work here?');
			update_option( PF_SLUG . '_feeds_iteration', $feeds_iteration+1);
			$feeds_iteration++;
		} else {
			pf_log('Yes');
		}

		pf_log('The current iterate state is: ' . $feeds_iteration);
		if ($feeds_iteration <= $last_key) {
			pf_log('The iteration is less than the last key.');
//		print_r($feeds_iteration . ' iterate state'); die();
			//If the feed item is empty, can I loop back through this function for max efficiency? I think so.
			$aFeed = $feedlist[$feeds_iteration];
			pf_log('Retrieved feed ' . $aFeed);
			$did_we_start_over = get_option(PF_SLUG . '_iterate_going_switch', 1);
			pf_log('Iterate going switch is set to: ' . $did_we_start_over);
			if (($last_key === $feeds_iteration)){
				pf_log('The last key is equal to the feeds_iteration. This is the last feed.');
				$feeds_iteration = 0;
//				pf_log('feeds_go_switch updated?.');
//				$go_switch_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
//				pf_log($go_switch_bool);
				pf_log('iterate_going_switch updated?.');
				$going_switch_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($going_switch_bool);
				//print_r('TURN IT OFF');

			} elseif ($did_we_start_over == 1) {
				pf_log('No, we didn\'t start over.');
				pf_log('Did we set the previous iteration option to ' . $feeds_iteration . '?');
				$prev_iteration = update_option( PF_SLUG . '_prev_iteration', $feeds_iteration);
				pf_log($prev_iteration);
				$feeds_iteration = $feeds_iteration+1;
				pf_log('Did the iterate_going_switch update?');
				$iterate_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 1);
				pf_log($iterate_going_bool);
				pf_log('We are set to a reiterate state.');
			}

			pf_log('Did the feeds_iteration option update to ' . $feeds_iteration . '?');
			$iterate_op_check = update_option( PF_SLUG . '_feeds_iteration', $feeds_iteration);
			pf_log($iterate_op_check);
			if ($iterate_op_check === false) {
				pf_log('For no apparent reason, the option did not update. Delete and try again.');
				pf_log('Did the option delete?');
				$deleteCheck = delete_option( PF_SLUG . '_feeds_iteration' );
				pf_log($deleteCheck);
				$iterate_op_check = update_option( PF_SLUG . '_feeds_iteration', $feeds_iteration);
				pf_log('Did the new option setup work?');
				pf_log($iterate_op_check);
			}
			pf_log('The feed iteration option is now set to ' . $feeds_iteration);

			if (((empty($aFeed)) || ($aFeed == '')) && ($feeds_iteration <= $last_key)){
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is less than or equal to the last key.');
				$theFeed = call_user_func(array($this, 'step_through_feedlist'));
			} elseif (((empty($aFeed)) || ($aFeed == '')) && ($feeds_iteration > $last_key)){
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is greater than the last key.');
				pf_log('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( PF_SLUG . '_feeds_iteration', 0);
				pf_log($feed_it_bool);

				pf_log('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
				pf_log($feed_go_bool);

				pf_log('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($feed_going_bool);

				pf_log('End of the update process. Return false.');
				return false;
			}

			if (is_wp_error($theFeed = fetch_feed($aFeed))){
				$aFeed = '';
			}
			//If the array entry is empty and this isn't the end of the feedlist, then get the next item from the feedlist while iterating the count.
			if (((empty($aFeed)) || ($aFeed == '') || (is_wp_error($theFeed))) && ($feeds_iteration <= $last_key)){
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is less than or equal to the last key.');
				$theFeed = call_user_func(array($this, 'step_through_feedlist'));
			} elseif (((empty($aFeed)) || ($aFeed == '') || (is_wp_error($theFeed))) && ($feeds_iteration > $last_key)){
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is greater then the last key.');
				pf_log('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( PF_SLUG . '_feeds_iteration', 0);
				pf_log($feed_it_bool);

				pf_log('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
				pf_log($feed_go_bool);

				pf_log('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($feed_going_bool);

				pf_log('End of the update process. Return false.');
				return false;
			}
			return $theFeed;
		} else {
			//An error state that should never, ever, ever, ever, ever happen.
			pf_log('The iteration is now greater than the last key.');
				pf_log('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( PF_SLUG . '_feeds_iteration', 0);
				pf_log($feed_it_bool);

				pf_log('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
				pf_log($feed_go_bool);

				pf_log('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($feed_going_bool);
				pf_log('End of the update process. Return false.');
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
		pf_log('Begin advance_feeds.');
		//Here: If feedlist_iteration is not == to feedlist_count, scheduale a cron and trigger it before returning.
				$feedlist = self::pf_feedlist();
		//The array keys start with zero, as does the iteration number. This will account for that.
		$feedcount = count($feedlist) - 1;
		//Get the iteration state. If this variable doesn't exist the planet will break in half.
		$feeds_iteration = get_option( PF_SLUG . '_feeds_iteration');

		$feed_get_switch = get_option( PF_SLUG . '_feeds_go_switch');
		if ($feed_get_switch != 0) {
			pf_log('Feeds go switch is NOT set to 0.');
			pf_log('Getting import-cron.');

			//http://codex.wordpress.org/Function_Reference/wp_schedule_single_event
			//add_action( 'pull_feed_in', array($this, 'assemble_feed_for_pull') );
			//wp_schedule_single_event(time()-3600, 'get_more_feeds');
			//print_r('<br /> <br />' . PF_URL . 'modules/rss-import/import-cron.php <br /> <br />');
			$theRetrievalLoop = add_query_arg( 'press', 'forward',  site_url() );
			$pfnonce = get_option('chunk_nonce');
			$theRetrievalLoopNounced = add_query_arg( '_wpnonce', $pfnonce,  $theRetrievalLoop );
			pf_log('Checking remote get at ' . $theRetrievalLoopNounced . ' : ');
			$wprgCheck = wp_remote_get($theRetrievalLoopNounced);

			
			return;
			//pf_log($wprgCheck);
			//Looks like it is schedualed properly. But should I be using wp_cron() or spawn_cron to trigger it instead?
			//wp_cron();
			//If I use spawn_cron here, it can only occur every 60 secs. That's no good!
			//print_r('<br />Cron: ' . wp_next_scheduled('get_more_feeds') . ' The next event.');
			//print_r(get_site_url() . '/wp-cron.php');
			//print_r($wprgCheck);
		} else {
			pf_log('Feeds go switch is set to 0.');
		}
	}

	public function alter_for_retrieval() {
		//$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
		//$nonce_check = get_option('chunk_nonce');
		if ( isset( $_GET['press'] ) && $_GET['press'] == 'forward'){
			# Removing this until we decide to replace or eliminate. It isn't working. 
			//if ( $nonce === $nonce_check){
				pf_log('Pressing forward.');
				include(PF_ROOT . '/modules/rss-import/import-cron.php');
				exit;
			//} else {
			//	$verify_val = wp_verify_nonce($nonce, 'retrieve-pressforward');
			//	pf_log('Nonce check of ' . $nonce . ' failed. Returned: ');
			//	pf_log($verify_val);
			//	pf_log('Stored nonce:');
			//	pf_log($nonce_check);			
			//}
		}
	}

	/**
	 * Run any setup that has to happen after initial module registration
	 */
	public function post_setup_module_info() {
		$this->includes();
	}

	/**
	 * Includes necessary files
	 */
	public function includes() {
		require_once(PF_ROOT . "/includes/opml-reader/opml-reader.php");
	}

	/**
	 * Gets the data from an RSS feed and turns it into a data object
	 * as expected by PF
	 *
	 * @global $pf Used to access the feed_object() method
	 */
	public function get_data_object() {
		global $pf;
		pf_log('Begin get_data_object.');
		//Is this process already occuring?
		$feed_go = update_option( PF_SLUG . '_feeds_go_switch', 0);
		pf_log('The Feeds go switch has been updated?');
		pf_log($feed_go);
		$is_it_going = get_option(PF_SLUG . '_iterate_going_switch', 1);
		if ($is_it_going == 0){
			//WE ARE? SHUT IT DOWN!!!
			update_option( PF_SLUG . '_feeds_go_switch', 0);
			update_option( PF_SLUG . '_feeds_iteration', 0);
			update_option( PF_SLUG . '_iterate_going_switch', 0);
			//print_r('<br /> We\'re doing this thing already in the data object. <br />');
			if ( (get_option( PF_SLUG . '_ready_to_chunk', 1 )) === 0 ){
				pf_log('The chunk is still open because there are no more feeds. [THIS SHOULD NOT OCCUR except at the conclusion of feeds retrieval.]');
				update_option( PF_SLUG .  '_ready_to_chunk', 1 );
			} else {
				pf_log('We\'re doing this thing already in the data object.', true);
			}
			//return false;
			die();
		}

		$theFeed = call_user_func(array($this, 'step_through_feedlist'));
		if (!$theFeed){
			pf_log('The feed is false, exit process. [THIS SHOULD NOT OCCUR except at the conclusion of feeds retrieval.]');

			$chunk_state = update_option( PF_SLUG . '_ready_to_chunk', 1 );			
			exit;
		}
		$theFeed->set_timeout(60);
		$rssObject = array();
		$c = 0;
		pf_log('Begin processing the feed.');
		foreach($theFeed->get_items() as $item) {
			pf_log('Feed looping through for the ' . $c . ' time.');
			$check_date = $item->get_date('U');
			$dead_date = time() - (60*60*24*60); //Get the unixdate for two months ago.
			if ($check_date <= $dead_date) {
				pf_log('Feed item too old. Skip it.');
			} else {
				$id = md5($item->get_link() . $item->get_title()); //die();
				pf_log('Now on feed ID ' . $id . '.');
				//print_r($item_categories_string); die();

				if ( false === ( $rssObject['rss_' . $c] = get_transient( 'pf_' . $id ) ) ) {
					if ($item->get_source()){
						$sourceObj = $item->get_source();
						# Get the link of what created the RSS entry.
						$source = $sourceObj->get_link(0,'alternate');
						# Check if the feed item creator is an aggregator.
						$agStatus = $this->is_from_aggregator($source);
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
	//					$realContent = $pf->get_content_through_aggregator($realLink);
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
					$rssObject['rss_' . $c] = pf_feed_object(
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
					pf_log('Setting new transient for ' . $item->get_title() . ' of ' . $iFeed->get_title() . '.');
					set_transient( 'pf_' . $id, $rssObject['rss_' . $c], 60*10 );

				}
			}
			$c++;
			# What the hell RSS feed? This is just ridiculous. 
			if ($c > 300) {break;}

		}
		# We've completed the feed retrieval, the system should know it is now ok to ask for another feed. 
		$feed_go = update_option( PF_SLUG . '_feeds_go_switch', 1);
		pf_log('The Feeds go switch has been updated to on?');
		pf_log($feed_go);
		$prev_iteration = get_option( PF_SLUG . '_prev_iteration', 0);
		$iterate_op_check = get_option( PF_SLUG . '_feeds_iteration', 1);
		pf_log('Did the option properly iterate so that the previous iteration count of ' . $prev_iteration . ' is not equal to the current of ' . $iterate_op_check . '?');
		if ($prev_iteration === $iterate_op_check){
			pf_log('Nope. Did the iteration option emergency update function here?');
			$check_iteration = update_option( PF_SLUG . '_feeds_iteration', $iterate_op_check+1);
			pf_log($check_iteration);

		} else {
			pf_log('Yes');
		}

		//$this->advance_feeds();

		return $rssObject;

	}

	/////////////////////////////
	// UTILITY METHODS         //
	/////////////////////////////

	# Where we store a list of feeds to check.
	public function pf_feedlist() {

		$feedlist = array('http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Flabel%2FEditors-at-Large');
		//http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FEditors-at-Large%20Stream
		//'http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FNominations';
		//http://feeds.feedburner.com/DHNowEditorsChoiceAndNews
		//http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FNominations
		if ( false == (get_option( PF_SLUG . '_feedlist' )) ){
			add_option( PF_SLUG . '_feedlist', $feedlist);
		} else {
			$feedlist = get_option( PF_SLUG . '_feedlist' );
		}
		$all_feeds_array = apply_filters( 'imported_rss_feeds', $feedlist );
		pf_log('Sending feedlist to function.');
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
			$authors = __('No author.', 'pf');
		}

		return $authors;
	}

	function add_to_feeder() {

		?><form method="post" action="options.php"><?php
        settings_fields( PF_SLUG . '_feedlist_group' );
		$feedlist = get_option( PF_SLUG . '_feedlist' );

        ?>
			<br />
			<br />
		<button type="submit" class="resetFeedOps btn btn-warning" id="resetFeedOps" value="Reset all Feed Retrieval Options"><?php _e('Reset all Feed Retrieval Options', 'pf'); ?></button>    <br />
			 <?php
			$feed_go = get_option( PF_SLUG . '_feeds_go_switch', 0);
			$feed_iteration = get_option( PF_SLUG . '_feeds_iteration', 0);
			$retrieval_state = get_option( PF_SLUG . '_iterate_going_switch', 0);
			$chunk_state = get_option( PF_SLUG . '_ready_to_chunk', 1 );
			$retrieval_state = sprintf(__('Feeds Go? %1$d  Feeds iteration? %2$d  Going switch? %3$d  Ready to chunk? %4$d', 'pf'), $feed_go, $feed_iteration, $retrieval_state, $chunk_state);
			echo $retrieval_state;
			?>
			<br />
			<br />
			<div><?php _e('Add Single Feed', 'pf'); ?></div>
				<div>
					<input id="<?php echo PF_SLUG . '_feedlist[single]'; ?>" class="regular-text" type="text" name="<?php echo PF_SLUG . '_feedlist[single]'; ?>" value="" />
                    <label class="description" for="<?php echo PF_SLUG . '_feedlist[single]'; ?>"><?php _e('*Complete URL or RSS path', 'pf'); ?></label>


                </div>

			<div><?php _e('Add OPML', 'pf'); ?></div>
				<div>
					<input id="<?php echo PF_SLUG . '_feedlist[opml]'; ?>" class="regular-text" type="text" name="<?php echo PF_SLUG . '_feedlist[opml]'; ?>" value="" />
                    <label class="description" for="<?php echo PF_SLUG . '_feedlist[opml]'; ?>"><?php _e('*Drop link to OPML here. No HTTPS allowed.', 'pf'); ?></label>


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
					wp_nonce_field('feedremove', PF_SLUG . '_o_feed_nonce', false);
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

	function pf_feedlist_validate($input){
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
		//$feedlist = $this->pf_feedlist();
		// Needs something to do here if option is empty.
		$feedlist = get_option( PF_SLUG . '_feedlist' );
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
			if ( !wp_verify_nonce($_POST[PF_SLUG . '_o_feed_nonce'], 'feedremove') )
				die( __( 'Nonce check failed. Please ensure you\'re supposed to be removing feeds.', 'pf' ) );
		/**
			$feedlist = get_option( PF_SLUG . '_feedlist' );

			$offender = array_search($feedURL, $feedlist);
			if ($offender !== false){
				unset($feedlist[$offender]);
			}
			//$modfeedlist = array_diff($feedlist, array($feedURL));
			//update_option( PF_SLUG . '_feedlist', '');
			//delete_option( PF_SLUG . '_feedlist' );
**/
			// The pf_feedlist setting is being filtered through the pf_feedlist_validate
			// method, as a result of being registered with register_setting(). We'll work
			// around this by unhooking the validation method during this update
			//remove_action( 'sanitize_option_pf_feedlist', array( 'PF_RSS_Import', 'pf_feedlist_validate' ) );
			$check = update_option( PF_SLUG . '_feedlist', $_POST);

			if (!$check){
				$result = __('The feedlist failed to update.', 'pf');
			} else {
				$result = $feedURL . __(' has been removed from your feedlist.', 'pf');
			}

			die($result);
		} else {
			die(__('Error', 'pf'));
		}

	}

	function register_settings(){
		register_setting(PF_SLUG . '_feedlist_group', PF_SLUG . '_feedlist', array('PF_RSS_Import', 'pf_feedlist_validate'));
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

	public function admin_enqueue_scripts() {
		global $pf;

		wp_enqueue_script( 'feed-manip-ajax', $pf->modules['rss-import']->module_url . 'assets/js/feed-manip-imp.js', array( 'jquery', 'twitter-bootstrap') );
		wp_enqueue_style( 'feeder-style', $pf->modules['rss-import']->module_url . 'assets/css/feeder-styles.css' );
	}

	function feed_retrieval_reset(){
		$feed_go = update_option( PF_SLUG . '_feeds_go_switch', 0);
		$feed_iteration = update_option( PF_SLUG . '_feeds_iteration', 0);
		$retrieval_state = update_option( PF_SLUG . '_iterate_going_switch', 0);
		$chunk_state = update_option( PF_SLUG . '_ready_to_chunk', 1 );
 	}

	public function trigger_source_data(){
		$feed_iteration = get_option( PF_SLUG . '_feeds_iteration', 0);
		$retrieval_state = get_option( PF_SLUG . '_iterate_going_switch', 0);
		$chunk_state = get_option( PF_SLUG . '_ready_to_chunk', 1 );
		pf_log( 'Invoked: PF_RSS_Import::trigger_source_data()' );
		pf_log( 'Feed iteration: ' . $feed_iteration );
		pf_log( 'Retrieval state: ' . $retrieval_state );
		pf_log( 'Chunk state: ' . $chunk_state );
		if ($feed_iteration == 0 && $retrieval_state == 0 && $chunk_state == 1){
			$status = update_option( PF_SLUG . '_iterate_going_switch', 1);

			pf_log( __('Beginning the retrieval process', 'pf'), true, true );

			if ( $status ) {
				pf_log( __( 'Iterate switched to going.', 'pf' ) );
			} else {
				pf_log( __( 'Iterate option not switched.', 'pf') );
			}

			PF_Feed_Item::assemble_feed_for_pull();
		} else {
			pf_log(__('The sources are already being retrieved.', 'pf'), true);
		}
	}
}

function pf_test_import() {
	if ( is_super_admin() && ! empty( $_GET['pf_test_import'] ) ) {
		var_dump( pf_get_starred_items_for_user( get_current_user_id(), 'simple' ) );
		return;
		$feed = fetch_feed( 'http://teleogistic.net/feed' );

		$source = $feed->subscribe_url();

		foreach ( $feed->get_items() as $item ) {
			$io = new PF_Feed_Item();

			// Check for existing items before importing
			$foo = $io->get( array(
				'url' => $item->get_link( 0 ),
				'foo' => 'bar',
			) );

			if ( empty( $foo ) ) {
				$tags = wp_list_pluck( $item->get_categories(), 'term' );
				$fi_id = $io->create( array(
					'title'   => $item->get_title(),
					'url'     => $item->get_link( 0 ),
					'content' => $item->get_content(),
					'source'  => $source,
					'date'    => strtotime( $item->get_date() ),
					'tags'    => $tags,
				) );
			} else {
				$fi_id = $foo[0]->ID;
			}

			pf_star_item_for_user( $fi_id, get_current_user_id() );
			if ( rand( 0, 1 ) ) {
				echo 'deleted:'; var_dump( pf_unstar_item_for_user( $fi_id, get_current_user_id() ) );
			}

			echo 'starred: ';
			var_dump( pf_is_item_starred_for_user( $fi_id, get_current_user_id() ) );
			var_dump( $fi_id );
		}
	}
}
add_action( 'admin_init', 'pf_test_import' );
