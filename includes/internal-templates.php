<?php

/**
 * Setting up the admin interface pieces, including menus and AJAX
 *
 * @since 3.5
 */

class PF_Form_Of {

	public function __construct() {
		add_action( 'current_screen', array( $this, 'init' ) );
	}

	public function init() {
		$this->parts = PF_ROOT . "/parts/";
		$this->the_screen = $this->the_screen();
	}

	public function valid_pf_page_ids($page_id = false){
		$valid = array(
				'toplevel_page_pf-menu',
				'pressforward_page_pf-review',
				'pressforward_page_pf-feeder',
				'edit-pf_feed',
				'pressforward_page_pf-options',
				'pressforward_page_pf-tools',
				'edit-pf_feed_category',
				'pressforward_page_pf-debugger'
			);
		$valid = apply_filters('pf_page_ids', $valid);
		if (false != $page_id){
			return in_array($page_id, $valid);
		} else {
			return $valid;
		}
	}

	public function the_screen(){
		#global $current_screen;
		$screen = get_current_screen();
		$id = $screen->id;
		$action = $screen->action;
		$base = $screen->base;
		$parent_base = $screen->parent_base;
		$parent_file = $screen->parent_file;
		$post_type = $screen->post_type;
		$taxonomy = $screen->taxonomy;
		$is_pf = self::valid_pf_page_ids($id);
		if (WP_DEBUG && $is_pf){
			var_dump("PF screen trace: ID: $id; action: $action; base: $base; parent_base: $parent_base; parent_file: $parent_file; post_type: $post_type; taxonomy: $taxonomy;");
		}
		#echo $base;
		$screen_array = array(

				'screen' 		=> $screen,
				'id'			=> $id,
				'action'		=> $action,
				'base'			=> $base,
				'parent_base'	=> $parent_base,
				'parent_file'	=> $parent_file,
				'post_type'		=> $post_type,
				'taxonomy'		=> $taxonomy

			);
		$screen_array = apply_filters('pf_screen', $screen_array);
		return $screen_array;
	}

	public function is_a_pf_page(){
		$screen = $this->the_screen;
		$is_pf = self::valid_pf_page_ids($screen['id']);
		$this->is_pf = $is_pf;
		return $is_pf;
	}

}