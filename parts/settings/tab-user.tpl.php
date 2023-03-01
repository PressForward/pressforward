<?php
/**
 * Template for user preferences tab.
 *
 * @package PressForward
 */

$current_user_id = get_current_user_id();

?>

<p>
	<?php esc_html_e( 'Users can control aspects of the content display in the All Content and Nominated pages by setting preferences here.', 'pf' ); ?>
</p>

<hr />

<p>
	<?php

	$pf_user_scroll_switch = get_user_option( 'pf_user_scroll_switch', $current_user_id );

	if ( empty( $pf_user_scroll_switch ) || 'true' === $pf_user_scroll_switch ) {
		$mark = 'checked';
	} else {
		$mark = '';
	}

	echo '<input id="pf_user_scroll_switch" type="checkbox" name="pf_user_scroll_switch" value="true" ' . esc_attr( $mark ) . ' class="user_setting" />
	<label for="pf_user_scroll_switch" >' . esc_html__( 'Infinite Scroll Active', 'pf' ) . '</label>';

	?>
	<br />

	<?php esc_html_e( 'When this box is checked, users can scroll through content continuously.', 'pf' ); ?>
</p>

<hr />

<p>
	<?php
	$pf_user_menu_set = get_user_option( 'pf_user_menu_set', $current_user_id );
	if ( 'true' === $pf_user_menu_set ) {
		$mark = 'checked';
	} else {
		$mark = '';
	}
	echo '<input id="pf_user_menu_set" type="checkbox" name="pf_user_menu_set" value="true" ' . esc_attr( $mark ) . ' class="user_setting" />
	<label for="pf_user_menu_set" >' . esc_html__( 'Show side menu', 'pf' ) . '</label>';
	?>
	<br />
	<?php esc_html_e( 'When this box is checked, a menu that includes a list of feeds with alerts displays on the right side of the All Content and Nominated pages.', 'pf' ); ?>
</p>

<hr />

<p>
	<?php
	$default_pf_pagefull = get_user_option( 'pf_pagefull', $current_user_id );
	if ( empty( $default_pf_pagefull ) ) {
		$default_pf_pagefull = 20;
	}

	echo '<input id="pf_pagefull" name="pf_pagefull" type="number" class="pf_pagefull" value="' . esc_attr( $default_pf_pagefull ) . '" />';
	echo '<label class="description" for="pf_pagefull"> ' . esc_html__( 'Number of feed items per page.', 'pf' ) . ' </label>';
	?>

	<br />

	<?php esc_html_e( 'Setting this number determines how many items will appear on the All Content and Nominated pages when infinite scroll is turned off.', 'pf' ); ?>
</p>

<hr />

<fieldset>
	<legend><?php esc_html_e( 'Email Notifications', 'pf' ); ?></legend>

	<p><?php esc_html_e( 'Send me an email notification when:', 'pf' ); ?></p>

	<input id="pf-user-nomination-success-email-toggle" type="checkbox" name="pf-user-nomination-success-email-toggle" value="1" <?php checked( pressforward()->fetch( 'controller.users' )->get_user_setting( get_current_user_id(), 'nomination-success-email-toggle' ) ); ?> /> <label for="pf-user-nomination-success-email-toggle"><?php esc_html_e( "I've successfully nominated an item", 'pf' ); ?></label><br />
	<input id="pf-user-nomination-promoted-email-toggle" type="checkbox" name="pf-user-nomination-promoted-email-toggle" value="1" <?php checked( pressforward()->fetch( 'controller.users' )->get_user_setting( get_current_user_id(), 'nomination-promoted-email-toggle' ) ); ?> /> <label for="pf-user-nomination-promoted-email-toggle"><?php esc_html_e( "An item that I've nominated is promoted", 'pf' ); ?></label><br />
	<input id="pf-user-nomination-duplicate-email-toggle" type="checkbox" name="pf-user-nomination-duplicate-email-toggle" value="1" <?php checked( pressforward()->fetch( 'controller.users' )->get_user_setting( get_current_user_id(), 'nomination-duplicate-email-toggle' ) ); ?> /> <label for="pf-user-nomination-duplicate-email-toggle"><?php esc_html_e( "Another user nominates an item that previously I've nominated", 'pf' ); ?></label>
</fieldset>
