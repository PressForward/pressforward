<?php
header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
if (!WP_DEBUG){
	error_reporting(0);
}
// var_dump($_POST);  die();
set_transient( 'is_multi_author', true );

/**
 * Press It form handler.
 *
 * @package WordPress
 * @subpackage Press_This
 * @since 2.6.0
 *
 * @return int Post ID
 */
function nominate_it() {

	$post = array();
	// $post_ID = $post['ID'] = (int) $_POST['post_id'];
	if ( ! current_user_can( get_option( 'pf_menu_nominate_this_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'contributor' ) ) ) ) {
		wp_die( __( 'You do not have access to the Nominate This bookmarklet.' ) ); }

	$post['post_category'] = isset( $_POST['post_category'] ) ? $_POST['post_category'] : '';
	$post['tax_input'] = isset( $_POST['tax_input'] ) ? $_POST['tax_input'] : '';
	$post['post_title'] = isset( $_POST['title'] ) ? $_POST['title'] : '';
	$content = isset( $_POST['content'] ) ? $_POST['content'] : '';

	// var_dump('<pre>'); var_dump($_POST);
	// set the post_content and status
	$post['post_content'] = $content;
	if ( isset( $_POST['publish'] ) && current_user_can( 'publish_posts' ) ) {
		$post['post_status'] = 'publish'; } elseif ( isset( $_POST['review'] ) ) {
		$post['post_status'] = 'pending';
		} else { 		$post['post_status'] = get_option( PF_SLUG . '_draft_post_status', 'draft' ); }

		$nom_check = false;
		$feed_nom = array( 'error' => false, 'simple' => '' );
		$post['guid'] = $_POST['item_link'];
		// var_dump('<pre>'); var_dump($_POST['pf-feed-subscribe']); die();
		if ( ! empty( $_POST['pf-feed-subscribe'] ) && ( 'subscribe' == $_POST['pf-feed-subscribe'] ) ) {
			$url_array = parse_url( esc_url( $_POST['item_link'] ) );
			$sourceLink = 'http://' . $url_array['host'];
			$create_started = 'Attempting to nominate a feed with the result of: <br />';
			if ( current_user_can( 'edit_posts' ) ) {
				$create = pressforward( 'schema.feeds' )->create( $sourceLink, array( 'post_status' => 'under_review' ) );
				if ( is_numeric( $create ) ) {
					$feed_nom['id'] = $create;
					$create = 'Feed created with ID of ' . $create;
					$feed_nom['simple'] = 'The feed has been nominated successfully.';
					$error_check = pressforward( 'controller.metas' )->get_post_pf_meta( $feed_nom['id'], 'ab_alert_msg', true );
					if ( ! empty( $error_check ) ) {
						$create .= ' But the following error occured: ' . $error_check;
						$feed_nom['simple'] = 'There is a problem with the feed associated with this post. The feed could not be verified.';
					}
					$feed_nom['error'] = $error_check;
				} else {
					$feed_nom['id'] = 0;
					$feed_nom['simple'] = "PressForward was unable to identify a feed associated with this site. Please contact the site administrator or add the feed manually in the 'Add Feeds' panel.";
					$message_one = pf_message( 'An error occured when adding the feed: ' );
					if ( is_wp_error( $create ) ) {
						$create_wp_error = $create->get_error_message();
						$message_two = pf_message( $create_wp_error );
						$feed_nom['error'] = $message_two;
					} else {
						$message_two = pf_message( $create );
					}
					$create = $message_one . $message_two;
				}
			} else {
				$create = 'User doesn\'t have permission to create feeds.';
				$feed_nom['id'] = 0;
				$feed_nom['error'] = $create;
				$feed_nom['simple'] = $create;
			}
			$feed_nom['msg'] = $create_started . $create;

			update_option( 'pf_last_nominated_feed', $feed_nom );

		} else {
			$feed_nom = array(
			'id' => 0,
			'msg'	=> 'No feed was nominated.',
			'simple'  => 'User hasn\'t nominated a feed.',
			);
			update_option( 'pf_last_nominated_feed', $feed_nom );
		}

		// Why does this hinge on $upload?
		// Post formats
		if ( 0 != $feed_nom['id'] ) {
			$post['post_parent'] = $feed_nom['id'];
		}
		$post['post_author'] = get_current_user_id();
		$post['post_type'] = 'nomination';
		pf_log($post);
		$_POST = array_merge($_POST, $post);
		// var_dump($_POST); die();
		if ( isset( $_POST['publish'] ) && ($_POST['publish'] == 'Send to ' . ucwords( get_option( PF_SLUG . '_draft_post_status', 'draft' ) ) ) ) {

			$post_ID = pressforward( 'utility.forward_tools' )->bookmarklet_to_last_step( false, $post );

		} else {
			$post_ID = pressforward( 'utility.forward_tools' )->bookmarklet_to_nomination( false, $post );
		}

		if ( ! empty( $_POST['item_feat_img'] ) && ( $_POST['item_feat_img'] != '' ) ) {
			pressforward( 'schema.feed_item' )->set_ext_as_featured( $post_ID,  $_POST['item_feat_img'] );
		}
		$upload = false;
		if ( ! empty( $_POST['photo_src'] ) && current_user_can( 'upload_files' ) ) {
			foreach ( (array) $_POST['photo_src'] as $key => $image ) {
				// see if files exist in content - we don't want to upload non-used selected files.
				if ( strpos( $_POST['content'], htmlspecialchars( $image ) ) !== false ) {
					$desc = isset( $_POST['photo_description'][ $key ] ) ? $_POST['photo_description'][ $key ] : '';
					$upload = media_sideload_image( $image, $post_ID, $desc );

					// Replace the POSTED content <img> with correct uploaded ones. Regex contains fix for Magic Quotes
					if ( ! is_wp_error( $upload ) ) {
						$content = preg_replace( '/<img ([^>]*)src=\\\?(\"|\')' . preg_quote( htmlspecialchars( $image ), '/' ) . '\\\?(\2)([^>\/]*)\/*>/is', $upload, $content ); }
				}
			}
		}
		// error handling for media_sideload
		if ( is_wp_error( $upload ) ) {
			wp_delete_post( $post_ID );
			wp_die( $upload );
			// Why is this here?
			// Oh, because it is trying to upload the images in the item into our
			// system. But if that doesn't work, something has gone pretty wrong.
			// $nom_check = true;
		}
		// var_dump($post); die();
		return $post_ID;
}
$posted = false;
// For submitted posts.
if ( isset( $_REQUEST['action'] ) && 'post' == $_REQUEST['action'] ) {
	check_admin_referer( 'nominate-this' );
	$posted = nominate_it();
	$post_ID = $posted;
} else {
	$title = isset( $_GET['t'] ) ? trim( strip_tags( html_entity_decode( stripslashes( $_GET['t'] ) , ENT_QUOTES ) ) ) : '';
	// $post_ID = wp_insert_post(array('post_title' => $title, 'post_type' => 'nomination', 'guid' => $_GET['u']));
	// $post_ID = $post->ID;
	// pf_log('Establish post '.$post_ID);
	// var_dump($_GET['u']); die();
	global $pf_nt;

	// Set Variables
	$selection = '';
	if ( ! empty( $_GET['s'] ) ) {
		$selection = str_replace( '&apos;', "'", stripslashes( $_GET['s'] ) );
		$selection = trim( htmlspecialchars( html_entity_decode( $selection, ENT_QUOTES ) ) );
	}

	if ( ! empty( $selection ) ) {
		$selection = preg_replace( '/(\r?\n|\r)/', '</p><p>', $selection );
		$selection = '<p>' . str_replace( '<p></p>', '', $selection ) . '</p>';
		$selection = '<blockquote>' . $selection . '</blockquote>';
	}

	$url = isset( $_GET['u'] ) ? esc_url( $_GET['u'] ) : '';
	$image = isset( $_GET['i'] ) ? $_GET['i'] : '';

	if ( ! empty( $_REQUEST['ajax'] ) ) {
		switch ( $_REQUEST['ajax'] ) {
			case 'video': ?>
    			<script type="text/javascript">
    			/* <![CDATA[ */
    				jQuery('.select').click(function() {
    					append_editor(jQuery('#embed-code').val());
    					jQuery('#extra-fields').hide();
    					jQuery('#extra-fields').html('');
    				});
    				jQuery('.close').click(function() {
    					jQuery('#extra-fields').hide();
    					jQuery('#extra-fields').html('');
    				});
    			/* ]]> */
    			</script>
    			<div class="postbox">
    				<h2><label for="embed-code"><?php _e( 'Embed Code', 'pf' ) ?></label></h2>
    				<div class="inside">
    					<textarea name="embed-code" id="embed-code" rows="8" cols="40"><?php echo esc_textarea( $selection ); ?></textarea>
    					<p id="options"><a href="#" class="select button"><?php _e( 'Insert Video', 'pf' ); ?></a> <a href="#" class="close button"><?php _e( 'Cancel', 'pf' ); ?></a></p>
    				</div>
    			</div>
    			<?php break;

			case 'photo_thickbox': ?>
    			<script type="text/javascript">
    				/* <![CDATA[ */
    				jQuery('.cancel').click(function() {
    					tb_remove();
    				});
    				jQuery('.select').click(function() {
    					image_selector(this);
    				});
    				/* ]]> */
    			</script>
    			<h3 class="tb"><label for="tb_this_photo_description"><?php _e( 'Description', 'pf' ); ?></label></h3>
    			<div class="titlediv">
    				<div class="titlewrap">
    					<input id="tb_this_photo_description" name="photo_description" class="tb_this_photo_description tbtitle text" onkeypress="if(event.keyCode==13) image_selector(this);" value="<?php echo esc_attr( $title );?>"/>
    				</div>
    			</div>

    			<p class="centered">
    				<input type="hidden" name="this_photo" value="<?php echo esc_attr( $image ); ?>" id="tb_this_photo" class="tb_this_photo" />
    				<a href="#" class="select">
    					<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( __( 'Click to insert.', 'pf' ) ); ?>" title="<?php echo esc_attr( __( 'Click to insert.', 'pf' ) ); ?>" />
    				</a>
    			</p>

    			<p id="options"><a href="#" class="select button"><?php _e( 'Insert Image','pf' ); ?></a> <a href="#" class="cancel button"><?php _e( 'Cancel','pf' ); ?></a></p>
    			<?php break;
			case 'photo_images':
				/**
				 * Retrieve all image URLs from given URI.
				 *
				 * @package WordPress
				 * @subpackage Press_This
				 * @since 2.6.0
				 *
				 * @param string $uri
				 * @return string
				 */
				function get_images_from_uri( $uri ) {
					$uri = preg_replace( '/\/#.+?$/','', $uri );
					if ( preg_match( '/\.(jpe?g|jpe|gif|png)\b/i', $uri ) && ! strpos( $uri, 'blogger.com' ) ) {
						return "'" . esc_attr( html_entity_decode( $uri ) ) . "'"; }
					$content = wp_remote_fopen( $uri );
					if ( false === $content ) {
						return ''; }
					$host = parse_url( $uri );
					$pattern = '/<img ([^>]*)src=(\"|\')([^<>\'\"]+)(\2)([^>]*)\/*>/i';
					$content = str_replace( array( "\n", "\t", "\r" ), '', $content );
					preg_match_all( $pattern, $content, $matches );
					if ( empty( $matches[0] ) ) {
						return ''; }
					$sources = array();
					foreach ( $matches[3] as $src ) {
						// if no http in url
						if ( strpos( $src, 'http' ) === false ) {
							// if it doesn't have a relative uri
							if ( strpos( $src, '../' ) === false && strpos( $src, './' ) === false && strpos( $src, '/' ) === 0 ) {
								$src = 'http://' . str_replace( '//','/', $host['host'] . '/' . $src );
							}
						} else { 							$src = 'http://' . str_replace( '//','/', $host['host'] . '/' . dirname( $host['path'] ) . '/' . $src ); }
						$sources[] = esc_url( $src );
					}
					return "'" . implode( "','", $sources ) . "'";
				}
				$url = wp_kses( urldecode( $url ), null );
				echo 'new Array(' . get_images_from_uri( $url ) . ')';
			break;

			case 'photo_js': ?>
				// gather images and load some default JS
				var last = null
				var img, img_tag, aspect, w, h, skip, i, strtoappend = "";
				if(photostorage == false) {
				var my_src = eval(
					jQuery.ajax({
						type: "GET",
						url: "<?php echo esc_url( $_SERVER['PHP_SELF'] ); ?>",
						cache : false,
						async : false,
						data: "ajax=photo_images&u=<?php echo urlencode( $url ); ?>",
						dataType : "script"
					}).responseText
				);
				if(my_src.length == 0) {
					var my_src = eval(
						jQuery.ajax({
							type: "GET",
							url: "<?php echo esc_url( $_SERVER['PHP_SELF'] ); ?>",
							cache : false,
							async : false,
							data: "ajax=photo_images&u=<?php echo urlencode( $url ); ?>",
							dataType : "script"
						}).responseText
					);
					if(my_src.length == 0) {
						strtoappend = '<?php _e( 'Unable to retrieve images or no images on page.','pf' ); ?>';
					}
				}
				}
				for (i = 0; i < my_src.length; i++) {
					img = new Image();
					img.src = my_src[i];
					img_attr = 'id="img' + i + '"';
					skip = false;

					maybeappend = '<a href="?ajax=photo_thickbox&amp;i=' + encodeURIComponent(img.src) + '&amp;u=<?php echo urlencode( $url ); ?>&amp;height=400&amp;width=500" title="" class="thickbox"><img src="' + img.src + '" ' + img_attr + '/></a>';

					if (img.width && img.height) {
						if (img.width >= 30 && img.height >= 30) {
							aspect = img.width / img.height;
							scale = (aspect > 1) ? (71 / img.width) : (71 / img.height);

							w = img.width;
							h = img.height;

							if (scale < 1) {
    						w = parseInt(img.width * scale);
    						h = parseInt(img.height * scale);
							}
							img_attr += ' style="width: ' + w + 'px; height: ' + h + 'px;"';
							strtoappend += maybeappend;
						}
					} else {
						strtoappend += maybeappend;
					}
				}

				function pick(img, desc) {
					if (img) {
						if('object' == typeof jQuery('.photolist input') && jQuery('.photolist input').length != 0) length = jQuery('.photolist input').length;
						if(length == 0) length = 1;
						jQuery('.photolist').append('<input name="photo_src[' + length + ']" value="' + img +'" type="hidden"/>');
						jQuery('.photolist').append('<input name="photo_description[' + length + ']" value="' + desc +'" type="hidden"/>');
						insert_editor( "\n\n" + encodeURI('<p style="text-align: center;"><a href="<?php echo $url; ?>"><img src="' + img +'" alt="' + desc + '" /></a></p>'));
					}
					return false;
				}

				function image_selector(el) {
					var desc, src, parent = jQuery(el).closest('#photo-add-url-div');

					if ( parent.length ) {
						desc = parent.find('input.tb_this_photo_description').val() || '';
						src = parent.find('input.tb_this_photo').val() || ''
					} else {
						desc = jQuery('#tb_this_photo_description').val() || '';
						src = jQuery('#tb_this_photo').val() || ''
					}

					tb_remove();
					pick(src, desc);
					jQuery('#extra-fields').hide();
					jQuery('#extra-fields').html('');
					return false;
				}

				jQuery('#extra-fields').html('<div class="postbox"><h2><?php _e( 'Add Photos','pf' ); ?> <small id="photo_directions">(<?php _e( 'click images to select' ) ?>)</small></h2><ul class="actions"><li><a href="#" id="photo-add-url" class="button button-small"><?php _e( 'Add from URL','pf' ) ?> +</a></li></ul><div class="inside"><div class="titlewrap"><div id="img_container"></div></div><p id="options"><a href="#" class="close button"><?php _e( 'Cancel','pf' ); ?></a><a href="#" class="refresh button"><?php _e( 'Refresh','pf' ); ?></a></p></div>');
				jQuery('#img_container').html(strtoappend);
				<?php break;
		}
		die;
	}
}
	wp_enqueue_style( 'colors' );
	wp_enqueue_script( 'post' );
	_wp_admin_html_begin();
?>
<title><?php _e( 'Nominate This','pf' ) ?></title>
<script type="text/javascript">
//<![CDATA[
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var userSettings = {'url':'<?php echo SITECOOKIEPATH; ?>','uid':'<?php if ( ! isset( $current_user ) ) { $current_user = wp_get_current_user();
} echo $current_user->ID; ?>','time':'<?php echo time() ?>'};
var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>', pagenow = 'nominate-this', isRtl = <?php echo (int) is_rtl(); ?>;
var photostorage = false;
//]]>
</script>

<?php
	do_action( 'admin_print_styles' );
	do_action( 'admin_print_scripts' );
	do_action( 'admin_head' );
?>

    <style type="text/css">
    .postbox{
        padding: 0 5px;
    }

    @media screen and (min-width: 670px) {
        #side-sortables {
    		float: right;
            width: 22%;
            margin-right: 16%;
    	}
    	.posting {
    		float: left;
            width: 58%;
            margin-left: 2%;
    	}
    }
    @media screen and (max-width: 660px) {
        #side-sortables {
            width: 90%;
            margin: 0 auto;
    	}
    	.posting {
            width: 90%;
            margin: 0 auto;
    	}
    }
    </style>

	<script type="text/javascript">
	var wpActiveEditor = 'content';

	function insert_plain_editor(text) {
		if ( typeof(QTags) != 'undefined' )
			QTags.insertContent(text);
	}
	function set_editor(text) {
		if ( '' == text || '<p></p>' == text )
			text = '<p><br /></p>';

		if ( tinyMCE.activeEditor )
			tinyMCE.execCommand('mceSetContent', false, text);
	}
	function insert_editor(text) {
		if ( '' != text && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden()) {
			tinyMCE.execCommand('mceInsertContent', false, '<p>' + decodeURI(tinymce.DOM.decode(text)) + '</p>', {format : 'raw'});
		} else {
			insert_plain_editor(decodeURI(text));
		}
	}
	function append_editor(text) {
		if ( '' != text && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden()) {
			tinyMCE.execCommand('mceSetContent', false, tinyMCE.activeEditor.getContent({format : 'raw'}) + '<p>' + text + '</p>');
		} else {
			insert_plain_editor(text);
		}
	}

	function show(tab_name) {
		jQuery('#extra-fields').html('');
		switch(tab_name) {
			case 'video' :
				jQuery('#extra-fields').load('<?php echo esc_url( $_SERVER['PHP_SELF'] ); ?>', { ajax: 'video', s: '<?php echo esc_attr( $selection ); ?>'}, function() {
					<?php
					$content = '';
					if ( preg_match( '/youtube\.com\/watch/i', $url ) ) {
						list($domain, $video_id) = explode( 'v=', $url );
						$video_id = esc_attr( $video_id );
						$content = '<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/' . $video_id . '"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/' . $video_id . '" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed></object>';

					} elseif ( preg_match( '/vimeo\.com\/[0-9]+/i', $url ) ) {
						list($domain, $video_id) = explode( '.com/', $url );
						$video_id = esc_attr( $video_id );
						$content = '<object width="400" height="225"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="http://www.vimeo.com/moogaloop.swf?clip_id=' . $video_id . '&amp;server=www.vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" />	<embed src="http://www.vimeo.com/moogaloop.swf?clip_id=' . $video_id . '&amp;server=www.vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="400" height="225"></embed></object>';

						if ( trim( $selection ) == '' ) {
							$selection = '<p><a href="http://www.vimeo.com/' . $video_id . '?pg=embed&sec=' . $video_id . '">' . $title . '</a> on <a href="http://vimeo.com?pg=embed&sec=' . $video_id . '">Vimeo</a></p>'; }
					} elseif ( strpos( $selection, '<object' ) !== false ) {
						$content = $selection;
					}
					?>
					jQuery('#embed-code').prepend('<?php echo htmlentities( $content ); ?>');
				});
				jQuery('#extra-fields').show();
				return false;
				break;
			case 'photo' :
				function setup_photo_actions() {
					jQuery('.close').click(function() {
						jQuery('#extra-fields').hide();
						jQuery('#extra-fields').html('');
					});
					jQuery('.refresh').click(function() {
						photostorage = false;
						show('photo');
					});
					jQuery('#photo-add-url').click(function(){
						var form = jQuery('#photo-add-url-div').clone();
						jQuery('#img_container').empty().append( form.show() );
					});
					jQuery('#waiting').hide();
					jQuery('#extra-fields').show();
				}

				jQuery('#waiting').show();
				if(photostorage == false) {
					jQuery.ajax({
						type: "GET",
						cache : false,
						url: "<?php echo esc_url( $_SERVER['PHP_SELF'] ); ?>",
						data: "ajax=photo_js&u=<?php echo urlencode( $url )?>",
						dataType : "script",
						success : function(data) {
							eval(data);
							photostorage = jQuery('#extra-fields').html();
							setup_photo_actions();
						}
					});
				} else {
					jQuery('#extra-fields').html(photostorage);
					setup_photo_actions();
				}
				return false;
				break;
		}
	}
	jQuery(document).ready(function($) {
		//resize screen
		window.resizeTo(740,580);
		// set button actions
		jQuery('#photo_button').click(function() { show('photo'); return false; });
		jQuery('#video_button').click(function() { show('video'); return false; });
		// auto select
		<?php if ( preg_match( '/youtube\.com\/watch/i', $url ) ) { ?>
			show('video');
		<?php } elseif ( preg_match( '/vimeo\.com\/[0-9]+/i', $url ) ) { ?>
			show('video');
		<?php } elseif ( preg_match( '/flickr\.com/i', $url ) ) { ?>
			show('photo');
		<?php } ?>
		jQuery('#title').unbind();
		jQuery('#publish, #save').click(function() { jQuery('.press-this #publishing-actions .spinner').css('display', 'inline-block'); });

		$('#tagsdiv-post_tag, #categorydiv').children('h3, .handlediv').click(function(){
			$(this).siblings('.inside').toggle();
		});
	});
