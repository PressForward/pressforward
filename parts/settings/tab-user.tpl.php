<?php 
	$user_ID = get_current_user_id();
?>
<p>
	<?php 
		$pf_user_scroll_switch = get_user_option('pf_user_scroll_switch', $user_ID);
		if ( empty($pf_user_scroll_switch) || 'true' == $pf_user_scroll_switch){
			$mark = 'checked';
		} else {
			$mark = '';
		}
		echo '<input id="pf_user_scroll_switch" type="checkbox" name="pf_user_scroll_switch" value="true" '.$mark.' class="user_setting" />
				<label for="pf_user_scroll_switch" >' . 'Infinite Scroll Active' . '</label>';
	?>
</p>
<p>
	<?php
		$pf_user_menu_set = get_user_option('pf_user_menu_set', $user_ID);
		if ( 'true' == $pf_user_menu_set){
			$mark = 'checked';
		} else {
			$mark = '';
		}
		echo '<input id="pf_user_menu_set" type="checkbox" name="pf_user_menu_set" value="true" '.$mark.' class="user_setting" />
				<label for="pf_user_menu_set" >' . 'Show side menu' . '</label>';
	?>
</p>
<p>
	<?php
		$default_pf_pagefull = get_user_option('pf_pagefull', $user_ID);
		if ( empty($default_pf_pagefull)){
			$default_pf_pagefull = 20;
		}
		echo '<input id="pf_pagefull" name="pf_pagefull" type="number" class="pf_pagefull" value="'.$default_pf_pagefull.'" />';
		echo '<label class="description" for="pf_pagefull"> ' .__('Number of feed items per page.', 'pf'). ' </label>';
	?>
</p>