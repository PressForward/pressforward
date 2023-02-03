<?php
/**
 * Template for Nominate This section of tools panel.
 *
 * @package PressForward
 */

if ( 'as_paragraph' === $context ) {
	?>
		<div class="tool-box">
			<h3 class="title"><?php esc_html_e( 'Nominate This', 'pf' ); ?></h3>
			<p><?php esc_html_e( 'Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pf' ); ?></p>

			<p><?php esc_html_e( 'Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pf' ); ?></p>
			<p class="description"><?php esc_html_e( 'Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pf' ); ?></p>
			<p class="pressthis"><a class="button" onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo esc_attr( htmlspecialchars( pf_get_shortcut_link() ) ); ?>"><span><?php esc_html_e( 'Nominate This', 'pf' ); ?></span></a></p>
			<div class="pressthis-code" style="display:none;">
				<p class="description"><?php esc_html_e( 'If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pf' ); ?></p>
				<p><textarea rows="5" cols="120" readonly="readonly"><?php echo esc_html( htmlspecialchars( pf_get_shortcut_link() ) ); ?></textarea></p>
			</div><br />

			<div>
			<h3><?php esc_html_e( 'Nominate This Extension', 'pf' ); ?></h3>
			<p><?php esc_html_e( 'Nominate This now has an extension for Chrome with additional functionality.', 'pf' ); ?></p>
			<p><a href="https://github.com/PressForward/PressForwardChromeExtension/releases" target="_blank"><?php esc_html_e( 'You can download this new extension at our GitHub repository.', 'pf' ); ?></a></p>
			<p><?php esc_html_e( 'Once you have installed the extension, it needs your API keys.', 'pf' ); ?></p>
			<p>
				<?php esc_html_e( 'Click here to send the installed extension your API keys:', 'pf' ); ?><br />
				<a class="button" id="pressforward-nt__setup-button" onclick=""><?php esc_html_e( 'Send API Keys to Extension', 'pf' ); ?></span></a> <br /><br />
				<?php esc_html_e( 'Click here to regenerate API keys:', 'pf' ); ?><br />
				<a class="button" id="pressforward-nt__regenerate-button" onclick=""><?php esc_html_e( 'Regenerate and send API Keys to Extension', 'pf' ); ?></span></a> <br />
			</p>
			</div>
		</div>

	<?php
} elseif ( 'as_feed' === $context ) {
	?>
	<div class="pf-opt-group span5">
		<div class="rss-box postbox">
				<div class="handlediv"><br></div>
				<h3 class="hndle"><span><?php esc_html_e( 'Nominate This', 'pf' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pf' ); ?></p>

					<p><?php esc_html_e( 'Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pf' ); ?></p>
					<p class="description"><?php esc_html_e( 'Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pf' ); ?></p>
					<p class="pressthis"><a class="button" onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo esc_attr( htmlspecialchars( pf_get_shortcut_link() ) ); ?>"><span><?php esc_html_e( 'Nominate This', 'pf' ); ?></span></a></p>
					<div class="pressthis-code" style="display:none;">
						<p class="description"><?php esc_html_e( 'If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pf' ); ?></p>
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
				<i class="icon-remove pf-item-remove remove-nom-this-prompt" id="remove_nominate_this_preview" title="<?php esc_attr_e( 'Delete', 'pf' ); ?>"></i>
			</div>
			<header>
				<h1 class="item_title">
					<?php esc_html_e( 'Nominate posts using PressForward\'s Bookmarklet', 'pf' ); ?>
				</h1>
			</header>
			<div class="content">
				<div class="item_excerpt" id="excerpt1">
					<p>
						<?php
							esc_html_e( 'Use Nominate This to pull in text, images and videos from any web page. Then you can edit, add author and category before you nominate or draft it in a post on your site.', 'pf' );
						?>
						</p>

						<p>
							<?php esc_html_e( 'Drag the button up to your bookmark bar.', 'pf' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-tools' ) ); ?>" class="remove-nom-this-prompt"><?php esc_html_e( 'Click here to find out more.', 'pf' ); ?></a>
						</p>

						<p class="pressthis"><a class="button" onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo esc_js( htmlspecialchars( pf_get_shortcut_link() ) ); ?>"><span><?php esc_html_e( 'Nominate This', 'pf' ); ?></span></a></p>

					</div>
				</div>

				<footer>
					<p class="pubdate"><?php esc_html_e( 'This item will stay in place until deleted with the top button or the link is clicked.', 'pf' ); ?></p>
				</footer>

			</article>

		<?php

} elseif ( empty( $_GET['pc'] ) ) {
	esc_html_e( 'Try Nominate This in PressForward\'s Tools menu.', 'pf' );
}
