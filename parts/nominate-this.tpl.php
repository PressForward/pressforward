<?php
/**
 * Template for Nominate This section of tools panel.
 *
 * @package PressForward
 */

?>
	<div class="tool-box">
		<h3 class="title"><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></h3>
		<p><?php esc_html_e( 'Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web. Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pressforward' ); ?></p>

		<p><?php esc_html_e( 'Drag-and-drop the "Nominate This" button to your bookmarks bar, or right-click and add it to your favorites.', 'pressforward' ); ?></p>
		<p><?php esc_html_e( 'On a mobile device? Use the clipboard icon to copy the bookmarklet code and copy into the URL field of a manually-created bookmark.', 'pressforward' ); ?></p>

		<?php require __DIR__ . '/nominate-this-buttons.tpl.php'; ?>
	</div>

<?php
