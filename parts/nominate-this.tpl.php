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
		<p class="pressthis-bookmarklet-wrapper">
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<a class="pressthis-bookmarklet" onclick="return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></span></a>
			<button type="button" class="button pressthis-js-toggle js-show-pressthis-code-wrap" aria-expanded="false" aria-controls="pressthis-code-wrap">
				<span class="dashicons dashicons-clipboard"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Copy &#8220;Nominate This&#8221; bookmarklet code', 'pressforward' ); ?></span>
			</button>
		</p>

		<div class="hidden js-pressthis-code-wrap clear" id="pressthis-code-wrap">
			<p id="pressthis-code-desc">
				<?php esc_html_e( 'If you can&#8217;t drag the bookmarklet to your bookmarks, copy the following code and create a new bookmark. Paste the code into the new bookmark&#8217;s URL field.', 'pressforward' ); ?>
			</p>

			<p>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<textarea class="js-pressthis-code" rows="5" cols="120" readonly="readonly" aria-labelledby="pressthis-code-desc"><?php echo htmlspecialchars( pf_get_shortcut_link() ); ?></textarea>
			</p>
		</div>

		<script>
		jQuery( document ).ready( function( $ ) {
			var $showPressThisWrap = $( '.js-show-pressthis-code-wrap' );
			var $pressthisCode = $( '.js-pressthis-code' );

			$showPressThisWrap.on( 'click', function( event ) {
				var $this = $( this );

				$this.parent().next( '.js-pressthis-code-wrap' ).slideToggle( 200 );
				$this.attr( 'aria-expanded', $this.attr( 'aria-expanded' ) === 'false' ? 'true' : 'false' );
			});

			// Select Press This code when focusing (tabbing) or clicking the textarea.
			$pressthisCode.on( 'click focus', function() {
				var self = this;
				setTimeout( function() { self.select(); }, 50 );
			});
		});
		</script>
	</div>

<?php
