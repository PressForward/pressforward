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
	}

	/**
	 * Gets the data from an RSS feed and turns it into a data object
	 * as expected by RSSPF
	 *
	 * @global $rsspf Used to access the feed_object() method
	 */
	public function get_data_object() {
		global $rsspf;

		$feedlist = call_user_func(array($this, 'rsspf_feedlist'));
		$theFeed = fetch_feed($feedlist);
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
				if ($agStatus){
					# Get the origin post link.
					$realLink = $item->get_link();
					# Try and get the actual content of the post.
					$realContent = $rsspf->get_content_through_aggregator($realLink);
					# If we can't get the actual content, then just use what we've got from the RSS feed.
					if (!$realContent){
						$item_content = $item->get_content();
					} else {
						$item_content = $realContent;
						//print_r($realContent);
					}
				} else {
						$item_content = $item->get_content();
				}
				$iFeed = $item->get_feed();
				$authors = $this->get_rss_authors($item);
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

		return $authors;

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
				
			<div class="show-feeds">
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
				echo '<li id="feed-' . $feedID . '" class="feed-list-item">' . $feed . ' <a id="' . $feedID . '" class="removeMyFeed" href="#"><i class="icon-remove-sign"></i></a>';
				echo '<input type="hidden" name="feed_url" id="o_feed_url_' . $feedID . '" value="' . $feed . ' "></li>';
			} elseif (is_array($feed)){
				$this->feedlist_builder($feed);
			}
						
		}
		
		return;
	}
	
	function rsspf_feedlist_validate($input){
		if (!(is_array($input['single']))){
			$input['single'] = array($input['single']);
		}
		
		$OPML_reader = new OPML_reader;
		$opml_array = $OPML_reader->get_OPML_data($input['opml']);

		//$feedlist = $this->rsspf_feedlist();
		// Needs something to do here if option is empty. 
		$feedlist = get_option( RSSPF_SLUG . '_feedlist' );		
//		if (false == $feedlist){
//			$feedlist = $this->rsspf_feedlist();
//		}
	
//		$feedlist = array('http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Flabel%2FEditors-at-Large');
	
		$feedlist = array_merge($feedlist, $input['single']);
		$feedlist = array_merge($feedlist, $opml_array);
		return $feedlist;
	}
	
	function remove_a_feed() {
	
		$feedURL = $POST['o_feed_url'];
		if ( !wp_verify_nonce($_POST[RSSPF_SLUG . '_o_feed_nonce'], 'feedremove') )
			die( __( "Nonce check failed. Please ensure you're supposed to be removing feeds.", 'rsspf' ) );		
			
		$feedlist = get_option( RSSPF_SLUG . '_feedlist' );
		
		$offender = array_search($feedURL, $feedlist);
		if ($offender != false){
			unset($feedlist[$offender]);
		}
		
		$check = update_option( RSSPF_SLUG . '_feedlist', $feedlist);
		
		if (!$check){
			$result = 'The feedlist failed to update.'; 
		} else {
			$result = $feedURL . ' has been removed from your feedlist.';
		}
		
		return($result);
	
	}	
	
	function register_settings(){
		register_setting(RSSPF_SLUG . '_feedlist_group', RSSPF_SLUG . '_feedlist', array($this, 'rsspf_feedlist_validate'));
	}
	
	public function admin_enqueue_scripts() {
		global $rsspf;
		
		wp_enqueue_script( 'feed-manip-ajax', $rsspf->modules['rss-import']->module_url . 'assets/js/feed-manip-imp.js', array( 'jquery', 'twitter-bootstrap') );
	}	

}
