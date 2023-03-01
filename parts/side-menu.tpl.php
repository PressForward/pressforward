<?php
/**
 * Template for side menu.
 *
 * @package PressForward
 */

?>
	<div id="tools">
			<?php
			// Widgets.
			echo '<div class="primary-btn-tools">';
			if ( 'pressforward_page_pf-review' === $slug && $version >= 0 && current_user_can( pf_get_defining_capability_by_role( 'administrator' ) ) ) {
				?>
						<button type="submit" class="btn btn-warning pull-right" id="archivebefore" value="<?php esc_attr_e( 'Archive before', 'pf' ); ?>:" ><?php esc_html_e( 'Archive before', 'pf' ); ?>:</button>
						<select class="pull-right" id="archiveBeforeOption">
							<option value="1week"><?php esc_html_e( 'Older than 1 week', 'pf' ); ?></option>
							<option value="2weeks"><?php esc_html_e( 'Older than 2 weeks', 'pf' ); ?></option>
							<option value="1month"><?php esc_html_e( 'Older than 1 month', 'pf' ); ?></option>
							<option value="1year"><?php esc_html_e( 'Before this year', 'pf' ); ?></option>
						</select>
				<?php
			}
			echo '</div>';
			?>
				<div class="alert-box">
					<h3><span><?php esc_html_e( 'Feed Problems', 'pf' ); ?></span></h3>
					<div class="inside">
					<?php
						pressforward( 'admin.menu' )->pf_alert_displayer();
					?>
					</div>
				</div>

			<?php
			if ( 'toplevel_page_pf-menu' === $slug && $version >= 0 && current_user_can( pf_get_defining_capability_by_role( 'administrator' ) ) ) {
				?>

						<button type="submit" class="delete btn btn-danger pull-right" id="deletefeedarchive" value="<?php esc_attr_e( 'Delete all items', 'pf' ); ?>" ><?php esc_html_e( 'Delete all items', 'pf' ); ?></button>
				<?php
			}

			do_action( 'pf_side_menu_widgets', $slug );
			?>

		</div>