</script>
</head>
<?php
$admin_body_class = ( is_rtl() ) ? 'rtl' : '';
$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );
?>
<body class="press-this wp-admin wp-core-ui nominate-this <?php echo $admin_body_class; ?>">
<?php
//var_dump('<pre>',$_GET);
	if( 2 == $_GET['pf-nominate-this']) {
		$post_url = trailingslashit(get_bloginfo('wpurl')).'wp-admin/edit.php?pf-nominate-this=2';
		echo '<form action="'.$post_url.'&action=post" method="post">';
	} else {
		echo '<form action="nominate-this.php?action=post" method="post">';
	}
?>
<div id="poststuff" class="metabox-holder">
<?php
if ( isset( $posted ) && intval( $posted ) ) { } else {
	?>
	<div id="side-sortables" class="press-this-sidebar">
	<div class="sleeve">
		<?php wp_nonce_field( 'nominate-this' ) ?>
		<input type="hidden" name="post_type" id="post_type" value="text"/>
		<input type="hidden" name="autosave" id="autosave" />
		<input type="hidden" id="original_post_status" name="original_post_status" value="draft" />
		<input type="hidden" id="prev_status" name="prev_status" value="draft" />
		<input type="hidden" id="post_id" name="post_id" value="0" />
		<?php if ( $url != '' ) {

				$author_retrieved = pressforward( 'controller.metas' )->get_author_from_url( $url );
				$tags_retrieved = array();
				$og = pressforward( 'library.opengraph' )->fetch( $url );
			if ( ! empty( $og ) && ! empty( $og->article_tag ) ) {
				$tags_retrieved[] = $og->article_tag;
			}
			if ( ! empty( $og ) && ! empty( $og->article_tag_additional ) ) {
				$tags_retrieved = array_merge( $tags_retrieved, $og->article_tag_additional );
			}
			if ( ! empty( $tags_retrieved ) ) {
				$tags_retrieved[] = 'via bookmarklet';
				$tags_retrieved = implode( ', ', $tags_retrieved );
			} else {
				$tags_retrieved = 'via bookmarklet';
			}
				// var_dump($og); die();
				// $response_body = wp_remote_retrieve_body( $response );
				// $response_dom = pf_str_get_html( $response_body );
			if ( isset( $og->url ) ) {
				$url = $og->url;
			}

			?>
				<?php  ?>
				<input type="hidden" id="source_title" name="source_title" value="<?php echo esc_attr( $title );?>" />
				<input type="hidden" id="date_nominated" name="date_nominated" value="<?php echo current_time( 'mysql' ); ?>" />
				<?php // Metadata goes here.
				if ( isset( $url ) && ! empty( $url ) && ($url) != '' ) {
					pf_log( 'Getting OpenGraph image on ' );
					pf_log( $url );
					// var_dump($_POST['item_link']); die();
					// Gets OG image
					$itemFeatImg = pressforward( 'schema.feed_item' )->get_ext_og_img( $url );
					// var_dump($itemFeatImg); die();
				} else {
					$itemFeatImg = false;
				}
				if ( ! $itemFeatImg || is_wp_error( $itemFeatImg ) ) {
					$itemFeatImg = '';
				}
				?>
				<input type="hidden" id="item_link" name="item_link" value="<?php echo esc_url( $url ); ?>" />
                <input type="hidden" id="item_feat_img" name="item_feat_img" value="<?php echo esc_url( $itemFeatImg ); ?>" />
			<?php } ?>

			<!-- This div holds the photo metadata -->
			<div class="photolist"></div>

			<div id="submitdiv" class="postbox">
				<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle','pf' ); ?>"><br /></div>
				<h3 class="hndle"><?php _e( 'Nominate This','pf' ) ?></h3>
				<div class="inside">
				<p id="publishing-actions">
				<?php
					$publish_type = get_option( PF_SLUG . '_draft_post_status', 'draft' );

					$create_nom_post_cap_test = current_user_can( get_post_type_object( pressforward( 'schema.nominations' )->post_type )->cap->create_posts );

					$pf_draft_post_type_value = get_option( PF_SLUG . '_draft_post_type', 'post' );

					if ('draft' == $publish_type){
						$cap =  'edit_posts';
					} else {
						$cap = 'publish_posts';
					}
					$create_post_cap_test = current_user_can( get_post_type_object( $pf_draft_post_type_value )->cap->$cap );
				if ($create_nom_post_cap_test){
					submit_button( __( 'Nominate' ), 'button', 'draft', false, array( 'id' => 'save' ) );
				} else {
					echo 'You do not have the ability to create nominations.';
				}
				if ( $create_post_cap_test ) {
					submit_button( __( 'Send to ' . ucwords( $publish_type ) ), 'primary', 'publish', false );
				} else {
					echo '<!-- User cannot '.$publish_type.' posts -->';
				} ?>
						<span class="spinner" style="display: none;"></span>
					</p>
					<p>
						<?php
						if ( ! $author_retrieved ) {
							$author_value = '';
						} else {
							$author_value = $author_retrieved;
						}
						?>
					<label for="item_author"><input type="text" id="item_author" name="item_author" value="<?php echo $author_value; ?>" /><br />&nbsp;<?php echo apply_filters( 'pf_author_nominate_this_prompt', __( 'Enter Authors', 'pf' ) ); ?></label>
					</p>
                    <p>
					<label for="pf-feed-subscribe"><input type="checkbox" id="pf-feed-subscribe" name="pf-feed-subscribe" value="subscribe" />&nbsp;&nbsp;<?php _e( 'Nominate feed associated with item.', 'pf' ); ?></label>
					</p>
					<?php if ( current_theme_supports( 'post-formats' ) && post_type_supports( 'post', 'post-formats' ) ) :
							$post_formats = get_theme_support( 'post-formats' );
						if ( is_array( $post_formats[0] ) ) :
							$default_format = get_option( 'default_post_format', '0' );
						?>
					<p>
						<label for="post_format"><?php _e( 'Post Format:','pf' ); ?>
						<select name="post_format" id="post_format">
						<option value="0"><?php _ex( 'Standard', 'Post format' ); ?></option>
						<?php foreach ( $post_formats[0] as $format ) :  ?>
							<option<?php selected( $default_format, $format ); ?> value="<?php echo esc_attr( $format ); ?>"> <?php echo esc_html( get_post_format_string( $format ) ); ?></option>
						<?php endforeach; ?>
						</select></label>
					</p>
					<?php endif;
endif;
					do_action( 'nominate_this_sidebar_head' );
				?>
				</div>
			</div>

			<?php
			do_action( 'nominate_this_sidebar_top' );
			$tax = get_taxonomy( 'category' ); ?>
			<div id="categorydiv" class="postbox">
				<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle' ); ?>"><br /></div>
				<h3 class="hndle"><?php _e( 'Categories' ) ?></h3>
				<div class="inside">
				<div id="taxonomy-category" class="categorydiv">

					<ul id="category-tabs" class="category-tabs">
						<li class="tabs"><a href="#category-all"><?php echo $tax->labels->all_items; ?></a></li>
						<li class="hide-if-no-js"><a href="#category-pop"><?php _e( 'Most Used','pf' ); ?></a></li>
					</ul>

					<div id="category-pop" class="tabs-panel" style="display: none;">
						<ul id="categorychecklist-pop" class="categorychecklist form-no-clear" >
							<?php $popular_ids = wp_popular_terms_checklist( 'category' ); ?>
						</ul>
					</div>

					<div id="category-all" class="tabs-panel">
						<ul id="categorychecklist" data-wp-lists="list:category" class="categorychecklist form-no-clear">
							<?php wp_terms_checklist( 0, array( 'taxonomy' => 'category', 'popular_cats' => $popular_ids ) ) ?>
						</ul>
					</div>

					<?php if ( ! current_user_can( $tax->cap->assign_terms ) ) : ?>
					<p><em><?php _e( 'You cannot modify this Taxonomy.','pf' ); ?></em></p>
					<?php endif; ?>
					<?php if ( current_user_can( $tax->cap->edit_terms ) ) : ?>
						<div id="category-adder" class="wp-hidden-children">
							<h4>
								<a id="category-add-toggle" href="#category-add" class="hide-if-no-js">
									<?php printf( __( '+ %s' ), $tax->labels->add_new_item ); ?>
								</a>
							</h4>
							<p id="category-add" class="category-add wp-hidden-child">
								<label class="screen-reader-text" for="newcategory"><?php echo $tax->labels->add_new_item; ?></label>
								<input type="text" name="newcategory" id="newcategory" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" aria-required="true"/>
								<label class="screen-reader-text" for="newcategory_parent">
									<?php echo $tax->labels->parent_item_colon; ?>
								</label>
								<?php wp_dropdown_categories( array( 'taxonomy' => 'category', 'hide_empty' => 0, 'name' => 'newcategory_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;' ) ); ?>
								<input type="button" id="category-add-submit" data-wp-lists="add:categorychecklist:category-add" class="button category-add-submit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" />
								<?php wp_nonce_field( 'add-category', '_ajax_nonce-add-category', false ); ?>
								<span id="category-ajax-response"></span>
							</p>
						</div>
					<?php endif; ?>
				</div>
				</div>
			</div>
			<div id="tagdiv" class="postbox">
				<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle' ); ?>"><br /></div>
				<h3 class="hndle"><?php _e( 'Tags' ) ?></h3>
				<div class="inside">
				<div id="taxonomy-category" class="tagdiv">
					<p>
						<?php
						if ( ! $tags_retrieved ) {
							$post_tags = '';
						} else {
							$post_tags = $tags_retrieved;
						}
						?>
						<label for="post_tags"><input type="text" id="post_tags" name="post_tags" value="<?php echo $post_tags; ?>" /><br />&nbsp;<?php echo apply_filters( 'pf_tags_prompt', __( 'Enter Tags', 'pf' ) ); ?></label>
					</p>
				</div>
				</div>
			</div>

			<?php do_action( 'nominate_this_sidebar_bottom' ); ?>
		</div>
	</div>
    <?php }
