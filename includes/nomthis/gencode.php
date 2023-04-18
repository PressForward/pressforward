<?php
/**
 * Nominate This gen code.
 *
 * @package PressForward
 */

if ( current_user_can( 'edit_posts' ) ) {
	?>
	<div class="tool-box">
		<h3 class="title"><?php esc_html_e( 'Press This', 'pressforward' ); ?></h3>
		<p><?php esc_html_e( 'Press This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pressforward' ); ?></p>

		<p><?php esc_html_e( 'Use Press This to clip text, images and videos from any web page. Then edit and add more straight from Press This before you save or publish it in a post on your site.', 'pressforward' ); ?></p>
		<p class="description"><?php esc_html_e( 'Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pressforward' ); ?></p>
		<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo esc_attr( htmlspecialchars( pf_get_shortcut_link() ) ); ?>"><span><?php esc_html_e( 'Press This', 'pressforward' ); ?></span></a></p>
		<div class="pressthis-code" style="display:none;">
		<p class="description"><?php esc_html_e( 'If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pressforward' ); ?></p>
		<p><textarea rows="5" cols="120" readonly="readonly"><?php echo esc_attr( htmlspecialchars( pf_get_shortcut_link() ) ); ?></textarea></p>
		</div>
	</div>
	<?php
}

/**
 * Generates the shortcut link for the bookmarklet.
 *
 * Press This get_shortcut_link() from wp-includes/link-template.php
 * http://wpseek.com/get_shortcut_link/.
 *
 * @return string
 */
function get_shortcut_link() {
	// In case of breaking changes, version this. #WP20071.
	$link = "javascript:
			var d=document,
			w=window,
			e=w.getSelection,
			k=d.getSelection,
			x=d.selection,
			s=(e?e():(k)?k():(x?x.createRange().text:0)),
			f='" . admin_url( 'press-this.php' ) . "',
			l=d.location,
			e=encodeURIComponent,
			u=f+'?u='+e(l.href)+'&t='+e(d.title)+'&s='+e(s)+'&v=4';
			a=function(){if(!w.open(u,'t','toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=570'))l.href=u;};
			if (/Firefox/.test(navigator.userAgent)) setTimeout(a, 0); else a();
			void(0)";

	$link = str_replace( array( "\r", "\n", "\t" ), '', $link );

	return apply_filters( 'shortcut_link', $link );
}
