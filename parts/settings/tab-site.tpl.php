<?php
/**
 * Template for Preferences section.
 *
 * @package PressForward
 */

?>
<p>
	<?php esc_html_e( 'These preferences are available only to users with an Administrator role in PressForward. Options set on this page will determine behavior across the site as a whole.', 'pressforward' ); ?>
</p>

<h3 class="title"><?php esc_html_e( 'Source Options', 'pressforward' ); ?></h3>

<p><?php esc_html_e( 'These settings control the way that each piece PressForward content relates to its "source" - that is, the original content from which it was copied.', 'pressforward' ); ?></p>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Redirect to source', 'pressforward' ); ?>
			</th>

			<td>
				<label for="pf_link_to_source">
					<input id="pf_link_to_source" name="pf_link_to_source" type="number" class="pf_link_to_source_class" value="<?php echo esc_attr( get_option( 'pf_link_to_source', 0 ) ); ?>" />
					<?php esc_html_e( 'Seconds before redirectng user to source', 'pressforward' ); ?>
				</label>

				<p class="description">
					<?php esc_html_e( 'PressForward makes use of canonical URLs. If you would like to redirect your readers automatically to the items aggregated by the feed reader and published to your site, this setting will determine how long to wait before redirecting.', 'pressforward' ); ?>
				</p>

				<p class="description">
					<strong><?php esc_html_e( 'Set to 0 to disable this feature.', 'pressforward' ); ?></strong>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Source author', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$default_pf_present_author_value = get_option( 'pf_present_author_as_primary', 'yes' );
				?>

				<label for="pf_present_author_as_primary">
					<select id="pf_present_author_as_primary" name="pf_present_author_as_primary">
						<option value="yes" <?php selected( $default_pf_present_author_value, 'yes' ); ?>><?php esc_html_e( 'Yes', 'pressforward' ); ?></option>
						<option value="no" <?php selected( $default_pf_present_author_value, 'no' ); ?>><?php esc_html_e( 'No', 'pressforward' ); ?></option>
					</select>

					<?php esc_html_e( 'Use source author for bylines', 'pressforward' ); ?>
				</label>

				<p>
					<?php esc_html_e( 'When set to "Yes", the name of the author pulled from the source item will be used in place of the WordPress user\'s name when displaying the item around the site.', 'pressforward' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="pf_source_statement_position">
					<?php esc_html_e( 'Source statement position', 'pressforward' ); ?>
				</label>
			</th>

			<td>
				<?php
				$pf_source_statement_position = get_option( 'pf_source_statement_position', 'bottom' );
				?>

				<select id="pf_source_statement_position" name="pf_source_statement_position">
					<option value="top" <?php selected( $pf_source_statement_position, 'top' ); ?>><?php esc_html_e( 'Top', 'pressforward' ); ?></option>
					<option value="bottom" <?php selected( $pf_source_statement_position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'pressforward' ); ?></option>
				</select>

				<p>
					<?php esc_html_e( 'Controls where the link to the source will be positioned on published content.', 'pressforward' ); ?>
				</p>
			</td>
		</tr>

		<tr class="pf-source-statement-format-fields">
			<th scope="row">
				<?php esc_html_e( 'Source statement format', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$source_formats = pressforward_source_statement_formats();
				?>

				<p>
					<label for="pf_source_format_with_publication">
						<input type="text" name="pf_source_format_with_publication" id="pf_source_format_with_publication" value="<?php echo esc_attr( $source_formats['with_publication'] ); ?>" />
					</label>

					<?php esc_html_e( 'Format with publication', 'pressforward' ); ?>
				</p>

				<p>
					<label for="pf_source_format_without_publication">
						<input type="text" name="pf_source_format_without_publication" id="pf_source_format_without_publication" value="<?php echo esc_attr( $source_formats['without_publication'] ); ?>" />
					</label>

					<?php esc_html_e( 'Format without publication', 'pressforward' ); ?>
				</p>

				<p><?php esc_html_e( 'Defines the format of the source statement added to PressForward items.', 'pressforward' ); ?></p>

				<p><?php echo wp_kses_post( __( 'Use the <code>{{item}}</code> and <code>{{publisher}}</code> placeholders.', 'pressforward' ) ); ?></p>

				<p><?php esc_html_e( '"Format without publication" will be used when no publication name can be detected automatically.', 'pressforward' ); ?></p>

				<p><?php esc_html_e( 'Leave both fields empty to disable the source statement.', 'pressforward' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>

<h3 class="title"><?php esc_html_e( 'Email Options', 'pressforward' ); ?></h3>

<p><?php esc_html_e( 'These settings relate to how PressForward generates email notifications.', 'pressforward' ); ?></p>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Nomination emails', 'pressforward' ); ?>
			</th>

			<td>
				<p>
					<label for="pf_nomination_send_email">
						<input id="pf_nomination_send_email" name="pf_nomination_send_email" type="text" class="pf_nomination_send_email" value="<?php echo esc_attr( get_option( 'pf_nomination_send_email', '' ) ); ?>" />
						<?php esc_html_e( 'Addresses to receive notifications about new nominations', 'pressforward' ); ?>
					</label>
				</p>

				<p class="description">
					<?php esc_html_e( 'Separate with commas. If left blank, no notifications will be sent.', 'pressforward' ); ?>
				</p>

				<?php
				$nomination_success_default  = get_option( 'pf_user_nomination_success_email_default', 'off' );
				$nomination_promoted_default = get_option( 'pf_user_nomination_promoted_email_default', 'on' );
				?>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'User defaults', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$nomination_success_default  = get_option( 'pf_user_nomination_success_email_default', 'off' );
				$nomination_promoted_default = get_option( 'pf_user_nomination_promoted_email_default', 'on' );
				$item_comment_default        = get_option( 'pf_user_item_comment_email_default', 'off' );
				?>

				<fieldset role="group" aria-labelledby="email-notifications-legend">
					<legend class="screen-reader-text" id="email-notifications-legend"><?php esc_html_e( 'Email Notifications', 'pressforward' ); ?></legend>

					<p><?php esc_html_e( 'Configure the default user preferences for email notifications. Note that individual users can override these preferences on the User Options panel.', 'pressforward' ); ?></p>

					<br />

					<p>
						<label for="pf-user-nomination-success-email-default">
							<select id="pf-user-nomination-success-email-default" name="pf-user-nomination-success-email-default">
								<option value="on" <?php selected( 'on' === $nomination_success_default ); ?>><?php esc_html_e( 'Enabled', 'pressforward' ); ?></option>
								<option value="off" <?php selected( 'on' !== $nomination_success_default ); ?>><?php esc_html_e( 'Disabled', 'pressforward' ); ?></option>
							</select>

							<?php esc_html_e( 'User has successfully nominated an item', 'pressforward' ); ?>
						</label>
					</p>

					<p>
						<label for="pf-user-nomination-promoted-email-default">
							<select id="pf-user-nomination-promoted-email-default" name="pf-user-nomination-promoted-email-default">
								<option value="on" <?php selected( 'on' === $nomination_promoted_default ); ?>><?php esc_html_e( 'Enabled', 'pressforward' ); ?></option>
								<option value="off" <?php selected( 'on' !== $nomination_promoted_default ); ?>><?php esc_html_e( 'Disabled', 'pressforward' ); ?></option>
							</select>

							<?php esc_html_e( 'An item that the user has nominated is promoted', 'pressforward' ); ?>
						</label>
					</p>

					<p>
						<label for="pf-user-item-comment-email-default">
							<select id="pf-user-item-comment-email-default" name="pf-user-item-comment-email-default">
								<option value="on" <?php selected( 'on' === $item_comment_default ); ?>><?php esc_html_e( 'Enabled', 'pressforward' ); ?></option>
								<option value="off" <?php selected( 'on' !== $item_comment_default ); ?>><?php esc_html_e( 'Disabled', 'pressforward' ); ?></option>
							</select>

							<?php esc_html_e( 'A comment is posted on an item the user has interacted with', 'pressforward' ); ?>
						</label>
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>

<h3 class="title"><?php esc_html_e( 'Feed Options', 'pressforward' ); ?></h3>

<p><?php esc_html_e( 'Control how RSS feeds are fetched and retained.', 'pressforward' ); ?></p>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Time to retain feed items', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$default_pf_link_value = get_option( PF_SLUG . '_retain_time', 2 );
				?>

				<label for="pf_retain_time">
					<input id="pf_retain_time" name="pf_retain_time" type="number" class="pf_retain_time" value="<?php echo esc_attr( $default_pf_link_value ); ?>" />
					<?php esc_html_e( 'Months to retain feed items', 'pressforward' ); ?>
				</label>

				<p class="description">
					<?php esc_html_e( 'Set to "0" to disable automatic cleanup. (Feed items will remain in the database until manually deleted.)', 'pressforward' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Error limit', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$default_pf_link_value = get_option( PF_SLUG . '_errors_until_alert', 3 );
				?>

				<label for="pf_errors_until_alert">
					<input id="pf_errors_until_alert" name="pf_errors_until_alert" type="number" class="pf_errors_until_alert" value="<?php echo esc_attr( $default_pf_link_value ); ?>" />
					<?php esc_html_e( 'Number of errors before a feed is marked as malfunctioning', 'pressforward' ); ?>
				</label>

				<p class="description">
					<?php esc_html_e( 'Feeds sometimes respond slowly or have errors that cause them to be unreadable. This setting determines the number of consecutive errors PressForward will allow from a feed before creating an alert and disabling it.', 'pressforward' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Retrieval frequency', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$default_pf_retrieval_frequency = get_option( PF_SLUG . '_retrieval_frequency', 30 );
				?>

				<label for="<?php echo esc_attr( PF_SLUG ); ?>_retrieval_frequency">
					<input id="<?php echo esc_attr( PF_SLUG ); ?>_retrieval_frequency" name="<?php echo esc_attr( PF_SLUG ); ?>_retrieval_frequency" type="number" class="<?php echo esc_attr( PF_SLUG ); ?>_retrieval_frequency" value="<?php echo esc_attr( $default_pf_retrieval_frequency ); ?>" />
					<?php esc_html_e( 'Minutes between feed retrieval cycles.', 'pressforward' ); ?>
				</label>
			</td>
		</tr>
	</tbody>
</table>

<h3 class="title"><?php esc_html_e( 'Nomination and Promotion Options', 'pressforward' ); ?></h3>

<p><?php esc_html_e( 'The following settings relate to the nomination process, as well as the process of promoting feed items or nominated items.', 'pressforward' ); ?></p>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Promoted item behavior', 'pressforward' ); ?>
			</th>

			<td>
				<fieldset aria-labelledby="promoted-item-settings" class="promoted-item-settings">
					<legend class="screen-reader-text" id="promoted-item-settings"><?php esc_html_e( 'Promoted Item Behavior', 'pressforward' ); ?></legend>

					<p class="description"><?php esc_html_e( 'Control the behavior of the PressForward items created when promoting nominated items or feed items.', 'pressforward' ); ?></p>

					<br />

					<?php
					$the_post_types           = get_post_types( array( 'public' => true ), 'objects' );
					$pf_draft_post_type_value = pressforward_draft_post_type();
					?>

					<p>
						<label for="pf_draft_post_type" class="label-left">
							<?php esc_html_e( 'Post type', 'pressforward' ); ?>
						</label>

						<select name="pf_draft_post_type" id="pf_draft_post_type">
							<?php foreach ( $the_post_types as $the_post_type ) : ?>
								<option value="<?php echo esc_attr( $the_post_type->name ); ?>" <?php selected( $pf_draft_post_type_value, $the_post_type->name ); ?>><?php echo esc_html( $the_post_type->labels->singular_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<?php
					$post_statuses              = get_post_statuses();
					$pf_draft_post_status_value = get_option( PF_SLUG . '_draft_post_status', 'draft' );
					?>

					<p>
						<label for="pf_draft_post_status" class="label-left">
							<?php esc_html_e( 'Post status', 'pressforward' ); ?>
						</label>

						<select name="pf_draft_post_status" id="pf_draft_post_status">
							<?php foreach ( $post_statuses as $status_name => $status_label ) : ?>
								<option value="<?php echo esc_attr( $status_name ); ?>" <?php selected( $pf_draft_post_status_value, $status_name ); ?>><?php echo esc_html( $status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

				</fieldset>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Nominate This', 'pressforward' ); ?>
			</th>

			<td>
				<?php
				$use_classic_override = get_option( 'pf_force_classic_nominate_this', 'no' );
				?>

				<label for="pf_force_classic_nominate_this">
					<select name="pf_force_classic_nominate_this" id="pf_force_classic_nominate_this">
						<option value="no" <?php selected( $use_classic_override, 'no' ); ?>><?php esc_html_e( 'No', 'pressforward' ); ?></option>
						<option value="yes" <?php selected( $use_classic_override, 'yes' ); ?>><?php esc_html_e( 'Yes', 'pressforward' ); ?></option>
					</select>

					<?php esc_html_e( 'Force Classic editor for Nominate This?', 'pressforward' ); ?></label>
				</label>

				<p class="description"><?php esc_html_e( 'By default, Nominate This will use the Block Editor, unless you are using a plugin like Classic Editor that allows the site admin or individual users to default to the Classic Editor. If you would like to force Nominate This to use the Classic Editor, regardless of plugins or user settings, select Yes.', 'pressforward' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>

<?php if ( class_exists( 'The_Alert_Box' ) ) : ?>
	<p>
		<?php
		$alert_settings = pressforward( 'library.alertbox' )->settings_fields();
		$alert_switch   = $alert_settings['switch'];

		$check = pressforward( 'library.alertbox' )->setting( $alert_switch, $alert_switch['default'] );
		if ( 'true' === $check ) {
			$mark = 'checked';
		} else {
			$mark = '';
		}

		echo '<input id="alert_switch" type="checkbox" name="' . esc_attr( pressforward( 'library.alertbox' )->option_name() . '[' . $alert_switch['parent_element'] . '][' . $alert_switch['element'] . ']' ) . '" value="true" ' . esc_attr( $mark ) . ' class="' . esc_attr( $alert_switch['parent_element'] . ' ' . $alert_switch['element'] ) . '" />  <label for="' . esc_attr( pressforward( 'library.alertbox' )->option_name() . '[' . $alert_switch['parent_element'] . '][' . $alert_switch['element'] . ']' ) . '" class="' . esc_attr( $alert_switch['parent_element'] . ' ' . $alert_switch['element'] ) . '" >' . esc_html( $alert_switch['label_for'] ) . '</label>';
		?>
	</p>

	<p>
		<?php esc_html_e( 'When alerts are on, feeds that continually return errors display as alerted. You can dismiss alerts in the Subscribed Feeds page.', 'pressforward' ); ?>
	</p>
	<hr />
<?php endif; ?>
