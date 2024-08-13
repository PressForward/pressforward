<?php
/**
 * Nominate This.
 *
 * @package PressForward
 */

header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
if ( ! WP_DEBUG ) {
	// phpcs:ignore
	error_reporting( 0 );
}

set_current_screen();

require_once ABSPATH . '/wp-admin/includes/meta-boxes.php';

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
	return pressforward( 'bookmarklet.core' )->nominate_it();
}

$posted = false;

// For submitted posts.
$selection   = '';
$the_title   = '';
$current_url = '';
$url         = '';
if ( isset( $_REQUEST['action'] ) && 'post' === $_REQUEST['action'] ) {
	check_admin_referer( 'nominate-this' );
	$posted      = nominate_it();
	$the_post_id = $posted;
} else {
	$the_title = isset( $_GET['t'] ) ? trim( wp_strip_all_tags( html_entity_decode( sanitize_text_field( wp_unslash( $_GET['t'] ) ), ENT_QUOTES ) ) ) : '';

	global $pf_nt;

	// Set Variables.
	if ( ! empty( $_GET['s'] ) ) {
		$selection_search_term = sanitize_text_field( wp_unslash( $_GET['s'] ) );

		$selection = str_replace( '&apos;', "'", $selection_search_term );
		$selection = trim( htmlspecialchars( html_entity_decode( $selection, ENT_QUOTES ) ) );
	}

	if ( ! empty( $selection ) ) {
		$selection = preg_replace( '/(\r?\n|\r)/', '</p><p>', $selection );
		$selection = '<p>' . str_replace( '<p></p>', '', $selection ) . '</p>';
		$selection = '<blockquote>' . $selection . '</blockquote>';
	}

	$url   = isset( $_GET['u'] ) ? esc_url( sanitize_text_field( wp_unslash( $_GET['u'] ) ) ) : '';
	$image = isset( $_GET['i'] ) ? sanitize_text_field( wp_unslash( $_GET['i'] ) ) : '';

	$current_url = isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '';

}

wp_enqueue_style( 'colors' );
wp_enqueue_script( 'post' );

wp_enqueue_script( 'pf-nominate-this' );
wp_enqueue_style( 'pf-nominate-this' );

_wp_admin_html_begin();
?>

<title><?php esc_html_e( 'Nominate This', 'pressforward' ); ?></title>

<?php
do_action( 'admin_enqueue_scripts' );
do_action( 'admin_print_styles' );
do_action( 'admin_print_scripts' );
do_action( 'admin_head' );
?>

<script type="text/javascript">
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
var pagenow = 'nominate-this';
</script>

</head>
<?php
$the_admin_body_class  = ( is_rtl() ) ? 'rtl' : '';
$the_admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );
?>
<body class="press-this wp-admin wp-core-ui nominate-this <?php echo esc_attr( $the_admin_body_class ); ?>">

<div id="loading-indicator" class="loading-indicator nomthis-indicator">
	<?php // translators: URL being loaded ?>
	<img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" role="presentation" /> <span><?php printf( esc_html__( 'Loading content from %s.', 'pressforward' ), '<span id="loading-url"></span>' ); ?></span>
</div>

<div id="failure-indicator" class="failure-indicator nomthis-indicator">
	<?php esc_html_e( 'Could not fetch remote URL', 'pressforward' ); ?>
</div>

<?php
if ( isset( $_GET['pf-nominate-this'] ) && 2 === intval( $_GET['pf-nominate-this'] ) ) {
	$post_url = trailingslashit( get_bloginfo( 'wpurl' ) ) . 'wp-admin/edit.php?pf-nominate-this=2';
	echo '<form action="' . esc_attr( $post_url ) . '&action=post" method="post">';
} else {
	echo '<form action="nominate-this.php?action=post" method="post">';
}
?>

