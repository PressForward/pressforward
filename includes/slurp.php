<?php 

/**
 * Feed 'slurping' class
 *
 * This class handles the functions for iterating through
 * a feed list and retrieving the items in those feeds. 
 * This class should only contain those functions that
 * can be generalized to work on multiple content 
 * retrieval methods (not just RSS).
 *
 */

define( 'FEED_LOG', PF_ROOT . "/modules/rss-import/rss-import.txt" ); 
class PF_Feed_Retrieve {
	
	
	 function cron_add_short( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['halfhour'] = array(
			'interval' => 30*60,
			'display' => __( 'Half-hour' )
		);
		return $schedules;
	 }	
	 
	
	/**
	 * Schedules the half-hour wp-cron job
	 */
	public function schedule_feed_in() {
		if ( ! wp_next_scheduled( 'pull_feed_in' ) ) {
			wp_schedule_event( time(), 'halfhour', 'pull_feed_in' );
		}
	}

	/**
	 * Schedules the monthly feed item cleanup
	 */
	function schedule_feed_out() {
		if ( ! wp_next_scheduled( 'take_feed_out' ) ) {
			wp_schedule_event( time(), 'monthly', 'take_feed_out' );
		}
	}

	
	/**
	 * Creates a custom nonce in order to secure feed
	 * retrieval requests.
	 */
	public function get_chunk_nonce(){
		$create_nonce = wp_create_nonce('chunkpressforward');
		update_option('chunk_nonce', $create_nonce);
	}	
	
	
	
}