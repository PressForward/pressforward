<?php
// Create Full-Text Feeds
// Author: Keyvan Minoukadeh
// Copyright (c) 2011 Keyvan Minoukadeh
// License: AGPLv3
// Version: 2.8
// Date: 2011-05-23

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Usage
// -----
// Request this file passing it your feed in the querystring: makefulltextfeed.php?url=mysite.org
// The following options can be passed in the querystring:
// * URL: url=[feed or website url] (required, should be URL-encoded - in php: urlencode($url))
// * URL points to HTML (not feed): html=true (optional, by default it's automatically detected)
// * API key: key=[api key] (optional, refer to config.php)
// * Max entries to process: max=[max number of items] (optional)

error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
@set_time_limit(120);

// set include path
set_include_path(realpath(dirname(__FILE__).'/libraries').PATH_SEPARATOR.get_include_path());

// Autoloading of classes allows us to include files only when they're
// needed. If we've got a cached copy, for example, only Zend_Cache is loaded.
function __autoload($class_name) {
	static $mapping = array(
		// Include SimplePie for RSS/Atom parsing
		'SimplePie' => 'simplepie/simplepie.class.php',
		'SimplePie_Misc' => 'simplepie/simplepie.class.php',	
		'SimplePie_HTTP_Parser' => 'simplepie/simplepie.class.php',
		'SimplePie_File' => 'simplepie/simplepie.class.php',
		// Include FeedCreator for RSS/Atom creation
		'FeedWriter' => 'feedwriter/FeedWriter.php',
		'FeedItem' => 'feedwriter/FeedItem.php',
		// Include ContentExtractor and Readability for identifying and extracting content from URLs
		'ContentExtractor' => 'content-extractor/ContentExtractor.php',
		'SiteConfig' => 'content-extractor/SiteConfig.php',
		'Readability' => 'readability/Readability.php',
		// Include Humble HTTP Agent to allow parallel requests and response caching
		'HumbleHttpAgent' => 'humble-http-agent/HumbleHttpAgent.php',
		'SimplePie_HumbleHttpAgent' => 'humble-http-agent/SimplePie_HumbleHttpAgent.php',
		'CookieJar' => 'humble-http-agent/CookieJar.php',
		// Include IRI class for resolving relative URLs
		'IRI' => 'iri/iri.php',
		// Include Zend Cache to improve performance (cache results)
		'Zend_Cache' => 'Zend/Cache.php',
		// Include Zend CSS to XPath for dealing with custom patterns
		'Zend_Dom_Query_Css2Xpath' => 'Zend/Dom/Query/Css2Xpath.php'
	);
	if (isset($mapping[$class_name])) {
		//echo "Loading $class_name\n<br />";
		require_once $mapping[$class_name];
		return true;
	} else {
		return false;
	}
}

////////////////////////////////
// Load config file if it exists
////////////////////////////////
require_once(dirname(__FILE__).'/config.php');

////////////////////////////////
// Check if service is enabled
////////////////////////////////
if (!$options->enabled) { 
	die('The full-text RSS service is currently disabled'); 
}

////////////////////////////////
// Check for feed URL
////////////////////////////////
if (!isset($_GET['url'])) { 
	die('No URL supplied'); 
}
$url = trim($_GET['url']);
if (!preg_match('!^https?://.+!i', $url)) {
	$url = 'http://'.$url;
}

