<?php 

	$arrayedAdminRights = array(
		'pf_menu_group_access'	=>	array(
										'default'=>'contributor',
										'title'=>__( 'PressForward Menu Group', 'pf' )
									),
		'pf_menu_all_content_access'=>array(
										'default'=>'contributor',
										'title'=>__( 'All Content Menu', 'pf' )
									),
		'pf_menu_under_review_access'=>array(
										'default'=>'contributor',
										'title'=>__( 'Nominated Menu', 'pf' )
									),
		'pf_menu_preferences_access'=>array(
										'default'=>'administrator',
										'title'=>__( 'Preferences Menu', 'pf' )
									),
		'pf_menu_feeder_access'=>array(
										'default'=>'editor',
										'title'=>__( 'Feeder Menu', 'pf' )
									),
		'pf_menu_add_nomination_access'=>array(
										'default'=>'contributor',
										'title'=> __( 'Add Nomination Menu', 'pf' )
									)
	);

	$arrayedAdminRights = apply_filters('pf_setup_admin_rights',$arrayedAdminRights);

	foreach($arrayedAdminRights as $right=>$parts){

		?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo $right; ?>-enable"><?php echo $parts['title']; ?></label>
						</th>

						<td>
							<select id="<?php echo $right; ?>" name="<?php echo $right; ?>">
								<?php pressforward()->admin->pf_get_user_role_select($right, pf_get_defining_capability_by_role($parts['default'])); ?>
							</select>
						</td>
					</tr>
				</table>

			<br />

		<?php

	}
	
	do_action('pf_admin_user_settings');