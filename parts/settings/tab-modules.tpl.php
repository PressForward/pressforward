<?php
/**
 * Template for Modules section.
 *
 * @package PressForward
 */

?>

<p class="description">
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?>
	<?php _e( '<strong>PressForward Modules</strong> are addons to alter or improve the functionality of the plugin.', 'pf' ); ?>
</p>

<hr />

<?php do_action( 'pf_admin_op_page' ); ?>
