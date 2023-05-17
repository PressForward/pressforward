<?php
/**
 * Template for Alerts tab.
 *
 * @package PressForward
 */

?>

<?php if ( current_user_can( 'edit_posts' ) ) : ?>
	<div class="alert-box postbox">
		<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'pressforward' ); ?>"><br></div>
		<h3 class="hndle"><span><?php esc_html_e( 'Feed Problems', 'pressforward' ); ?></span></h3>
		<div class="inside">
			<?php
			pressforward( 'admin.menu' )->pf_alert_displayer();
			?>
		</div>
	</div>
<?php endif; ?>

<form method="post" action="options.php" enctype="multipart/form-data">
	<?php settings_fields( PF_SLUG . '_feedlist_group' ); ?>
</form>
