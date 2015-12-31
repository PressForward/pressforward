<?php

require_once(dirname(dirname(__FILE__)).'/interfaces/interface-users.php');

class PF_to_WP_Users implements System_Users {

	public function did_user_nominate($post_id, $user_id = false){
		$nominators = pressforward()->metas->retrieve_meta($id, 'nominator_array');
		if (!$user_id){
			$current_user = wp_get_current_user();
			$user_id = $current_user->ID;
		}
		if (!empty($nominators) && in_array($user_id, $nominators)){
			return true;
		} else {
			return false;
		}
	}

}
