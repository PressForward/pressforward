<?php
/**
 * Template for user control preferences tab.
 *
 * @package PressForward
 */

?>

<p><?php esc_html_e( 'The settings on this page control how user roles interact with PressForward.', 'pressforward' ); ?></p>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Advanced user roles', 'pressforward' ); ?>
			</th>

			<td>
				<label for="pf_use_advanced_user_roles">
					<select id="pf_use_advanced_user_roles" name="pf_use_advanced_user_roles">
						<option value="yes" <?php selected( get_option( 'pf_use_advanced_user_roles', 'no' ), 'yes' ); ?>><?php esc_html_e( 'Yes', 'pressforward' ); ?></option>
						<option value="no" <?php selected( get_option( 'pf_use_advanced_user_roles', 'no' ), 'no' ); ?>><?php esc_html_e( 'No', 'pressforward' ); ?></option>
					</select>
					<?php esc_html_e( 'Use advanced user role management?', 'pressforward' ); ?>
				</label>

				<p class="description">
					<?php esc_html_e( 'Enable advanced user role management if you use a plugin that customizes user roles or capabilities.', 'pressforward' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>

<h3 class="title"><?php esc_html_e( 'Access by Role', 'pressforward' ); ?></h3>

<p><?php esc_html_e( 'Use the following settings to control which users can access PressForward functionality', 'pressforward' ); ?></p>

<?php // translators: %s is a link to the WordPress.org documentation page on Roles and Capabilities. ?>
<p class="description"><?php echo wp_kses_post( sprintf( __( 'For reference, WordPress\'s default user roles are, in order of most to least powerful: Administrator, Editor, Author, Contributor, and Subscriber. For more information, visit <a href="%s">the wordpress.org documentation page on Roles and Capabilities</a>.', 'pressforward' ), 'https://wordpress.org/documentation/article/roles-and-capabilities/' ) ); ?></p>

<?php

$admin_rights = array(
	'pf_menu_group_access'         => array(
		'default' => 'contributor',
		'title'   => __( 'PressForward Menu Group', 'pressforward' ),
		'details' => __( 'PressForward Menu Group sets the user role required in order to access the PressForward plugin from the WordPress dashboard.', 'pressforward' ),
	),
	'pf_menu_all_content_access'   => array(
		'default' => 'contributor',
		'title'   => __( 'All Content Menu', 'pressforward' ),
		'details' => __( 'All Content Menu sets the user role required to see items in All Content.', 'pressforward' ),
	),
	'pf_menu_under_review_access'  => array(
		'default' => 'contributor',
		'title'   => __( 'Nominated Menu', 'pressforward' ),
		'details' => __( 'Nominated Menu sets the user role required to see items in Nominated.', 'pressforward' ),
	),
	'pf_menu_nominate_this_access' => array(
		// Default to the All Content access permissions.
		'default' => pf_get_role_by_capability( get_option( 'pf_menu_all_content_access', 'edit_posts' ) ),
		'title'   => __( 'Nominate This', 'pressforward' ),
		'details' => __( 'Access to the Nominate This bookmarklet. Note, ability to use different features of the Nominate This bookmarklet will be set by user permissions on feed item and post post types.', 'pressforward' ),
	),
	'pf_menu_preferences_access'   => array(
		'default' => 'administrator',
		'title'   => __( 'Non-User Preferences Tabs', 'pressforward' ),
		'details' => __( 'Side-wide Preferences sets the user role required to access the preferences on this page.', 'pressforward' ),
	),
	'pf_menu_feeder_access'        => array(
		'default' => 'editor',
		'title'   => __( 'Feeder Menu', 'pressforward' ),
		'details' => __( 'Feeder Menu sets the user role required to access the Add Feeds and Subscribed Feeds pages.', 'pressforward' ),
	),
	'pf_menu_stats_access'         => array(
		'default' => 'editor',
		'title'   => __( 'Stats Panel', 'pressforward' ),
		'details' => __( 'The user role required to access the Stats panel under PressForward > Tools.', 'pressforward' ),
	),
);

$admin_rights = apply_filters( 'pf_setup_admin_rights', $admin_rights );

?>

<table class="form-table">
	<tbody>
		<?php foreach ( $admin_rights as $right => $parts ) : ?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $right ); ?>-enable"><?php echo esc_attr( $parts['title'] ); ?></label>
				</th>

				<td>
					<select id="<?php echo esc_attr( $right ); ?>" name="<?php echo esc_attr( $right ); ?>">
						<?php pressforward( 'admin.settings' )->pf_get_user_role_select( $right, pressforward( 'controller.users' )->pf_get_defining_capability_by_role( $parts['default'] ) ); ?>
					</select>

					<?php if ( ! empty( $parts['details'] ) ) : ?>
						<p class="description"><?php echo esc_html( $parts['details'] ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php

do_action( 'pf_admin_user_settings' );
