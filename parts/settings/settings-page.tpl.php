<?php 
# via http://www.smashingmagazine.com/2011/10/20/create-tabs-wordpress-settings-pages/
?>
<div class="wrap">
	<div id="icon-themes" class="icon32">
		<br> <h2><?php _e('PressForward Settings', 'pf'); ?></h2>
	</div>
	<div class="metabox-holder">
		<div class="meta-box-sortables ui-sortable">
			<form action="<?php pf_admin_url(); ?>" method="post">
				
				<?php
					wp_nonce_field( 'pf_settings' );
				?>
				<h2 class="nav-tab-wrapper" id="pf-settings-tabs">
				<?php 
					$tabs = pressforward()->form_of->permitted_tabs();
					foreach( $tabs as $tab => $name ){
				        $class = ( $tab == $current ) ? 'nav-tab-active' : '';
				        echo "<a class='nav-tab $class' id='$tab-tab' href='#top#$tab' data-tab-target='$tab'>$name</a>";
				    }
				    ?>
				</h2>
				<div class="tabwrappper">
					<?php 
						pressforward()->form_of->settings_tab_group($current);

					?>
				</div>
			</form>
		</div>
	</div>
</div>