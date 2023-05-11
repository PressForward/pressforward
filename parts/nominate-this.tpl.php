<?php
/**
 * Template for Nominate This section of tools panel.
 *
 * @package PressForward
 */

if ( 'as_paragraph' === $context ) {
	?>
		<div class="tool-box">
			<h3 class="title"><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></h3>
			<p><?php esc_html_e( 'Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web. Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pressforward' ); ?></p>

			<p><?php esc_html_e( 'Drag-and-drop the "Nominate This" button to your bookmarks bar, or right-click and add it to your favorites.', 'pressforward' ); ?></p>
			<p><?php esc_html_e( 'On a mobile device? Use the clipboard icon to copy the bookmarklet code and copy into the URL field of a manually-created bookmark.', 'pressforward' ); ?></p>
			<p class="pressthis-bookmarklet-wrapper">
				<a class="pressthis-bookmarklet" onclick="return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php esc_html_e( 'Nominate This', 'press-this' ); ?></span></a>
				<button type="button" class="button pressthis-js-toggle js-show-pressthis-code-wrap" aria-expanded="false" aria-controls="pressthis-code-wrap">
					<span class="dashicons dashicons-clipboard"></span>
					<span class="screen-reader-text"><?php _e( 'Copy &#8220;Nominate This&#8221; bookmarklet code', 'pressforward' ) ?></span>
				</button>
			</p>

			<div class="hidden js-pressthis-code-wrap clear" id="pressthis-code-wrap">
				<p id="pressthis-code-desc">
					<?php _e( 'If you can&#8217;t drag the bookmarklet to your bookmarks, copy the following code and create a new bookmark. Paste the code into the new bookmark&#8217;s URL field.', 'press-this' ) ?>
				</p>

				<p>
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

			<br /><br />

			<div>

			<h3><?php esc_html_e( 'Nominate This Extension', 'pressforward' ); ?></h3>
			<p><?php esc_html_e( 'Nominate This now has an extension for Chrome with additional functionality.', 'pressforward' ); ?></p>
			<p><a href="https://github.com/PressForward/PressForwardChromeExtension/releases" target="_blank"><?php esc_html_e( 'You can download this new extension at our GitHub repository.', 'pressforward' ); ?></a></p>
			<p><?php esc_html_e( 'Once you have installed the extension, it needs your API keys.', 'pressforward' ); ?></p>
			<p>
				<?php esc_html_e( 'Click here to send the installed extension your API keys:', 'pressforward' ); ?><br />
				<a class="button" id="pressforward-nt__setup-button" onclick=""><?php esc_html_e( 'Send API Keys to Extension', 'pressforward' ); ?></span></a> <br /><br />
				<?php esc_html_e( 'Click here to regenerate API keys:', 'pressforward' ); ?><br />
				<a class="button" id="pressforward-nt__regenerate-button" onclick=""><?php esc_html_e( 'Regenerate and send API Keys to Extension', 'pressforward' ); ?></span></a> <br />
			</p>
			</div>
		</div>

	<?php
} elseif ( 'as_feed' === $context ) {
	?>
	<div class="pf-opt-group span5">
		<div class="rss-box postbox">
				<div class="handlediv"><br></div>
				<h3 class="hndle"><span><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pressforward' ); ?></p>

					<p><?php esc_html_e( 'Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pressforward' ); ?></p>
					<p class="pressthis"><a class="button" onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo esc_attr( htmlspecialchars( pf_get_shortcut_link() ) ); ?>"><span><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></span></a></p>
					<div class="pressthis-code" style="display:none;">
						<p class="description"><?php esc_html_e( 'If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pressforward' ); ?></p>
						<p><textarea rows="5" cols="120" readonly="readonly"><?php echo esc_attr( htmlspecialchars( pf_get_shortcut_link() ) ); ?></textarea></p>

					</div>
				</div>
		</div>
		</div>
		<?php
} elseif ( 'as_feed_item' === $context && empty( $_GET['pc'] ) ) {

	?>

		<article class="feed-item entry nominate-this-preview">
			<div class="box-controls">
				<i class="icon-remove pf-item-remove remove-nom-this-prompt" id="remove_nominate_this_preview" title="<?php esc_attr_e( 'Delete', 'pressforward' ); ?>"></i>
			</div>
			<header>
				<h1 class="item_title">
					<?php esc_html_e( 'Nominate posts using PressForward\'s Bookmarklet', 'pressforward' ); ?>
				</h1>
			</header>
			<div class="content">
				<div class="item_excerpt" id="excerpt1">
					<p>
						<?php
							esc_html_e( 'Use Nominate This to pull in text, images and videos from any web page. Then you can edit, add author and category before you nominate or draft it in a post on your site.', 'pressforward' );
						?>
						</p>

						<p>
							<?php esc_html_e( 'Drag the button up to your bookmark bar.', 'pressforward' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-tools' ) ); ?>" class="remove-nom-this-prompt"><?php esc_html_e( 'Click here to find out more.', 'pressforward' ); ?></a>
						</p>

						<p class="pressthis"><a class="button" onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo esc_js( htmlspecialchars( pf_get_shortcut_link() ) ); ?>"><span><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></span></a></p>

					</div>
				</div>

				<footer>
					<p class="pubdate"><?php esc_html_e( 'This item will stay in place until deleted with the top button or the link is clicked.', 'pressforward' ); ?></p>
				</footer>

			</article>

		<?php

} elseif ( empty( $_GET['pc'] ) ) {
	esc_html_e( 'Try Nominate This in PressForward\'s Tools menu.', 'pressforward' );
}
