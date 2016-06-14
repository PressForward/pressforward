<?php
namespace PressForward\Interfaces;
interface Templates
{
	public function build_path($segments=array(), $leading = true, $url = false);
	public function the_screen();
	public function user_id();
	public function is_a_pf_page();
	public function title_variant();
	public function valid_pf_page_ids($page_id = false);
	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null );
	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' );
}