// Post complete template
?>
	<div class="posting">

		<div id="wphead">
			<img id="header-logo" src="<?php echo esc_url( includes_url( 'images/blank.gif' ) ); ?>" alt="" width="16" height="16" />
			<h1 id="site-heading">
				<a href="<?php echo get_option( 'home' ); ?>/" target="_blank">
					<span id="site-title"><?php bloginfo( 'name' ); ?></span>
				</a>
			</h1>
		</div>

		<?php
		if ( isset( $posted ) && intval( $posted ) ) {
			$post_ID = intval( $posted );
			$pt = get_post_type( $post_ID );
			if ( $pt == 'nomination' ) {
				?>
                <div id="message" class="updated">
                <p><strong><?php _e( 'Your nomination has been saved.' ); ?></strong>
                    <a href="#" onclick="window.close();"><?php _e( 'Close Window' ); ?></a>
                    </p>
                </div>
				<?php
			} else {
				?>
                <div id="message" class="updated">
                <p><strong><?php _e( 'Your post has been saved.' ); ?></strong>
                <a onclick="window.opener.location.assign(this.href); window.close();" href="<?php echo get_permalink( $post_ID ); ?>"><?php _e( 'View post' ); ?></a>
                | <a href="<?php echo get_edit_post_link( $post_ID ); ?>" onclick="window.opener.location.assign(this.href); window.close();"><?php _e( 'Edit Post' ); ?></a>
                | <a href="#" onclick="window.close();"><?php _e( 'Close Window' ); ?></a></p>
                </div>
				<?php
			}
			$feed_nom = get_option( 'pf_last_nominated_feed', array() );
			if ( ! empty( $feed_nom ) ) {
				if ( ! empty( $feed_nom['error'] ) ) {
					$feed_nom_class = 'error';
				} else {
					$feed_nom_class = 'updated';
				}
				// var_dump($feed_nom); die();
				?>
                <div id="nom-message" class="<?php echo $feed_nom_class; ?>">
                  <p><strong><?php
				  	if ( ! current_user_can( 'publish_posts' ) || ( false == WP_DEBUG ) ) {
				  		print_r( $feed_nom['simple'] );
				  	} else {
				  		print_r( $feed_nom['msg'] );
				  	}

					?></strong>
					<?php
					if ( 0 !== $feed_nom['id'] ) {
						?>
                      <a href="<?php echo get_edit_post_link( $feed_nom['id'] ); ?>" onclick="window.opener.location.assign(this.href); window.close();"><?php _e( 'Edit Feed' ); ?></a>
                    <?php
					} else {

					}
					?>
                  | <a href="#" onclick="window.close();"><?php _e( 'Close Window' ); ?></a></p>
                </div>
				<?php
				update_option( 'pf_last_nominated_feed', array() );
			}
			die();
		} ?>

		<div id="titlediv">
			<div class="titlewrap">
				<input name="title" id="title" class="text" value="<?php echo esc_attr( $title );?>"/>
			</div>
		</div>

		<div id="waiting" style="display: none"><span class="spinner"></span> <span><?php esc_html_e( 'Loading...' ); ?></span></div>

		<div id="extra-fields" style="display: none"></div>

		<div class="postdivrich">
		<?php

		$editor_settings = array(
			'teeny' => true,
			'textarea_rows' => '15',
		);

		$content = '';
		if ( $selection ) {
			$content .= $selection; }
		ob_start();
		if ( ! $selection ) {
			if ( $url != '' ) {
				$content .= pressforward( 'schema.feed_item' )->get_content_through_aggregator( $url );
			}
		}

		if (WP_DEBUG){
			$cache_errors = ob_get_contents();
		} else {
			$cache_errors = '';
		}
		ob_end_clean();

		//$source_position = get_option( 'pf_source_statement_position', 'bottom' );

		//if ( $url ) {

		//	$source_statement = '<p>';

		//	if ( $selection ) {
		//		$source_statement .= __( 'via ' ); }

		//	$source_statement .= sprintf( "<a href='%s'>%s</a>.</p>", esc_url( $url ), esc_html( $title ) );

		//	if ( 'bottom' == $source_position ) {
		//		  $content .= $source_statement;
		//	} else {
		//		$content = $source_statement . $content;
		//	}
		//}

		remove_action( 'media_buttons', 'media_buttons' );
		add_action( 'media_buttons', 'nominate_this_media_buttons' );
		function nominate_this_media_buttons() {
			_e( 'Add:','pf' );

			if ( current_user_can( 'upload_files' ) ) {
				?>
				<a id="photo_button" title="<?php esc_attr_e( 'Insert an Image' ); ?>" href="#">
				<img alt="<?php esc_attr_e( 'Insert an Image' ); ?>" src="<?php echo esc_url( admin_url( 'images/media-button-image.gif?ver=20100531' ) ); ?>"/></a>
				<?php
			}
			?>
			<a id="video_button" title="<?php esc_attr_e( 'Embed a Video' ); ?>" href="#"><img alt="<?php esc_attr_e( 'Embed a Video' ); ?>" src="<?php echo esc_url( admin_url( 'images/media-button-video.gif?ver=20100531' ) ); ?>"/></a>
			<?php
		}

		wp_editor( $content, 'content', $editor_settings );

		?>
		</div>
	</div>
</div>
</form>
<div id="photo-add-url-div" style="display:none;">
	<table><tr>
	<td><label for="this_photo"><?php _e( 'URL','pf' ) ?></label></td>
	<td><input type="text" id="this_photo" name="this_photo" class="tb_this_photo text" onkeypress="if(event.keyCode==13) image_selector(this);" /></td>
	</tr><tr>
	<td><label for="this_photo_description"><?php _e( 'Description','pf' ) ?></label></td>
	<td><input type="text" id="this_photo_description" name="photo_description" class="tb_this_photo_description text" onkeypress="if(event.keyCode==13) image_selector(this);" value="<?php echo esc_attr( $title );?>"/></td>
	</tr><tr>
	<td><input type="button" class="button" onclick="image_selector(this)" value="<?php esc_attr_e( 'Insert Image' ); ?>" /></td>
	</tr></table>
</div>
<?php
do_action( 'admin_footer' );
do_action( 'admin_print_footer_scripts' );
echo '<pre>'.$cache_errors.'</pre>';
?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>
