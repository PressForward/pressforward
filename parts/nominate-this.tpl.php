<?php
	if ('as_paragraph' == $context) {
?>
			<div class="tool-box">
				<h3 class="title"><?php _e('Nominate This', 'pf'); ?></h3>
				<p><?php _e('Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pf');?></p>

				<p><?php _e('Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pf'); ?></p>
				<p class="description"><?php _e('Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pf'); ?></p>
				<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php _e('Nominate This', 'pf'); ?></span></a></p>
				<div class="pressthis-code" style="display:none;">
					<p class="description"><?php _e('If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pf'); ?></p>
					<p><textarea rows="5" cols="120" readonly="readonly"><?php echo htmlspecialchars( pf_get_shortcut_link() ); ?></textarea></p>
				</div>
			</div>
<?php
	} elseif ( 'as_feed' == $context ) {
		?>
		<div class="pf-opt-group span5">
            <div class="rss-box postbox">
                    <div class="handlediv"><br></div>
                    <h3 class="hndle"><span><?php _e('Nominate This', 'pf'); ?></span></h3>
                    <div class="inside">
                        <p><?php _e('Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pf');?></p>

						<p><?php _e('Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pf'); ?></p>
						<p class="description"><?php _e('Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pf'); ?></p>
						<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php _e('Nominate This', 'pf'); ?></span></a></p>
						<div class="pressthis-code" style="display:none;">
							<p class="description"><?php _e('If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pf'); ?></p>
							<p><textarea rows="5" cols="120" readonly="readonly"><?php echo htmlspecialchars( pf_get_shortcut_link() ); ?></textarea></p>

                    	</div>
                    </div>
            </div>
		</div>
		<?php
	} elseif ( 'as_feed_item' == $context && empty($_GET["pc"]) ){

		?>

			<article class="feed-item entry nominate-this-preview">
				<div class="box-controls">
					<i class="icon-remove pf-item-remove remove-nom-this-prompt" id="remove_nominate_this_preview" title="Delete"></i>
				</div>
				<header>
					<h1 class="item_title">
						Nominate posts using PressForward's Bookmarklet
					</h1>
				</header>
				<div class="content">
					<div class="item_excerpt" id="excerpt1">
						<p>
							<?php
								_e('Use Nominate This to pull in text, images and videos from any web page.
									Then you can edit, add author and category before
									you nominate or draft it in a post on your site.', 'pf');
							?>
						</p>
						<p>
							<?php printf(
										__('Drag the button up to your bookmark bar or <a href="%s" class="%s">click here to find out more</a>.', 'pf'),
										esc_url('admin.php?page=pf-tools'),
										esc_attr('remove-nom-this-prompt')
									);
							?>
						</p>
						<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php _e('Nominate This', 'pf'); ?></span></a></p>

					</div>
				</div>

				<footer>
					<p class="pubdate">This item will stay in place until deleted with the top button or the link is clicked.</p>
				</footer>

			</article>

		<?php

	} elseif ( empty($_GET["pc"]) ) {
		_e('Try Nominate This in PressForward\'s Tools menu.', 'pf');
	}