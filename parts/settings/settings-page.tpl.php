<?php
// via http://www.smashingmagazine.com/2011/10/20/create-tabs-wordpress-settings-pages/
?>
<div class="wrap">
	<h2><?php echo $page_title; ?></h2>
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
