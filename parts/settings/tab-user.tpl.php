<?php
/**
 * Template for user preferences tab.
 *
 * @package PressForward
 */

$current_user_id = get_current_user_id();

?>

<p>
	<?php esc_html_e( 'Users can control aspects of the content display in the All Content and Nominated pages by setting preferences here.', 'pressforward' ); ?>
</p>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Infinite scroll', 'pressforward' ); ?>
			</th>

			<td>
				<?php $pf_user_scroll_switch = get_user_option( 'pf_user_scroll_switch', $current_user_id ); ?>
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php esc_html_e( 'Infinite Scroll', 'pressforward' ); ?></span>
					</legend>

					<label for="pf_user_scroll_switch">
						<input id="pf_user_scroll_switch" type="checkbox" name="pf_user_scroll_switch" value="true" <?php checked( empty( $pf_user_scroll_switch ) || 'true' === $pf_user_scroll_switch ); ?> class="user_setting" />
						<?php esc_html_e( 'Infinite Scroll Active', 'pressforward' ); ?>
					</label>

					<p class="description">
						<?php esc_html_e( 'When this box is checked, users can scroll through content continuously.', 'pressforward' ); ?>
					</p>
				</fieldset>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Side menu', 'pressforward' ); ?>
			</th>

			<td>
				<?php $pf_user_menu_set = get_user_option( 'pf_user_menu_set', $current_user_id ); ?>
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php esc_html_e( 'Side Menu', 'pressforward' ); ?></span>
					</legend>

					<label for="pf_user_menu_set">
						<input id="pf_user_menu_set" type="checkbox" name="pf_user_menu_set" value="true" <?php checked( 'true' === $pf_user_menu_set ); ?> class="user_setting" />
						<?php esc_html_e( 'Show side menu', 'pressforward' ); ?>
					</label>

					<p class="description">
						<?php esc_html_e( 'When this box is checked, a menu that includes a list of feeds with alerts displays on the right side of the All Content and Nominated pages.', 'pressforward' ); ?>
					</p>
				</fieldset>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="pf_pagefull">
					<?php esc_html_e( 'Number of feed items per page', 'pressforward' ); ?>
				</label>
			</th>

			<td>
				<?php
				$default_pf_pagefull = get_user_option( 'pf_pagefull', $current_user_id );
				if ( empty( $default_pf_pagefull ) ) {
					$default_pf_pagefull = 20;
				}
				?>

				<input id="pf_pagefull" type="number" name="pf_pagefull" value="<?php echo esc_attr( $default_pf_pagefull ); ?>" class="pf_pagefull" />&nbsp;

				<p class="description">
					<?php esc_html_e( 'Setting this number determines how many items will appear on the All Content and Nominated pages when infinite scroll is turned off.', 'pressforward' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Email notifications', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$default_pf_pagefull = get_user_option( 'pf_pagefull', $current_user_id );
				if ( empty( $default_pf_pagefull ) ) {
					$default_pf_pagefull = 20;
				}
				?>

				<fieldset>
					<legend class="screen-reader-text">
						<span><?php esc_html_e( 'Email Notifications', 'pressforward' ); ?></span>
					</legend>

					<p><?php esc_html_e( 'Send me an email notification when:', 'pressforward' ); ?></p><br />

					<input id="pf-user-nomination-success-email-toggle" type="checkbox" name="pf-user-nomination-success-email-toggle" value="1" <?php checked( pressforward()->fetch( 'controller.users' )->get_user_setting( get_current_user_id(), 'nomination-success-email-toggle' ) ); ?> /> <label for="pf-user-nomination-success-email-toggle"><?php esc_html_e( "I've successfully nominated an item", 'pressforward' ); ?></label><br />
					<input id="pf-user-nomination-promoted-email-toggle" type="checkbox" name="pf-user-nomination-promoted-email-toggle" value="1" <?php checked( pressforward()->fetch( 'controller.users' )->get_user_setting( get_current_user_id(), 'nomination-promoted-email-toggle' ) ); ?> /> <label for="pf-user-nomination-promoted-email-toggle"><?php esc_html_e( "An item that I've nominated is promoted", 'pressforward' ); ?></label><br />
					<input id="pf-user-item-comment-email-toggle" type="checkbox" name="pf-user-item-comment-email-toggle" value="1" <?php checked( pressforward()->fetch( 'controller.users' )->get_user_setting( get_current_user_id(), 'item-comment-email-toggle' ) ); ?> /> <label for="pf-user-item-comment-email-toggle"><?php esc_html_e( "A comment is posted on an item I've interacted with", 'pressforward' ); ?></label> <span class="checkbox-description"><?php esc_html_e( '(Includes items I\'ve nominated, commented on, or promoted)', 'pressforward' ); ?></span><br />
					<br />
				</fieldset>
			</td>
		</tr>
	</tbody>
	</tbody>
</table>
