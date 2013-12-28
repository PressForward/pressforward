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
	public function get_data_object($aFeed){
	
	}
	

} 