$url = filter_var($url, FILTER_SANITIZE_URL);
$test = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
// deal with bug http://bugs.php.net/51192 (present in PHP 5.2.13 and PHP 5.3.2)
if ($test === false) {
	$test = filter_var(strtr($url, '-', '_'), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
}
if ($test !== false && $test !== null && preg_match('!^https?://!', $url)) {
	// all okay
	unset($test);
} else {
	die('Invalid URL supplied');
}

////////////////////////////////
// Redirect to alternative URL?
////////////////////////////////
if ($options->alternative_url != '' && !isset($_GET['redir']) && mt_rand(0, 100) > 50) {
	$redirect = $options->alternative_url.'?redir=true&url='.urlencode($url);
	if (isset($_GET['html'])) $redirect .= '&html='.urlencode($_GET['html']);	
	if (isset($_GET['key'])) $redirect .= '&key='.urlencode($_GET['key']);
	if (isset($_GET['max'])) $redirect .= '&max='.(int)$_GET['max'];
	if (isset($_GET['links'])) $redirect .= '&links='.$_GET['links'];
	if (isset($_GET['exc'])) $redirect .= '&exc='.$_GET['exc'];
	if (isset($_GET['what'])) $redirect .= '&what='.$_GET['what'];	
	if (isset($_GET['format'])) $redirect .= '&format='.$_GET['format'];	
	header("Location: $redirect");
	exit;
}

/////////////////////////////////
// Redirect to hide API key
/////////////////////////////////
if (isset($_GET['key']) && ($key_index = array_search($_GET['key'], $options->api_keys)) !== false) {
	$host = $_SERVER['HTTP_HOST'];
	$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
	$redirect = 'http://'.htmlspecialchars($host.$path).'/makefulltextfeed.php?url='.urlencode($url);
	$redirect .= '&key='.$key_index;
	$redirect .= '&hash='.urlencode(sha1($_GET['key'].$url));
	if (isset($_GET['html'])) $redirect .= '&html='.urlencode($_GET['html']);
	if (isset($_GET['max'])) $redirect .= '&max='.(int)$_GET['max'];
	if (isset($_GET['links'])) $redirect .= '&links='.urlencode($_GET['links']);
	if (isset($_GET['exc'])) $redirect .= '&exc='.urlencode($_GET['exc']);
	if (isset($_GET['what'])) $redirect .= '&what='.urlencode($_GET['what']);
	if (isset($_GET['format'])) $redirect .= '&format='.urlencode($_GET['format']);
	header("Location: $redirect");
	exit;
}

///////////////////////////////////////////////
// Set timezone.
// Prevents warnings, but needs more testing - 
// perhaps if timezone is set in php.ini we
// don't need to set it at all...
///////////////////////////////////////////////
if (!ini_get('date.timezone') || !@date_default_timezone_set(ini_get('date.timezone'))) {
	date_default_timezone_set('UTC');
}

///////////////////////////////////////////////
// Check if the request is explicitly for an HTML page
///////////////////////////////////////////////
$html_only = (isset($_GET['html']) && ($_GET['html'] == '1' || $_GET['html'] == 'true'));

///////////////////////////////////////////////
// Check if valid key supplied
///////////////////////////////////////////////
$valid_key = false;
if (isset($_GET['key']) && isset($_GET['hash']) && isset($options->api_keys[(int)$_GET['key']])) {
	$valid_key = ($_GET['hash'] == sha1($options->api_keys[(int)$_GET['key']].$url));
}

///////////////////////////////////////////////
// Check URL against list of blacklisted URLs
///////////////////////////////////////////////
if (!url_allowed($url)) die('URL blocked');

///////////////////////////////////////////////
// Max entries
// see config.php to find these values
///////////////////////////////////////////////
if (isset($_GET['max'])) {
	$max = (int)$_GET['max'];
	if ($valid_key) {
		$max = min($max, $options->max_entries_with_key);
	} else {
		$max = min($max, $options->max_entries);
	}
} else {
	if ($valid_key) {
		$max = $options->default_entries_with_key;
	} else {
		$max = $options->default_entries;
	}
}

///////////////////////////////////////////////
// Link handling
///////////////////////////////////////////////
if (($valid_key || !$options->restrict) && isset($_GET['links']) && in_array($_GET['links'], array('preserve', 'footnotes', 'remove'))) {
	$links = $_GET['links'];
} else {
	$links = 'preserve';
}

///////////////////////////////////////////////
// Exclude items if extraction fails
///////////////////////////////////////////////
if ($options->exclude_items_on_fail == 'user') {
	$exclude_on_fail = (isset($_GET['exc']) && ($_GET['exc'] == '1'));
} else {
	$exclude_on_fail = $options->exclude_items_on_fail;
}

///////////////////////////////////////////////
// Extraction pattern
///////////////////////////////////////////////
$auto_extract = true;
if ($options->extraction_pattern == 'user') {
	$extract_pattern = (isset($_GET['what']) ? trim($_GET['what']) : 'auto');
} else {
	$extract_pattern = trim($options->extraction_pattern);
}
if (($extract_pattern != '') && ($extract_pattern != 'auto')) {
	// split pattern by space (currently only descendants of 'auto' are recognised)
	$extract_pattern = preg_split('/\s+/', $extract_pattern, 2);
	if ($extract_pattern[0] == 'auto') { // parent selector is 'auto'
		$extract_pattern = $extract_pattern[1];
	} else {
		$extract_pattern = implode(' ', $extract_pattern);
		$auto_extract = false;
	}
	// Convert CSS to XPath
	// Borrowed from Symfony's cssToXpath() function: https://github.com/fabpot/symfony/blob/master/src/Symfony/Component/CssSelector/Parser.php
	// (Itself based on Python's lxml library)
	if (preg_match('#^\w+\s*$#u', $extract_pattern, $match)) {
		$extract_pattern = '//'.trim($match[0]);
	} elseif (preg_match('~^(\w*)#(\w+)\s*$~u', $extract_pattern, $match)) {
		$extract_pattern = sprintf("%s%s[@id = '%s']", '//', $match[1] ? $match[1] : '*', $match[2]);
	} elseif (preg_match('#^(\w*)\.(\w+)\s*$#u', $extract_pattern, $match)) {
		$extract_pattern = sprintf("%s%s[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]", '//', $match[1] ? $match[1] : '*', $match[2]);
	} else {
		// if the patterns above do not match, invoke Zend's CSS to Xpath function
		$extract_pattern = Zend_Dom_Query_Css2Xpath::transform($extract_pattern);
	}
} else {
	$extract_pattern = false;
}

/////////////////////////////////////
// Check for valid format
// (stick to RSS (or RSS as JSON) for the time being)
/////////////////////////////////////
if (isset($_GET['format']) && $_GET['format'] == 'json') {
	$format = 'json';
} else {
	$format = 'rss';
}

//////////////////////////////////
// Check for cached copy
//////////////////////////////////
if ($options->caching) {
	$frontendOptions = array(
	   'lifetime' => ($valid_key || !$options->restrict) ? 10*60 : 20*60, // cache lifetime of 10 or 20 minutes
	   'automatic_serialization' => false,
	   'write_control' => false,
	   'automatic_cleaning_factor' => $options->cache_cleanup,
	   'ignore_user_abort' => false
	);
	$backendOptions = array(
		'cache_dir' => ($valid_key) ? $options->cache_dir.'/rss-with-key/' : $options->cache_dir.'/rss/', // directory where to put the cache files
		'file_locking' => false,
		'read_control' => true,
		'read_control_type' => 'strlen',
		'hashed_directory_level' => $options->cache_directory_level,
		'hashed_directory_umask' => 0777,
		'cache_file_umask' => 0664,
		'file_name_prefix' => 'ff'
	);

	// getting a Zend_Cache_Core object
	$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
	$cache_id = md5($max.$url.$valid_key.$links.$exclude_on_fail.$auto_extract.$extract_pattern.$format.(int)isset($_GET['pubsub']));
	
	if ($data = $cache->load($cache_id)) {
		if ($format == 'json') {
			header("Content-type: application/json; charset=UTF-8");
		} else {
			header("Content-type: text/xml; charset=UTF-8");
		}
		if (headers_sent()) die('Some data has already been output, can\'t send RSS file');
		echo $data;
		exit;
	}
}

//////////////////////////////////
// Set Expires header
//////////////////////////////////
if ($valid_key) {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+(60*10)) . ' GMT');
} else {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+(60*20)) . ' GMT');
}

