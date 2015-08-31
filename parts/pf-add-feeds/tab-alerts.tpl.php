<?php

?>

	<?php
		if ( current_user_can('edit_posts') ) : ?>
	        <div class="alert-box postbox">
	            <div class="handlediv" title="Click to toggle"><br></div>
	            <h3 class="hndle"><span>Feed Problems</span></h3>
	            <div class="inside">
					<?php
	                    pressforward()->admin->pf_alert_displayer();
	                ?>
	            </div>
	        </div>
	<?php
		endif;
	?>
	<form method="post" action="options.php" enctype="multipart/form-data">
	<?php
	   	//settings_fields(PF_SLUG . '_feeder_options');
	    //$options = get_option(PF_SLUG . '_plugin_feeder_options');
		settings_fields( PF_SLUG . '_feedlist_group' );
		//do_action( 'feeder_menu' );
	?>
	</form>