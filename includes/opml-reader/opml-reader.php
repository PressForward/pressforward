<?php

$file="http://www.google.com/reader/public/subscriptions/user%2F10862070116690190079%2Fbundle%2FWriting%2BTech%20Bundle";

if (fopen ($file, "r")) {
	$opml_data = simplexml_load_file($file);

}

//Site data
$a = array();
//Feed URI
$b = array();
$c = 0;

/** Get XML data:
  * supplies:
  * [text] - Text version of title
  * [text] - Text version of title
  * [type] - Feed type (should be rss)
  * [xmlUrl] - location of the RSS feed on the site.
  * [htmlUrl] - The site home URI.
**/
foreach ($opml_data->body->outline->outline as $data){
	$a[] = reset($data);
}
// Pulls out the feed location. 
foreach ($a as $outline) {
	$b[] = $outline['xmlUrl'];
}


?>