<?php
// via http://www.smashingmagazine.com/2011/10/20/create-tabs-wordpress-settings-pages/
$user = wp_get_current_user();
$user_id = $user->ID;
$public_key = bin2hex(pressforward('controller.jwt')->get_a_user_public_key());
$private_key = (pressforward('controller.jwt')->get_a_user_private_key());
?>
<div class="wrap">
	<h2><?php echo $page_title; ?></h2>
	<input type="hidden" id="pfnt__pfSiteData" name="pfnt__pfSiteData">
	<script>
	<?php
		echo 'window.pfSiteData = {}; ';
		echo 'window.pfSiteData.site_url = "'. \get_site_url() . '"; ';
		echo 'window.pfSiteData.plugin_url = "'. plugin_dir_url( dirname(dirname(__FILE__)) ) . '"; ';
		echo 'window.pfSiteData.submit_endpoint = "' . trailingslashit(trailingslashit(\get_site_url()) . 'wp-json/pf/v1/submit-nomination') . '"; ';
		echo 'window.pfSiteData.categories_endpoint = "'. trailingslashit(trailingslashit(\get_site_url()) . 'wp-json/wp/v2/categories') . '"; ';
		echo 'window.pfSiteData.ku="' . $public_key . '"; ';
		echo 'window.pfSiteData.ki="' . $private_key . '"; ';
		echo 'document.getElementById("pfnt__pfSiteData").value = JSON.stringify(window.pfSiteData)';
	?>
	</script>
	<div class="metabox-holder" id="pf-settings-box">
		<div class="meta-box-sortables ui-sortable">
			<?php if (empty($form_head)) {
	?>
			<form action="<?php pressforward('admin.settings')->pf_admin_url(); ?>" method="post">

				<?php
					wp_nonce_field('pf_settings');
} else {
	echo $form_head;
	settings_fields($settings_field);
}
				?>
				<h2 class="nav-tab-wrapper" id="pf-settings-tabs">
				<?php
					$tabs = pressforward('admin.templates')->permitted_tabs($page_slug);
					// var_dump($current);
				foreach ($tabs as $tab => $tab_meta) {
					if (current_user_can($tab_meta['cap'])) {
						$title = $tab_meta['title'];
						$class = ($tab == $current) ? 'nav-tab-active' : '';
						echo "<a class='nav-tab $class' id='$tab-tab' href='#top#$tab' data-tab-target='#$tab'>$title</a>";
					}
				}
					?>
				</h2>
				<div class="tabwrappper">
					<?php
						// var_dump($page_slug); die();
						echo pressforward('admin.templates')->settings_tab_group($current, $page_slug);
						// var_dump(pressforward('admin.templates')->settings_tab_group($current, $page_slug)); die();
						echo $settings_tab_group;

					?>
				</div>
				<br />
					<?php
					if (empty($no_save_button)) {
						?>
					<input type="submit" name="submit" class="button-primary" value="<?php _e('Submit', 'pf'); ?>" />
					<?php
					}
					?>
				<br />
			</form>
		</div>
	</div>
</div>
