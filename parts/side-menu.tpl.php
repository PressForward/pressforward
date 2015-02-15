<?php #var_dump('atest'); ?>
	<div id="tools">
			<?php
		#Widgets
			#echo '<a href="#" id="settings" class="button">Settings</a>';
			echo '<div class="primary-btn-tools">';
			if ( $slug == 'pressforward_page_pf-review' && (get_bloginfo('version') >= 3.7) && $version >= 0 && current_user_can(pf_get_defining_capability_by_role('administrator'))){
				?>
						<button type="submit" class="btn btn-warning pull-right" id="archivebefore" value="<?php  _e('Archive before', 'pf');  ?>:" ><?php  _e('Archive before', 'pf');  ?>:</button>
						<select class="pull-right" id="archiveBeforeOption">
							<option value="1week">Older than 1 week</option>
							<option value="2weeks">Older than 2 weeks</option>
							<option value="1month">Older than 1 month</option>
							<option value="1year">Before this year</option>
						</select>
				<?php
			}
			echo '</div>';
				?>
                <div class="alert-box">
                    <h3><span>Feed Problems</span></h3>
                    <div class="inside">
                    <?php
                        pressforward()->admin->pf_alert_displayer();
                    ?>
                    </div>
                </div>

			<?php if ($slug == 'toplevel_page_pf-menu' && $version >= 0 && current_user_can(pf_get_defining_capability_by_role('administrator'))){
				?>

						<button type="submit" class="delete btn btn-danger pull-right" id="deletefeedarchive" value="<?php  _e('Delete all items', 'pf');  ?>" ><?php  _e('Delete all items', 'pf');  ?></button>
				<?php
			}

			do_action('pf_side_menu_widgets', $slug);
			?>

		</div>