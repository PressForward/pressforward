<?php
require_once(dirname(__FILE__).'/config.php');
// check for custom index.php (custom_index.php)
if (!defined('_FF_FTR_INDEX')) {
	define('_FF_FTR_INDEX', true);
	if (file_exists(dirname(__FILE__).'/custom_index.php')) {
		include(dirname(__FILE__).'/custom_index.php');
		exit;
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Full-Text RSS Feeds | from fivefilters.org</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />	
	<meta name="robots" content="noindex, follow" />
	<script type="text/javascript" src="niceforms.js"></script>	
	<link rel="stylesheet" href="css/niceforms-default.css" type="text/css" media="screen" />
	<script type="text/javascript" src="js/niceforms.js"></script>
	<!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>-->
	<script type="text/javascript" src="js/jquery-1.4.4.min.js"></script>
	<script type="text/javascript">
	var baseUrl = 'http://'+window.location.host+window.location.pathname.replace(/(\/index\.php|\/)$/, '');
	$(document).ready(function() {
		$('#form').submit(function() {
			$('#url').val($('#url').val().replace(/^http:\/\//i, ''));
			return true;
		});
	});
	</script>
  </head>
  <body style="background-color: #eee; margin: 0; line-height: 1.4em;">
	<div id="container">
	<h1 style="padding-bottom: 30px;">Full-Text RSS <?php echo _FF_FTR_VERSION; ?> <span style="font-size: .7em; font-weight: normal;">&mdash; from <a href="http://fivefilters.org">FiveFilters.org</a></span></h1>
    <form method="get" action="makefulltextfeed.php" id="form" class="niceform">
	<fieldset>
		<legend>Create full-text feed from feed or webpage URL</legend>
		<dl>
			<dt><label for="url">Enter URL:</label></dt>
			<dd><input type="text" id="url" name="url" style="width: 450px;" /></dd>
		</dl>
	</fieldset>
	<fieldset>
	<legend>Options</legend>
	<?php if ($options->extraction_pattern == 'user') { ?>
	<dl>
		<dt><label for="what">Extraction pattern:</label></dt>
		<dd><input type="text" id="what" name="what" value="auto" style="width: 250px;" /></dd>
	</dl>
	<?php } ?>	
	<?php if (isset($options->api_keys) && !empty($options->api_keys)) { ?>
	<dl>
		<dt><label for="key">API key:</label></dt>
		<dd><input type="text" id="key" name="key" style="width: 250px;" /></dd>
	</dl>
	<?php } ?>
	<dl>
		<dt><label for="max">Max items:</label></dt>
		<dd><select name="max" id="max">
			<?php for ($i = 1; $i <= $options->max_entries; $i++) {
				printf("<option value=\"%s\"%s>%s</option>\n", $i, ($i==$options->default_entries) ? ' selected="selected"' : '', $i);
				}
			?>
			</select>
		</dd>
	</dl>
	<dl>
		<dt><label for="links">Links:</label></dt>
		<dd><select name="links">
			<option value="preserve" selected="selected">preserve</option>
			<option value="footnotes">add to footnotes</option>
			<option value="remove">remove</option>
			</select></label></dd>
	</dl>
	<?php if ($options->exclude_items_on_fail == 'user') { ?>
	<dl>
		<dt><label for="exc">If extraction fails:</label></dt>
		<dd><select name="exc">
			<option value="" selected="selected">keep item in feed</option>
			<option value="1">remove item from feed</option>
			</select></label></dd>
	</dl>
	<?php } ?>
	</fieldset>
	<fieldset class="action" style="background: transparent; border: none; margin-bottom: 20px; padding: 0;">
		<input type="submit" id="sudbmit" name="submit" value="Create Feed" />
	</fieldset>
	</form>
	
	<hr />
	
	<h2>For the site owner</h2>
	
	<p>Thanks for downloading and setting this up. If you haven't done so already, <a href="ftr_compatibility_test.php">check server compatibility</a>
	to see if your environment will support this application. Full-Text RSS runs on most shared web hosting environments.</p>
	<h3>Configure</h3>
	<p>In addition to the options above, Full-Text RSS can be configured to better suit your needs. Features include:</p>
	<ul>
		<li>Site patterns for better control over extraction (<a href="site_config/README.txt">more info</a>)</li>
		<li>Restrict access to a pre-defined set of URLs or block certain URLs</li>
		<li>Restrict the maximum number of feed items to be processed</li>
		<li>JSON output</li>
		<li>Prepend or append an HTML fragment to each feed item processed</li>
		<li>Caching</li>		
	</ul>
	<p>Please refer to the <a href="http://fivefilters.org/content-only/guide/user_guide_2.8.pdf">user guide</a> for more information.</p>
	<p><?php if (!file_exists('custom_config.php')) { ?>To change the configuration, save a copy of <tt>config.php</tt> as <tt>custom_config.php</tt> and make any changes you like to it.<?php } else { ?>To change the configuration, edit <tt>custom_config.php</tt> and make any changes you like.<?php } ?></p>

	<p>If everything works fine, feel free to modify this page by saving it as <tt>custom_index.php</tt> and change it to whatever you like.</p>
	
	<h3>Sharing is caring</h3>
	If you plan	to offer this service to others through your hosted copy, please keep a download link so users can grab a copy of the code if they 
	want it (you can either offer the download yourself, or link to the download page on fivefilters.org to support us). 
	That's one requirement of the <a href="http://www.gnu.org/licenses/agpl-3.0.html" title="AGPLv3">license</a>.</p>
	<p>Thanks! :)</p>

	<h3 id="donate">Updates</h3>
	<p>To see if you're running the latest version, <a href="http://fivefilters.org/content-only/latest_version.php?version=<?php echo urlencode(_FF_FTR_VERSION); ?>">check for updates</a>.</p>
	
	<h3 id="donate">Support</h3>
	<p>We have a <a href="https://member.fivefilters.org/f/">public forum</a> which anyone can use to discuss any issues, post questions and find answers (it's free to join and post).</p>
	<p>We provide a little more information in the section below, but if you need help with anything, you can also email us at <a href="mailto:fivefilters@fivefilters.org">fivefilters@fivefilters.org</a>.</p>
	
	<hr />
	
	<h2>For everyone</h2>
	
	<h3>About</h3>
	<p>This is a free software project to help people extract content from web pages. It can extract content from a standard HTML page and return a 1-item feed or it can transform an existing feed into a full-text feed. It is being developed as part of the <a href="http://fivefilters.org">Five Filters</a> project to promote independent, non-corporate media.</p>
	
	<h3>Bookmarklet</h3>
	<p>To easily transform partial-feeds you encounter (or convert any content on a page into a 1-item feed), drag the link below to your browser's bookmarks toolbar.
	Then whenever you'd like a full-text feed, click the bookmarklet.</p>
	<p>Drag this: 
	<script type="text/javascript">
	document.write('<a style="cursor: move;" onclick="alert(\'drag to bookmarks toolbar\'); return false;" href="javascript:location.href=\''+baseUrl+'/makefulltextfeed.php?url=\'+encodeURIComponent(document.location.href);">Full-Text RSS</a>');
	</script>
	
	<h3 id="api">API</h3>
	<p>To extract content from a web page or to transform an existing partial feed to full text, pass the URL (<a href="http://meyerweb.com/eric/tools/dencoder/">encoded</a>) in the querystring to the following URL:</p>
	<ul>
		<li style="font-family: monospace;"><script type="text/javascript">document.write(baseUrl);</script>/makefulltextfeed.php?url=<strong>[url]</strong></li>
	</ul>
	
	<p>All the parameters in the form above can be passed in this way. Examine the URL in the addressbar after you click 'Create Feed' to see the values.</p>
	
	<p>Note: If you're not hosting this yourself, you do not have to rely on an external API if you don't want to &mdash; this is a free software (open source) 
	project licensed under the AGPL. You're free to <a href="http://fivefilters.org/content-only/#download">download your own copy</a>.</p>
	
	<h3>Source Code and Technologies</h3>
	<p><p>The application uses <a href="http://en.wikipedia.org/wiki/PHP">PHP</a>, <a href="http://www.keyvan.net/2010/08/php-readability/">PHP Readability</a>, <a href="http://simplepie.org/">SimplePie</a>, <a href="http://www.ajaxray.com/blog/2008/03/08/php-universal-feed-generator-supports-rss-10-rss-20-and-atom/">FeedWriter</a>, Humble HTTP Agent. Depending on your configuration, these optional components may also be used: <a href="http://framework.zend.com/manual/en/zend.cache.introduction.html">Zend Cache</a>, <a href="http://framework.zend.com/manual/en/zend.dom.query.html">Zend DOM Query</a>, <a href="http://code.google.com/p/rolling-curl/">Rolling Curl</a> and <a href="http://hg.gsnedders.com/iri/">IRI</a>. Readability is the magic piece of code that tries to identify and extract the content block from any given web page.</p>

	<h3>System Requirements</h3>
	
	<p>PHP 5.2 or above is required. A simple shared web hosting account will work fine.
	The code has been tested on Windows and Linux using the Apache web server. If you're a Windows user, you can try it on your own machine using <a href="http://www.wampserver.com/en/index.php">WampServer</a>. It has also been reported as working under IIS, but we have not tested this ourselves.</p>
	
	<h3 id="download">Download</h3>
	<p>Download from <a href="http://fivefilters.org/content-only/#download">fivefilters.org</a> - old versions are available in the code repository.</p>
	
	<h3>License</h3>
	<p><a href="http://en.wikipedia.org/wiki/Affero_General_Public_License" style="border-bottom: none;"><img src="images/agplv3.png" alt="AGPL logo" /></a><br />This web application is licensed under the <a href="http://en.wikipedia.org/wiki/Affero_General_Public_License">AGPL version 3</a> &mdash; which basically means if you use the code to offer the same or similar service for your users, you are also required to share the code with your users so they can examine the code and run it for themselves. (<a href="http://www.clipperz.com/users/marco/blog/2008/05/30/freedom_and_privacy_cloud_call_action">More on why this is important.</a>)</p> 
	<p>The libraries used by the application are licensed as follows...</p>
	<ul>
		<li>PHP Readability: <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License v2</a></li>
		<li>SimplePie: <a href="http://en.wikipedia.org/wiki/BSD_license">BSD</a></li>
		<li>FeedWriter: <a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html">GPL v2</a></li>
		<li>Humble HTTP Agent: <a href="http://en.wikipedia.org/wiki/Affero_General_Public_License">AGPL v3</a></li>
		<li>Zend: <a href="http://framework.zend.com/license/new-bsd">New BSD</a></li>
		<li>IRI: <a href="http://en.wikipedia.org/wiki/BSD_license">BSD</a></li>
		<li>Rolling Curl: <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License v2</a></li>		
	</ul>
	
	</div>
  </body>
</html>