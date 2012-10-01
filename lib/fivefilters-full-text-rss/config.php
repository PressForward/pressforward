<?php
/* Full-Text RSS config */

// ......IMPORTANT......................................
// .....................................................
// Please do not change this file (config.php) directly.
// Save a copy as custom_config.php and make your
// changes to that instead. It will automatically
// override anything in config.php. Because config.php
// always gets loaded anyway, you can simply specify
// options you'd like to override in custom_config.php.
// .....................................................

// Create config object
$options = new stdClass();

// Enable service
// ----------------------
// Set this to false if you want to disable the service.
// If set to false, no feed is produced and users will 
// be told that the service is disabled.
$options->enabled = true;

// Default entries (without API key)
// ----------------------
// The number of feed items to process when no API key is supplied.
$options->default_entries = 5;

// Max entries (without API key)
// ----------------------
// The maximum number of feed items to process when no API key is supplied.
$options->max_entries = 10;

// Rewrite relative URLs
// ----------------------
// With this enabled relative URLs found in the extracted content
// block are automatically rewritten as absolute URLs.
$options->rewrite_relative_urls = true;

// Exclude items if extraction fails
// ---------------------------------
// Excludes items from the resulting feed
// if we cannot extract any content from the
// item URL.
// Possible values...
// Enable: true
// Disable: false (default)
// User decides: 'user' (this option will appear on the form)
$options->exclude_items_on_fail = 'user';

// Extraction pattern
// ---------------------------------
// Specify what should get extracted
// Possible values:
// Auto detect: 'auto'
// Custom: css string (e.g. 'div#content')
// Element within auto-detected block: 'auto ' + css string (e.g. 'auto p')
// User decides: 'user' (default, this option will appear on the form)
$options->extraction_pattern = 'user';

// Enable caching
// ----------------------
// Enable this if you'd like to cache results
// for 10 minutes. Initially it's best
// to keep this disabled to make sure everything works
// as expected.
$options->caching = false;

// Cache directory
// ----------------------
// Only used if caching is true
$options->cache_dir = dirname(__FILE__).'/cache';

// Message to prepend (without API key)
// ----------------------
// HTML to insert at the beginning of each feed item when no API key is supplied.
// Substitution tags:
// {url} - Feed item URL
// {effective-url} - Feed item URL after we've followed all redirects
$options->message_to_prepend = '';

// Message to append (without API key)
// ----------------------
// HTML to insert at the end of each feed item when no API key is supplied.
// Substitution tags:
// {url} - Feed item URL
// {effective-url} - Feed item URL after we've followed all redirects
$options->message_to_append = '';

// URLs to allow
// ----------------------
// List of URLs (or parts of a URL) which the service will accept.
// If the list is empty, all URLs (except those specified in the blocked list below)
// will be permitted.
// Empty: array();
// Non-empty example: array('example.com', 'anothersite.org');
$options->allowed_urls = array();

// URLs to block
// ----------------------
// List of URLs (or parts of a URL) which the service will not accept.
// Note: this list is ignored if allowed_urls is not empty
$options->blocked_urls = array();

// Error message when content extraction fails (without API key)
// ----------------------
$options->error_message = '[unable to retrieve full-text content]';

/////////////////////////////////////////////////
/// ADVANCED OPTIONS ////////////////////////////
/////////////////////////////////////////////////

// Alternative Full-Text RSS service URL
// ----------------------
// This option is to offer very simple load distribution for the service.
// If you've set up another instance of the Full-Text RSS service on a different
// server, you can enter its full URL here. 
// E.g. 'http://my-other-server.org/full-text-rss/makefulltextfeed.php'
// If you specify a URL here, 50% of the requests to makefulltextfeed.php on
// this server will be redirected to the URL specified here.
$options->alternative_url = '';

// Cache directory level
// ----------------------
// Spread cache files over different directories (only used if caching is enabled).
// Used to prevent large number of files in one directory.
// This corresponds to Zend_Cache's hashed_directory_level
// see http://framework.zend.com/manual/en/zend.cache.backends.html
// It's best not to change this if you're unsure.
$options->cache_directory_level = 0;

// Cache cleanup
// -------------
// 0 = script will not clean cache (rename cachecleanup.php and use it for scheduled (e.g. cron) cache cleanup)
// 1 = clean cache everytime the script runs (not recommended)
// 100 = (roughly) clean cache once out of every 100 script runs
// x = (roughly) clean cache once out of every x script runs
// ...you get the idea :)
$options->cache_cleanup = 100;

/////////////////////////////////////////////////
/// DEPRECATED OPTIONS
/// THESE OPTIONS WILL CHANGE IN VERSION 3.0
/// WE RECOMMEND YOU DO NOT USE THEM
/////////////////////////////////////////////////

// Restrict service (deprecated)
// -----------------------------
// Set this to true if you'd like certain features
// to be available only to key holders.
// Affected features:
// * Link handling (disabled for non-key holders if set to true)
// * Cache time (20 minutes for non-key holders if set to true)
$options->restrict = false;

// API keys (deprecated)
// ----------------------
// NOTE: You do not need an API key from fivefilters.org to run your own 
// copy of the code. This is here if you'd like to offer others an API key 
// to access _your_ copy.
// Keys let you group users - those with a key and those without - and
// restrict access to the service to those without a key.
// If you want everyone to access the service in the same way, you can
// leave the array below empty and ignore the API key options further down.
// The options further down in this file will allow you to specify
// how the service should behave in each mode.
$options->api_keys = array();

// Default entries (with API key) (deprecated)
// ----------------------
// The number of feed items to process when a valid API key is supplied.
$options->default_entries_with_key = 5;

// Max entries (with API key) (deprecated)
// ----------------------
// The maximum number of feed items to process when a valid API key is supplied.
$options->max_entries_with_key = 10;

// Message to prepend (with API key) (deprecated)
// ----------------------
// HTML to insert at the beginning of each feed item when a valid API key is supplied.
$options->message_to_prepend_with_key = '';

// Message to append (with API key) (deprecated)
// ----------------------
// HTML to insert at the end of each feed item when a valid API key is supplied.
$options->message_to_append_with_key = '';

// Error message when content extraction fails (with API key) (deprecated)
// ----------------------
$options->error_message_with_key = '[unable to retrieve full-text content]';

/////////////////////////////////////////////////
/// DO NOT CHANGE ANYTHING BELOW THIS ///////////
/////////////////////////////////////////////////

if (!defined('_FF_FTR_VERSION')) define('_FF_FTR_VERSION', '2.8');

if ((basename(__FILE__) == 'config.php') && (file_exists(dirname(__FILE__).'/custom_config.php'))) {
	require_once(dirname(__FILE__).'/custom_config.php');
}