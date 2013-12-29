<?php 

/**
 * This module will allow you to subscribe to OPML files.
 * These subscriptions will populate your feedlist with new feeds
 * as they are added to the OPML file. 
 **/
 
class PF_OPML_Subscribe extends PF_Module {

	/**
	 * Constructor 
	 */
	public function __construct(){
		
		global $pf;
		$this->feed_type = 'opml';
		parent::start();
		
		add_action ('admin_init', array($this, 'register_settings'));
	}
	
	/**
	 * Run any setup that has to happen after initial module registration
	 */
	public function post_setup_module_info() {
		$this->includes();
	}	
	
	/**
	 * Includes necessary files
	 */
	public function includes() {
		require_once(PF_ROOT . "/includes/opml-reader/opml-reader.php");
	}

	/**
	 * Gets the data from an OPML file and turns it into a data object
	 * as expected by PF
	 *
	 * @global $pf Used to access the feed_object() method
	 */	
	public function get_data_object($aOPML){
		$feed_obj = new PF_Feeds_Schema();
		pf_log( 'Invoked: PF_OPML_Subscribe::get_data_object()' );
		$aOPML_url = $aOPML->guid;
		pf_log( 'Getting OPML Feed at '.$aOPML_url );
		$OPML_reader = new OPML_reader;
		$opml_array = $OPML_reader->get_OPML_data($aOPML_url, false);
		foreach($opml_array as $feedObj){
			# Adding this as a 'quick' type so that we can process the list quickly.
			if ($feedObj['type'] == 'rss'){ $feedObj['type'] = 'rss-quick'; }
			if ($feedObj['title'] == ''){ $feedObj['title'] = $feedObj['text']; }
			$feed_obj->create(
				$feedObj['xmlUrl'], 
				array(
					'type' => $feedObj['type'],
					'title' => $feedObj['title'],
					'htmlUrl' => $feedObj['htmlUrl'],
					'description' => $feedObj['text']
				)
			);
		}		
		
	}
	

} 