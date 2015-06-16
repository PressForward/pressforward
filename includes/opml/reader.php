<?php

class OPML_reader {

	function __construct($file = ''){
		if (!empty($file)){
			$this->opml_file = $this->open_OPML($file);
			$this->file_url = $file;
//			$this->get_OPML_obj();
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
            pf_log('Received file.');
            //pf_log($file);
			$opml_data = $file;
			return $opml_data;
		}
	}

	function get_OPML_obj($url = false){
		pf_log('get_OPML_obj invoked.');
		if (false == $url){
			$opml_data = $this->opml_file;
		} else {
			$opml_data = $this->open_OPML($url);
		}
		$obj = new OPML_Object($url);
		$this->opml = $obj;
		$this->opml->set_title($opml_data->head->title);
		pf_log('Reading out from OPML file named '.$opml_data->head->title);
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
		pf_log('Making an OPML obj using properties of:');
		pf_log($entry_a);
		if ( isset($entry_a['xmlUrl']) ){
			//pf_log('Making a feed.');
			$feed_obj = $this->opml->make_a_feed_obj($entry_a);
			$this->opml->set_feed($feed_obj, $parent);
		} else {
			//pf_log('Making a folder.');
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
		//pf_log('OPML Reader process invoked: get_OPML_data');
		$opml_data = $this->open_OPML($url);

        #var_dump($opml_data); die();
		if (!$opml_data || empty($opml_data)){
			//pf_log('Could not open the OPML file.');
            //pf_log('Resulted in:');
            //pf_log($opml_data);
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
            //pf_log($c++);
            #var_dump($folder); die();
			# Check if there are no folders.
            if (isset($folder['xmlUrl'])){
                //pf_log('Not a folder.');
                $b[] = $folder['xmlUrl']->__toString();
            }

            foreach ($folder->outline as $data){
                //pf_log('A folder.');
				$a[] = reset($data);
			}
			// Pulls out the feed location.
			foreach ($a as $outline) {
               // pf_log('Feed found:');
                //pf_log($outline['xmlUrl']);
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