<?php

//$file="http://www.google.com/reader/public/subscriptions/user%2F10862070116690190079%2Fbundle%2FWriting%2BTech%20Bundle";

class OPML_reader {

	function __construct($file = ''){
		if (!empty($file)){
			$this->opml_file = $this->open_OPML($file);

			$this->file_url = $file;
		}
	}

	function open_OPML($file) {
        pf_log('open_OPML invoked.');
		if(1 == ini_get('allow_url_fopen')){
			pf_log('Using simplexml_load_file to load OPML.');
            $file = simplexml_load_file($file);
		} else {
            pf_log('Using cURL to load OPML file.');
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $file);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($ch);
			curl_close($ch);
			$file = simplexml_load_string($data);
		}

    #echo '<pre>'; var_dump($data); #die();
		if (empty($file)) {
            pf_log('Received an empty file.');
			return false;
		} else {
            pf_log('Received:');
            pf_log($file);
			$opml_data = $file;
			return $opml_data;
		}
	}

	function get_OPML_obj($url = false){
		if (false == $url){
			$opml_data = $this->opml_file;
		} else {
			$opml_data = $this->open_OPML($url);
		}
		$obj = new OPML_Object($url);
		$this->opml = $obj;
		foreach ( $opml_data->body->outline as $folder ){
			//return $folder;
			$this->make_OPML_obj($folder);
		}
		return $this->opml;
	}

	function make_OPML_obj($entry, $parent = false) {
		//$entry = (array) $entry;
		#return $entry; #die();
		$entry_a = $this->get_opml_properties($entry);
		if ( isset($entry_a['xmlUrl']) ){
			$feed_obj = $this->opml->make_a_feed_obj($entry_a);
			$this->opml->set_feed($feed_obj, $parent);
		} else {
			$folder_obj = $this->opml->make_a_folder_obj($entry_a);
			$this->opml->set_folder($folder_obj);
			foreach ($entry as $feed){
				$this->make_OPML_obj($feed, $folder_obj);
			}
		}
	}

	function get_opml_properties($simple_xml_obj){
		$obj = $simple_xml_obj->attributes();
		$array = array();
		foreach ($obj as $key=>$value){
			$array[$key] = (string) $value;
		}
		return $array;
	}

	function add_to_opml_data($feed_obj, $param) {
		$array = $obj->$param;
		$array[] = $string;
		$obj->$param =  $array;
		return $obj;
	}

	# Pass the URL and if you want to return an array of objects or of urls.
	function get_OPML_data($url, $is_array = true){
		pf_log('OPML Reader process invoked: get_OPML_data');
		$opml_data = $this->open_OPML($url);

        #var_dump($opml_data); die();
		if (!$opml_data || empty($opml_data)){
			pf_log('Could not open the OPML file.');
            pf_log('Resulted in:');
            pf_log($opml_data);
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
            pf_log($c++);
            #var_dump($folder); die();
			# Check if there are no folders.
            if (isset($folder['xmlUrl'])){
                pf_log('Not a folder.');
                $b[] = $folder['xmlUrl']->__toString();
            }

            foreach ($folder->outline as $data){
                pf_log('A folder.');
				$a[] = reset($data);
			}
			// Pulls out the feed location.
			foreach ($a as $outline) {
                pf_log('Feed found:');
                pf_log($outline['xmlUrl']);
				$b[] = $outline['xmlUrl'];
			}

		}
		#var_dump($a);
   #var_dump($b);
   #die();
			if ($is_array){
                pf_log('Is array:');
                pf_log($b);
				return $b;
			} else {
                pf_log('Is not array:');
                pf_log($a);
				return $a;
			}

	}

}

class OPML_Object {

	function __construct($url){
		$this->url = $url;
		$this->folders = array();
		$this->feeds = array();
	}

