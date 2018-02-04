<?php
namespace PressForward\Controllers;

use PressForward\Interfaces\Templates as Template_Interface;
use Intraxia\Jaxion\Contract\Core\HasActions;

class PFtoWPTemplates implements Template_Interface, HasActions {
	function __construct() {

	}

	public function action_hooks() {
		return array(
			array(
				'hook'   => 'current_screen',
				'method' => 'build_screen_obj',
			),
		);
	}

	public function build_screen_obj() {
		$this->the_screen = $this->the_screen();
		$this->user_id    = $this->user_id();
		$this->is_a_pf_page();
		define( 'IS_A_PF', $this->is_a_pf_page() );
		add_filter( 'ab_alert_specimens_labels', array( $this, 'alter_alert_boxes' ) );
		if ( WP_DEBUG && $this->is_pf ) {
			@trigger_error( $this->pf_current_screen_trace, E_USER_NOTICE );
		}
	}

	/**
	 * Build file paths.
	 *
	 * Build paths with arrays Call out of static function class_name->build_path
	 * or self::build_path. Use like:
	 *
	 *      build_path(array("home", "alice", "Documents", "example.txt"));
	 *
	 * @see http://php.net/manual/en/dir.constants.php
	 * @global string DIRECTORY_SEPARATOR Called from class definition, system variable
	 *
	 * @param array $segments The pieces of the URL, should be array of strings. Default null Accepts string.
	 * @param bool  $leading Optional If the returned path should have a leading slash. Default true.
	 * @param bool  $url Optional If the returned path should use web URL style pathing or system style. Default false
	 * @return string The composed path.
	 */
	public function build_path( $segments = array(), $leading = true, $url = false ) {
		if ( $url ) {
			$slash = '/';
		} else {
			$slash = DIRECTORY_SEPARATOR;
		}
		$string = join( $slash, $segments );
		if ( $leading ) {
			$string = $slash . $string;
		}
		// Let's make sure eh?
		if ( '/' != $slash ) {
			$string = str_replace( '/', $slash, $string );
		}
		return $string;
	}

	public function user_id() {
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		return $user_id;
	}
	public function alter_alert_boxes( $alert_names ) {
		if ( $this->is_pf ) {
			$new_alert_names = array(
				'name'               => _x( 'Feed Alerts', 'post type general name', 'pf' ),
				'singular_name'      => _x( 'Alert', 'post type singular name', 'pf' ),
				'menu_name'          => _x( 'Feed Alerts', 'admin menu', 'pf' ),
				'name_admin_bar'     => _x( 'Feed Alert', 'add new on admin bar', 'pf' ),
				'add_new'            => _x( 'Add Feed Alert', 'alert', 'pf' ),
				'add_new_item'       => __( 'Add New Feed Alert', 'pf' ),
				'new_item'           => __( 'New Feed Alert', 'pf' ),
				'edit_item'          => __( 'Edit Feed Alert', 'pf' ),
				'view_item'          => __( 'View Feed Alert', 'pf' ),
				'all_items'          => __( 'All Feed Alerts', 'pf' ),
				'search_items'       => __( 'Search Feed Alerts', 'pf' ),
				'parent_item_colon'  => __( 'Parent Alerts:', 'pf' ),
				'not_found'          => __( 'No feed alerts found.', 'pf' ),
				'not_found_in_trash' => __( 'No feed alerts found in Trash.', 'pf' ),
				'dismiss_one_check'  => __( 'This will set the feed to inactive. Are you sure you want to dismiss the alert on', 'pf' ),
				'dismiss_all_check'  => __( 'Are you sure you want to dismiss all alerts? It will set all alerted feeds to inactive.', 'pf' ),
				'dismiss_all'        => __( 'Dismiss all alerts', 'pf' ),
				'delete_all_check'   => __( 'Are you sure you want to delete all feeds with alerts?', 'pf' ),
				'delete_all'         => __( 'Delete all feeds with alerts', 'pf' ),
				'dismissed'          => __( 'Inactive', 'pf' ),
				'all_well'           => __( 'No problems!', 'pf' ),
				'turn_on'            => __( 'Turn alerts on.', 'pf' ),
				'activate_q'         => __( 'Active Alert Boxes?', 'pf' ),
				'turned_off'         => __( 'Feed alert boxes not active.', 'pf' ),
			);
			$new_alert_names = array_merge( $alert_names, $new_alert_names );
			return $new_alert_names;
		} else {
			return $alert_names;
		}
	}

	public function valid_pf_page_ids( $page_id = false ) {
		$valid = array(
			'toplevel_page_pf-menu',
			'pressforward_page_pf-review',
			'pressforward_page_pf-feeder',
			'edit-pf_feed',
			'pressforward_page_pf-options',
			'pressforward_page_pf-tools',
			'edit-pf_feed_category',
			'pressforward_page_pf-debugger',
		);
		$valid = apply_filters( 'pf_page_ids', $valid );
		if ( false != $page_id ) {
			return in_array( $page_id, $valid );
		} else {
			return $valid;
		}
	}

