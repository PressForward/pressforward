<?php

?>
<p>
	<?php _e( 'The default WordPress settings for drafting, editing, and publishing a post remain the same regardless of other PressForward Settings. For more details visit <a href="https://codex.wordpress.org/Roles_and_Capabilities">Roles and Capabilities</a>. To change the default roles for new users visit <a href="/wp-admin/options-general.php">Admin &#187; Settings &#187; General Settings</a>. To change user roles and capabilities, edit them at <a href="/wp-admin/users.php">Admin &#187; Users</a>.<br /><br />The additional preferences below determine the type of access granted to each user role within the PressForward plugin. The user roles allowed by PressForward in order of most restrictive to least restrictive include: Administrator, Editor, Contributor, and Subscriber.', 'pf' ); ?>
</p>
<hr />
<?php

	$arrayedAdminRights = array(
		'pf_menu_group_access'	=> array(
										'default' => 'contributor',
										'title' => __( 'PressForward Menu Group', 'pf' ),
										'details' => __( 'PressForward Menu Group sets the user role required in order to access the PressForward plugin from the WordPress dashboard.', 'pf' ),
									),
		'pf_menu_all_content_access' => array(
										'default' => 'contributor',
										'title' => __( 'All Content Menu', 'pf' ),
										'details' => __( 'All Content Menu sets the user role required to see items in All Content.', 'pf' ),
									),
		'pf_menu_under_review_access' => array(
										'default' => 'contributor',
										'title' => __( 'Nominated Menu', 'pf' ),
										'details' => __( 'Nominated Menu sets the user role required to see items in Nominated.', 'pf' ),
									),
		'pf_menu_nominate_this_access' => array(
										// Default to the All Content access permissions.
										'default' => pf_get_role_by_capability(get_option( 'pf_menu_all_content_access', 'edit_posts' )),
										'title' => __( 'Nominate This', 'pf' ),
										'details' => __( 'Access to the Nominate This bookmarklet. Note, ability to use different features of the Nominate This bookmarklet will be set by user permissions on feed item and post post types.', 'pf' ),
									),
		'pf_menu_preferences_access' => array(
										'default' => 'administrator',
										'title' => __( 'Non-User Preferences Tabs', 'pf' ),
										'details' => __( 'Side-wide Preferences sets the user role required to access the preferences on this page.', 'pf' ),
									),
		'pf_menu_feeder_access' => array(
										'default' => 'editor',
										'title' => __( 'Feeder Menu', 'pf' ),
										'details' => __( 'Feeder Menu sets the user role required to access the Add Feeds and Subscribed Feeds pages.', 'pf' ),
									),
	);

	$arrayedAdminRights = apply_filters( 'pf_setup_admin_rights',$arrayedAdminRights );

	foreach ( $arrayedAdminRights as $right => $parts ) {

		?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo $right; ?>-enable"><?php echo $parts['title']; ?></label>
						</th>

						<td>
							<select id="<?php echo $right; ?>" name="<?php echo $right; ?>">
								<?php pressforward( 'admin.settings' )->pf_get_user_role_select( $right, pressforward( 'controller.users' )->pf_get_defining_capability_by_role( $parts['default'] ) ); ?>
							</select>
						</td>
					</tr>
				</table>
			<p>
				<?php
				if ( ! empty( $parts['details'] ) ) {
					echo $parts['details'];
				}

				?>
			</p>
			<br />
			<hr />

		<?php

	}

	do_action( 'pf_admin_user_settings' );
