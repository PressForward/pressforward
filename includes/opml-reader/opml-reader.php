<?php

//$file="http://www.google.com/reader/public/subscriptions/user%2F10862070116690190079%2Fbundle%2FWriting%2BTech%20Bundle";

class OPML_reader {

	function open_OPML($file) {
		if (fopen ($file, "r")) {
			$opml_data = simplexml_load_file($file);
			return $opml_data;
		} else {
			return false;
		}
	}
	
	# Pass the URL and if you want to return an array of objects or of urls.
	function get_OPML_data($url, $is_array = true){
		
		$opml_data = $this->open_OPML($url);
		if (false == $opml_data){
			return false;
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
		foreach ($opml_data->body->outline as $folder){
			foreach ($folder->outline as $data){
				$a[] = reset($data);
			}
			// Pulls out the feed location. 
			foreach ($a as $outline) {
				$b[] = $outline['xmlUrl'];
			}
			
			if ($is_array){
				return $b;
			} else {
				return $a;
			}
		}
	}

}
?>