//////////////////////////////////
// Set up HTTP agent
//////////////////////////////////
$http = new HumbleHttpAgent();

//////////////////////////////////
// Set up Content Extractor
//////////////////////////////////
$extractor = new ContentExtractor(dirname(__FILE__).'/site_config/custom', dirname(__FILE__).'/site_config/standard');

/*
if ($options->caching) {
	$frontendOptions = array(
	   'lifetime' => 30*60, // cache lifetime of 30 minutes
	   'automatic_serialization' => true,
	   'write_control' => false,
	   'automatic_cleaning_factor' => $options->cache_cleanup,
	   'ignore_user_abort' => false
	); 
	$backendOptions = array(
		'cache_dir' => $options->cache_dir.'/http-responses/', // directory where to put the cache files
		'file_locking' => false,
		'read_control' => true,
		'read_control_type' => 'strlen',
		'hashed_directory_level' => $options->cache_directory_level,
		'hashed_directory_umask' => 0777,
		'cache_file_umask' => 0664,
		'file_name_prefix' => 'ff'
	);
	$httpCache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
	$http->useCache($httpCache);
}
*/

////////////////////////////////
// Get RSS/Atom feed
////////////////////////////////
if (!$html_only) {
	// configure SimplePie HTTP extension class to use our HumbleHttpAgent instance
	SimplePie_HumbleHttpAgent::set_agent($http);
	$feed = new SimplePie();
	$feed->set_file_class('SimplePie_HumbleHttpAgent');
	//$feed->set_feed_url($url); // colons appearing in the URL's path get encoded
	$feed->feed_url = $url;
	$feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
	$feed->set_timeout(20);
	$feed->enable_cache(false);
	$feed->set_stupidly_fast(true);
	$feed->enable_order_by_date(false); // we don't want to do anything to the feed
	$feed->set_url_replacements(array());
	// initialise the feed
	// the @ suppresses notices which on some servers causes a 500 internal server error
	$result = @$feed->init();
	//$feed->handle_content_type();
	//$feed->get_title();
	if ($result && (!is_array($feed->data) || count($feed->data) == 0)) {
		die('Sorry, no feed items found');
	}
}

