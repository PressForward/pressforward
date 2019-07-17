<?php

class OPML_reader {

	/**
	 * OPML file url/path
	 *
	 * @var string
	 */
	public $file_url = '';

	/**
	 * Parsed OPML file data
	 *
	 * @var SimpleXMLObject
	 */
	public $opml_file;

	/**
	 * Construct an OPML_Reader object
	 *
	 * Accepts a file url string and opens the OPML file for reading
	 *
	 * @param string $file url
	 */
	function __construct( $url = '' ) {
		$this->file_url = $url;

		$this->open_file();
		// $this->get_OPML_obj();
	}

	public function build_from_string( $string_opml ){
		$file = simplexml_load_string( $string_opml );
		$this->opml_file = $file;
	}


	/**
	 * Opens the OPML file
	 *
	 * Uses the object's $url property to pull down and get the
	 * OPML file data, assigned to $opml_file
	 */
	function open_file() {
		if ( empty( $this->file_url ) ) {
			return;
		}

		pf_log( 'open_OPML invoked.' );

		if ( 1 == ini_get( 'allow_url_fopen' ) ) {
			pf_log( 'Using simplexml_load_file to load OPML.' );
			$file = simplexml_load_file( $this->file_url );
		} else {
			pf_log( 'Using WP_HTTP_Api to load OPML file.' );

			$response = wp_remote_get( $this->file_url, array(
				'timeout' => 5,
			) );

			if ( is_wp_error( $response ) ) {
				pf_log( 'OPML file failed to GET. Error: ' . $response->get_error_message() );
				$this->opml_file = false;
				return;
			}

			$file = simplexml_load_string( wp_remote_retrieve_body( $response ) );
		}

		if ( empty( $file ) ) {
			pf_log( 'Received an empty file.' );
			$file = false;
		} else {
			pf_log( 'Received file.' );
		}

		$this->opml_file = $file;
	}

	/**
	 * Retrieves the OPML_Object from the provided url
	 *
	 * @return OPML_Object
	 */
	public function get_OPML_obj() {
		pf_log( 'get_OPML_obj invoked.' );

		$this->opml = new OPML_Object( $this->file_url );
		$this->opml->set_title( (string) $this->opml_file->head->title );

		pf_log( 'Reading out from OPML file named ' . $this->opml->get_title() );

		foreach ( $this->opml_file->body->outline as $folder ) {
			$this->make_OPML_obj( $folder );
		}

		return $this->opml;
	}

	/**
	 * Recursively builds the OPML_Object for each folder
	 *
	 * @param  SimpleXMLObject         $entry
	 * @param  boolean|SimpleXMLObject $parent
	 */
	public function make_OPML_obj( $entry, $parent = false ) {
		// $entry = (array) $entry;
		// return $entry; #die();
		$entry_a = $this->get_opml_properties( $entry );
		pf_log( 'Making an OPML obj using properties of:' );
		pf_log( $entry_a );
		if ( isset( $entry_a['xmlUrl'] ) ) {
			// pf_log('Making a feed.');
			$feed_obj = $this->opml->make_a_feed_obj( $entry_a );
			$this->opml->set_feed( $feed_obj, $parent );
		} else {
			// pf_log('Making a folder.');
			$folder_obj = $this->opml->make_a_folder_obj( $entry_a );
			$this->opml->set_folder( $folder_obj );
			foreach ( $entry as $feed ) {
				$this->make_OPML_obj( $feed, $folder_obj );
			}
		}
	}

	/**
	 * Builds the SimpleXMLObject's attributes into an array
	 *
	 * @param  SimpleXMLObject $simple_xml_obj
	 * @return array
	 */
	public function get_opml_properties( $simple_xml_obj ) {
		$obj = $simple_xml_obj->attributes();
		$array = array();
		foreach ( $obj as $key => $value ) {
			$array[ $key ] = (string) $value;
		}
		return $array;
	}

	function open_OPML( $file ) {
		pf_log( 'open_OPML invoked.' );
		if ( 1 == ini_get( 'allow_url_fopen' ) ) {
			pf_log( 'Using simplexml_load_file to load OPML.' );
			$file = simplexml_load_file( $file );
		} else {
			pf_log( 'Using cURL to load OPML file.' );
			$ch = curl_init();
			$timeout = 5;
			curl_setopt( $ch, CURLOPT_URL, $file );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			$data = curl_exec( $ch );
			curl_close( $ch );
			$file = simplexml_load_string( $data );
		}
		// echo '<pre>'; var_dump($data); #die();
		if ( empty( $file ) ) {
			pf_log( 'Received an empty file.' );
			return false;
		} else {
			pf_log( 'Received:' );
			pf_log( $file );
			$opml_data = $file;
			return $opml_data;
		}
	}


	// Pass the URL and if you want to return an array of objects or of urls.
	// @todo remove this function
	function get_OPML_data( $url, $is_array = true ) {
		// pf_log('OPML Reader process invoked: get_OPML_data');
		$opml_data = $this->open_OPML( $url );

		// var_dump($opml_data); die();
		if ( ! $opml_data || empty( $opml_data ) ) {
			// pf_log('Could not open the OPML file.');
			// pf_log('Resulted in:');
			// pf_log($opml_data);
			return false;
		}

		// Site data
		$a = array();
		// Feed URI
		$b = array();
		$c = 0;

		/** Get XML data:
		  * supplies:
		  * [text] - Text version of title
		  * [text] - Text version of title
		  * [type] - Feed type (should be rss)
		  * [xmlUrl] - location of the RSS feed on the site.
		  * [htmlUrl] - The site home URI.
		*/
		foreach ( $opml_data->body->outline as $folder ) {
			// pf_log($c++);
			// var_dump($folder); die();
			// Check if there are no folders.
			if ( isset( $folder['xmlUrl'] ) ) {
				// pf_log('Not a folder.');
				$b[] = $folder['xmlUrl']->__toString();
			}

			foreach ( $folder->outline as $data ) {
				// pf_log('A folder.');
				$a[] = reset( $data );
			}
			// Pulls out the feed location.
			foreach ( $a as $outline ) {
				// pf_log('Feed found:');
				// pf_log($outline['xmlUrl']);
				$b[] = $outline['xmlUrl'];
			}
		}
		// var_dump($a);
		// var_dump($b);
		// die();
		if ( $is_array ) {
			pf_log( 'Is array:' );
			pf_log( $b );
			return $b;
		} else {
			pf_log( 'Is not array:' );
			pf_log( $a );
			return $a;
		}

	}

}