	function set_folder($folder_obj){
		$folder_obj->slug = $this->slugify($folder_obj->title);
		$this->folders[$folder_obj->slug] = $folder_obj;
	}
	function set_feed($feed_obj, $folder = false){
		if (!$folder){
			$feed_obj->folder = false;
			return array_push($this->feeds, $feed_obj);
		} else {
			$feed_obj->folder = $folder->slug;
			$this->feeds[md5($feed_obj->xmlUrl)] = $feed_obj;
		}
	}
	public function check_keys($array, $keys, $strict = false){
		$array['missing'] = array();
		foreach($keys as $key){
			if ( !array_key_exists($key, $array) ){
				if ($strict) {
					return false;
				} else {
					$array[$key] = '';
					$array['missing'][] = $key;
				}
			}
		}
		return $array;
	}
	function make_a_folder_obj($entry){
		$folder = new stdClass();
		$entry = (array) $entry;
		$entry = $this->check_keys($entry, array('title', 'text') );
		$entry['title'] = (!empty($entry['title']) ? $entry['title'] : false);
		$entry['text'] = (!empty($entry['text']) ? $entry['text'] : false);
		if (isset($entry['title']) && !$entry['text']){
			$entry['text'] = $entry['title'];
		} elseif (isset($entry['text']) && !$entry['title']) {
			$entry['title'] = $entry['text'];
		}
		#var_dump($entry); die();
		$folder->title = $entry['title'];
		$folder->text = $entry['text'];
		return $folder;
	}
	function make_a_feed_obj($entry){
		$feed = new stdClass();
		$entry = (array) $entry;
		$entry = $this->check_keys($entry, array( 'title', 'text', 'type', 'xmlUrl', 'htmlUrl' ) );
		$feed->title = $entry['title'];
		$feed->text = $entry['text'];
		$feed->type = $entry['type'];
		$feed->xmlUrl = $entry['xmlUrl'];
		$feed->htmlUrl = $entry['htmlUrl'];
		return $feed;
	}
	function order_opml_entries($a, $b){
		if (empty($a->folder)){
			return 1;
		}
		if (empty($b->folder)){
			return -1;
		}
		$a = $a->folder;
		$b = $b->folder;
		if (!$a){
			return -1;
		}
		if (strcasecmp($a, $b) == 0){
			return 0;
		}
		if (strcasecmp($a, $b) < 0){
			return -1;
		} else {
			return 1;
		}
	}
	function order_feeds_by_folder(){
		usort($this->feeds, array($this, 'order_opml_entries'));
	}
	function get_feeds_by_folder($folder){
		$folder = array();
		foreach ( $this->feeds as $feed ){
			if ( $this->slugify($folder) == $feed->folder ){
				$folder[] = $feed;
			}
		}
		if ( empty($folder) ){
			return false;
		}
		return $folder;
	}
	function sanitize($string, $force_lowercase = true, $anal = false) {
		$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
					   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
					   "", "", ",", "<", ".", ">", "/", "?");
		if (is_array($string)){
			$string = implode(' ', $string);
		}
		$clean = trim(str_replace($strip, "", strip_tags($string)));
		$clean = preg_replace('/\s+/', "-", $clean);
		$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;

		return ($force_lowercase) ?
			(function_exists('mb_strtolower')) ?
				mb_strtolower($clean, 'UTF-8') :
				strtolower($clean) :
			$clean;
	}
	public function slugify($string, $case = true, $strict = false, $spaces = false) {
		$string = strip_tags($string);
		// replace non letter or digits by -
		$string = preg_replace('~[^\\pL\d]+~u', '-', $string);
		if ($spaces == false){
			$stringSlug = str_replace(' ', '-', $string);
			$stringSlug = trim($stringSlug);
			$stringSlug = str_replace('&amp;','&', $stringSlug);
			//$charsToElim = array('?','/','\\');
			$stringSlug = $this->sanitize($stringSlug, $case, $strict);
		} else {
			//$string = strip_tags($string);
			//$stringArray = explode(' ', $string);
			//$stringSlug = '';
			//foreach ($stringArray as $stringPart){
			//	$stringSlug .= ucfirst($stringPart);
			//}
			$stringSlug = str_replace('&amp;','&', $string);
			//$charsToElim = array('?','/','\\');
			$stringSlug = $this->sanitize($stringSlug, $case, $strict);
		}

		$stringSlug = htmlspecialchars( $stringSlug, null, null, false );

		if (empty($stringSlug))
		{
			//var_dump('probs: ' .$string); die();
			return 'empty';
		}

		return $stringSlug;
	}
}


?>