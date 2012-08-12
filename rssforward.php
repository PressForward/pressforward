<?php
/*
Plugin Name: RSS to PressForward
Plugin URI: http://aramzs.me
Description: This plugin is am RSS parser for CHNM's Press Forward project. 
Version: 0.001
Author: Aram Zucker-Scharff
Author URI: http://aramzs.me
License: GPL2
*/

/*  Developed for the Center for History and New Media

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Set some variables for likely later re-use. 
$rsspf_slug = 'rsspf';
$rsspf_title = 'RSS to Press Forward';
$rsspf_menu_slug = $rsspf_slug . '-menu';

//Activate the admin menu creation function.
add_action('admin_menu', 'register_rsspf_custom_menu_page');
/*First create the plugin menu, with the following variables
The page title (in title tags)
The menu title (on screen title)
The permissions level required for access (edit_posts makes it accessable to Contrributor level users and above). 
The menu's slug.
The function to build the menu.
The icon URL.
The menu position (25 is Comments' menu position, so 24 should put it right above comments).
*/
function register_rsspf_custom_menu_page() {
	global $rsspf_slug, $rsspf_title, $rsspf_menu_slug;

	add_menu_page ($rsspf_title, $rsspf_title, 'edit_posts', $rsspf_menu_slug, array('rsspf', 'rsspf_menu_builder'), plugins_url('rss-to-pressforward/rss-forward-16.png'), 24);
	
}

class rsspf {

	function rsspf_menu_builder() {
		global $rsspf_slug, $rsspf_title, $rsspf_menu_slug;
		echo $rsspf_title;

	}



}

?>