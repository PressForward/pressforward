<?php
/**
 * Nominate This Display and Handler.
 *
 * Based on the PressThis code.
 */

	// Orig. file called from wp-admin/ by the bookmarklet.
define( 'IFRAME_REQUEST' , true );
define( 'WP_ADMIN', false );
global $pagenow;
$wp_bootstrap = dirname( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) );
// echo '<pre>'; var_dump($_POST); die();
$wp_bootstrap_d = dirname( dirname( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) ) );

if ( is_dir( $wp_bootstrap . '/wp-admin' ) ) {
	$wp_bootstrap = $wp_bootstrap . '/wp-admin';
} elseif ( is_dir( $wp_bootstrap . '/wordpress/wp-admin' ) ) {
	$wp_bootstrap = $wp_bootstrap . '/wordpress/wp-admin';
} elseif ( is_dir( $wp_bootstrap_d . '/wordpress/wp-admin' ) ) {
	$wp_bootstrap = $wp_bootstrap_d . '/wordpress/wp-admin';
} elseif ( is_dir( $wp_bootstrap . '/data/current/wp-admin' ) ) {
	$wp_bootstrap = $wp_bootstrap . '/data/current/wp-admin';
} elseif ( is_dir( $wp_bootstrap . '/blog/wp-admin' ) ) {
	$wp_bootstrap = $wp_bootstrap . '/blog/wp-admin';
} elseif ( is_dir( $wp_bootstrap_d . '/blog/wp-admin' ) ) {
	$wp_bootstrap = $wp_bootstrap_d . '/blog/wp-admin';
} elseif ( is_dir( $wp_bootstrap . '/wp/wp-admin' ) ) {
	$wp_bootstrap = $wp_bootstrap . '/wp/wp-admin';
} else {
	echo 'Base directory attempt at: <pre>';
	var_dump( $wp_bootstrap );
		echo 'Nominate This can not find your WP-Admin directory';
	die();
}

/** WordPress Administration Bootstrap */
require_once( $wp_bootstrap . '/admin.php' );
// require_once( $wp_bootstrap . '/includes/meta-boxes.php' );
	// PF Correction - this will need to be changed to a constant later.
// require_once( dirname(dirname(dirname(__FILE__))) . "/Libraries/OpenGraph.php");
// global $pf_nt;
// $pf_nt = new PressForward();
require_once( 'nominte-this-core.php' );
