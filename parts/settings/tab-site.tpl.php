<?php

// pressforward()->pf_update_php_notice->does_it_meet_required_php_version();
?>
<p>
	<?php esc_html_e( 'These preferences are available only to users with an Administrator role in PressForward. Options set on this page will determine behavior across the site as a whole.', 'pf' ); ?>
</p>
<hr />
<p>
	<?php
		$default_pf_link_value = get_option( 'pf_link_to_source', 0 );
		echo '<input id="pf_link_to_source" name="pf_link_to_source" type="number" class="pf_link_to_source_class" value="' . esc_attr( $default_pf_link_value ) . '" />';

		echo '<label class="description" for="pf_link_to_source"> ' . esc_html__( 'Seconds to redirect user to source. (0 means no redirect)', 'pf' ) . ' </label>';
	?>
</p>
<p>
	<?php esc_html_e( 'PressForward makes use of canonical URLs. If you would like to redirect your readers automatically to the items aggregated by the feed reader and published to your site, this setting will determine how long to wait before redirecting.', 'pf' ); ?>
<hr />
<p>
	<?php
		$default_pf_use_advanced_user_roles = get_option( 'pf_use_advanced_user_roles', 'no' );
	?>
	<select id="pf_use_advanced_user_roles" name="pf_use_advanced_user_roles">
		<option value="yes" <?php if ( $default_pf_use_advanced_user_roles == 'yes' ) { echo 'selected="selected"'; }?>>Yes</option>
		<option value="no" <?php if ( $default_pf_use_advanced_user_roles == 'no' ) { echo 'selected="selected"'; }?>>No</option>
	</select>
	<label class="description" for="pf_use_advanced_user_roles"> <?php esc_html_e( 'Use advanced user role management? (May be needed if you customize user roles or capabilities).', 'pf' ); ?> </label>
</p>
<p>
	<?php esc_html_e( 'For sites that manage multiple users using special plugins, administrators can use this option to insure PressForward respects customized user roles and capacities.', 'pf' ); ?>
</p>
<hr />
<p>
	<?php
	$pf_nomination_send_email = get_option( 'pf_nomination_send_email', '' );
	echo '<input id="pf_nomination_send_email" name="pf_nomination_send_email" type="text" class="pf_nomination_send_email" value="' . esc_attr( $pf_nomination_send_email ) . '" />';

	echo '<label class="description" for="pf_nomination_send_email"> ' . esc_html__( 'Email notifications for nominations.', 'pf' ) . ' </label>';
	?>
</p>
<p>
	<?php esc_html_e( 'To receive an email notification when there is a new nomination, enter a comma separated list of email notification recipients. If left blank, no notifications will be sent.', 'pf' ); ?>
</p>
<hr />
<p>
	<?php
		$default_pf_present_author_value = get_option( 'pf_present_author_as_primary', 'yes' );
	?>
	<select id="pf_present_author_as_primary" name="pf_present_author_as_primary">
		<option value="yes" <?php if ( $default_pf_present_author_value == 'yes' ) { echo 'selected="selected"'; }?>>Yes</option>
		<option value="no" <?php if ( $default_pf_present_author_value == 'no' ) { echo 'selected="selected"'; }?>>No</option>
	</select>
	<?php

	echo '<label class="description" for="pf_present_author_as_primary"> ' . esc_html__( 'Show item_author as source.', 'pf' ) . ' </label>';
	?>
</p>
<p>
	<?php esc_html_e( 'When this preference is on, the name of the author in a PressFoward item will appear in the item_author custom field when the item is sent to Draft. This author will overwrite the creator of the post.', 'pf' ); ?>
</p>
<hr />
<p>
	<?php
		$pf_source_statement_position = get_option( 'pf_source_statement_position', 'bottom' );
	?>
	<select id="pf_source_statement_position" name="pf_source_statement_position">
		<option value="top" <?php if ( $pf_source_statement_position == 'top' ) { echo 'selected="selected"'; }?>><?php esc_html_e( 'Top', 'pf' ); ?></option>
		<option value="bottom" <?php if ( $pf_source_statement_position == 'bottom' ) { echo 'selected="selected"'; }?>><?php esc_html_e( 'Bottom', 'pf' ); ?></option>
	</select>
	<?php

	echo '<label class="description" for="pf_source_statement_position"> ' . esc_html__( 'Source statement position.', 'pf' ) . ' </label>';
	?>
</p>
<p>
	<?php esc_html_e( 'Choose the position for source title and link on published content.', 'pf' ); ?>
