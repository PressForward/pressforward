<?php
namespace PressForward\Core\Admin;
use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Interfaces\SystemUsers;

use PressForward\Core\Admin\PFTemplater as PFTemplater;
use PressForward\Core\Utility\Forward_Tools as Forward_Tools;
use PressForward\Core\Schema\Nominations as Nominations;
use PressForward\Controllers\Metas;
use AlertBox\The_Alert_Box as The_Alert_Box;

class SubscribedFeeds implements HasActions {

    function __construct( SystemUsers $user_interface, The_Alert_Box $alertbox ) {
        $this->user_interface = $user_interface;
        $this->alertbox = $alertbox;
    }

    public function action_hooks() {
        return array(
            array(
                'hook' => 'admin_menu',
                'method' => 'add_plugin_admin_menu',
            )
        );
    }


    public function add_plugin_admin_menu() {

		if ( $alert_count = $this->alertbox->alert_count() ) {
			$alert_count_notice = '<span class="feed-alerts count-' . intval( $alert_count ) . '"><span class="alert-count">' . number_format_i18n( $alert_count ) . '</span></span>';
			$subscribed_feeds_menu_text = sprintf( __( 'Subscribed Feeds %s', 'pf' ), $alert_count_notice );
		} else {
			$subscribed_feeds_menu_text = __( 'Subscribed Feeds', 'pf' );
		}

		add_submenu_page(
			PF_MENU_SLUG,
			__('Subscribed Feeds', 'pf'),
			$subscribed_feeds_menu_text,
			get_option('pf_menu_feeder_access', $this->user_interface->pf_get_defining_capability_by_role('editor')),
			'edit.php?post_type=' . pressforward('schema.feeds')->post_type
		);

    }

}
