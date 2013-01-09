<?php

ignore_user_abort(true);
set_time_limit(0);

define( 'IC_SITEBASE', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) );
// print_r(IC_SITEBASE . '\wp-load.php');
 
if ( !defined('ABSPATH') ) {
	/** Set up WordPress environment */
	require_once(IC_SITEBASE . '\wp-load.php');
}
global $rsspf;
		
//Is this process already occuring?
$is_it_going = get_option(RSSPF_SLUG . '_iterate_going_switch', 0);
$is_it_done = get_option(RSSPF_SLUG . '_feeds_go_switch', 0);
if (($is_it_going == 0) && ($is_it_done == 0)){
	//WE ARE? SHUT IT DOWN!!!
	update_option( RSSPF_SLUG . '_feeds_go_switch', 0);
	update_option( RSSPF_SLUG . '_feeds_iteration', 0);
	update_option( RSSPF_SLUG . '_iterate_going_switch', 1);
	print_r('<br /> We\'re doing this thing already. <br />');
	return;
} else {
	$rsspf->assemble_feed_for_pull();
}

//do_action('get_more_feeds');

print_r('Triggered <br />');
print_r('Iteration active: ' . get_option( RSSPF_SLUG . '_feeds_iteration') . '<br />');

?>