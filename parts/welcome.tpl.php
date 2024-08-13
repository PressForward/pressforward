<?php
/**
 * 'All Content' admin panel.
 *
 * @package PressForward
 *
 * @since 5.7.0
 */

?>

<div class="wrap pf-welcome">
	<div class="pf-welcome-header">
		<h1 class="title"><?php esc_html_e( 'Welcome to PressForward', 'pressforward' ); ?></h1>

		<p class="pf-version">
			<?php
			printf(
				/* translators: %s: PressForward version number */
				esc_html__( 'Version %s', 'pressforward' ),
				esc_html( PF_VERSION )
			)
			?>
		</p>
	</div>

	<div id="pf-welcome-dashboard-widgets" class="pf-welcome-dashboard-widgets metabox-holder">
		<?php do_meta_boxes( 'toplevel_page_pf-menu', 'normal', null ); ?>
	</div>

	<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
	<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
</div>
