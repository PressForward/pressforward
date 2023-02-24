<?php
/**
 * OPML reader.
 *
 * @package PressForward
 */

// phpcs:disable PEAR.NamingConventions.ValidClassName.Invalid
/**
 * OPML_reader class.
 */
class OPML_reader {
	/**
	 * OPML file url/path
	 *
	 * @access public
	 * @var string
	 */
	public $file_url = '';

	/**
	 * Parsed OPML file data
	 *
	 * @access public
	 * @var SimpleXMLElement|bool
	 */
	public $opml_file;

	/**
	 * OPML object.
	 *
	 * @access protected
	 * @var OPML_Object
	 */
	protected $opml;

	/**
	 * Construct an OPML_Reader object.
	 *
	 * Accepts a file url string and opens the OPML file for reading.
	 *
	 * @param string $url File URL.
	 */
	public function __construct( $url = '' ) {
		$this->file_url = $url;

		$this->open_file();
	}

	/**
	 * Populates OPML from string.
	 *
	 * @param string $string_opml OPML string.
	 */
	public function build_from_string( $string_opml ) {
		$this->opml_file = simplexml_load_string( $string_opml );
	}

	/**
	 * Opens the OPML file.
	 *
	 * Uses the object's $url property to pull down and get the
	 * OPML file data, assigned to $opml_file.
	 */
	public function open_file() {
		if ( empty( $this->file_url ) ) {
			return;
		}

		pf_log( 'open_OPML invoked.' );

		if ( ini_get( 'allow_url_fopen' ) ) {
			pf_log( 'Using simplexml_load_file to load OPML.' );
			$file = simplexml_load_file( $this->file_url );
		} else {
			pf_log( 'Using WP_HTTP_Api to load OPML file.' );

			$response = wp_remote_get(
				$this->file_url,
				array(
					'timeout' => 5,
				)
			);

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
	 * Retrieves the OPML_Object from the provided url.
	 *
	 * @return OPML_Object
	 */
	public function get_OPML_obj() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
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
	 * Recursively builds the OPML_Object for each folder.
	 *
	 * @param  SimpleXMLElement      $entry        XML object.
	 * @param  bool|SimpleXMLElement $parent_entry Optional. Parent object.
	 */
	public function make_OPML_obj( $entry, $parent_entry = false ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$entry_a = $this->get_opml_properties( $entry );

		pf_log( 'Making an OPML obj using properties of:' );
		pf_log( $entry_a );

		if ( isset( $entry_a['xmlUrl'] ) ) {
			$feed_obj = $this->opml->make_a_feed_obj( $entry_a );
			$this->opml->set_feed( $feed_obj, $parent_entry );
		} else {
			$folder_obj = $this->opml->make_a_folder_obj( $entry_a );
			$this->opml->set_folder( $folder_obj );
			foreach ( $entry as $feed ) {
				$this->make_OPML_obj( $feed, $folder_obj );
			}
		}
	}

	/**
	 * Builds the SimpleXMLElement's attributes into an array.
	 *
	 * @param  SimpleXMLElement $simple_xml_obj XML object.
	 * @return array
	 */
	public function get_opml_properties( $simple_xml_obj ) {
		$obj   = $simple_xml_obj->attributes();
		$array = array();

		foreach ( $obj as $key => $value ) {
			$array[ $key ] = (string) $value;
		}

		return $array;
	}

	/**
	 * Opens an OPML file and returns a corresponding SimpleXMLElement object.
	 *
	 * @param string $file File path.
	 * @return SimpleXMLElement|bool
	 */
	public function open_OPML( $file ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		pf_log( 'open_OPML invoked.' );
		if ( ini_get( 'allow_url_fopen' ) ) {
			pf_log( 'Using simplexml_load_file to load OPML.' );
			$file = simplexml_load_file( $file );
		} else {
			pf_log( 'Using cURL to load OPML file.' );

			// phpcs:disable
			$ch = curl_init();
			$timeout = 5;
			curl_setopt( $ch, CURLOPT_URL, $file );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			$data = curl_exec( $ch );
			curl_close( $ch );
			// phpcs:enable

			$file = simplexml_load_string( $data );
		}

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

	/**
	 * Gets OPML data.
	 *
	 * Pass the URL and if you want to return an array of objects or of urls..
	 *
	 * @todo remove this function.
	 *
	 * @param string $url      URL.
	 * @param bool   $is_array Is array.
	 */
	public function get_OPML_data( $url, $is_array = true ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$opml_data = $this->open_OPML( $url );

		if ( ! $opml_data || empty( $opml_data ) ) {
			return false;
		}

		// Site data.
		$a = array();

		// Feed URI.
		$b = array();
		$c = 0;

		/*
		 * * Get XML data:
		 * supplies:
		 * [text] - Text version of title
		 * [text] - Text version of title
		 * [type] - Feed type (should be rss)
		 * [xmlUrl] - location of the RSS feed on the site.
		 * [htmlUrl] - The site home URI.
		 */
		foreach ( $opml_data->body->outline as $folder ) {
			// Check if there are no folders.
			if ( isset( $folder['xmlUrl'] ) ) {
				$b[] = $folder['xmlUrl']->__toString();
			}

			foreach ( $folder->outline as $data ) {
				$a[] = reset( $data );
			}

			// Pulls out the feed location.
			foreach ( $a as $outline ) {
				$b[] = $outline['xmlUrl'];
			}
		}

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
// phpcs:enable PEAR.NamingConventions.ValidClassName.Invalid
