<?php
/**
 * Template for the bookmarklet-code block.
 *
 * @package PressForward
 * @since 5.6.0
 */

?>

<?php wp_enqueue_script( 'pf-blocks-frontend' ); ?>
<?php wp_enqueue_style( 'pf-blocks-frontend' ); ?>

<div class="nominate-this-bookmarklet-code">
	<p class="nominate-this-bookmarklet-wrapper">
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<a class="nominate-this-bookmarklet" onclick="return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></span></a>
		<button type="button" class="button nominate-this-js-toggle js-show-nominate-this-code-wrap" aria-expanded="false" aria-controls="nominate-this-code-wrap">
			<span class="dashicons dashicons-clipboard"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Copy &#8220;Nominate This&#8221; bookmarklet code', 'pressforward' ); ?></span>
		</button>
	</p>

	<div class="nominate-this-code-wrap clear">
		<p id="nominate-this-code-desc">
			<?php esc_html_e( 'If you can&#8217;t drag the bookmarklet to your bookmarks, copy the following code and create a new bookmark. Paste the code into the new bookmark&#8217;s URL field.', 'pressforward' ); ?>
		</p>

		<p>
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<textarea class="js-nominate-this-code" rows="5" cols="120" readonly="readonly" aria-labelledby="nominate-this-code-desc"><?php echo htmlspecialchars( pf_get_shortcut_link() ); ?></textarea>
		</p>
	</div>
</div>