</p>
<hr />
<?php
if ( class_exists( 'The_Alert_Box' ) ) { ?>
		<p>
			<?php
			if ( class_exists( 'The_Alert_Box' ) ) {
				$alert_settings = pressforward( 'library.alertbox' )->settings_fields();
				$alert_switch = $alert_settings['switch'];
				$check = pressforward( 'library.alertbox' )->setting( $alert_switch, $alert_switch['default'] );
				// var_dump($check);
					$check = pressforward( 'library.alertbox' )->setting( $alert_switch, $alert_switch['default'] );
				if ( 'true' == $check ) {
					$mark = 'checked';
				} else {
					$mark = '';
				}
					echo '<input id="alert_switch" type="checkbox" name="' . esc_attr( pressforward( 'library.alertbox' )->option_name() . '[' . $alert_switch['parent_element'] . '][' . $alert_switch['element'] . ']' ) . '" value="true" ' . esc_attr( $mark ) . ' class="' . esc_attr( $alert_switch['parent_element'] . ' ' . $alert_switch['element'] ) . '" />  <label for="' . esc_attr( pressforward( 'library.alertbox' )->option_name() . '[' . $alert_switch['parent_element'] . '][' . $alert_switch['element'] . ']' ) . '" class="' . esc_attr( $alert_switch['parent_element'] . ' ' . $alert_switch['element'] ) . '" >' . esc_html( $alert_switch['label_for'] ) . '</label>';
			}
			?>
		</p>
		<p>
			<?php esc_html_e( 'When alerts are on, feeds that continually return errors display as alerted. You can dismiss alerts in the Subscribed Feeds page.', 'pf' ); ?>
		</p>
		<hr />
<?php
}
?>
<p>
	<?php
		$default_pf_link_value = get_option( 'pf_retain_time', 2 );
		echo '<input id="pf_retain_time" name="pf_retain_time" type="number" class="pf_retain_time" value="' . esc_attr( $default_pf_link_value ) . '" />';

		echo '<label class="description" for="pf_retain_time"> ' . esc_html__( 'Months to retrieve and retain feed items.', 'pf' ) . ' </label>';
	?>
</p>
<p>
	<?php esc_html_e( 'This number determines the number of previous months that PressForward will retrieve items for All Content from subscribed feeds and the number of months that items will remain in PressForward.', 'pf' ); ?>
</p>
<hr />
<p>
	<?php
		$default_pf_link_value = get_option( PF_SLUG . '_errors_until_alert', 3 );
		echo '<input id="pf_errors_until_alert" name="pf_errors_until_alert" type="number" class="pf_errors_until_alert" value="' . esc_attr( $default_pf_link_value ) . '" />';

		echo '<label class="description" for="pf_errors_until_alert"> ' . esc_html__( 'Number of errors before a feed is marked as malfunctioning.', 'pf' ) . ' </label>';
	?>
</p>
<p>
	<?php esc_html_e( 'Feeds sometimes respond slowly or have errors that cause them to be unreadable. This setting determines the number of consecutive errors PressForward will allow from a feed before creating an alert and disabling it.', 'pf' ); ?>
</p>
<hr />
<p>
	<?php
		$default_pf_retrieval_frequency = get_option( PF_SLUG . '_retrieval_frequency', 30 );
		echo '<input id="' . esc_attr( PF_SLUG ) . '_retrieval_frequency" name="' . esc_attr( PF_SLUG ) . '_retrieval_frequency" type="number" class="' . esc_attr( PF_SLUG ) . '_retrieval_frequency" value="' . esc_attr( $default_pf_retrieval_frequency ) . '" />';

		echo '<label class="description" for="' . esc_attr( PF_SLUG ) . '_retrieval_frequency"> ' . esc_html__( 'Minutes between feed retrieval cycles.', 'pf' ) . ' </label>';
	?>
</p>
<p>
	<?php esc_html_e( 'This setting is the frequency at which PressForward will attempt to start the process of retrieving all the feeds in your list. Warning: if you have a large number of feeds this setting should not go below 30 minutes.', 'pf' ); ?>
</p>
<hr />
<p>
	<select name="<?php echo esc_attr( PF_SLUG ); ?>_draft_post_status" id="<?php echo esc_attr( PF_SLUG ); ?>_draft_post_status"><?php
		$post_statuses = get_post_statuses();
		$pf_draft_post_status_value = get_option( PF_SLUG . '_draft_post_status', 'draft' );
	foreach ( $post_statuses as $status_name => $status_label ) : ?>
			<option value="<?php echo esc_attr( $status_name ); ?>" <?php if ( $pf_draft_post_status_value === $status_name ) { echo 'selected="selected"'; } ?>><?php echo esc_html( $status_label ); ?></option><?php
		endforeach; ?>
	</select>
	<label class="description" for="<?php echo esc_attr( PF_SLUG ); ?>_draft_post_status"><?php esc_html_e( 'Post status for new content.', 'pf' ); ?></label>
</p>
<p>
	<?php esc_html_e( 'This setting allows you to set a default post status that gets set when you send nominations to become posts.', 'pf' ); ?>
</p>
<hr />
<p>
	<select name="<?php echo esc_attr( PF_SLUG ); ?>_draft_post_type" id="<?php echo esc_attr( PF_SLUG ); ?>_draft_post_type"><?php
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$pf_draft_post_type_value = get_option( PF_SLUG . '_draft_post_type', 'post' );
	foreach ( $post_types as $post_type ) : ?>
			<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php if ( $pf_draft_post_type_value === $post_type->name ) { echo 'selected="selected"'; } ?>><?php echo esc_html( $post_type->label ); ?></option><?php
		endforeach; ?>
	</select>
	<label class="description" for="<?php echo esc_attr( PF_SLUG ); ?>_draft_post_type"><?php esc_html_e( 'Post type for new content.', 'pf' ); ?></label>
</p>
<p>
	<?php esc_html_e( 'Your WordPress site may have more than one Post Type installed, this setting will allow you to send nominations to the post type of your choice.', 'pf' ); ?>
</p>
