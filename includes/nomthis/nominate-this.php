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

$nominate_access = get_option( 'pf_menu_all_content_access' );
if ( ! current_user_can( get_option( 'pf_menu_nominate_this_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'contributor' ) ) ) ) {
	wp_die( esc_html__( 'You do not have the capacity to access the Nominate This bookmarklet.', 'pressforward' ) ); }

require_once PF_ROOT . '/includes/nomthis/nominate-this-core.php';
