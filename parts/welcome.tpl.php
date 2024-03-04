<?php
/**
 * 'All Content' admin panel.
 *
 * @package PressForward
 *
 * @since 5.7.0
 */

/*
phpcs:disable

Content notes:

- Welcome/orientation message
- Link to documentation
- "New to Pressforward?" link to documentation
- Quick links:
  - Curation: All Content, Nominated
  - Admin: Add new feeds, view feeds, settings
  - User: User-specific settings
- At-a-glance
  - Total new? How to keep track? Maybe "past day" or "past week".

phpcs:enable
*/

?>

<div class="wrap pf-welcome">
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

	<p><?php esc_html_e( 'PressForward is a free plugin that provides an editorial workflow for content aggregation and curation within the WordPress dashboard. It is designed for bloggers and editorial teams who wish to collect, discuss, and share content from a variety of sources on the open web.', 'pressforward' ); ?></p>

	<div id="pf-welcome-dashboard-widgets" class="metabox-holder">
		<?php do_meta_boxes( 'toplevel_page_pf-menu', 'normal', null ); ?>
	</div>

	<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
	<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
</div>
