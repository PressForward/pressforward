<?php
/**
 * Utilities related to admin panel screens.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

use PressForward\Interfaces\Templates as Template_Interface;
use Intraxia\Jaxion\Contract\Core\HasActions;

/**
 * Screen template utilities.
 */
class PFtoWPTemplates implements Template_Interface, HasActions {
	/**
	 * Whether this is a PF page.
	 *
	 * @access protected
	 * @var mixed
	 */
	protected $is_pf;

	/**
	 * Current user ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $user_id;

	/**
	 * Screen trace, for debugging.
	 *
	 * @access protected
	 * @var string
	 */
	protected $pf_current_screen_trace;

	/**
	 * Array of screen properties.
	 *
	 * @access public
	 * @var array
	 */
	public $the_screen;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Sets up action hooks for this object.
	 *
	 * @return array
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'current_screen',
				'method' => 'build_screen_obj',
			),
		);
	}

	/**
	 * Sets up the current class properties.
	 *
	 * @return void
	 */
	public function build_screen_obj() {
		$this->the_screen = $this->the_screen();
		$this->user_id    = $this->user_id();

		$this->is_a_pf_page();
		define( 'IS_A_PF', $this->is_a_pf_page() );

		add_filter( 'ab_alert_specimens_labels', array( $this, 'alter_alert_boxes' ) );
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
	 * @param bool  $leading  Optional If the returned path should have a leading slash. Default true.
	 * @param bool  $url      Optional If the returned path should use web URL style pathing or system style. Default false.
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
		if ( '/' !== $slash ) {
			$string = str_replace( '/', $slash, $string );
		}

		return $string;
	}

	/**
	 * Fetches the ID of the currently logged in user.
	 *
	 * @return int
	 */
	public function user_id() {
		$user_obj = wp_get_current_user();
		$user_id  = $user_obj->ID;
		return $user_id;
	}

	/**
	 * Filter callback for feed alert labels.
	 *
	 * @param array $alert_names Labels.
	 * @return array
	 */
	public function alter_alert_boxes( $alert_names ) {
		if ( $this->is_pf ) {
			$new_alert_names = array(
				'name'               => _x( 'Feed Alerts', 'post type general name', 'pressforward' ),
				'singular_name'      => _x( 'Alert', 'post type singular name', 'pressforward' ),
				'menu_name'          => _x( 'Feed Alerts', 'admin menu', 'pressforward' ),
				'name_admin_bar'     => _x( 'Feed Alert', 'add new on admin bar', 'pressforward' ),
				'add_new'            => _x( 'Add Feed Alert', 'alert', 'pressforward' ),
				'add_new_item'       => __( 'Add New Feed Alert', 'pressforward' ),
				'new_item'           => __( 'New Feed Alert', 'pressforward' ),
				'edit_item'          => __( 'Edit Feed Alert', 'pressforward' ),
				'view_item'          => __( 'View Feed Alert', 'pressforward' ),
				'all_items'          => __( 'All Feed Alerts', 'pressforward' ),
				'search_items'       => __( 'Search Feed Alerts', 'pressforward' ),
				'parent_item_colon'  => __( 'Parent Alerts:', 'pressforward' ),
				'not_found'          => __( 'No feed alerts found.', 'pressforward' ),
				'not_found_in_trash' => __( 'No feed alerts found in Trash.', 'pressforward' ),
				'dismiss_one_check'  => __( 'This will set the feed to inactive. Are you sure you want to dismiss the alert on', 'pressforward' ),
				'dismiss_all_check'  => __( 'Are you sure you want to dismiss all alerts? It will set all alerted feeds to inactive.', 'pressforward' ),
				'dismiss_all'        => __( 'Dismiss all alerts', 'pressforward' ),
				'delete_all_check'   => __( 'Are you sure you want to delete all feeds with alerts?', 'pressforward' ),
				'delete_all'         => __( 'Delete all feeds with alerts', 'pressforward' ),
				'dismissed'          => __( 'Inactive', 'pressforward' ),
				'all_well'           => __( 'No problems!', 'pressforward' ),
				'turn_on'            => __( 'Turn alerts on.', 'pressforward' ),
				'activate_q'         => __( 'Active Alert Boxes?', 'pressforward' ),
				'turned_off'         => __( 'Feed alert boxes not active.', 'pressforward' ),
			);
			$new_alert_names = array_merge( $alert_names, $new_alert_names );
			return $new_alert_names;
		} else {
			return $alert_names;
		}
	}

	/**
	 * Returns PF's admin page IDs, or checks whether a given screen is in the list.
	 *
	 * @param string|bool $page_id Optional. If provided, the list is checked for this $page_id.
	 * @return bool|array
	 */
	public function valid_pf_page_ids( $page_id = false ) {
		$valid = array(
			'toplevel_page_pf-menu',
			'pressforward_page_pf-all-content',
			'pressforward_page_pf-review',
			'pressforward_page_pf-feeder',
			'edit-pf_feed',
			'pressforward_page_pf-options',
			'pressforward_page_pf-tools',
			'edit-pf_feed_category',
			'pressforward_page_pf-debugger',
		);

		$valid = apply_filters( 'pf_page_ids', $valid );

		if ( false !== $page_id ) {
			return in_array( $page_id, $valid, true );
		} else {
			return $valid;
		}
	}

	/**
	 * Sets up the current PF screen.
	 *
	 * @return array
	 */
	public function the_screen() {
		$screen      = get_current_screen();
		$id          = $screen->id;
		$action      = $screen->action;
		$base        = $screen->base;
		$parent_base = $screen->parent_base;
		$parent_file = $screen->parent_file;
		$post_type   = $screen->post_type;
		$taxonomy    = $screen->taxonomy;
		$is_pf       = self::valid_pf_page_ids( $id );

		$this->pf_current_screen_trace = "PF screen trace: ID: $id; action: $action; base: $base; parent_base: $parent_base; parent_file: $parent_file; post_type: $post_type; taxonomy: $taxonomy;";

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

	/**
	 * Determines whether this is a PressForward page.
	 *
	 * @return bool
	 */
	public function is_a_pf_page() {
		$screen      = $this->the_screen();
		$is_pf       = $this->valid_pf_page_ids( $screen['id'] );
		$this->is_pf = $is_pf;
		return $is_pf;
	}

	/**
	 * Gets a display title for the folder or feed of the current item.
	 *
	 * @return string
	 */
	public function get_the_folder_view_title() {
		if ( isset( $_GET['feed'] ) ) {
			$title = get_the_title( (int) sanitize_text_field( wp_unslash( $_GET['feed'] ) ) );
		} elseif ( isset( $_GET['folder'] ) ) {
			$term  = get_term( (int) sanitize_text_field( wp_unslash( $_GET['folder'] ) ), pressforward( 'schema.feeds' )->tag_taxonomy );
			$title = $term->name;
		} else {
			$title = '';
		}

		return $title;
	}

	/**
	 * Builds a title variant.
	 *
	 * @todo This must be reworked for i18n.
	 *
	 * @return string
	 */
	public function title_variant() {
		$is_variant = false;
		$variant    = '';
		$showing    = __( 'Showing', 'pressforward' );

		if ( isset( $_GET['feed'] ) || isset( $_GET['folder'] ) ) {
			$variant   .= ' ' . $this->get_the_folder_view_title();
			$is_variant = true;
		}

		if ( isset( $_POST['search-terms'] ) ) {
			$variant   .= ' <span class="search-term-title">' . esc_html__( 'Search for:', 'pressforward' ) . ' ' . esc_html( sanitize_text_field( wp_unslash( $_POST['search-terms'] ) ) ) . '</span>';
			$is_variant = true;
		}

		if ( isset( $_GET['by'] ) ) {
			$variant   .= ' <span>' . esc_html( $showing ) . ' - ' . ucfirst( sanitize_text_field( wp_unslash( $_GET['by'] ) ) ) . '</span>';
			$is_variant = true;
		}

		if ( isset( $_GET['pc'] ) ) {
			$page = intval( $_GET['pc'] );
			$page = $page;
			if ( $page > 0 ) {
				/* translators: Page number */
				$page_num_for_print = sprintf( __( 'Page %1$d', 'pressforward' ), $page );

				$variant .= ' <span> ' . esc_html( $page_num_for_print ) . '</span>';

				$is_variant = true;
			}
		}

		if ( isset( $_GET['reveal'] ) ) {
			$revealing = '';
			if ( 'no_hidden' === $_GET['reveal'] ) {
				$revealing = __( 'hidden', 'pressforward' );
			} else {
				$revealing = sanitize_text_field( wp_unslash( $_GET['reveal'] ) );
			}

			$variant   .= ' <span>' . esc_html( $showing . ' ' . $revealing ) . '</span>';
			$is_variant = true;
		}

		if ( isset( $_GET['pf-see'] ) ) {
			$only   = ' ';
			$and    = 'only ';
			$pf_see = sanitize_text_field( wp_unslash( $_GET['pf-see'] ) );
			if ( 'archive-only' === $pf_see ) {
				$only .= $and . __( 'archived', 'pressforward' );
				$and   = ' ';
			}
			if ( 'starred-only' === $pf_see ) {
				$only .= $and . __( 'starred', 'pressforward' );
			}
			if ( 'unread-only' === $pf_see ) {
				$only .= $and . __( 'unread', 'pressforward' );
			}
			if ( 'drafted-only' === $pf_see ) {
				$only .= $and . __( 'drafted', 'pressforward' );
			}
			$variant   .= ' <span>' . esc_html( $showing . $only ) . '</span>';
			$is_variant = true;
		}

		if ( isset( $_GET['sort-by'] ) ) {
			$sort_by    = sanitize_text_field( wp_unslash( $_GET['sort-by'] ) );
			$sort_order = isset( $_GET['sort-order'] ) && 'asc' === strtolower( sanitize_text_field( wp_unslash( $_GET['sort-order'] ) ) ) ? 'ASC' : 'DESC';

			switch ( $sort_by ) {
				case 'item-date':
					$sort_by_text = 'ASC' === $sort_order ? __( 'Sorted by item date, ascending', 'pressforward' ) : __( 'Sorted by item date, descending', 'pressforward' );
					break;

				case 'feed-in-date':
					$sort_by_text = 'ASC' === $sort_order ? __( 'Sorted by time retrieved, ascending', 'pressforward' ) : __( 'Sorted by time retrieved, descending', 'pressforward' );
					break;

				case 'nom-date':
					$sort_by_text = 'ASC' === $sort_order ? __( 'Sorted by time nominated, ascending', 'pressforward' ) : __( 'Sorted by time nominated, descending', 'pressforward' );
					break;

				case 'nom-count':
					$sort_by_text = 'ASC' === $sort_order ? __( 'Sorted by nominations, ascending', 'pressforward' ) : __( 'Sorted by nominations, descending', 'pressforward' );
					break;

				default:
					$sort_by_text = '';
					break;
			}

			if ( $sort_by_text ) {
				if ( ! empty( $variant ) ) {
					$variant .= ' |';
				}

				$variant   .= '&nbsp;<span>' . esc_html( $sort_by_text ) . '</span>';
				$is_variant = true;
			}
		}

		$variant = apply_filters( 'pf_title_variation', $variant, $is_variant );

		if ( ! empty( $variant ) ) {
			$variant = ' |' . $variant;
		}

		return $variant;
	}

	/**
	 * Generates a page headline.
	 *
	 * @param string $page_title Title.
	 * @return string
	 */
	public function get_page_headline( $page_title = '' ) {
		if ( $this->is_a_pf_page() ) {
			$title = '<h1>' . esc_html( PF_TITLE );

			if ( ! empty( $page_title ) ) {
				$page_title = ' ' . $page_title;
			}

			$middle = $page_title;

			$middle = $middle . $this->title_variant();

			$end_title = '</h1> <span id="h-after"> &#8226; </span>';

			$title = $title . $middle . $end_title;

			return $title;
		} else {
			return '';
		}
	}

	/**
	 * Echoes the output of get_the_page_headline().
	 *
	 * @param string $title Title.
	 * @return void
	 */
	public function the_page_headline( $title = '' ) {
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->get_page_headline( $title );
	}

	/**
	 * Wrapper for add_menu_page().
	 *
	 * @param string          $page_title   Title of the added page.
	 * @param string          $menu_title   Title to use for menu.
	 * @param string          $capability   Cap to check for access.
	 * @param string          $menu_slug    Slug for the menu item.
	 * @param callable|string $the_function Display callback.
	 * @param string          $icon_url     URL of the icon.
	 * @param int             $position     Position.
	 * @return void
	 */
	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $the_function = '', $icon_url = '', $position = null ) {
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $the_function, $icon_url, $position );
	}

	/**
	 * Wrapper for add_submenu_page().
	 *
	 * @param string          $parent_slug  Parent slug.
	 * @param string          $page_title   Title of the added page.
	 * @param string          $menu_title   Title to use for menu.
	 * @param string          $capability   Cap to check for access.
	 * @param string          $menu_slug    Slug for the menu item.
	 * @param callable|string $the_function Display callback.
	 * @return void
	 */
	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $the_function = '' ) {
		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $the_function );
	}
}
