<?php 
	$user_ID = get_current_user_id();
?>
<p>
	<?php _e('Users can control aspects of the content display in the All Content and Nominated pages by setting preferences here.', 'pf'); ?>
</p>
<hr />
<p>
	<?php 
		$pf_user_scroll_switch = get_user_option('pf_user_scroll_switch', $user_ID);
		if ( empty($pf_user_scroll_switch) || 'true' == $pf_user_scroll_switch){
			$mark = 'checked';
		} else {
			$mark = '';
		}
		echo '<input id="pf_user_scroll_switch" type="checkbox" name="pf_user_scroll_switch" value="true" '.$mark.' class="user_setting" />
				<label for="pf_user_scroll_switch" >' . __('Infinite Scroll Active', 'pf') . '</label>';
	?> <br /><?php _e('When this box is checked, users can scroll through content continuously.', 'pf'); ?>
</p>
<hr />
<p>
	<?php
		$pf_user_menu_set = get_user_option('pf_user_menu_set', $user_ID);
		if ( 'true' == $pf_user_menu_set){
			$mark = 'checked';
		} else {
			$mark = '';
		}
		echo '<input id="pf_user_menu_set" type="checkbox" name="pf_user_menu_set" value="true" '.$mark.' class="user_setting" />
				<label for="pf_user_menu_set" >' . __('Show side menu', 'pf') . '</label>';
	?> <br /><?php _e('When this box is checked, a menu that includes a list of feeds with alerts displays on the right side of the All Content and Nominated pages.', 'pf'); ?> 
</p>
<hr />
<p>
	<?php
		$default_pf_pagefull = get_user_option('pf_pagefull', $user_ID);
		if ( empty($default_pf_pagefull)){
			$default_pf_pagefull = 20;
		}
		echo '<input id="pf_pagefull" name="pf_pagefull" type="number" class="pf_pagefull" value="'.$default_pf_pagefull.'" />';
		echo '<label class="description" for="pf_pagefull"> ' .__('Number of feed items per page.', 'pf'). ' </label>';
	?> <br /><?php _e('Setting this number determines how many items will appear on the All Content and Nominated pages when infinite scroll is turned off.', 'pf'); ?>
</p>