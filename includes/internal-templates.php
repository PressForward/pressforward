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
		$this->parts = $this->build_path(array(PF_ROOT, "parts"), false);
		$this->the_screen = $this->the_screen();
		$this->user_id = $this->user_id();
	}

	/**
	 * Build file paths.
	 *
	 * Build paths with arrays Call out of static function class_name->build_path
	 * or self::build_path. Use like:
	 *
	 * 		build_path(array("home", "alice", "Documents", "example.txt"));
	 *
	 * @see http://php.net/manual/en/dir.constants.php
	 * @global string DIRECTORY_SEPARATOR Called from class definition, system variable
	 *
	 * @param array $segments The pieces of the URL, should be array of strings. Default null Accepts string.
	 * @param bool $leading Optional If the returned path should have a leading slash. Default true.
	 * @param bool $url Optional If the returned path should use web URL style pathing or system style. Default false
	 * @return string The composed path.
	 *
	 */
	public function build_path($segments=array(), $leading = true, $url = false) {
		if ($url){
            $slash = '/';
        } else {
            $slash = DIRECTORY_SEPARATOR;
        }
        $string = join($slash, $segments);
		if ($leading){
			$string = $slash . $string;
		}
		# Let's make sure eh?
		if ('/' != $slash){
			$string = str_replace('/', $slash, $string);
		}
		return $string;
	}

	public function user_id(){
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		return $user_id;
	}

	/**
	 * Get a given view (if it exists)
	 * 
	 * @param string     $view      The slug of the view
	 * @return string
	 */
	public function get_view( $view, $vars = array() ) {
		$view_file = $this->build_path(array($this->parts, $view.'.tpl.php'), false);
		#if (WP_DEBUG){ var_dump( $view_file ); }
		if ( ! file_exists( $view_file ) ){
			if (WP_DEBUG){ var_dump( $view_file ); }
			return ' ';
		}
		extract( $vars, EXTR_SKIP );
		ob_start();
		include $view_file;
		return ob_get_clean();
	}

	public function the_side_menu(){
		$user_ID = get_current_user_id();
		$pf_user_menu_set = get_user_option('pf_user_menu_set', $user_ID);
		if ('true' == $pf_user_menu_set){
			$screen = $this->the_screen;
			$vars = array(
					'slug'		=> $screen['id'],
					'version'	=> 0,
					'deck'		=> false
				);
			echo $this->get_view('side-menu', $vars);
		}
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

	public function get_the_folder_view_title(){
		if (isset($_GET['feed'])){
			$title = get_the_title($_GET['feed']);
		} else if (isset($_GET['folder'])){
			
			$term = get_term($_GET['folder'], pressforward()->pf_feeds->tag_taxonomy);
			$title = $term->name;

		} else {
			$title = '';
		}
		return $title;
	}

	public function title_variant(){
		$is_variant = false;
		$variant = '';
		$showing = __('Showing', 'pf');

		if (isset($_GET['feed']) || isset($_GET['folder'])){
			$variant .= ' ' . $this->get_the_folder_view_title();
			$is_variant = true;
		}

		if (isset($_POST['search-terms'])){
			$variant .= ' <span class="search-term-title">' . __('Search for:', 'pf') . ' ' . $_POST['search-terms'] . '</span>';
			$is_variant = true;
		}

		if (isset($_GET['by'])){
			$variant .= ' <span>'.$showing.' - ' . ucfirst($_GET['by']) . '</span>';
			$is_variant = true;
		}

		if (isset($_GET["pc"])){
			$page = $_GET["pc"];
			$page = $page-1;
			if ($page > 0) {
				$pageNumForPrint = sprintf( __('Page %1$d', 'pf'), $page);
				$variant .= ' <span> - ' . $pageNumForPrint . '</span>';
				$is_variant = true;
			}
		}

		if (isset($_GET['reveal'])){
			
			$revealing = '';
			if ('no_hidden' == $_GET['reveal']){
				$revealing = 'hidden';
			}
			
			$variant .= ' <span>'. $showing . ' ' . $revealing . '</span>';
			$is_variant = true;
		}

		if (isset($_GET['pf-see'])){
			$only_archived = __('only archived', 'pf');
			$variant .= ' <span>'. $showing . ' ' . $only_archived . '</span>';
			$is_variant = true;
		}

		$variant = apply_filters('pf_title_variation', $variant, $is_variant);
		
		if (!empty($variant)){
			$variant = ' |' . $variant;
		}
		
		return $variant;

	}

	public function get_page_headline($page_title = ''){
		if ($this->is_a_pf_page()){
			$title = '<h1>'.PF_TITLE;

			if (!empty($page_title)){
				$page_title = ' ' . $page_title;
			}

			$middle = $page_title;

			$middle = $middle . $this->title_variant();

			$end_title = '</h1> <span id="h-after"> &#8226; </span>';

			$title = $title.$middle.$end_title;

			return $title;
		} else {
			return NULL;
		}
	}

	public function the_page_headline($title = ''){
		echo $this->get_page_headline($title);
	}

}