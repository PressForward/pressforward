<?php
header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
if (!WP_DEBUG){
	error_reporting(0);
}
// var_dump($_POST);  die();
set_transient( 'is_multi_author', true );

require_once( ABSPATH . '/wp-admin/includes/meta-boxes.php' );

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
	return pressforward('bookmarklet.core')->nominate_it();
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

	$empty_nomination = new WP_Post( new stdClass() );
	$empty_nomination->post_type = 'nomination';

	do_action( 'add_meta_boxes_nomthis', $empty_nomination );
	do_meta_boxes( 'nomthis', 'side', $empty_nomination );
	wp_nonce_field( 'nominate-this' );
	?>

	<input type="hidden" name="post_type" id="post_type" value="text"/>
	<input type="hidden" name="autosave" id="autosave" />
	<input type="hidden" id="original_post_status" name="original_post_status" value="draft" />
	<input type="hidden" id="prev_status" name="prev_status" value="draft" />
	<input type="hidden" id="post_id" name="post_id" value="0" />
	<?php if ( $url != '' ) {
		$og = pressforward( 'library.opengraph' )->fetch( $url );

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