	public function the_screen() {
		// global $current_screen;
		$screen                        = get_current_screen();
		$id                            = $screen->id;
		$action                        = $screen->action;
		$base                          = $screen->base;
		$parent_base                   = $screen->parent_base;
		$parent_file                   = $screen->parent_file;
		$post_type                     = $screen->post_type;
		$taxonomy                      = $screen->taxonomy;
		$is_pf                         = self::valid_pf_page_ids( $id );
		$this->pf_current_screen_trace = "PF screen trace: ID: $id; action: $action; base: $base; parent_base: $parent_base; parent_file: $parent_file; post_type: $post_type; taxonomy: $taxonomy;";
		// echo $base;
		$screen_array = array(

			'screen'      => $screen,
			'id'          => $id,
			'action'      => $action,
			'base'        => $base,
			'parent_base' => $parent_base,
			'parent_file' => $parent_file,
			'post_type'   => $post_type,
			'taxonomy'    => $taxonomy,

		);
		$screen_array = apply_filters( 'pf_screen', $screen_array );
		return $screen_array;
	}

	public function is_a_pf_page() {
		$screen      = $this->the_screen();
		$is_pf       = $this->valid_pf_page_ids( $screen['id'] );
		$this->is_pf = $is_pf;
		return $is_pf;
	}

	public function get_the_folder_view_title() {
		if ( isset( $_GET['feed'] ) ) {
			$title = get_the_title( $_GET['feed'] );
		} elseif ( isset( $_GET['folder'] ) ) {

			$term  = get_term( $_GET['folder'], pressforward( 'schema.feeds' )->tag_taxonomy );
			$title = $term->name;

		} else {
			$title = '';
		}
		return $title;
	}

	public function title_variant() {
		$is_variant = false;
		$variant    = '';
		$showing    = __( 'Showing', 'pf' );

		if ( isset( $_GET['feed'] ) || isset( $_GET['folder'] ) ) {
			$variant   .= ' ' . $this->get_the_folder_view_title();
			$is_variant = true;
		}

		if ( isset( $_POST['search-terms'] ) ) {
			$variant   .= ' <span class="search-term-title">' . __( 'Search for:', 'pf' ) . ' ' . $_POST['search-terms'] . '</span>';
			$is_variant = true;
		}

		if ( isset( $_GET['by'] ) ) {
			$variant   .= ' <span>' . $showing . ' - ' . ucfirst( $_GET['by'] ) . '</span>';
			$is_variant = true;
		}

		if ( isset( $_GET['pc'] ) ) {
			$page = $_GET['pc'];
			$page = $page;
			if ( $page > 0 ) {
				$pageNumForPrint = sprintf( __( 'Page %1$d', 'pf' ), $page );
				$variant        .= ' <span> ' . $pageNumForPrint . '</span>';
				$is_variant      = true;
			}
		}

		if ( isset( $_GET['reveal'] ) ) {

			$revealing = '';
			if ( 'no_hidden' == $_GET['reveal'] ) {
				$revealing = 'hidden';
			} else {
				$revealing = $_GET['reveal'];
			}

			$variant   .= ' <span>' . $showing . ' ' . $revealing . '</span>';
			$is_variant = true;
		}

		if ( isset( $_GET['pf-see'] ) ) {
			$only = ' ';
			$and  = 'only ';
			if ( 'archive-only' == $_GET['pf-see'] ) {
				$only .= $and . __( 'archived', 'pf' );
				$and   = ' ';
			}
			if ( 'starred-only' == $_GET['pf-see'] ) {
				$only .= $and . __( 'starred', 'pf' );
			}
			if ( 'unread-only' == $_GET['pf-see'] ) {
				$only .= $and . __( 'unread', 'pf' );
			}
			if ( 'drafted-only' == $_GET['pf-see'] ) {
				$only .= $and . __( 'drafted', 'pf' );
			}
			$variant   .= ' <span>' . $showing . $only . '</span>';
			$is_variant = true;
		}

		$variant = apply_filters( 'pf_title_variation', $variant, $is_variant );

		if ( ! empty( $variant ) ) {
			$variant = ' |' . $variant;
		}

		return $variant;

	}

	public function get_page_headline( $page_title = '' ) {
		if ( $this->is_a_pf_page() ) {
			$title = '<h1>' . PF_TITLE;

			if ( ! empty( $page_title ) ) {
				$page_title = ' ' . $page_title;
			}

			$middle = $page_title;

			$middle = $middle . $this->title_variant();

			$end_title = '</h1> <span id="h-after"> &#8226; </span>';

			$title = $title . $middle . $end_title;

			return $title;
		} else {
			return null;
		}
	}

	public function the_page_headline( $title = '' ) {
		echo $this->get_page_headline( $title );
		return;
	}

	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}
}
