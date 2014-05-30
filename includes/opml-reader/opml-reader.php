<?php

//$file="http://www.google.com/reader/public/subscriptions/user%2F10862070116690190079%2Fbundle%2FWriting%2BTech%20Bundle";

class OPML_reader {

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
?>