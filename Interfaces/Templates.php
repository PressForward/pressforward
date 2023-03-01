<?php
/**
 * Templates interface.
 *
 * @package PressForward
 */

namespace PressForward\Interfaces;

/**
 * Templates interface.
 */
interface Templates {
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
	public function build_path( $segments = array(), $leading = true, $url = false );

	/**
	 * Sets up the current PF screen.
	 *
	 * @return array
	 */
	public function the_screen();

	/**
	 * Fetches the ID of the currently logged in user.
	 *
	 * @return int
	 */
	public function user_id();

	/**
	 * Determines whether this is a PressForward page.
	 *
	 * @return bool
	 */
	public function is_a_pf_page();

	/**
	 * Builds a title variant.
	 *
	 * @return string
	 */
	public function title_variant();

	/**
	 * Returns PF's admin page IDs, or checks whether a given screen is in the list.
	 *
	 * @param string $page_id Optional. If provided, the list is checked for this $page_id.
	 * @return bool|array
	 */
	public function valid_pf_page_ids( $page_id = '' );

	/**
	 * Adds menu page.
	 *
	 * @param string        $page_title      Title of the added page.
	 * @param string        $menu_title      Title to use for menu.
	 * @param string        $capability      Cap to check for access.
	 * @param string        $menu_slug       Slug for the menu item.
	 * @param callable|null $the_function    Display callback.
	 * @param string        $icon_url        URL of the icon.
	 * @param int           $position        Position.
	 * @return void
	 */
	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $the_function = null, $icon_url = '', $position = null );

	/**
	 * Adds submenu page.
	 *
	 * @param string        $parent_slug     Parent slug.
	 * @param string        $page_title      Title of the added page.
	 * @param string        $menu_title      Title to use for menu.
	 * @param string        $capability      Cap to check for access.
	 * @param string        $menu_slug       Slug for the menu item.
	 * @param callable|null $the_function    Display callback.
	 * @return void
	 */
	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $the_function = null );
}
