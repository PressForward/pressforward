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

	public function step_through_feedlist() {
		# Log the beginning of this function.
		
		pf_log('step_through_feedlist begins.');
		
		# Retrieve the list of feeds. 
		
		$feedlist = $this->pf_feedlist();
		
		# Move array internal pointer to end.
		
		end($feedlist);
		
		# Because the internal pointer is now at the end
		# we can find the key for the last entry.
		
		$last_key = key($feedlist);
		
		# Log the key we retrieved. This allows us to compare
		# to the iteration state in order to avoid multiple
		# processes spawning at the same time. 
		
		pf_log('The last key is: ' . $last_key);
		
		# Get the option that stores the current step of iteration. 
		
		$feeds_iteration = get_option( PF_SLUG . '_feeds_iteration');
		
		# We will now set the lock on the feed retrieval process.
		# The logging here is to insure that lock is set. 
		
		pf_log('feeds_go_switch updated? (first check).');
		
		# We begin the process of getting the next feed. 
		# If anything asks the system, from here until the end of the feed
		# retrieval process, you DO NOT attempt to retrieve another feed.
		
		$go_switch_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
		
		# A check to see if the lock has been set.
		
		pf_log($go_switch_bool);
		
		# We want to insure that we are neither skipping ahead or
		# overlapping with a previous process. To do so we store two
		# options. The first tells us the current state of iteration.
		# We don't reset the pf_feeds_iteration option until we have actually
		# begun to retrieve the feed. The pf_prev_iteration stores the 
		# last time it was set. When the feed retrieval checks begin, we set the
		# prev_iteration. When they are completed and ready to progress we
		# set the pf_feeds_iteration option. 
		#
		# At this point the last run was started (and, we assume,
		# completed, checks for that later.) but we have not advanced
		# so the two options should match.
		
		$prev_iteration = get_option( PF_SLUG . '_prev_iteration', 0);
		pf_log('Did the option properly iterate so that the previous iteration count of ' . $prev_iteration . ' is equal to the current of ' . $feeds_iteration . '?');
		
		/* @todo This appears to be reporting with the wrong messages.
		 * We need to resolve what is going on here with the right log.
		 */
		
		// This is the fix for the insanity caused by the planet money feed - http://www.npr.org/rss/podcast.php?id=510289.
		if ( (int) $prev_iteration == (int) $feeds_iteration){
			
			# In some cases the option fails to update for reasons that are not
			# clear. In those cases, we will risk skipping a feed rather 
			# than get caught in an endless loop. 
			
			pf_log('Nope. Did the step_though_feedlist iteration option emergency update work here?');
			
			# Make an attempt to update the option to its appropriate state. 
			
			update_option( PF_SLUG . '_feeds_iteration', $feeds_iteration+1);
			
			# Regardless of success, iterate this process forward.
			
			$feeds_iteration++;
		} else {
			
			# No rest for the iterative, so on we go. 
			
			pf_log('Yes');
		}
		
		# If everything goes wrong, we need to know what the iterate state is.
		# At this point $feeds_iteration should be one greater than prev_iteration.
		
		pf_log('The current iterate state is: ' . $feeds_iteration);
		
		# Insure that the function hasn't gone rogue and is not past the
		# last element in the array. 
		
		if ($feeds_iteration <= $last_key) {
			
			# The iteration state is not beyond the limit of the array
			# which means we can move forward. 
			
			pf_log('The iteration is less than the last key.');

			# Get the basic URL for the feed. 
			
			$aFeed = $feedlist[$feeds_iteration];
			pf_log('Retrieved feed ' . $aFeed);
			
			# Check the option to insure that we are currently inside the 
			# iteration process. The 'going' switch is used elsewhere to check
			# if the iteration process is active or ended.
			
			$are_we_going = get_option(PF_SLUG . '_iterate_going_switch', 1);
			pf_log('Iterate going switch is set to: ' . $are_we_going);
			
			# The last key of the array is equal to our current key? Then we are 
			# at the end of the feedlist. Set options appropriately to indicate to 
			# other processes that the iterate state will soon be terminated. 
			
			if (($last_key === $feeds_iteration)){
				pf_log('The last key is equal to the feeds_iteration. This is the last feed.');
				
				# If we're restarting after this, we need to tell the system 
				# to begin the next retrieve cycle at 0.
				
				$feeds_iteration = 0;
				pf_log('iterate_going_switch updated?');
				$going_switch_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($going_switch_bool);
				

			} elseif ($are_we_going == 1) {
				pf_log('No, we didn\'t start over.');
				pf_log('Did we set the previous iteration option to ' . $feeds_iteration . '?');
				
				# We should have advanced the feeds_iteration by now, 
				# it is the active array pointer. To track this action for
				# future iterations, we store the current iteration state as
				# prev_iteration.
				
				$prev_iteration = update_option( PF_SLUG . '_prev_iteration', $feeds_iteration);
				pf_log($prev_iteration);
				
				# Now we advance the feeds_iteration var to the array pointer
				# that represents the next feed we will need to retrieve. 
				
				$feeds_iteration = $feeds_iteration+1;
				
				pf_log('Did the iterate_going_switch update?');
				
				# Insure that the system knows we are actively iterating. 
				
				$iterate_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 1);
				
				# Show us that this important option has been properly set.
				
				pf_log($iterate_going_bool);
				pf_log('We are set to a reiterate state.');
			} else {
				# Oh noes, what has occurred!?
				pf_log('There is a problem with the iterate_going_switch and now the program does not know its state.');
			}

			pf_log('Did the feeds_iteration option update to ' . $feeds_iteration . '?');
			
			# Set and log the update that gives us the future feed retrieval. 
			
			$iterate_op_check = update_option( PF_SLUG . '_feeds_iteration', $feeds_iteration);
			pf_log($iterate_op_check);
			if ($iterate_op_check === false) {
			
				# Occasionally WP refuses to set this option.
				# In these situations we will take more drastic measures
				# and attempt to set it again. 
			
				pf_log('For no apparent reason, the option did not update. Delete and try again.');
				pf_log('Did the option delete?');
				$deleteCheck = delete_option( PF_SLUG . '_feeds_iteration' );
				pf_log($deleteCheck);
				$iterate_op_check = update_option( PF_SLUG . '_feeds_iteration', $feeds_iteration);
				pf_log('Did the new option setup work?');
				pf_log($iterate_op_check);
			}
			
			# Log a (hopefully) successful update. 
			
			pf_log('The feed iteration option is now set to ' . $feeds_iteration);

			# If the feed retrieved is empty and we haven't hit the last feed item.
			
			if (((empty($aFeed)) || ($aFeed == '')) && ($feeds_iteration <= $last_key)){
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is less than or equal to the last key.');
				$theFeed = call_user_func(array($this, 'step_through_feedlist'));
			} elseif (((empty($aFeed)) || ($aFeed == '')) && ($feeds_iteration > $last_key)){
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is greater than the last key.');
				pf_log('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( PF_SLUG . '_feeds_iteration', 0);
				pf_log($feed_it_bool);

				pf_log('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
				pf_log($feed_go_bool);

				pf_log('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($feed_going_bool);

				pf_log('End of the update process. Return false.');
				return false;
			}

			# If the feed isn't empty, attempt to retrieve it. 
			
			if (is_wp_error($theFeed = fetch_feed($aFeed))){
				$aFeed = '';
				pf_log($theFeed->get_error_message());
			}
			# If the array entry is empty and this isn't the end of the feedlist,
			# then get the next item from the feedlist while iterating the count.
			if (((empty($aFeed)) || ($aFeed == '') || (is_wp_error($theFeed))) && ($feeds_iteration <= $last_key)){
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is less than or equal to the last key.');
				
				# The feed is somehow bad, lets get the next one. 
				
				$theFeed = call_user_func(array($this, 'step_through_feedlist'));
			} elseif (((empty($aFeed)) || ($aFeed == '') || (is_wp_error($theFeed))) && ($feeds_iteration > $last_key)){
			
				# The feed is somehow bad and we've come to the end of the array.
				# Now we switch all the indicators to show that the process is
				# over and log the process. 
			
				pf_log('The feed is either an empty entry or un-retrievable AND the iteration is greater then the last key.');
				pf_log('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( PF_SLUG . '_feeds_iteration', 0);
				pf_log($feed_it_bool);

				pf_log('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
				pf_log($feed_go_bool);

				pf_log('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($feed_going_bool);

				pf_log('End of the update process. Return false.');
				return false;
			}
			return $theFeed;
		} else {
			//An error state that should never, ever, ever, ever, ever happen.
			pf_log('The iteration is now greater than the last key.');
				pf_log('Did the feeds_iteration option update?');
				$feed_it_bool = update_option( PF_SLUG . '_feeds_iteration', 0);
				pf_log($feed_it_bool);

				pf_log('Did the feeds_go_switch option update?');
				$feed_go_bool = update_option( PF_SLUG . '_feeds_go_switch', 0);
				pf_log($feed_go_bool);

				pf_log('Did the iterate_going_switch option update?');
				$feed_going_bool = update_option( PF_SLUG . '_iterate_going_switch', 0);
				pf_log($feed_going_bool);
				pf_log('End of the update process. Return false.');
				return false;
			//return false;
		}

	}	
		
	
	
}