<div id="poststuff" class="metabox-holder">
<?php
$empty_nomination = null;
if ( empty( $posted ) ) {
	$empty_nomination            = new WP_Post( new stdClass() );
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

	<?php if ( ! empty( $url ) ) : ?>
		<input type="hidden" id="source_title" name="source_title" value="<?php echo esc_attr( $the_title ); ?>" />
		<input type="hidden" id="date_nominated" name="date_nominated" value="<?php echo esc_attr( current_time( 'mysql' ) ); ?>" />

		<input type="hidden" id="item_link" name="item_link" value="<?php echo esc_url( $url ); ?>" />
	<?php endif; ?>

	<input type="hidden" id="item_feat_img" name="item_feat_img" value="" />

	<?php
}
// Post complete template.
?>
	<div class="posting">

		<div id="wphead">
			<img id="header-logo" src="<?php echo esc_url( includes_url( 'images/blank.gif' ) ); ?>" alt="" width="16" height="16" />
			<h1 id="site-heading">
				<a href="<?php echo esc_attr( get_option( 'home' ) ); ?>/" target="_blank">
					<span id="site-title"><?php bloginfo( 'name' ); ?></span>
				</a>
			</h1>
		</div>

		<?php
		if ( ! empty( $posted ) ) {
			$the_post_id = intval( $posted );
			$pt          = get_post_type( $the_post_id );
			if ( 'nomination' === $pt ) {
				?>
				<div id="message" class="updated">
				<p><strong><?php esc_html_e( 'Your nomination has been saved.', 'pressforward' ); ?></strong>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-review' ) ); ?>"><?php esc_html_e( 'See all nominations', 'pressforward' ); ?></a>
				| <a href="#" onclick="window.close();"><?php esc_html_e( 'Close Window', 'pressforward' ); ?></a>
					</p>
				</div>
				<?php
			} else {
				?>
				<div id="message" class="updated">
				<p><strong><?php esc_html_e( 'Your post has been saved.', 'pressforward' ); ?></strong>
				<a onclick="window.opener.location.assign(this.href); window.close();" href="<?php echo esc_attr( get_permalink( $the_post_id ) ); ?>"><?php esc_html_e( 'View post', 'pressforward' ); ?></a>
				| <a href="<?php echo esc_attr( get_edit_post_link( $the_post_id ) ); ?>" onclick="window.opener.location.assign(this.href); window.close();"><?php esc_html_e( 'Edit Post', 'pressforward' ); ?></a>
				| <a href="#" onclick="window.close();"><?php esc_html_e( 'Close Window', 'pressforward' ); ?></a></p>
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
				?>
				<div id="nom-message" class="<?php echo esc_attr( $feed_nom_class ); ?>">
					<p><strong>
					<?php
					if ( ! current_user_can( 'publish_posts' ) || ! WP_DEBUG ) {
						echo esc_html( $feed_nom['simple'] );
					} else {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
						print_r( $feed_nom['msg'] );
					}

					?>
					</strong>
					<?php
					if ( 0 !== $feed_nom['id'] ) {
						?>
						<a href="<?php echo esc_attr( get_edit_post_link( $feed_nom['id'] ) ); ?>" onclick="window.opener.location.assign(this.href); window.close();"><?php esc_html_e( 'Edit Feed', 'pressforward' ); ?></a>
						<?php
					}
					?>
					| <a href="#" onclick="window.close();"><?php esc_html_e( 'Close Window', 'pressforward' ); ?></a></p>
				</div>
				<?php
				update_option( 'pf_last_nominated_feed', array() );
			}
			die();
		}
		?>

		<div id="titlediv">
			<div class="titlewrap">
				<input name="title" id="title" class="text" value="<?php echo esc_attr( $the_title ); ?>"/>
			</div>
		</div>

		<div id="waiting" style="display: none"><span class="spinner"></span> <span><?php esc_html_e( 'Loading...', 'pressforward' ); ?></span></div>

		<div id="extra-fields" style="display: none"></div>

		<div class="postdivrich">
		<?php

		$editor_settings = array(
			'teeny'         => true,
			'textarea_rows' => 18,
		);

		$content = '';
		if ( $selection ) {
			$content .= $selection;
		}

		wp_editor( $content, 'content', $editor_settings );

		?>
		</div>

		<div class="metabox-holder metabox-holder-advanced">
			<?php do_meta_boxes( 'nomthis', 'advanced', $empty_nomination ); ?>
		</div>
	</div>
</div>

<?php
// Needed for the closed-postboxes AJAX action.
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>

</form>

<?php
do_action( 'admin_footer' );
do_action( 'admin_print_footer_scripts' );
?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>
