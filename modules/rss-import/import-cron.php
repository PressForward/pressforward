<?php

ignore_user_abort(true);
set_time_limit(0);

define( 'IC_SITEBASE', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) );
// print_r(IC_SITEBASE . '\wp-load.php');
 
if ( !defined('ABSPATH') ) {
	/** Set up WordPress environment */
	require_once(IC_SITEBASE . '\wp-load.php');
}
global $pf;
		$fo = fopen(PF_ROOT . "/modules/rss-import/rss-import.txt", 'a') or print_r('Can\'t open log file.');

		$string_to_log = "\nimport-cron.php triggered.\n";
		fwrite($fo, $string_to_log);
		fclose($fo);		
		
$pf->assemble_feed_for_pull();

$fo = fopen(PF_ROOT . "/modules/rss-import/rss-import.txt", 'a') or print_r('Can\'t open log file.');
fwrite($fo, "\nimport-cron.php compleated.\n\n\n");
fclose($fo);		
//do_action('get_more_feeds');

//print_r('<br /><br />Triggered <br />');
//print_r('Iteration active: ' . get_option( PF_SLUG . '_feeds_iteration') . '<br />');

?>