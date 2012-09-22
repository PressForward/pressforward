<?php

/**
 * Test of module base class
 */

class RSSPF_RSS_Import extends RSSPF_Module {

	/////////////////////////////
	// PARENT OVERRIDE METHODS //
	/////////////////////////////

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::start();
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
					$realContent = $this->get_content_through_aggregator($realLink);
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

		$feedlist = 'http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FEditors-at-Large%20Stream';
		//'http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FNominations';
		//http://feeds.feedburner.com/DHNowEditorsChoiceAndNews
		//http://www.google.com/reader/public/atom/user%2F12869634832753741059%2Fbundle%2FNominations
		return $feedlist;

	}

	# Tries to get the RSS item author for the meta.
	function get_rss_authors($item) {

		$authorArray = ($item->get_authors());
		foreach ($authorArray as $author) {

			$nameArray[] = $author->get_name();

		}
		$authors = implode(', ', $nameArray);

		return $authors;

	}

	# This function takes measures to try and get item content throguh methods of increasing reliability, but decreasing relevance.
	public function get_content_through_aggregator($url){
		global $rsspf;

		set_time_limit(0);
		//$this->set_error_handler("customError");
		$url = $rsspf->de_https($url);
		$descrip = '';
		//$url = http_build_url($urlParts, HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT);
		//print_r($url);
		# First run it through Readability.
		$descrip = $rsspf->readability_object($url);
		//print_r($url);
		# If that doesn't work...
		while (!$descrip) {
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

}