////////////////////////////////////////////////////////////////////////////////
// Our given URL is not a feed, so let's create our own feed with a single item:
// the given URL. This basically treats all non-feed URLs as if they were
// single-item feeds.
////////////////////////////////////////////////////////////////////////////////
$isDummyFeed = false;
if ($html_only || !$result) {
	$isDummyFeed = true;
	unset($feed, $result);
	// create single item dummy feed object
	class DummySingleItemFeed {
		public $item;
		function __construct($url) { $this->item = new DummySingleItem($url); }
		public function get_title() { return ''; }
		public function get_description() { return 'Content extracted from '.$this->item->url; }
		public function get_link() { return $this->item->url; }
		public function get_image_url() { return false; }
		public function get_items($start=0, $max=1) { return array(0=>$this->item); }
	}
	class DummySingleItem {
		public $url;
		function __construct($url) { $this->url = $url; }
		public function get_permalink() { return $this->url; }
		public function get_title() { return ''; }
		public function get_date($format='') { return false; }
		public function get_author() { return false; }
		public function get_description() { return ''; }
	}
	$feed = new DummySingleItemFeed($url);
}

////////////////////////////////////////////
// Create full-text feed
////////////////////////////////////////////
$output = new FeedWriter();
$output->setTitle($feed->get_title());
$output->setDescription($feed->get_description());
$output->setXsl('css/feed.xsl'); // Chrome uses this, most browsers ignore it
if ($valid_key && isset($_GET['pubsub'])) { // used only on fivefilters.org at the moment
	$output->addHub('http://fivefilters.superfeedr.com/');
	$output->addHub('http://pubsubhubbub.appspot.com/');
	$output->setSelf('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
}
$output->setLink($feed->get_link()); // Google Reader uses this for pulling in favicons
if ($img_url = $feed->get_image_url()) {
	$output->setImage($feed->get_title(), $feed->get_link(), $img_url);
}
if ($format == 'atom') {
	$output->setChannelElement('updated', date(DATE_ATOM));
	$output->setChannelElement('author', array('name'=>'Five Filters', 'uri'=>'http://fivefilters.org'));
}

////////////////////////////////////////////
// Loop through feed items
////////////////////////////////////////////
$items = $feed->get_items(0, $max);	
// Request all feed items in parallel (if supported)
$urls_sanitized = array();
$urls = array();
foreach ($items as $key => $item) {
	$permalink = htmlspecialchars_decode($item->get_permalink());
	// Colons in URL path segments get encoded by SimplePie, yet some sites expect them unencoded
	$permalink = str_replace('%3A', ':', $permalink);
	$permalink = $http->validateUrl($permalink);
	if ($permalink) {
		$urls_sanitized[] = $permalink;
	}
	$urls[$key] = $permalink;
}
$http->fetchAll($urls_sanitized);
//$http->cacheAll();

foreach ($items as $key => $item) {
	$extract_result = false;
	$permalink = $urls[$key];
	$newitem = $output->createNewItem();
	$newitem->setTitle(htmlspecialchars_decode($item->get_title()));
	if ($valid_key && isset($_GET['pubsub'])) { // used only on fivefilters.org at the moment
		if ($permalink !== false) {
			$newitem->setLink('http://fivefilters.org/content-only/redirect.php?url='.urlencode($permalink));
		} else {
			$newitem->setLink('http://fivefilters.org/content-only/redirect.php?url='.urlencode($item->get_permalink()));
		}
	} else {
		if ($permalink !== false) {
			$newitem->setLink($permalink);
		} else {
			$newitem->setLink($item->get_permalink());
		}
	}
	if ($permalink && ($response = $http->get($permalink, true)) && $response['status_code'] < 300) {
		$effective_url = $response['effective_url'];
		if (!url_allowed($effective_url)) continue;
		$html = $response['body'];
		// remove strange things
		$html = str_replace('</[>', '', $html);
		$html = convert_to_utf8($html, $response['headers']);
		if ($auto_extract) {
			// check site config for single page URL - fetch it if found
			if ($single_page_response = getSinglePage($item, $html, $effective_url)) {
				$html = $single_page_response['body'];
				// remove strange things
				$html = str_replace('</[>', '', $html);	
				$html = convert_to_utf8($html, $single_page_response['headers']);
				$effective_url = $single_page_response['effective_url'];
				unset($single_page_response);
			}
			$extract_result = $extractor->process($html, $effective_url);
			$readability = $extractor->readability;
			$content_block = ($extract_result) ? $extractor->getContent() : null;
			$title = ($extract_result) ? $extractor->getTitle() : '';
		} else {
			$readability = new Readability($html, $effective_url);
			// content block is entire document (for now...)
			$content_block = $readability->dom;
			//TODO: get title
			$title = '';
		}
		// use extracted title for both feed and item title if we're using single-item dummy feed
		if ($isDummyFeed) {
			$output->setTitle($title);
			$newitem->setTitle($title);
		}
		if ($extract_pattern && isset($content_block)) {
			$xpath = new DOMXPath($readability->dom);
			$elems = @$xpath->query($extract_pattern, $content_block);
			// check if our custom extraction pattern matched
			if ($elems && $elems->length > 0) {
				$extract_result = true;				
				// get the first matched element
				$content_block = $elems->item(0);
				// clean it up
				$readability->removeScripts($content_block);
				$readability->prepArticle($content_block);
			}
		}
	}
	// if we failed to extract content...
	if (!$extract_result) {
		if ($exclude_on_fail) continue; // skip this and move to next item
		if (!$valid_key) {
			$html = $options->error_message;
		} else {
			$html = $options->error_message_with_key;
		}
		// keep the original item description
		$html .= $item->get_description();
	} else {
		$readability->clean($content_block, 'select');
		if ($options->rewrite_relative_urls) makeAbsolute($effective_url, $content_block);
		// footnotes
		if (($links == 'footnotes') && (strpos($effective_url, 'wikipedia.org') === false)) {
			$readability->addFootnotes($content_block);
		}
		if ($extract_pattern) {
			// get outerHTML
			$html = $content_block->ownerDocument->saveXML($content_block);
		} else {
			if ($content_block->childNodes->length == 1 && $content_block->firstChild->nodeType === XML_ELEMENT_NODE) {
				$html = $content_block->firstChild->innerHTML;
			} else {
				$html = $content_block->innerHTML;
			}
		}
		// post-processing cleanup
		$html = preg_replace('!<p>[\s\h\v]*</p>!u', '', $html);
		if ($links == 'remove') {
			$html = preg_replace('!</?a[^>]*>!', '', $html);
		}
		if (!$valid_key) {
			$html = make_substitutions($options->message_to_prepend).$html;
			$html .= make_substitutions($options->message_to_append);
		} else {
			$html = make_substitutions($options->message_to_prepend_with_key).$html;	
			$html .= make_substitutions($options->message_to_append_with_key);
		}
	}
	if ($format == 'atom') {
		$newitem->addElement('content', $html);
		$newitem->setDate((int)$item->get_date('U'));
		if ($author = $item->get_author()) {
			$newitem->addElement('author', array('name'=>$author->get_name()));
		}
	} else {
		if ($valid_key && isset($_GET['pubsub'])) { // used only on fivefilters.org at the moment
			$newitem->addElement('guid', 'http://fivefilters.org/content-only/redirect.php?url='.urlencode($item->get_permalink()), array('isPermaLink'=>'false'));
		} else {
			$newitem->addElement('guid', $item->get_permalink(), array('isPermaLink'=>'true'));
		}
		$newitem->setDescription($html);
		if ((int)$item->get_date('U') > 0) {
			$newitem->setDate((int)$item->get_date('U'));
		}
		if ($author = $item->get_author()) {
			$newitem->addElement('dc:creator', $author->get_name());
		}
	}
	$output->addItem($newitem);
	unset($html);
}
// output feed
if ($options->caching || $format == 'json') {
	ob_start();
	$output->genarateFeed();
	$output = ob_get_contents();
	ob_end_clean();
	if ($format == 'json') {
		$jsonrss = new stdClass();
		$jsonrss->rss = @simplexml_load_string($output);
		$output = json_encode($jsonrss);
		header("Content-type: application/json; charset=UTF-8");
	}
	if ($options->caching) $cache->save($output, $cache_id);
	echo $output;
} else {
	$output->genarateFeed();
}

///////////////////////////////
// HELPER FUNCTIONS
///////////////////////////////

function url_allowed($url) {
	global $options;
	if (!empty($options->allowed_urls)) {
		$allowed = false;
		foreach ($options->allowed_urls as $allowurl) {
			if (stristr($url, $allowurl) !== false) {
				$allowed = true;
				break;
			}
		}
		if (!$allowed) return false;
	} else {
		foreach ($options->blocked_urls as $blockurl) {
			if (stristr($url, $blockurl) !== false) {
				return false;
			}
		}
	}
	return true;
}

//////////////////////////////////////////////
// Convert $html to UTF8
// (uses HTTP headers and HTML to find encoding)
// adapted from http://stackoverflow.com/questions/910793/php-detect-encoding-and-make-everything-utf-8
//////////////////////////////////////////////
function convert_to_utf8($html, $header=null)
{
	$encoding = null;
	if ($html || $header) {
		if (is_array($header)) $header = implode("\n", $header);
		if (!$header || !preg_match_all('/^Content-Type:\s+([^;]+)(?:;\s*charset=["\']?([^;"\'\n]*))?/im', $header, $match, PREG_SET_ORDER)) {
			// error parsing the response
		} else {
			$match = end($match); // get last matched element (in case of redirects)
			if (isset($match[2])) $encoding = trim($match[2], '"\'');
		}
		if (!$encoding) {
			if (preg_match('/^<\?xml\s+version=(?:"[^"]*"|\'[^\']*\')\s+encoding=("[^"]*"|\'[^\']*\')/s', $html, $match)) {
				$encoding = trim($match[1], '"\'');
			} elseif(preg_match('/<meta\s+http-equiv=["\']Content-Type["\'] content=["\'][^;]+;\s*charset=["\']?([^;"\'>]+)/i', $html, $match)) {
				if (isset($match[1])) $encoding = trim($match[1]);
			}
		}
		if (!$encoding) {
			$encoding = 'utf-8';
		} else {
			if (strtolower($encoding) != 'utf-8') {
				if (strtolower($encoding) == 'iso-8859-1') {
					// replace MS Word smart qutoes
					$trans = array();
					$trans[chr(130)] = '&sbquo;';    // Single Low-9 Quotation Mark
					$trans[chr(131)] = '&fnof;';    // Latin Small Letter F With Hook
					$trans[chr(132)] = '&bdquo;';    // Double Low-9 Quotation Mark
					$trans[chr(133)] = '&hellip;';    // Horizontal Ellipsis
					$trans[chr(134)] = '&dagger;';    // Dagger
					$trans[chr(135)] = '&Dagger;';    // Double Dagger
					$trans[chr(136)] = '&circ;';    // Modifier Letter Circumflex Accent
					$trans[chr(137)] = '&permil;';    // Per Mille Sign
					$trans[chr(138)] = '&Scaron;';    // Latin Capital Letter S With Caron
					$trans[chr(139)] = '&lsaquo;';    // Single Left-Pointing Angle Quotation Mark
					$trans[chr(140)] = '&OElig;';    // Latin Capital Ligature OE
					$trans[chr(145)] = '&lsquo;';    // Left Single Quotation Mark
					$trans[chr(146)] = '&rsquo;';    // Right Single Quotation Mark
					$trans[chr(147)] = '&ldquo;';    // Left Double Quotation Mark
					$trans[chr(148)] = '&rdquo;';    // Right Double Quotation Mark
					$trans[chr(149)] = '&bull;';    // Bullet
					$trans[chr(150)] = '&ndash;';    // En Dash
					$trans[chr(151)] = '&mdash;';    // Em Dash
					$trans[chr(152)] = '&tilde;';    // Small Tilde
					$trans[chr(153)] = '&trade;';    // Trade Mark Sign
					$trans[chr(154)] = '&scaron;';    // Latin Small Letter S With Caron
					$trans[chr(155)] = '&rsaquo;';    // Single Right-Pointing Angle Quotation Mark
					$trans[chr(156)] = '&oelig;';    // Latin Small Ligature OE
					$trans[chr(159)] = '&Yuml;';    // Latin Capital Letter Y With Diaeresis
					$html = strtr($html, $trans);
				}
				$html = SimplePie_Misc::change_encoding($html, $encoding, 'utf-8');

				/*
				if (function_exists('iconv')) {
					// iconv appears to handle certain character encodings better than mb_convert_encoding
					$html = iconv($encoding, 'utf-8', $html);
				} else {
					$html = mb_convert_encoding($html, 'utf-8', $encoding);
				}
				*/
			}
		}
	}
	return $html;
}

function makeAbsolute($base, $elem) {
	$base = new IRI($base);
	// remove '//' in URL path (causes URLs not to resolve properly)
	if (isset($base->ipath)) $base->ipath = preg_replace('!//+!', '/', $base->ipath);
	foreach(array('a'=>'href', 'img'=>'src') as $tag => $attr) {
		$elems = $elem->getElementsByTagName($tag);
		for ($i = $elems->length-1; $i >= 0; $i--) {
			$e = $elems->item($i);
			//$e->parentNode->replaceChild($articleContent->ownerDocument->createTextNode($e->textContent), $e);
			makeAbsoluteAttr($base, $e, $attr);
		}
		if (strtolower($elem->tagName) == $tag) makeAbsoluteAttr($base, $elem, $attr);
	}
}
function makeAbsoluteAttr($base, $e, $attr) {
	if ($e->hasAttribute($attr)) {
		// Trim leading and trailing white space. I don't really like this but 
		// unfortunately it does appear on some sites. e.g.  <img src=" /path/to/image.jpg" />
		$url = trim(str_replace('%20', ' ', $e->getAttribute($attr)));
		$url = str_replace(' ', '%20', $url);
		if (!preg_match('!https?://!i', $url)) {
			$absolute = IRI::absolutize($base, $url);
			if ($absolute) {
				$e->setAttribute($attr, $absolute);
			}
		}
	}
}
function makeAbsoluteStr($base, $url) {
	$base = new IRI($base);
	// remove '//' in URL path (causes URLs not to resolve properly)
	if (isset($base->ipath)) $base->ipath = preg_replace('!//+!', '/', $base->ipath);
	if (preg_match('!^https?://!i', $url)) {
		// already absolute
		return $url;
	} else {
		$absolute = IRI::absolutize($base, $url);
		if ($absolute) return $absolute;
		return false;
	}
}
// returns single page response, or false if not found
function getSinglePage($item, $html, $url) {
	global $http;
	$host = @parse_url($url, PHP_URL_HOST);
	$site_config = SiteConfig::build($host);
	if ($site_config === false) return false;
	$splink = null;
	if (!empty($site_config->single_page_link)) {
		$splink = $site_config->single_page_link;
	} elseif (!empty($site_config->single_page_link_in_feed)) {
		// single page link xpath is targeted at feed
		$splink = $site_config->single_page_link_in_feed;
		// so let's replace HTML with feed item description
		$html = $item->get_description();
	}
	if (isset($splink)) {
		// Build DOM tree from HTML
		$readability = new Readability($html, $url);
		$xpath = new DOMXPath($readability->dom);
		// Loop through single_page_link xpath expressions
		$single_page_url = null;
		foreach ($splink as $pattern) {
			$elems = @$xpath->evaluate($pattern, $readability->dom);
			if (is_string($elems)) {
				$single_page_url = trim($elems);
				break;
			} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
				foreach ($elems as $item) {
					if ($item->hasAttribute('href')) {
						$single_page_url = $item->getAttribute('href');
						break;
					}
				}
			}
		}
		// If we've got URL, resolve against $url
		if (isset($single_page_url) && ($single_page_url = makeAbsoluteStr($url, $single_page_url))) {
			// check it's not what we have already!
			if ($single_page_url != $url) {
				// it's not, so let's try to fetch it...
				if (($response = $http->get($single_page_url, true)) && $response['status_code'] < 300) {
					return $response;
				}
			}
		}
	}
	return false;
}

function make_substitutions($string) {
	if ($string == '') return $string;
	global $item, $effective_url;
	$string = str_replace('{url}', htmlspecialchars($item->get_permalink()), $string);
	$string = str_replace('{effective-url}', htmlspecialchars($effective_url), $string);
	return $string;
}
?>