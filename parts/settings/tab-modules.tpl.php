<?php
/**
 * Template for Modules section.
 *
 * @package PressForward
 */

?>

<p>
	<?php echo wp_kses_post( __( '<strong>PressForward Modules</strong> are addons to alter or improve the functionality of the plugin.', 'pressforward' ) ); ?>
</p>

<hr />

<?php do_action( 'pf_admin_op_page' ); ?>
