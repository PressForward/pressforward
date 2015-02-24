<?php 
# via http://www.smashingmagazine.com/2011/10/20/create-tabs-wordpress-settings-pages/
?>
<div class="wrap">
	<h2><?php _e('PressForward Preferences', 'pf'); ?></h2>
	<div class="metabox-holder" id="pf-settings-box">
		<div class="meta-box-sortables ui-sortable">
			<form action="<?php pf_admin_url(); ?>" method="post">
				
				<?php
					wp_nonce_field( 'pf_settings' );
				?>
				<h2 class="nav-tab-wrapper" id="pf-settings-tabs">
				<?php 
					$tabs = pressforward()->form_of->permitted_tabs();
					foreach( $tabs as $tab => $tab_meta ){
						if (current_user_can($tab_meta['cap'])){
							$title = $tab_meta['title'];
					        $class = ( $tab == $current ) ? 'nav-tab-active' : '';
					        echo "<a class='nav-tab $class' id='$tab-tab' href='#top#$tab' data-tab-target='#$tab'>$title</a>";
				    	}
				    }
				    ?>
				</h2>
				<div class="tabwrappper">
					<?php 
						pressforward()->form_of->settings_tab_group($current);

					?>
				</div>
				<br />
					<input type="submit" name="submit" class="button-primary" value="<?php _e( "Save Changes", 'pf' ) ?>" />
				<br />
			</form>
		</div>
	</div>
</div>