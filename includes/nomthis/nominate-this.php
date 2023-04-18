<?php
/**
 * Nominate This Display and Handler.
 *
 * Based on the PressThis code.
 *
 * @package PressForward
 */

// Orig. file called from wp-admin/ by the bookmarklet.
define( 'IFRAME_REQUEST', true );
if ( defined( 'WP_ADMIN' ) ) {
	$admin_avail = true;
} else {
	$admin_avail = false;
	define( 'WP_ADMIN', false );
}

global $pagenow;
if ( ! $admin_avail ) {
	$wp_bootstrap   = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) );
	$wp_bootstrap_d = dirname( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) );

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
		echo 'Nominate This can not find your WP-Admin directory';
		die();
	}

	/** WordPress Administration Bootstrap */
	require_once $wp_bootstrap . '/admin.php';
}

$nominate_access = get_option( 'pf_menu_all_content_access' );
if ( ! current_user_can( get_option( 'pf_menu_nominate_this_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'contributor' ) ) ) ) {
	wp_die( esc_html__( 'You do not have the capacity to access the Nominate This bookmarklet.', 'pressforward' ) ); }

require_once PF_ROOT . '/includes/nomthis/nominate-this-core.php';
