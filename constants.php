<?php
/**
 * Plugin constants.
 *
 * @package PressForward
 */

define( 'PF_SLUG', 'pf' );
define( 'PF_TITLE', 'PressForward' );
define( 'PF_MENU_SLUG', PF_SLUG . '-menu' );
define( 'PF_NOM_EDITOR', 'edit.php?post_type=nomination' );
define( 'PF_NOM_POSTER', 'post-new.php?post_type=nomination' );
define( 'PF_ROOT', __DIR__ );
define( 'PF_FILE_PATH', PF_ROOT . '/pressforward.php' );
define( 'PF_URL', plugins_url( '/', __FILE__ ) );
define( 'PF_VERSION', '5.7.0' );
